<?php
/**
 * Code Snippet: [002] 9O Booking - business-logic
 * 
 * 處理所有業務邏輯：價格計算、預約提交、郵件發送
 * 從原始系統提取並優化
 * 
 * Code Snippets 設定:
 * - Title: [002] 9O業務邏輯
 * - Description: 價格計算、表單處理、API整合
 * - Tags: 9o-booking-business-logic
 * - Priority: 2
 * - Run snippet: Run snippet everywhere
 */

// =====================================
// 1. Google Maps API 整合
// =====================================
class NineoGoogleMaps {
    
    /**
     * 計算兩地距離
     */
    public static function calculate_distance($origin, $destination) {
        $api_key = defined('GOOGLE_MAPS_API_KEY') ? GOOGLE_MAPS_API_KEY : 'AIzaSyD7m-umnnMHhXTi11_uvI14Rel6mCN1Dz4';
        
        $origin = $origin . ', 台灣';
        $destination = $destination . ', 台灣';
        
        $url = 'https://maps.googleapis.com/maps/api/distancematrix/json';
        $params = array(
            'origins' => $origin,
            'destinations' => $destination,
            'units' => 'metric',
            'language' => 'zh-TW',
            'region' => 'TW',
            'key' => $api_key
        );
        
        $query_string = http_build_query($params);
        $full_url = $url . '?' . $query_string;
        
        error_log('Google Maps API Request: ' . $full_url);
        
        $response = wp_remote_get($full_url, array('timeout' => 10));
        
        if (is_wp_error($response)) {
            error_log('Google Maps API Error: ' . $response->get_error_message());
            return self::estimate_distance_fallback($origin, $destination);
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        error_log('Google Maps API Response Status: ' . ($data['status'] ?? 'NO STATUS'));
        
        if ($data['status'] !== 'OK') {
            error_log('Google Maps API Status Error: ' . $body);
            return self::estimate_distance_fallback($origin, $destination);
        }
        
        if (!isset($data['rows'][0]['elements'][0]['distance']['value'])) {
            error_log('Google Maps API No Distance: ' . $body);
            return self::estimate_distance_fallback($origin, $destination);
        }
        
        $distance_meters = $data['rows'][0]['elements'][0]['distance']['value'];
        $distance_km = $distance_meters / 1000;
        
        error_log('Calculated distance: ' . $distance_km . ' km');
        
        return $distance_km;
    }
    
    /**
     * 備用距離估算
     */
    private static function estimate_distance_fallback($origin, $destination) {
        $city_distances = array(
            '台北' => array('新北' => 10, '基隆' => 25, '桃園' => 30, '新竹' => 70, '台中' => 165),
            '桃園' => array('台北' => 30, '新北' => 25, '新竹' => 50, '苗栗' => 80, '台中' => 135),
            '新竹' => array('台北' => 70, '桃園' => 50, '苗栗' => 40, '台中' => 95),
            '台中' => array('台北' => 165, '桃園' => 135, '彰化' => 25, '南投' => 40),
            '高雄' => array('台北' => 350, '台中' => 185, '台南' => 45, '屏東' => 30)
        );
        
        $origin_city = self::extract_city_from_address($origin);
        $dest_city = self::extract_city_from_address($destination);
        
        if (isset($city_distances[$origin_city][$dest_city])) {
            return $city_distances[$origin_city][$dest_city];
        }
        if (isset($city_distances[$dest_city][$origin_city])) {
            return $city_distances[$dest_city][$origin_city];
        }
        
        if ($origin_city === $dest_city) {
            return 8;
        }
        
        return 15;
    }
    
    /**
     * 從地址提取城市名
     */
    private static function extract_city_from_address($address) {
        $cities = array('台北', '新北', '基隆', '桃園', '新竹', '苗栗', '台中', 
                       '彰化', '南投', '雲林', '嘉義', '台南', '高雄', '屏東', 
                       '宜蘭', '花蓮', '台東');
        
        foreach ($cities as $city) {
            if (strpos($address, $city) !== false) {
                return $city;
            }
        }
        
        if (strpos($address, '桃園國際機場') !== false) {
            return '桃園';
        }
        if (strpos($address, '松山機場') !== false) {
            return '台北';
        }
        
        return '台北';
    }
    
    /**
     * 地理編碼（地址轉座標）
     */
    public static function geocode_address($address) {
        if (empty($address)) {
            return false;
        }
        
        $api_key = defined('GOOGLE_MAPS_API_KEY') ? GOOGLE_MAPS_API_KEY : 'AIzaSyD7m-umnnMHhXTi11_uvI14Rel6mCN1Dz4';
        
        $cache_key = 'geocode_' . md5($address);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
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
        
        foreach ($result['address_components'] as $component) {
            $types = $component['types'];
            
            if (in_array('country', $types)) {
                $parsed_data['country'] = $component['long_name'];
            }
            if (in_array('administrative_area_level_1', $types)) {
                $parsed_data['city'] = $component['long_name'];
            }
            if (in_array('administrative_area_level_2', $types)) {
                $parsed_data['district'] = $component['long_name'];
            }
            if (in_array('administrative_area_level_3', $types)) {
                $parsed_data['county'] = $component['long_name'];
            }
            
            $parsed_data['components'][$types[0]] = $component['long_name'];
        }
        
        // 快取24小時
        set_transient($cache_key, $parsed_data, 86400);
        
        return $parsed_data;
    }
}

// =====================================
// 2. 機場接送業務邏輯
// =====================================
class NineoAirportLogic {
    
