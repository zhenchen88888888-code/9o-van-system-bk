if (!shortcode_exists('charter_booking_form')) {
    add_shortcode('charter_booking_form', 'render_charter_booking_form');
}

function render_charter_booking_form() {
    ob_start();
    ?>
    <div id="charter-booking-app">
        <div class="booking-container">
            <form id="charter-booking-form" class="booking-form">
                <div class="form-loading">
                    <span>載入中...</span>
                </div>
            </form>
            
            <div id="price-panel" class="price-panel">
                <h3>即時報價</h3>
                <div class="price-content">
                    <div class="price-loading">請選擇行程...</div>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// ====================================
// Google Maps API 配置
// ====================================
if (!defined('GOOGLE_MAPS_API_KEY')) {
    define('GOOGLE_MAPS_API_KEY', 'AIzaSyD7m-umnnMHhXTi11_uvI14Rel6mCN1Dz4');
}

// ====================================
// 使用 Google Maps API 處理地標
// ====================================
function geocode_address($address) {
    if (empty($address)) {
        return false;
    }
    
    $api_key = GOOGLE_MAPS_API_KEY;
    
    // 檢查快取
    $cache_key = 'geocode_' . md5($address);
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return $cached;
    }
    
    // 使用 Geocoding API 獲取地址詳細資訊
    $url = 'https://maps.googleapis.com/maps/api/geocode/json';
    $params = array(
        'address' => $address,
        'language' => 'zh-TW',
        'region' => 'TW',
        'key' => $api_key
    );
    
    $response = wp_remote_get($url . '?' . http_build_query($params));
    
    if (is_wp_error($response)) {
        error_log('Geocoding API Error: ' . $response->get_error_message());
        return false;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ($data['status'] !== 'OK' || empty($data['results'])) {
        return false;
    }
    
    $result = $data['results'][0];
    
    // 解析地址組件
    $parsed_data = array(
        'formatted_address' => $result['formatted_address'],
        'lat' => $result['geometry']['location']['lat'],
        'lng' => $result['geometry']['location']['lng'],
        'city' => '',
        'district' => '',
        'county' => '',
        'country' => '',
        'place_types' => $result['types'],
        'components' => array()
    );
    
    // 分析地址組件
    foreach ($result['address_components'] as $component) {
        $types = $component['types'];
        
        if (in_array('country', $types)) {
            $parsed_data['country'] = $component['long_name'];
        }
        if (in_array('administrative_area_level_1', $types)) {
            // 縣市級別
            $parsed_data['city'] = $component['long_name'];
        }
        if (in_array('administrative_area_level_2', $types)) {
            // 鄉鎮市區級別
            $parsed_data['district'] = $component['long_name'];
        }
        if (in_array('administrative_area_level_3', $types)) {
            // 區級別
            $parsed_data['county'] = $component['long_name'];
        }
        
        // 保存所有組件供後續分析
        $parsed_data['components'][$types[0]] = $component['long_name'];
    }
    
    // 快取24小時
    set_transient($cache_key, $parsed_data, 86400);
    
    return $parsed_data;
}

// ====================================
// 使用 Places API 搜尋地標
// ====================================
function search_place($query) {
    if (empty($query)) {
        return false;
    }
    
    $api_key = GOOGLE_MAPS_API_KEY;
    
    // 檢查快取
    $cache_key = 'place_' . md5($query);
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return $cached;
    }
    
    // 使用 Places Text Search API
    $url = 'https://maps.googleapis.com/maps/api/place/textsearch/json';
    $params = array(
        'query' => $query . ' 台灣',
        'language' => 'zh-TW',
        'region' => 'TW',
        'key' => $api_key
    );
    
    $response = wp_remote_get($url . '?' . http_build_query($params));
    
    if (is_wp_error($response)) {
        error_log('Places API Error: ' . $response->get_error_message());
        return false;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ($data['status'] !== 'OK' || empty($data['results'])) {
        return false;
    }
    
    $place = $data['results'][0];
    
    $result = array(
        'name' => $place['name'],
        'formatted_address' => $place['formatted_address'],
        'lat' => $place['geometry']['location']['lat'],
        'lng' => $place['geometry']['location']['lng'],
        'place_id' => $place['place_id'],
        'types' => $place['types']
    );
    
    // 快取24小時
    set_transient($cache_key, $result, 86400);
    
    return $result;
}

// ====================================
// 使用 Elevation API 判斷山區
// ====================================
function check_elevation($lat, $lng) {
    $api_key = GOOGLE_MAPS_API_KEY;
    
    // 檢查快取
    $cache_key = 'elevation_' . md5($lat . ',' . $lng);
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return $cached;
    }
    
    // 使用 Elevation API 檢查海拔
    $url = 'https://maps.googleapis.com/maps/api/elevation/json';
    $params = array(
        'locations' => $lat . ',' . $lng,
        'key' => $api_key
    );
    
    $response = wp_remote_get($url . '?' . http_build_query($params));
    
    if (is_wp_error($response)) {
        return 0;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    $elevation = 0;
    if ($data['status'] === 'OK' && !empty($data['results'])) {
        $elevation = $data['results'][0]['elevation'];
    }
    
    // 快取7天
    set_transient($cache_key, $elevation, 604800);
    
    return $elevation;
}

// ====================================
// 智能地址處理系統
// ====================================
function smart_address_processing($address) {
    // 空地址直接返回
    if (empty($address)) {
        return array(
            'original' => '',
            'formatted' => '',
            'city' => '',
            'district' => '',
            'is_mountain' => false,
            'elevation' => 0,
            'confidence' => 0,
            'method' => 'empty'
        );
    }
    
    // 先嘗試作為地標搜尋
    $place_result = search_place($address);
    
    if ($place_result) {
        // 使用找到的地標地址進行地理編碼
        $geocode_result = geocode_address($place_result['formatted_address']);
    } else {
        // 直接地理編碼
        $geocode_result = geocode_address($address);
    }
    
    if (!$geocode_result) {
        // 如果 API 失敗，返回原始地址
        return array(
            'original' => $address,
            'formatted' => $address,
            'city' => '',
            'district' => '',
            'is_mountain' => false,
            'elevation' => 0,
            'confidence' => 0,
            'method' => 'fallback'
        );
    }
    
    // 檢查海拔高度
    $elevation = 0;
    if (isset($geocode_result['lat']) && isset($geocode_result['lng'])) {
        $elevation = check_elevation($geocode_result['lat'], $geocode_result['lng']);
    }
    
    // 判斷是否為山區（海拔超過500公尺）
    $is_mountain = ($elevation > 500);
    
    // 額外的山區關鍵字檢查
    $mountain_keywords = array('山', '嶺', '峰', '谷', '溪', '瀑布', '森林', '農場');
    foreach ($mountain_keywords as $keyword) {
        if (mb_strpos($geocode_result['formatted_address'], $keyword) !== false) {
            $is_mountain = true;
            break;
        }
    }
    
    return array(
        'original' => $address,
        'formatted' => $geocode_result['formatted_address'],
        'city' => $geocode_result['city'],
        'district' => $geocode_result['district'],
        'lat' => $geocode_result['lat'],
        'lng' => $geocode_result['lng'],
        'is_mountain' => $is_mountain,
        'elevation' => $elevation,
        'confidence' => 95, // API 結果信心度高
        'method' => 'google_api',
        'components' => $geocode_result['components']
    );
}

// ====================================
// 山區判定系統（整合 API）- 修正版
// ====================================
function check_mountain_route($address, $day_routes = array()) {
    // 空地址直接返回非山區
    if (empty($address)) {
        return array(
            'is_mountain' => false,
            'confirmed' => false,
            'confidence' => 0,
            'detected_areas' => array(),
            'method' => 'empty',
            'requires_manual_check' => false,
            'is_excluded' => false,
            'excluded_reason' => '',
            'elevation' => 0,
            'formatted_address' => ''
        );
    }
    
    // 排除地區檢查
    $excluded_areas = array('司馬庫斯', '鎮西堡', '神木群');
    foreach ($excluded_areas as $excluded) {
        if (mb_strpos($address, $excluded) !== false) {
            return array(
                'is_mountain' => false,
                'confirmed' => false,
                'confidence' => 0,
                'detected_areas' => array(),
                'method' => 'excluded',
                'requires_manual_check' => false,
                'is_excluded' => true,
                'excluded_reason' => "不提供{$excluded}地區服務",
                'elevation' => 0,
                'formatted_address' => $address
            );
        }
    }
    
    // 使用智能地址處理
    $processed = smart_address_processing($address);
    
    $result = array(
        'is_mountain' => false,
        'confirmed' => false,
        'confidence' => $processed['confidence'],
        'detected_areas' => array(),
        'method' => $processed['method'],
        'requires_manual_check' => false,
        'is_excluded' => false,
        'excluded_reason' => '',
        'formatted_address' => $processed['formatted'],
        'elevation' => $processed['elevation']
    );
    
    // 根據處理結果判斷
    if ($processed['is_mountain']) {
        $result['is_mountain'] = true;
        
        // 海拔超過800公尺，高信心度
        if ($processed['elevation'] > 800) {
            $result['confirmed'] = true;
            $result['confidence'] = 99;
            $result['detected_areas'][] = "海拔 " . round($processed['elevation']) . " 公尺";
        }
        // 海拔500-800公尺，中信心度
        elseif ($processed['elevation'] > 500) {
            $result['confirmed'] = true;  // 修正：500公尺以上直接確認為山區
            $result['confidence'] = 90;
            $result['detected_areas'][] = "海拔 " . round($processed['elevation']) . " 公尺";
        }
        // 關鍵字判斷
        else {
            $result['confirmed'] = false;
            $result['requires_manual_check'] = true;
            $result['confidence'] = 60;
        }
    }
    
    // 備用：傳統關鍵字檢查（當 API 失敗時）
    if ($processed['method'] === 'fallback') {
        $mountain_areas = array(
            '武嶺' => 99, '合歡山' => 98, '阿里山' => 98, 
            '拉拉山' => 97, '谷關' => 96, '清境農場' => 95,
            '武陵農場' => 95, '太魯閣' => 95
        );
        
        foreach ($mountain_areas as $keyword => $confidence) {
            if (mb_strpos($address, $keyword) !== false) {
                $result['is_mountain'] = true;
                $result['confirmed'] = true;
                $result['confidence'] = $confidence;
                $result['method'] = 'keyword_fallback';
                $result['detected_areas'][] = $keyword;
                break;
            }
        }
    }
    
    return $result;
}

// ====================================
// 縣市基本費率判定（使用 API）
// ====================================
function get_city_base_rate($address) {
    // 空地址返回預設價格
    if (empty($address)) {
        return 12000;
    }
    
    // 使用智能地址處理
    $processed = smart_address_processing($address);
    
    // 14,000元區域：嘉義以南(不含) + 花東
    $south_14k_cities = array(
        '台南市', '臺南市',
        '高雄市',
        '屏東縣',
        '花蓮縣',
        '台東縣', '臺東縣'
    );
    
    // 檢查處理後的城市
    if (!empty($processed['city'])) {
        foreach ($south_14k_cities as $city) {
            if (mb_strpos($processed['city'], $city) !== false) {
                return 14000;
            }
        }
    }
    
    // 備用：檢查格式化地址
    foreach ($south_14k_cities as $city) {
        if (mb_strpos($processed['formatted'], $city) !== false) {
            return 14000;
        }
    }
    
    // 預設為北部價格
    return 12000;
}

// ====================================
// 價格計算系統 - 修正版（含停靠點山區檢測）
// ====================================
add_action('wp_ajax_calculate_charter_price', 'handle_charter_price_calculation');
add_action('wp_ajax_nopriv_calculate_charter_price', 'handle_charter_price_calculation');

function handle_charter_price_calculation() {
    try {
        // 收集資料
        $trip_days = isset($_POST['trip_days']) ? intval($_POST['trip_days']) : 1;
        $trip_days = max(1, min(7, $trip_days)); // 限制1-7天
        
        $passengers = isset($_POST['passengers']) ? intval($_POST['passengers']) : 1;
        $child_seats = isset($_POST['child_seats']) ? intval($_POST['child_seats']) : 0;
        $booster_seats = isset($_POST['booster_seats']) ? intval($_POST['booster_seats']) : 0;
        
        // 司機補貼
        $driver_accommodation = isset($_POST['driver_accommodation']) ? $_POST['driver_accommodation'] : 'self';
        $driver_meals = isset($_POST['driver_meals']) ? $_POST['driver_meals'] : 'provided';
        
        // 解析每日行程
        $daily_routes = array();
        if (!empty($_POST['daily_routes'])) {
            $decoded = json_decode(stripslashes($_POST['daily_routes']), true);
            if (is_array($decoded)) {
                $daily_routes = $decoded;
            }
        }
        
        // 價格明細
        $breakdown = array();
        $subtotal = 0;
        
        // 1. 基本費用計算
        $base_daily_rate = 12000; // 預設嘉義(含)以北 + 宜蘭
        $is_south = false;
        $detected_areas = array();
        
        // 檢查是否有任何有效的地址輸入
        $has_valid_route = false;
        foreach ($daily_routes as $route) {
            if (!empty($route['origin']) || !empty($route['destination']) || !empty($route['stops'])) {
                $has_valid_route = true;
                break;
            }
        }
        
        // 只有在有有效路線時才檢查南北區域
        if ($has_valid_route) {
            foreach ($daily_routes as $route) {
                // 檢查起點
                if (!empty($route['origin'])) {
                    $origin_rate = get_city_base_rate($route['origin']);
                    if ($origin_rate == 14000) {
                        $is_south = true;
                        $base_daily_rate = 14000;
                        $origin_info = smart_address_processing($route['origin']);
                        if (!empty($origin_info['city'])) {
                            $detected_areas[] = $origin_info['city'];
                        }
                    }
                }
                
                // 檢查終點
                if (!empty($route['destination'])) {
                    $dest_rate = get_city_base_rate($route['destination']);
                    if ($dest_rate == 14000) {
                        $is_south = true;
                        $base_daily_rate = 14000;
                        $dest_info = smart_address_processing($route['destination']);
                        if (!empty($dest_info['city'])) {
                            $detected_areas[] = $dest_info['city'];
                        }
                    }
                }
                
                // 檢查停靠點（修正：確保正確處理）
                if (!empty($route['stops']) && is_array($route['stops'])) {
                    foreach ($route['stops'] as $stop) {
                        if (!empty($stop)) {
                            $stop_rate = get_city_base_rate($stop);
                            if ($stop_rate == 14000) {
                                $is_south = true;
                                $base_daily_rate = 14000;
                                $stop_info = smart_address_processing($stop);
                                if (!empty($stop_info['city'])) {
                                    $detected_areas[] = $stop_info['city'];
                                }
                            }
                        }
                    }
                }
                
                // 如果已確定是南部/花東價格，就不用再檢查了
                if ($is_south) {
                    break;
                }
            }
        }
        
        // 多日優惠
        $daily_rates = array();
        for ($day = 1; $day <= $trip_days; $day++) {
            if ($day == 1) {
                $daily_rates[$day] = $base_daily_rate;
            } else {
                // 第二天起每日減1000
                $daily_rates[$day] = $base_daily_rate - 1000;
            }
        }
        
        $base_total = array_sum($daily_rates);
        $breakdown['base_price'] = $base_total;
        $breakdown['daily_rates'] = $daily_rates;
        $breakdown['base_daily_rate'] = $base_daily_rate; // 加入基本日費率
        $breakdown['is_south'] = $is_south;
        $breakdown['detected_areas'] = array_unique($detected_areas);
        $subtotal += $base_total;
        
        // 2. 山區加價判定 - 修正版（含停靠點）
        $mountain_surcharge = 0;
        $mountain_days = array();
        $needs_manual_check = false;
        
        foreach ($daily_routes as $index => $route) {
            $day_num = $index + 1;
            
            // 跳過沒有地址的日期
            if (empty($route['origin']) && empty($route['destination']) && empty($route['stops'])) {
                continue;
            }
            
            // 檢查起點
            $origin_check = check_mountain_route($route['origin']);
            // 檢查終點
            $dest_check = check_mountain_route($route['destination']);
            
            // 檢查停靠點（修正版：確保正確處理）
            $stops_mountain = false;
            $stops_elevation = 0;
            $mountain_stops = array();
            
            if (!empty($route['stops']) && is_array($route['stops'])) {
                foreach ($route['stops'] as $stop) {
                    if (!empty($stop)) {
                        $stop_check = check_mountain_route($stop);
                        
                        // 如果停靠點是山區
                        if ($stop_check['is_mountain'] || $stop_check['elevation'] > 500) {
                            $stops_mountain = true;
                            $stops_elevation = max($stops_elevation, $stop_check['elevation']);
                            
                            // 記錄山區停靠點
                            if (!empty($stop_check['formatted_address'])) {
                                $mountain_stops[] = $stop_check['formatted_address'];
                            } else {
                                $mountain_stops[] = $stop;
                            }
                        }
                        
                        // 檢查排除地區
                        if ($stop_check['is_excluded']) {
                            wp_send_json_error(array(
                                'message' => $stop_check['excluded_reason']
                            ));
                            wp_die();
                        }
                    }
                }
            }
            
            // 判斷該日是否為山區行程
            $is_mountain_day = false;
            $day_confidence = 0;
            $day_elevation = max($origin_check['elevation'], $dest_check['elevation'], $stops_elevation);
            
            // 檢查排除地區
            if ($origin_check['is_excluded'] || $dest_check['is_excluded']) {
                wp_send_json_error(array(
                    'message' => $origin_check['excluded_reason'] ?: $dest_check['excluded_reason']
                ));
                wp_die();
            }
            
            // 修正：包含停靠點的山區判斷
            // 只要起點、終點或任何停靠點在山區，該日就算山區行程
            if ($origin_check['confirmed'] || $dest_check['confirmed'] || ($stops_mountain && $stops_elevation > 500)) {
                // 確認是山區
                $is_mountain_day = true;
                $day_confidence = max(
                    $origin_check['confidence'], 
                    $dest_check['confidence'], 
                    ($stops_mountain && $stops_elevation > 500 ? 90 : 0)
                );
            } elseif (($origin_check['is_mountain'] || $dest_check['is_mountain'] || $stops_mountain) && $day_elevation > 0) {
                // 可能是山區，根據海拔高度判斷
                if ($day_elevation > 800) {
                    // 海拔超過800m，直接算山區
                    $is_mountain_day = true;
                    $day_confidence = 95;
                } elseif ($day_elevation > 500) {
                    // 海拔500-800m，也算山區
                    $is_mountain_day = true;
                    $day_confidence = 85;
                } else {
                    // 海拔較低，需人工確認
                    $is_mountain_day = false;
                    $needs_manual_check = true;
                }
            }
            
            if ($is_mountain_day) {
                $mountain_surcharge += 1000;
                
                // 收集所有山區地點
                $mountain_areas = array();
                if (!empty($origin_check['detected_areas'])) {
                    $mountain_areas = array_merge($mountain_areas, $origin_check['detected_areas']);
                }
                if (!empty($dest_check['detected_areas'])) {
                    $mountain_areas = array_merge($mountain_areas, $dest_check['detected_areas']);
                }
                if (!empty($mountain_stops)) {
                    $mountain_areas = array_merge($mountain_areas, $mountain_stops);
                }
                
                $mountain_days[] = array(
                    'day' => $day_num,
                    'confidence' => $day_confidence,
                    'elevation' => round($day_elevation),
                    'area' => array_unique(array_filter($mountain_areas))
                );
            }
        }
        
        // 只在真的有山區時才加入 breakdown
        if (!empty($mountain_days) && $mountain_surcharge > 0) {
            $breakdown['mountain_surcharge'] = $mountain_surcharge;
            $breakdown['mountain_days'] = $mountain_days;
            $subtotal += $mountain_surcharge;
        } else {
            $breakdown['mountain_surcharge'] = 0;
            $breakdown['mountain_days'] = array();
        }
        $breakdown['mountain_needs_check'] = $needs_manual_check;
        
        // 3. 司機補貼
        $driver_allowance = 0;
        
        // 住宿補貼（天數-1晚）
        if ($driver_accommodation === 'book' && $trip_days > 1) {
            $nights = $trip_days - 1;
            $accommodation_fee = $nights * 2000;
            $breakdown['driver_accommodation'] = $accommodation_fee;
            $driver_allowance += $accommodation_fee;
        }
        
        // 餐費補貼
        if ($driver_meals === 'allowance') {
            $meals_fee = $trip_days * 400;
            $breakdown['driver_meals'] = $meals_fee;
            $driver_allowance += $meals_fee;
        }
        
        $breakdown['driver_allowance'] = $driver_allowance;
        $subtotal += $driver_allowance;
        
        // 4. 加購項目
        $addon_charge = ($child_seats * 100) + ($booster_seats * 100);
        $breakdown['addon_charge'] = $addon_charge;
        $subtotal += $addon_charge;
        
        // 總計（已含稅5%）
        $total = $subtotal;
        
        // 訂金與尾款
        $deposit = round($total * 0.3);
        $balance = $total - $deposit;
        
        // 返回結果
        wp_send_json_success(array(
            'subtotal' => $subtotal,
            'total' => $total,
            'deposit' => $deposit,
            'balance' => $balance,
            'breakdown' => $breakdown,
            'trip_days' => $trip_days,
            'daily_routes' => $daily_routes
        ));
        
    } catch (Exception $e) {
        error_log('Charter Price Calc Error: ' . $e->getMessage());
        wp_send_json_error(array('message' => '計算錯誤：' . $e->getMessage()));
    }
    
    wp_die();
}

// ====================================
// AJAX：地址自動完成
// ====================================
add_action('wp_ajax_autocomplete_address', 'handle_address_autocomplete');
add_action('wp_ajax_nopriv_autocomplete_address', 'handle_address_autocomplete');

function handle_address_autocomplete() {
    $input = isset($_POST['input']) ? sanitize_text_field($_POST['input']) : '';
    
    if (mb_strlen($input) < 2) {
        wp_send_json_error('輸入太短');
    }
    
    $api_key = GOOGLE_MAPS_API_KEY;
    
    // 使用 Places Autocomplete API
    $url = 'https://maps.googleapis.com/maps/api/place/autocomplete/json';
    $params = array(
        'input' => $input,
        'language' => 'zh-TW',
        'components' => 'country:TW',
        'key' => $api_key
    );
    
    $response = wp_remote_get($url . '?' . http_build_query($params));
    
    if (is_wp_error($response)) {
        wp_send_json_error('API 錯誤');
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ($data['status'] === 'OK') {
        $suggestions = array();
        foreach ($data['predictions'] as $prediction) {
            $suggestions[] = array(
                'description' => $prediction['description'],
                'place_id' => $prediction['place_id'],
                'main_text' => $prediction['structured_formatting']['main_text'] ?? '',
                'secondary_text' => $prediction['structured_formatting']['secondary_text'] ?? ''
            );
        }
        wp_send_json_success($suggestions);
    }
    
    wp_send_json_error('無建議');
    wp_die();
}

// ====================================
// AJAX：驗證地址
// ====================================
add_action('wp_ajax_validate_address', 'handle_address_validation');
add_action('wp_ajax_nopriv_validate_address', 'handle_address_validation');

function handle_address_validation() {
    $address = isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '';
    
    if (empty($address)) {
        wp_send_json_error('地址為空');
    }
    
    // 使用智能地址處理
    $processed = smart_address_processing($address);
    
    if ($processed['method'] === 'fallback') {
        wp_send_json_error(array(
            'message' => '無法識別地址',
            'original' => $address
        ));
    }
    
    // 檢查山區
    $mountain_check = check_mountain_route($address);
    
    wp_send_json_success(array(
        'formatted' => $processed['formatted'],
        'city' => $processed['city'],
        'district' => $processed['district'],
        'elevation' => round($processed['elevation']),
        'is_mountain' => $mountain_check['is_mountain'],
        'mountain_confidence' => $mountain_check['confidence'],
        'base_rate' => get_city_base_rate($address)
    ));
    
    wp_die();
}

// ====================================
// 預約提交處理
// ====================================
add_action('wp_ajax_submit_charter_booking', 'handle_charter_booking_submission');
add_action('wp_ajax_nopriv_submit_charter_booking', 'handle_charter_booking_submission');

function handle_charter_booking_submission() {
    try {
        // 基本資料驗證
        $trip_days = isset($_POST['trip_days']) ? intval($_POST['trip_days']) : 0;
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
        $start_time = isset($_POST['start_time']) ? sanitize_text_field($_POST['start_time']) : '';
        $passengers = isset($_POST['passengers']) ? intval($_POST['passengers']) : 1;
        
        // 客戶資料
        $customer_name = isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '';
        $customer_phone = isset($_POST['customer_phone']) ? sanitize_text_field($_POST['customer_phone']) : '';
        $customer_email = isset($_POST['customer_email']) ? sanitize_email($_POST['customer_email']) : '';
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        
        // 每日行程
        $daily_routes = array();
        if (!empty($_POST['daily_routes'])) {
            $decoded = json_decode(stripslashes($_POST['daily_routes']), true);
            if (is_array($decoded)) {
                $daily_routes = $decoded;
            }
        }
        
        // 價格資訊
        $total_price = isset($_POST['total_price']) ? floatval($_POST['total_price']) : 0;
        $deposit = isset($_POST['deposit']) ? floatval($_POST['deposit']) : 0;
        
        // 山區檢測結果
        $mountain_detection = isset($_POST['mountain_detection']) ? json_decode(stripslashes($_POST['mountain_detection']), true) : null;
        
        // 驗證必填欄位
        $errors = array();
        
        if (empty($customer_name)) {
            $errors[] = '請填寫姓名';
        }
        
		$customer_phone = preg_replace('/[^0-9+]/', '', $customer_phone);
		
		if (empty($customer_phone)) {
			$errors[] = '請填寫電話';
		} else {
			if (!preg_match('/^(\+[0-9]{1,4})?[0-9]{9,15}$/', $customer_phone)) {
				$errors[] = '電話格式不正確';
			}
		}
        
        if (empty($start_date)) {
            $errors[] = '請選擇出發日期';
        }
        
        if ($trip_days < 1 || $trip_days > 7) {
            $errors[] = '行程天數必須在1-7天之間';
        }
        
        if (count($daily_routes) !== $trip_days) {
            $errors[] = '請完整填寫每日行程';
        }
        
        // 驗證每日行程
        foreach ($daily_routes as $day => $route) {
            if (empty($route['origin']) || empty($route['destination'])) {
                $errors[] = "第" . ($day + 1) . "天的起點或終點未填寫";
            }
            
            // 檢查是否有司馬庫斯等不服務地區
            $origin_check = check_mountain_route($route['origin']);
            $dest_check = check_mountain_route($route['destination']);
            
            if ($origin_check['is_excluded'] || $dest_check['is_excluded']) {
                $errors[] = $origin_check['excluded_reason'] ?: $dest_check['excluded_reason'];
            }
            
            // 檢查停靠點
            if (!empty($route['stops']) && is_array($route['stops'])) {
                foreach ($route['stops'] as $stop) {
                    if (!empty($stop)) {
                        $stop_check = check_mountain_route($stop);
                        if ($stop_check['is_excluded']) {
                            $errors[] = $stop_check['excluded_reason'];
                        }
                    }
                }
            }
        }
        
        // 檢查預約時間（至少提前2天）
        $min_booking_date = date('Y-m-d', strtotime('+2 days'));
        if ($start_date < $min_booking_date) {
            $errors[] = '請至少提前2天預約，以便安排車輛與司機';
        }
        
        if (!empty($errors)) {
            wp_send_json_error(array('message' => implode('、', $errors)));
            wp_die();
        }
        
        // 格式化電話號碼（統一格式儲存）
        if (strpos($customer_phone, '+') !== 0 && strlen($customer_phone) == 10) {
		$customer_phone = '+886' . ltrim($customer_phone, '0');
		}
        
        // 組裝訂單資料
        $booking_data = array(
            'booking_type' => 'charter',
            'trip_days' => $trip_days,
            'start_date' => $start_date,
            'start_time' => $start_time,
            'end_date' => date('Y-m-d', strtotime($start_date . ' +' . ($trip_days - 1) . ' days')),
            'passengers' => $passengers,
            'daily_routes' => $daily_routes,
            'customer_name' => $customer_name,
            'customer_phone' => $customer_phone,
            'customer_email' => $customer_email,
            'notes' => $notes,
            'total_price' => $total_price,
            'deposit' => $deposit,
            'balance' => $total_price - $deposit,
            'mountain_detection' => $mountain_detection,
            'driver_accommodation' => isset($_POST['driver_accommodation']) ? $_POST['driver_accommodation'] : 'self',
            'driver_meals' => isset($_POST['driver_meals']) ? $_POST['driver_meals'] : 'provided',
            'child_seats' => isset($_POST['child_seats']) ? intval($_POST['child_seats']) : 0,
            'booster_seats' => isset($_POST['booster_seats']) ? intval($_POST['booster_seats']) : 0,
            'booking_time' => current_time('mysql'),
            'booking_status' => 'pending',
            'ip_address' => $_SERVER['REMOTE_ADDR']
        );
        
        // 儲存為自訂 Post Type
        $post_data = array(
            'post_title' => sprintf('[包車] %s - %s (%d天)',
                $customer_name,
                $start_date,
                $trip_days
            ),
            'post_content' => wp_json_encode($booking_data, JSON_UNESCAPED_UNICODE),
            'post_status' => 'private',
            'post_type' => 'charter_booking',
            'meta_input' => array(
                '_booking_data' => $booking_data,
                '_customer_name' => $customer_name,
                '_customer_phone' => $customer_phone,
                '_customer_email' => $customer_email,
                '_start_date' => $start_date,
                '_trip_days' => $trip_days,
                '_total_price' => $total_price,
                '_mountain_needs_check' => !empty($mountain_detection['needs_manual_check'])
            )
        );
        
        $booking_id = wp_insert_post($post_data);
        
        if (is_wp_error($booking_id)) {
            throw new Exception('預約儲存失敗');
        }
        
        // 發送確認信 - 只在有 Email 時發送
		if (!empty($customer_email)) {
			send_charter_confirmation_email($booking_id, $booking_data);
		} else {
			// 記錄客戶未提供 Email
			update_post_meta($booking_id, '_no_email_notification', true);
		}

		// 發送管理員通知（一定要發送）
		send_charter_admin_notification($booking_id, $booking_data);
        
        // 返回成功訊息
        wp_send_json_success(array(
            'message' => '預約成功！我們將在24小時內與您聯繫確認。',
            'booking_id' => $booking_id,
            'booking_number' => 'CHT' . str_pad($booking_id, 6, '0', STR_PAD_LEFT),
            'needs_confirmation' => !empty($mountain_detection['needs_manual_check'])
        ));
        
    } catch (Exception $e) {
        error_log('Charter Booking Error: ' . $e->getMessage());
        wp_send_json_error(array('message' => '預約失敗：' . $e->getMessage()));
    }
    
    wp_die();
}

// ====================================
// Email 發送函數
// ====================================
function send_charter_confirmation_email($booking_id, $booking_data) {
    // 檢查是否有 Email
    if (empty($booking_data['customer_email'])) {
        return false;
    }
    
    $to = $booking_data['customer_email'];
    $subject = '包車旅遊預約確認 - 訂單編號 CHT' . str_pad($booking_id, 6, '0', STR_PAD_LEFT);
    
    $message = "親愛的 {$booking_data['customer_name']} 您好，\n\n";
    $message .= "您的包車旅遊預約已成功提交，以下是您的預約詳情：\n\n";
    $message .= "【預約編號】CHT" . str_pad($booking_id, 6, '0', STR_PAD_LEFT) . "\n";
    $message .= "【行程天數】{$booking_data['trip_days']}天\n";
    $message .= "【出發日期】{$booking_data['start_date']} {$booking_data['start_time']}\n";
    $message .= "【結束日期】{$booking_data['end_date']}\n";
    $message .= "【乘客人數】{$booking_data['passengers']}人\n";
    
    // 加購項目
    if (!empty($booking_data['child_seats']) && $booking_data['child_seats'] > 0) {
        $message .= "【嬰兒座椅】{$booking_data['child_seats']}張\n";
    }
    if (!empty($booking_data['booster_seats']) && $booking_data['booster_seats'] > 0) {
        $message .= "【增高墊】{$booking_data['booster_seats']}張\n";
    }
    $message .= "\n";
    
    // 每日行程
    $message .= "【行程安排】\n";
    foreach ($booking_data['daily_routes'] as $day => $route) {
        $day_num = $day + 1;
        $message .= "Day {$day_num}:\n";
        $message .= "  起點：{$route['origin']}\n";
        $message .= "  終點：{$route['destination']}\n";
        if (!empty($route['stops'])) {
            $message .= "  停靠點：" . implode('、', $route['stops']) . "\n";
        }
        $message .= "\n";
    }
    
    $message .= "【費用明細】\n";
    $message .= "總金額：NT$ " . number_format($booking_data['total_price']) . "\n";
    $message .= "訂金(30%)：NT$ " . number_format($booking_data['deposit']) . "\n";
    $message .= "尾款(70%)：NT$ " . number_format($booking_data['balance']) . "\n";
    $message .= "※ 所有價格均已含 5% 營業稅\n\n";
    
    if (!empty($booking_data['notes'])) {
        $message .= "【備註】{$booking_data['notes']}\n\n";
    }
    
    if (!empty($booking_data['mountain_detection']['needs_manual_check'])) {
        $message .= "【特別提醒】\n";
        $message .= "您的行程可能包含山區路線，我們將在確認後告知是否需要額外的山區服務費。\n\n";
    }
    
    $message .= "我們將在24小時內與您電話確認預約詳情。\n";
    $message .= "如有任何問題，請聯繫我們的客服專線。\n\n";
    $message .= "感謝您的預約！\n";
    $message .= "9o Van Strip 包車旅遊服務";
    
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'From: 9o Van Strip <noreply@' . $_SERVER['SERVER_NAME'] . '>'
    );
    
    return wp_mail($to, $subject, $message, $headers);
}

function send_charter_admin_notification($booking_id, $booking_data) {
    $admin_email = get_option('admin_email');
    
    $subject = '新包車預約通知 - ' . $booking_data['customer_name'] . ' - CHT' . str_pad($booking_id, 6, '0', STR_PAD_LEFT);
    
    $message = "有新的包車旅遊預約：\n\n";
    $message .= "【預約編號】CHT" . str_pad($booking_id, 6, '0', STR_PAD_LEFT) . "\n";
    $message .= "【客戶姓名】{$booking_data['customer_name']}\n";
    $message .= "【客戶電話】{$booking_data['customer_phone']}\n";
    
    // 加入 Email 狀態提醒
    if (!empty($booking_data['customer_email'])) {
        $message .= "【客戶Email】{$booking_data['customer_email']}\n";
    } else {
        $message .= "【客戶Email】⚠️ 未提供（無法發送確認信）\n";
    }
    
    $message .= "【行程天數】{$booking_data['trip_days']}天\n";
    $message .= "【出發日期】{$booking_data['start_date']}\n";
    $message .= "【總金額】NT$ " . number_format($booking_data['total_price']) . "\n";
    $message .= "※ 價格已含稅\n";
    
    // 司機安排資訊
    if ($booking_data['driver_accommodation'] === 'book') {
        $message .= "【司機住宿】提供住宿\n";
    }
    if ($booking_data['driver_meals'] === 'allowance') {
        $message .= "【司機餐費】需要補貼\n";
    }
    
    if (!empty($booking_data['mountain_detection']['needs_manual_check'])) {
        $message .= "\n⚠️ 【需要確認】此行程可能包含山區路線，請人工確認是否收取山區加價費用。\n";
    }
    
    $edit_link = admin_url('post.php?post=' . $booking_id . '&action=edit');
    $message .= "\n【管理連結】{$edit_link}\n";
    
    $headers = array('Content-Type: text/plain; charset=UTF-8');
    
    wp_mail($admin_email, $subject, $message, $headers);
}

// ====================================
// 註冊自訂 Post Type
// ====================================
add_action('init', 'register_charter_booking_post_type');

function register_charter_booking_post_type() {
    $args = array(
        'labels' => array(
            'name' => '包車旅遊預約',
            'singular_name' => '包車預約',
            'menu_name' => '包車旅遊',
            'add_new' => '新增預約',
            'add_new_item' => '新增包車預約',
            'edit_item' => '編輯預約',
            'view_item' => '查看預約',
            'search_items' => '搜尋預約',
            'not_found' => '沒有找到預約',
            'not_found_in_trash' => '回收桶中沒有預約'
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 26,
        'menu_icon' => 'dashicons-location-alt',
        'supports' => array('title'),
        'has_archive' => false,
        'rewrite' => false,
        'capability_type' => 'post',
        'capabilities' => array(
            'create_posts' => 'do_not_allow'
        ),
        'map_meta_cap' => true
    );
    
    register_post_type('charter_booking', $args);
}