    /**
     * AJAX: 計算機場接送價格
     */
    public static function handle_price_calculation() {
        try {
            // 基本價格表
            $price_table = array(
                'TPE' => array(
                    '台北市' => 3000,
                    '新北市' => 3000,
                    '基隆市' => 3500,
                    '桃園市' => 2800,
                    '宜蘭縣' => 4900,
                    '新竹地區' => 3500,
                    '苗栗縣' => 4900,
                    '台中市' => 6000,
                    '彰化縣' => 7600,
                    '南投縣' => 8500,
                    '雲林縣' => 9400,
                    '嘉義地區' => 9800,
                    '台南市' => 11000,
                    '高雄市' => 12000,
                    '屏東縣' => 13000,
                    '花蓮縣' => 13000,
                    '台東縣' => 14000
                ),
                'TSA' => array(
                    '台北市' => 3000,
                    '新北市' => 3000,
                    '基隆市' => 3500,
                    '桃園市' => 3100,
                    '宜蘭縣' => 4900,
                    '新竹地區' => 3800,
                    '苗栗縣' => 5400,
                    '台中市' => 6400,
                    '彰化縣' => 8000,
                    '南投縣' => 8900,
                    '雲林縣' => 9800,
                    '嘉義地區' => 10300,
                    '台南市' => 11500,
                    '高雄市' => 12500,
                    '屏東縣' => 13000,
                    '花蓮縣' => 13000,
                    '台東縣' => 14000
                )
            );
            
            $city_mapping = self::get_city_mapping();
            
            $price_key_mapping = array(
                'hsinchu-area' => '新竹地區',
                'chiayi-area' => '嘉義地區'
            );
            
            $airport_addresses = array(
                'TPE' => '桃園國際機場',
                'TSA' => '台北松山機場'
            );
            
            // 收集資料
            $airport = isset($_POST['airport']) ? strtoupper($_POST['airport']) : 'TPE';
            $destination = isset($_POST['destination']) ? $_POST['destination'] : 'taipei-city';
            $service_type = isset($_POST['service_type']) ? $_POST['service_type'] : 'pickup';
            $trip_type = isset($_POST['trip_type']) ? $_POST['trip_type'] : 'oneway';
            $time = isset($_POST['time']) ? $_POST['time'] : '';
            $child_seats = isset($_POST['child_seats']) ? intval($_POST['child_seats']) : 0;
            $booster_seats = isset($_POST['booster_seats']) ? intval($_POST['booster_seats']) : 0;
            $name_board = isset($_POST['name_board']) ? $_POST['name_board'] : 'no';
            
            $pickup_address = isset($_POST['pickup_address']) ? $_POST['pickup_address'] : '';
            $dropoff_address = isset($_POST['dropoff_address']) ? $_POST['dropoff_address'] : '';
            
            // 處理停靠點
            $stopovers = array();
            if (!empty($_POST['stopovers'])) {
                $decoded = json_decode(stripslashes($_POST['stopovers']), true);
                if (is_array($decoded)) {
                    $stopovers = $decoded;
                }
            }
            
            // 轉換城市名稱
            if (isset($price_key_mapping[$destination])) {
                $city = $price_key_mapping[$destination];
            } else {
                $city = isset($city_mapping[$destination]) ? $city_mapping[$destination] : '台北市';
            }
            $airport_address = $airport_addresses[$airport];
            
            // 計算價格
            $breakdown = array();
            $subtotal = 0;
            
            // 1. 基本費用
            $base_price = isset($price_table[$airport][$city]) ? $price_table[$airport][$city] : 3000;
            $breakdown['base_price'] = $base_price;
            $subtotal += $base_price;
            
            // 2. 夜間加價（22:00-08:00）
            $night_surcharge = 0;
            if (!empty($time)) {
                $hour = intval(date('H', strtotime($time)));
                if ($hour >= 22 || $hour < 8) {
                    $night_surcharge = 200;
                }
            }
            $breakdown['night_surcharge'] = $night_surcharge;
            $subtotal += $night_surcharge;
            
            // 3. 偏遠地區加價
            $remote_surcharge = 0;
            $main_address = '';
            
            if ($service_type === 'pickup' && !empty($dropoff_address)) {
                $main_address = $dropoff_address;
                error_log('接機服務 - 檢查下車地址: ' . $main_address);
            } elseif ($service_type === 'dropoff' && !empty($pickup_address)) {
                $main_address = $pickup_address;
                error_log('送機服務 - 檢查上車地址: ' . $main_address);
            }
            
            if (!empty($main_address)) {
                if (function_exists('check_wdap_remote_area_surcharge')) {
                    $remote_surcharge = check_wdap_remote_area_surcharge($main_address);
                } else {
                    error_log('偏遠地區檢查功能未載入');
                }
            }
            
            $breakdown['remote_surcharge'] = $remote_surcharge;
            $subtotal += $remote_surcharge;
            
            // 4. 停靠點費用計算
            $stopover_charge = 0;
            $stopover_details = array();
            
            if (count($stopovers) > 0) {
                $all_points = array();
                
                if ($service_type === 'pickup') {
                    $all_points[] = $airport_address;
                    foreach ($stopovers as $stop) {
                        if (!empty($stop['address'])) {
                            $all_points[] = $stop['address'];
                        }
                    }
                    if (!empty($dropoff_address)) {
                        $all_points[] = $dropoff_address;
                    }
                } else {
                    if (!empty($pickup_address)) {
                        $all_points[] = $pickup_address;
                    }
                    foreach ($stopovers as $stop) {
                        if (!empty($stop['address'])) {
                            $all_points[] = $stop['address'];
                        }
                    }
                    $all_points[] = $airport_address;
                }
                
                for ($i = 0; $i < count($all_points) - 1; $i++) {
                    $from = $all_points[$i];
                    $to = $all_points[$i + 1];
                    
                    $should_charge = true;
                    if ($service_type === 'pickup' && $i === 0) {
                        $should_charge = false;
                    } elseif ($service_type === 'dropoff' && $i === count($all_points) - 2) {
                        $should_charge = false;
                    }
                    
                    $distance = NineoGoogleMaps::calculate_distance($from, $to);
                    
                    $fee = $should_charge ? self::calculate_distance_fee($distance) : 0;
                    $stopover_charge += $fee;
                    
                    $from_short = mb_strlen($from) > 15 ? mb_substr($from, 0, 15) . '...' : $from;
                    $to_short = mb_strlen($to) > 15 ? mb_substr($to, 0, 15) . '...' : $to;
                    
                    $segment_name = sprintf("路段 %d (%s → %s)", 
                        $i + 1, 
                        $from_short, 
                        $to_short
                    );
                    
                    $stopover_details[] = array(
                        'segment' => $segment_name,
                        'distance' => round($distance, 1),
                        'fee' => $fee,
                        'charged' => $should_charge
                    );
                }
            }
            
            $breakdown['stopover_charge'] = $stopover_charge;
            $breakdown['stopover_details'] = $stopover_details;
            $subtotal += $stopover_charge;
            
            // 5. 加購項目（包含舉牌服務）
            $addon_charge = ($child_seats * 100) + ($booster_seats * 100);
            
            $name_board_charge = 0;
            if ($name_board === 'yes') {
                $name_board_charge = 200;
                $addon_charge += $name_board_charge;
            }
            
            $breakdown['addon_charge'] = $addon_charge;
            $breakdown['name_board_charge'] = $name_board_charge;
            $subtotal += $addon_charge;
            
            // 6. 來回程處理
            $total = $subtotal;
            if ($trip_type === 'roundtrip') {
                $return_stopovers = array();
                if (!empty($_POST['return_stopovers'])) {
                    $decoded = json_decode(stripslashes($_POST['return_stopovers']), true);
                    if (is_array($decoded)) {
                        $return_stopovers = $decoded;
                    }
                }
                
                // 回程偏遠地區加價
                $return_remote_surcharge = 0;
                $return_pickup_address = isset($_POST['return_pickup_address']) ? $_POST['return_pickup_address'] : '';
                $return_dropoff_address = isset($_POST['return_dropoff_address']) ? $_POST['return_dropoff_address'] : '';
                
                $return_main_address = '';
                
                if ($service_type === 'pickup') {
                    if (!empty($return_pickup_address)) {
                        $return_main_address = $return_pickup_address;
                    }
                } else {
                    if (!empty($return_dropoff_address)) {
                        $return_main_address = $return_dropoff_address;
                    }
                }
                
                if (!empty($return_main_address) && function_exists('check_wdap_remote_area_surcharge')) {
                    $return_remote_surcharge = check_wdap_remote_area_surcharge($return_main_address);
                }
                
                $breakdown['return_remote_surcharge'] = $return_remote_surcharge;
                
                // 計算回程停靠點費用
                $return_stopover_charge = 0;
                $return_stopover_details = array();
                
                if (count($return_stopovers) > 0) {
                    $return_points = array();
                    
                    if ($service_type === 'pickup') {
                        if (!empty($return_pickup_address)) {
                            $return_points[] = $return_pickup_address;
                        }
                        foreach ($return_stopovers as $stop) {
                            if (!empty($stop['address'])) {
                                $return_points[] = $stop['address'];
                            }
                        }
                        $return_points[] = $airport_address;
                    } else {
                        $return_points[] = $airport_address;
                        foreach ($return_stopovers as $stop) {
                            if (!empty($stop['address'])) {
                                $return_points[] = $stop['address'];
                            }
                        }
                        if (!empty($return_dropoff_address)) {
                            $return_points[] = $return_dropoff_address;
                        }
                    }
                    
                    for ($i = 0; $i < count($return_points) - 1; $i++) {
                        $from = $return_points[$i];
                        $to = $return_points[$i + 1];
                        
                        $should_charge = true;
                        if ($service_type === 'pickup' && $i === count($return_points) - 2) {
                            $should_charge = false;
                        } elseif ($service_type === 'dropoff' && $i === 0) {
                            $should_charge = false;
                        }
                        
                        $distance = NineoGoogleMaps::calculate_distance($from, $to);
                        
                        $fee = $should_charge ? self::calculate_distance_fee($distance) : 0;
                        $return_stopover_charge += $fee;
                        
                        $segment_name = sprintf("回程路段 %d", $i + 1);
                        
                        $return_stopover_details[] = array(
                            'segment' => $segment_name,
                            'distance' => round($distance, 1),
                            'fee' => $fee,
                            'charged' => $should_charge
                        );
                    }
                }
                
                // 回程加購項目
                $return_child_seats = isset($_POST['return_child_seats']) ? intval($_POST['return_child_seats']) : $child_seats;
                $return_booster_seats = isset($_POST['return_booster_seats']) ? intval($_POST['return_booster_seats']) : $booster_seats;
                $return_name_board = isset($_POST['return_name_board']) ? $_POST['return_name_board'] : $name_board;
                
                $return_addon = ($return_child_seats * 100) + ($return_booster_seats * 100);
                if ($return_name_board === 'yes') {
                    $return_addon += 200;
                }
                
                // 回程小計
                $return_subtotal = $base_price + $night_surcharge + $return_remote_surcharge + $return_stopover_charge + $return_addon;
                
                // 計算總價（含9折）
                $original_total = $subtotal + $return_subtotal;
                $total = round($original_total * 0.9);
                $discount = $original_total - $total;
                
                $breakdown['return_stopover_charge'] = $return_stopover_charge;
                $breakdown['return_stopover_details'] = $return_stopover_details;
                $breakdown['return_addon'] = $return_addon;
                $breakdown['return_subtotal'] = $return_subtotal;
                $breakdown['discount'] = -$discount;
                $breakdown['original_total'] = $original_total;
            }
            
            // 返回結果
            wp_send_json_success(array(
                'subtotal' => $subtotal,
                'total' => $total,
                'breakdown' => $breakdown
            ));
            
        } catch (Exception $e) {
            error_log('Airport Price Calc Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => '計算錯誤：' . $e->getMessage()));
        }
        
        wp_die();
    }
    
    /**
     * AJAX: 提交機場接送預約
     */
    public static function handle_booking_submission() {
        try {
            // 資料收集
            $airport = isset($_POST['airport']) ? sanitize_text_field($_POST['airport']) : '';
            $destination = isset($_POST['destination']) ? sanitize_text_field($_POST['destination']) : '';
            $service_type = isset($_POST['service_type']) ? sanitize_text_field($_POST['service_type']) : '';
            $trip_type = isset($_POST['trip_type']) ? sanitize_text_field($_POST['trip_type']) : '';
            
            $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
            $time = isset($_POST['time']) ? sanitize_text_field($_POST['time']) : '';
            $flight = isset($_POST['flight']) ? sanitize_text_field($_POST['flight']) : '';
            $passengers = isset($_POST['passengers']) ? intval($_POST['passengers']) : 1;
            $child_seats = isset($_POST['child_seats']) ? intval($_POST['child_seats']) : 0;
            $booster_seats = isset($_POST['booster_seats']) ? intval($_POST['booster_seats']) : 0;
            $name_board = isset($_POST['name_board']) ? sanitize_text_field($_POST['name_board']) : 'no';
            
            $pickup_address = isset($_POST['pickup_address']) ? sanitize_text_field($_POST['pickup_address']) : '';
            $dropoff_address = isset($_POST['dropoff_address']) ? sanitize_text_field($_POST['dropoff_address']) : '';
            
            $customer_name = isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '';
            $customer_phone = isset($_POST['customer_phone']) ? sanitize_text_field($_POST['customer_phone']) : '';
            $customer_phone = preg_replace('/[^0-9+]/', '', $customer_phone);
            $customer_email = isset($_POST['customer_email']) ? sanitize_email($_POST['customer_email']) : '';
            
            $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
            $total_price = isset($_POST['total_price']) ? floatval($_POST['total_price']) : 0;
            
            // 必填欄位驗證
            $errors = array();
            
            if (empty($customer_name)) {
                $errors[] = '請填寫姓名';
            }
            
            if (empty($customer_phone)) {
                $errors[] = '請填寫電話';
            } else {
                if (!preg_match('/^(\+[0-9]{1,4})?[0-9]{9,15}$/', $customer_phone)) {
                    $errors[] = '電話格式不正確';
                }
            }
            
            // Email 不強制驗證，但如果有填寫要驗證格式
            if (!empty($customer_email) && !filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email 格式不正確';
            }
            
            if (empty($date)) {
                $errors[] = '請選擇日期';
            }
            
            if (empty($time)) {
                $errors[] = '請選擇時間';
            }
            
            // 地址驗證
            if (class_exists('NineoUtils')) {
                $main_address = '';
                if ($service_type === 'pickup' && !empty($dropoff_address)) {
                    $main_address = $dropoff_address;
                    $validation_result = NineoUtils::validate_address_city($dropoff_address, $destination);
                    if (!$validation_result['valid']) {
                        $errors[] = '下車地址錯誤：' . $validation_result['message'];
                    }
                } elseif ($service_type === 'dropoff' && !empty($pickup_address)) {
                    $main_address = $pickup_address;
                    $validation_result = NineoUtils::validate_address_city($pickup_address, $destination);
                    if (!$validation_result['valid']) {
                        $errors[] = '上車地址錯誤：' . $validation_result['message'];
                    }
                }
            }
            
            // 處理停靠點
            $stopovers = array();
            if (!empty($_POST['stopovers'])) {
                $decoded = json_decode(stripslashes($_POST['stopovers']), true);
                if (is_array($decoded)) {
                    $stopovers = $decoded;
                }
            }
            
            // 處理回程資料
            $return_data = null;
            if ($trip_type === 'roundtrip') {
                $return_date = isset($_POST['return_date']) ? sanitize_text_field($_POST['return_date']) : '';
                $return_time = isset($_POST['return_time']) ? sanitize_text_field($_POST['return_time']) : '';
                $return_flight = isset($_POST['return_flight']) ? sanitize_text_field($_POST['return_flight']) : '';
                $return_passengers = isset($_POST['return_passengers']) ? intval($_POST['return_passengers']) : $passengers;
                $return_child_seats = isset($_POST['return_child_seats']) ? intval($_POST['return_child_seats']) : 0;
                $return_booster_seats = isset($_POST['return_booster_seats']) ? intval($_POST['return_booster_seats']) : 0;
                $return_name_board = isset($_POST['return_name_board']) ? sanitize_text_field($_POST['return_name_board']) : 'no';
                
                $return_pickup_address = isset($_POST['return_pickup_address']) ? sanitize_text_field($_POST['return_pickup_address']) : '';
                $return_dropoff_address = isset($_POST['return_dropoff_address']) ? sanitize_text_field($_POST['return_dropoff_address']) : '';
                
                $return_stopovers = array();
                if (!empty($_POST['return_stopovers'])) {
                    $decoded = json_decode(stripslashes($_POST['return_stopovers']), true);
                    if (is_array($decoded)) {
                        $return_stopovers = $decoded;
                    }
                }
                
                if (empty($return_date)) {
                    $errors[] = '請選擇回程日期';
                }
                if (empty($return_time)) {
                    $errors[] = '請選擇回程時間';
                }
                
                $return_data = array(
                    'date' => $return_date,
                    'time' => $return_time,
                    'flight' => $return_flight,
                    'passengers' => $return_passengers,
                    'child_seats' => $return_child_seats,
                    'booster_seats' => $return_booster_seats,
                    'name_board' => $return_name_board,
                    'pickup_address' => $return_pickup_address,
                    'dropoff_address' => $return_dropoff_address,
                    'stopovers' => $return_stopovers
                );
            }
            
            // 如果有錯誤，返回錯誤訊息
            if (!empty($errors)) {
                wp_send_json_error(array(
                    'message' => implode('、', $errors)
                ));
                wp_die();
            }
            
            // 格式化電話號碼
            if (strpos($customer_phone, '+') !== 0 && strlen($customer_phone) == 10) {
                $customer_phone = '+886' . ltrim($customer_phone, '0');
            }
            
            // 組裝預約資料
            $booking_data = array(
                'booking_type' => 'airport_transfer',
                'airport' => strtoupper($airport),
                'destination' => $destination,
                'service_type' => $service_type,
                'trip_type' => $trip_type,
                'date' => $date,
                'time' => $time,
                'flight' => $flight,
                'passengers' => $passengers,
                'child_seats' => $child_seats,
                'booster_seats' => $booster_seats,
                'name_board' => $name_board,
                'pickup_address' => $pickup_address,
                'dropoff_address' => $dropoff_address,
                'stopovers' => $stopovers,
                'customer_name' => $customer_name,
                'customer_phone' => $customer_phone,
                'customer_email' => $customer_email,
                'notes' => $notes,
                'total_price' => $total_price,
                'return_data' => $return_data,
                'booking_time' => current_time('mysql'),
                'booking_status' => 'pending',
                'ip_address' => $_SERVER['REMOTE_ADDR']
            );
            
            // 儲存為自訂 Post Type
            $post_data = array(
                'post_title' => sprintf('[%s] %s - %s %s', 
                    strtoupper($airport), 
                    $customer_name, 
                    $date,
                    $service_type === 'pickup' ? '接機' : '送機'
                ),
                'post_content' => wp_json_encode($booking_data, JSON_UNESCAPED_UNICODE),
                'post_status' => 'private',
                'post_type' => 'airport_booking',
                'meta_input' => array(
                    '_booking_data' => $booking_data,
                    '_customer_name' => $customer_name,
                    '_customer_phone' => $customer_phone,
                    '_customer_email' => $customer_email,
                    '_booking_date' => $date,
                    '_total_price' => $total_price,
                    '_trip_type' => $trip_type
                )
            );
            
            $booking_id = wp_insert_post($post_data);
            
            if (is_wp_error($booking_id)) {
                throw new Exception('預約儲存失敗');
            }
            
            // 發送 Email 通知
            if (!empty($customer_email)) {
                self::send_booking_confirmation($booking_id, $booking_data);
            }
            
            // 發送管理員通知
            self::send_admin_notification($booking_id, $booking_data);
            
            // 返回成功訊息
            wp_send_json_success(array(
                'message' => '預約成功！我們將在24小時內與您聯繫確認。',
                'booking_id' => $booking_id,
                'booking_number' => 'APT' . str_pad($booking_id, 6, '0', STR_PAD_LEFT),
            ));
            
        } catch (Exception $e) {
            error_log('Airport Booking Error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => '預約失敗：' . $e->getMessage()
            ));
        }
        
        wp_die();
    }
    
    /**
     * 計算距離費用
     */
    private static function calculate_distance_fee($distance_km) {
        if ($distance_km <= 1) return 0;
        if ($distance_km <= 5) return 200;
        if ($distance_km <= 10) return 300;
        if ($distance_km <= 20) return 400;
        if ($distance_km <= 30) return 600;
        if ($distance_km <= 40) return 800;
        if ($distance_km <= 50) return 1000;
        return 1000 + ceil(($distance_km - 50) / 10) * 200;
    }
    
    /**
     * 取得城市對應
     */
    private static function get_city_mapping() {
        return array(
            'taipei-city' => '台北市',
            'new-taipei' => '新北市',
            'keelung' => '基隆市',
            'taoyuan' => '桃園市',
            'yilan' => '宜蘭縣',
            'hsinchu-city' => '新竹市',
            'hsinchu-area' => '新竹地區',
            'miaoli' => '苗栗縣',
            'taichung' => '台中市',
            'changhua' => '彰化縣',
            'nantou' => '南投縣',
            'yunlin' => '雲林縣',
            'chiayi' => '嘉義縣',
            'chiayi-area' => '嘉義地區',
            'tainan' => '台南市',
            'kaohsiung' => '高雄市',
            'pingtung' => '屏東縣',
            'hualien' => '花蓮縣',
            'taitung' => '台東縣'
        );
    }
    
    /**
     * 發送確認信
     */
    private static function send_booking_confirmation($booking_id, $booking_data) {
        if (empty($booking_data['customer_email'])) {
            return false;
        }
        
        $to = $booking_data['customer_email'];
        $subject = '機場接送預約確認 - 訂單編號 APT' . str_pad($booking_id, 6, '0', STR_PAD_LEFT);
        
        $message = "親愛的 {$booking_data['customer_name']} 您好，\n\n";
        $message .= "您的機場接送預約已成功提交，以下是您的預約詳情：\n\n";
        $message .= "【預約編號】APT" . str_pad($booking_id, 6, '0', STR_PAD_LEFT) . "\n";
        $message .= "【服務類型】" . ($booking_data['service_type'] === 'pickup' ? '接機' : '送機') . "\n";
        $message .= "【機場】" . ($booking_data['airport'] === 'TPE' ? '桃園國際機場' : '台北松山機場') . "\n";
        $message .= "【日期時間】{$booking_data['date']} {$booking_data['time']}\n";
        
        if (!empty($booking_data['flight'])) {
            $message .= "【航班號碼】{$booking_data['flight']}\n";
        }
        
        if ($booking_data['service_type'] === 'pickup') {
            $message .= "【下車地址】{$booking_data['dropoff_address']}\n";
        } else {
            $message .= "【上車地址】{$booking_data['pickup_address']}\n";
        }
        
        if (!empty($booking_data['stopovers'])) {
            $message .= "【停靠點】\n";
            foreach ($booking_data['stopovers'] as $i => $stop) {
                $message .= "  " . ($i + 1) . ". {$stop['address']}\n";
            }
        }
        
        $message .= "【乘客人數】{$booking_data['passengers']} 人\n";
        
        if ($booking_data['child_seats'] > 0) {
            $message .= "【嬰兒座椅】{$booking_data['child_seats']} 張\n";
        }
        if ($booking_data['booster_seats'] > 0) {
            $message .= "【兒童增高墊】{$booking_data['booster_seats']} 張\n";
        }
        if ($booking_data['name_board'] === 'yes') {
            $message .= "【舉牌服務】是\n";
        }
        
        if ($booking_data['trip_type'] === 'roundtrip' && !empty($booking_data['return_data'])) {
            $return = $booking_data['return_data'];
            $message .= "\n【回程資訊】\n";
            $message .= "日期時間：{$return['date']} {$return['time']}\n";
            if (!empty($return['flight'])) {
                $message .= "航班號碼：{$return['flight']}\n";
            }
        }
        
        $message .= "\n【總金額】NT$ " . number_format($booking_data['total_price']) . "\n";
        
        if (!empty($booking_data['notes'])) {
            $message .= "\n【備註】{$booking_data['notes']}\n";
        }
        
        $message .= "\n我們將在24小時內與您電話確認預約詳情。\n";
        $message .= "如有任何問題，請聯繫我們的客服專線。\n\n";
        $message .= "感謝您的預約！\n";
        $message .= "9o Van Strip 機場接送服務";
        
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: 9o Van Strip <noreply@' . $_SERVER['SERVER_NAME'] . '>'
        );
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * 發送管理員通知
     */
    private static function send_admin_notification($booking_id, $booking_data) {
        $admin_email = get_option('admin_email');
        
        $subject = '新預約通知 - ' . $booking_data['customer_name'] . ' - APT' . str_pad($booking_id, 6, '0', STR_PAD_LEFT);
        
        $message = "有新的機場接送預約：\n\n";
        $message .= "【預約編號】APT" . str_pad($booking_id, 6, '0', STR_PAD_LEFT) . "\n";
        $message .= "【客戶姓名】{$booking_data['customer_name']}\n";
        $message .= "【客戶電話】{$booking_data['customer_phone']}\n";
        
        if (!empty($booking_data['customer_email'])) {
            $message .= "【客戶Email】{$booking_data['customer_email']}\n";
        } else {
            $message .= "【客戶Email】未提供\n";
        }
        
        $message .= "【服務類型】" . ($booking_data['service_type'] === 'pickup' ? '接機' : '送機');
        $message .= " / " . ($booking_data['trip_type'] === 'roundtrip' ? '來回' : '單程') . "\n";
        $message .= "【日期時間】{$booking_data['date']} {$booking_data['time']}\n";
        
        if ($booking_data['name_board'] === 'yes') {
            $message .= "【舉牌服務】需要舉牌\n";
        }
        
        $message .= "【總金額】NT$ " . number_format($booking_data['total_price']) . "\n";
        
        $edit_link = admin_url('post.php?post=' . $booking_id . '&action=edit');
        $message .= "\n【管理連結】{$edit_link}\n";
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        wp_mail($admin_email, $subject, $message, $headers);
    }
}

// =====================================
// 3. 包車旅遊業務邏輯
// =====================================
class NineoCharterLogic {
    
    /**
     * 山區檢測
     */
    public static function check_mountain_route($address, $day_routes = array()) {
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
        $processed = self::smart_address_processing($address);
        
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
            
            if ($processed['elevation'] > 800) {
                $result['confirmed'] = true;
                $result['confidence'] = 99;
                $result['detected_areas'][] = "海拔 " . round($processed['elevation']) . " 公尺";
            } elseif ($processed['elevation'] > 500) {
                $result['confirmed'] = true;
                $result['confidence'] = 90;
                $result['detected_areas'][] = "海拔 " . round($processed['elevation']) . " 公尺";
            } else {
                $result['confirmed'] = false;
                $result['requires_manual_check'] = true;
                $result['confidence'] = 60;
            }
        }
        
        // 備用：傳統關鍵字檢查
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
    
    /**
     * 智能地址處理
     */
    private static function smart_address_processing($address) {
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
        $place_result = self::search_place($address);
        
        if ($place_result) {
            $geocode_result = NineoGoogleMaps::geocode_address($place_result['formatted_address']);
        } else {
            $geocode_result = NineoGoogleMaps::geocode_address($address);
        }
        
        if (!$geocode_result) {
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
            $elevation = self::check_elevation($geocode_result['lat'], $geocode_result['lng']);
        }
        
        // 判斷是否為山區
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
            'confidence' => 95,
            'method' => 'google_api',
            'components' => $geocode_result['components']
        );
    }
    
    /**
     * 搜尋地標
     */
    private static function search_place($query) {
        if (empty($query)) {
            return false;
        }
        
        $api_key = defined('GOOGLE_MAPS_API_KEY') ? GOOGLE_MAPS_API_KEY : 'AIzaSyD7m-umnnMHhXTi11_uvI14Rel6mCN1Dz4';
        
        $cache_key = 'place_' . md5($query);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        $url = 'https://maps.googleapis.com/maps/api/place/textsearch/json';
        $params = array(
            'query' => $query . ' 台灣',
            'language' => 'zh-TW',
            'region' => 'TW',
            'key' => $api_key
        );
        
        $response = wp_remote_get($url . '?' . http_build_query($params));
        
        if (is_wp_error($response)) {
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
        
        set_transient($cache_key, $result, 86400);
        
        return $result;
    }
    
    /**
     * 檢查海拔高度
     */
    private static function check_elevation($lat, $lng) {
        $api_key = defined('GOOGLE_MAPS_API_KEY') ? GOOGLE_MAPS_API_KEY : 'AIzaSyD7m-umnnMHhXTi11_uvI14Rel6mCN1Dz4';
        
        $cache_key = 'elevation_' . md5($lat . ',' . $lng);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
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
        
        set_transient($cache_key, $elevation, 604800);
        
        return $elevation;
    }
    
    /**
     * 取得城市基本費率
     */
    private static function get_city_base_rate($address) {
        if (empty($address)) {
            return 12000;
        }
        
        $processed = self::smart_address_processing($address);
        
        // 14,000元區域：嘉義以南(不含) + 花東
        $south_14k_cities = array(
            '台南市', '臺南市',
            '高雄市',
            '屏東縣',
            '花蓮縣',
            '台東縣', '臺東縣'
        );
        
        if (!empty($processed['city'])) {
            foreach ($south_14k_cities as $city) {
                if (mb_strpos($processed['city'], $city) !== false) {
                    return 14000;
                }
            }
        }
        
        foreach ($south_14k_cities as $city) {
            if (mb_strpos($processed['formatted'], $city) !== false) {
                return 14000;
            }
        }
        
        return 12000;
    }
    
    /**
     * AJAX: 計算包車價格
     */
    public static function handle_price_calculation() {
        try {
            $trip_days = isset($_POST['trip_days']) ? intval($_POST['trip_days']) : 1;
            $trip_days = max(1, min(7, $trip_days));
            
            $passengers = isset($_POST['passengers']) ? intval($_POST['passengers']) : 1;
            $child_seats = isset($_POST['child_seats']) ? intval($_POST['child_seats']) : 0;
            $booster_seats = isset($_POST['booster_seats']) ? intval($_POST['booster_seats']) : 0;
            
            $driver_accommodation = isset($_POST['driver_accommodation']) ? $_POST['driver_accommodation'] : 'self';
            $driver_meals = isset($_POST['driver_meals']) ? $_POST['driver_meals'] : 'provided';
            
            $daily_routes = array();
            if (!empty($_POST['daily_routes'])) {
                $decoded = json_decode(stripslashes($_POST['daily_routes']), true);
                if (is_array($decoded)) {
                    $daily_routes = $decoded;
                }
            }
            
            $breakdown = array();
            $subtotal = 0;
            
            // 1. 基本費用計算
            $base_daily_rate = 12000;
            $is_south = false;
            $detected_areas = array();
            
            $has_valid_route = false;
            foreach ($daily_routes as $route) {
                if (!empty($route['origin']) || !empty($route['destination']) || !empty($route['stops'])) {
                    $has_valid_route = true;
                    break;
                }
            }
            
            if ($has_valid_route) {
                foreach ($daily_routes as $route) {
                    if (!empty($route['origin'])) {
                        $origin_rate = self::get_city_base_rate($route['origin']);
                        if ($origin_rate == 14000) {
                            $is_south = true;
                            $base_daily_rate = 14000;
                            $origin_info = self::smart_address_processing($route['origin']);
                            if (!empty($origin_info['city'])) {
                                $detected_areas[] = $origin_info['city'];
                            }
                        }
                    }
                    
                    if (!empty($route['destination'])) {
                        $dest_rate = self::get_city_base_rate($route['destination']);
                        if ($dest_rate == 14000) {
                            $is_south = true;
                            $base_daily_rate = 14000;
                            $dest_info = self::smart_address_processing($route['destination']);
                            if (!empty($dest_info['city'])) {
                                $detected_areas[] = $dest_info['city'];
                            }
                        }
                    }
                    
                    if (!empty($route['stops']) && is_array($route['stops'])) {
                        foreach ($route['stops'] as $stop) {
                            if (!empty($stop)) {
                                $stop_rate = self::get_city_base_rate($stop);
                                if ($stop_rate == 14000) {
                                    $is_south = true;
                                    $base_daily_rate = 14000;
                                    $stop_info = self::smart_address_processing($stop);
                                    if (!empty($stop_info['city'])) {
                                        $detected_areas[] = $stop_info['city'];
                                    }
                                }
                            }
                        }
                    }
                    
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
                    $daily_rates[$day] = $base_daily_rate - 1000;
                }
            }
            
            $base_total = array_sum($daily_rates);
            $breakdown['base_price'] = $base_total;
            $breakdown['daily_rates'] = $daily_rates;
            $breakdown['base_daily_rate'] = $base_daily_rate;
            $breakdown['is_south'] = $is_south;
            $breakdown['detected_areas'] = array_unique($detected_areas);
            $subtotal += $base_total;
            
            // 2. 山區加價判定
            $mountain_surcharge = 0;
            $mountain_days = array();
            $needs_manual_check = false;
            
            foreach ($daily_routes as $index => $route) {
                $day_num = $index + 1;
                
                if (empty($route['origin']) && empty($route['destination']) && empty($route['stops'])) {
                    continue;
                }
                
                $origin_check = self::check_mountain_route($route['origin']);
                $dest_check = self::check_mountain_route($route['destination']);
                
                $stops_mountain = false;
                $stops_elevation = 0;
                $mountain_stops = array();
                
                if (!empty($route['stops']) && is_array($route['stops'])) {
                    foreach ($route['stops'] as $stop) {
                        if (!empty($stop)) {
                            $stop_check = self::check_mountain_route($stop);
                            
                            if ($stop_check['is_mountain'] || $stop_check['elevation'] > 500) {
                                $stops_mountain = true;
                                $stops_elevation = max($stops_elevation, $stop_check['elevation']);
                                
                                if (!empty($stop_check['formatted_address'])) {
                                    $mountain_stops[] = $stop_check['formatted_address'];
                                } else {
                                    $mountain_stops[] = $stop;
                                }
                            }
                            
                            if ($stop_check['is_excluded']) {
                                wp_send_json_error(array(
                                    'message' => $stop_check['excluded_reason']
                                ));
                                wp_die();
                            }
                        }
                    }
                }
                
                $is_mountain_day = false;
                $day_confidence = 0;
                $day_elevation = max($origin_check['elevation'], $dest_check['elevation'], $stops_elevation);
                
                if ($origin_check['is_excluded'] || $dest_check['is_excluded']) {
                    wp_send_json_error(array(
                        'message' => $origin_check['excluded_reason'] ?: $dest_check['excluded_reason']
                    ));
                    wp_die();
                }
                
                if ($origin_check['confirmed'] || $dest_check['confirmed'] || ($stops_mountain && $stops_elevation > 500)) {
                    $is_mountain_day = true;
                    $day_confidence = max(
                        $origin_check['confidence'], 
                        $dest_check['confidence'], 
                        ($stops_mountain && $stops_elevation > 500 ? 90 : 0)
                    );
                } elseif (($origin_check['is_mountain'] || $dest_check['is_mountain'] || $stops_mountain) && $day_elevation > 0) {
                    if ($day_elevation > 800) {
                        $is_mountain_day = true;
                        $day_confidence = 95;
                    } elseif ($day_elevation > 500) {
                        $is_mountain_day = true;
                        $day_confidence = 85;
                    } else {
                        $is_mountain_day = false;
                        $needs_manual_check = true;
                    }
                }
                
                if ($is_mountain_day) {
                    $mountain_surcharge += 1000;
                    
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
            
            if ($driver_accommodation === 'book' && $trip_days > 1) {
                $nights = $trip_days - 1;
                $accommodation_fee = $nights * 2000;
                $breakdown['driver_accommodation'] = $accommodation_fee;
                $driver_allowance += $accommodation_fee;
            }
            
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
    
    /**
     * AJAX: 提交包車預約
     */
    public static function handle_booking_submission() {
        try {
            $trip_days = isset($_POST['trip_days']) ? intval($_POST['trip_days']) : 0;
            $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
            $start_time = isset($_POST['start_time']) ? sanitize_text_field($_POST['start_time']) : '';
            $passengers = isset($_POST['passengers']) ? intval($_POST['passengers']) : 1;
            
            $customer_name = isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '';
            $customer_phone = isset($_POST['customer_phone']) ? sanitize_text_field($_POST['customer_phone']) : '';
            $customer_email = isset($_POST['customer_email']) ? sanitize_email($_POST['customer_email']) : '';
            $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
            
            $daily_routes = array();
            if (!empty($_POST['daily_routes'])) {
                $decoded = json_decode(stripslashes($_POST['daily_routes']), true);
                if (is_array($decoded)) {
                    $daily_routes = $decoded;
                }
            }
            
            $total_price = isset($_POST['total_price']) ? floatval($_POST['total_price']) : 0;
            $deposit = isset($_POST['deposit']) ? floatval($_POST['deposit']) : 0;
            
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
            
            foreach ($daily_routes as $day => $route) {
                if (empty($route['origin']) || empty($route['destination'])) {
                    $errors[] = "第" . ($day + 1) . "天的起點或終點未填寫";
                }
                
                $origin_check = self::check_mountain_route($route['origin']);
                $dest_check = self::check_mountain_route($route['destination']);
                
                if ($origin_check['is_excluded'] || $dest_check['is_excluded']) {
                    $errors[] = $origin_check['excluded_reason'] ?: $dest_check['excluded_reason'];
                }
                
                if (!empty($route['stops']) && is_array($route['stops'])) {
                    foreach ($route['stops'] as $stop) {
                        if (!empty($stop)) {
                            $stop_check = self::check_mountain_route($stop);
                            if ($stop_check['is_excluded']) {
                                $errors[] = $stop_check['excluded_reason'];
                            }
                        }
                    }
                }
            }
            
            $min_booking_date = date('Y-m-d', strtotime('+2 days'));
            if ($start_date < $min_booking_date) {
                $errors[] = '請至少提前2天預約，以便安排車輛與司機';
            }
            
            if (!empty($errors)) {
                wp_send_json_error(array('message' => implode('、', $errors)));
                wp_die();
            }
            
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
            
            if (!empty($customer_email)) {
                self::send_booking_confirmation($booking_id, $booking_data);
            } else {
                update_post_meta($booking_id, '_no_email_notification', true);
            }
            
            self::send_admin_notification($booking_id, $booking_data);
            
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
    
    /**
     * 發送確認信
     */
    private static function send_booking_confirmation($booking_id, $booking_data) {
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
        
        if (!empty($booking_data['child_seats']) && $booking_data['child_seats'] > 0) {
            $message .= "【嬰兒座椅】{$booking_data['child_seats']}張\n";
        }
        if (!empty($booking_data['booster_seats']) && $booking_data['booster_seats'] > 0) {
            $message .= "【增高墊】{$booking_data['booster_seats']}張\n";
        }
        $message .= "\n";
        
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
    
    /**
     * 發送管理員通知
     */
    private static function send_admin_notification($booking_id, $booking_data) {
        $admin_email = get_option('admin_email');
        
        $subject = '新包車預約通知 - ' . $booking_data['customer_name'] . ' - CHT' . str_pad($booking_id, 6, '0', STR_PAD_LEFT);
        
        $message = "有新的包車旅遊預約：\n\n";
        $message .= "【預約編號】CHT" . str_pad($booking_id, 6, '0', STR_PAD_LEFT) . "\n";
        $message .= "【客戶姓名】{$booking_data['customer_name']}\n";
        $message .= "【客戶電話】{$booking_data['customer_phone']}\n";
        
        if (!empty($booking_data['customer_email'])) {
            $message .= "【客戶Email】{$booking_data['customer_email']}\n";
        } else {
            $message .= "【客戶Email】⚠️ 未提供（無法發送確認信）\n";
        }
        
        $message .= "【行程天數】{$booking_data['trip_days']}天\n";
        $message .= "【出發日期】{$booking_data['start_date']}\n";
        $message .= "【總金額】NT$ " . number_format($booking_data['total_price']) . "\n";
        $message .= "※ 價格已含稅\n";
        
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
    
    /**
     * AJAX: 地址自動完成
     */
    public static function handle_address_autocomplete() {
        $input = isset($_POST['input']) ? sanitize_text_field($_POST['input']) : '';
        
        if (mb_strlen($input) < 2) {
            wp_send_json_error('輸入太短');
        }
        
        $api_key = defined('GOOGLE_MAPS_API_KEY') ? GOOGLE_MAPS_API_KEY : 'AIzaSyD7m-umnnMHhXTi11_uvI14Rel6mCN1Dz4';
        
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
    
    /**
     * AJAX: 驗證地址
     */
    public static function handle_address_validation() {
        $address = isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '';
        
        if (empty($address)) {
            wp_send_json_error('地址為空');
        }
        
        $processed = self::smart_address_processing($address);
        
        if ($processed['method'] === 'fallback') {
            wp_send_json_error(array(
                'message' => '無法識別地址',
                'original' => $address
            ));
        }
        
        $mountain_check = self::check_mountain_route($address);
        
        wp_send_json_success(array(
            'formatted' => $processed['formatted'],
            'city' => $processed['city'],
            'district' => $processed['district'],
            'elevation' => round($processed['elevation']),
            'is_mountain' => $mountain_check['is_mountain'],
            'mountain_confidence' => $mountain_check['confidence'],
            'base_rate' => self::get_city_base_rate($address)
        ));
        
        wp_die();
    }
}

// =====================================
// 4. AJAX 註冊
// =====================================
class NineoAjaxHandlers {
    
    public static function init() {
        // 機場接送 AJAX
        add_action('wp_ajax_calculate_airport_price', [NineoAirportLogic::class, 'handle_price_calculation']);
        add_action('wp_ajax_nopriv_calculate_airport_price', [NineoAirportLogic::class, 'handle_price_calculation']);
        
        add_action('wp_ajax_submit_airport_booking', [NineoAirportLogic::class, 'handle_booking_submission']);
        add_action('wp_ajax_nopriv_submit_airport_booking', [NineoAirportLogic::class, 'handle_booking_submission']);
        
        // 包車旅遊 AJAX
        add_action('wp_ajax_calculate_charter_price', [NineoCharterLogic::class, 'handle_price_calculation']);
        add_action('wp_ajax_nopriv_calculate_charter_price', [NineoCharterLogic::class, 'handle_price_calculation']);
        
        add_action('wp_ajax_submit_charter_booking', [NineoCharterLogic::class, 'handle_booking_submission']);
        add_action('wp_ajax_nopriv_submit_charter_booking', [NineoCharterLogic::class, 'handle_booking_submission']);
        
        add_action('wp_ajax_autocomplete_address', [NineoCharterLogic::class, 'handle_address_autocomplete']);
        add_action('wp_ajax_nopriv_autocomplete_address', [NineoCharterLogic::class, 'handle_address_autocomplete']);
        
        add_action('wp_ajax_validate_address', [NineoCharterLogic::class, 'handle_address_validation']);
        add_action('wp_ajax_nopriv_validate_address', [NineoCharterLogic::class, 'handle_address_validation']);
        
        // 測試端點
        add_action('wp_ajax_test_airport_ajax', function() {
            wp_send_json_success(array(
                'message' => 'AJAX 系統正常運作',
                'timestamp' => current_time('mysql'),
                'api_key_set' => defined('GOOGLE_MAPS_API_KEY'),
                'php_version' => phpversion(),
                'wp_version' => get_bloginfo('version')
            ));
            wp_die();
        });
        add_action('wp_ajax_nopriv_test_airport_ajax', function() {
            wp_send_json_success(array(
                'message' => 'AJAX 系統正常運作',
                'timestamp' => current_time('mysql')
            ));
            wp_die();
        });
    }
}

// 初始化
NineoAjaxHandlers::init();

// 記錄模組載入
error_log('9O Business Logic Module loaded - All handlers registered');