/**
 * Code Snippet: [005] 9O Booking - Utilities
 * 
 * Code Snippets 設定:
 * - Title: [005] 9O Booking - Utilities
 * - Description: 9O Booking System 工具函數庫
 * - Tags: 9o-booking, utilities, tools
 * - Priority: 5
 */

// 確保核心設定已載入
if (!defined('NINEO_BOOKING_VERSION')) {
    error_log('[9O Booking] Utilities: Core Setup not loaded');
    return;
}

/**
 * 地址轉換為座標
 */
function nineo_get_address_coordinates($address) {
    if (empty($address)) {
        return false;
    }
    
    $api_key = GOOGLE_MAPS_API_KEY;
    if (empty($api_key)) {
        error_log('Google Maps API Key 未設定');
        return false;
    }
    
    $cache_key = '9o_geocode_' . md5($address);
    $cached_result = get_transient($cache_key);
    
    if ($cached_result !== false) {
        return $cached_result;
    }
    
    $address_encoded = urlencode($address . ', 台灣');
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address_encoded}&key={$api_key}&language=zh-TW&region=TW";
    
    $response = wp_remote_get($url, array('timeout' => 10));
    
    if (is_wp_error($response)) {
        error_log('Geocoding API 錯誤: ' . $response->get_error_message());
        return false;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ($data['status'] === 'OK' && isset($data['results'][0]['geometry']['location'])) {
        $result = array(
            'lat' => $data['results'][0]['geometry']['location']['lat'],
            'lng' => $data['results'][0]['geometry']['location']['lng'],
            'formatted_address' => $data['results'][0]['formatted_address']
        );
        
        // 快取結果 24 小時
        set_transient($cache_key, $result, DAY_IN_SECONDS);
        
        return $result;
    }
    
    error_log('Geocoding 失敗: ' . $data['status']);
    return false;
}

/**
 * 計算兩點間距離 (使用 Google Maps Distance Matrix API)
 */
function nineo_calculate_distance($origin, $destination) {
    if (empty($origin) || empty($destination)) {
        return false;
    }
    
    $api_key = GOOGLE_MAPS_API_KEY;
    if (empty($api_key)) {
        error_log('Google Maps API Key 未設定');
        return false;
    }
    
    $cache_key = '9o_distance_' . md5($origin . $destination);
    $cached_result = get_transient($cache_key);
    
    if ($cached_result !== false) {
        return $cached_result;
    }
    
    $origin_encoded = urlencode($origin);
    $destination_encoded = urlencode($destination);
    
    $url = "https://maps.googleapis.com/maps/api/distancematrix/json?" .
           "origins={$origin_encoded}&destinations={$destination_encoded}" .
           "&units=metric&key={$api_key}&language=zh-TW";
    
    $response = wp_remote_get($url, array('timeout' => 15));
    
    if (is_wp_error($response)) {
        error_log('Distance Matrix API 錯誤: ' . $response->get_error_message());
        return false;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ($data['status'] === 'OK' && 
        isset($data['rows'][0]['elements'][0]['status']) &&
        $data['rows'][0]['elements'][0]['status'] === 'OK') {
        
        $distance_meters = $data['rows'][0]['elements'][0]['distance']['value'];
        $distance_km = $distance_meters / 1000;
        
        // 快取結果 24 小時
        set_transient($cache_key, $distance_km, DAY_IN_SECONDS);
        
        return $distance_km;
    }
    
    error_log('Distance Matrix 計算失敗');
    return false;
}

/**
 * 點在多邊形內檢測 (Ray Casting Algorithm)
 */
function nineo_point_in_polygon($point, $polygon) {
    if (!is_array($polygon) || count($polygon) < 3) {
        return false;
    }
    
    $x = $point['lng'];
    $y = $point['lat'];
    $inside = false;
    
    $n = count($polygon);
    for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
        $xi = $polygon[$i]['lng'];
        $yi = $polygon[$i]['lat'];
        $xj = $polygon[$j]['lng'];
        $yj = $polygon[$j]['lat'];
        
        if ((($yi > $y) != ($yj > $y)) && 
            ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi)) {
            $inside = !$inside;
        }
    }
    
    return $inside;
}

/**
 * 智能地址處理
 */
function nineo_smart_address_processing($address) {
    if (empty($address)) {
        return ['city' => '', 'formatted' => '', 'type' => 'unknown'];
    }
    
    // 移除多餘空白
    $address = trim($address);
    
    // 縣市對應表
    $city_patterns = [
        '台北市|臺北市' => '台北市',
        '新北市' => '新北市',
        '基隆市' => '基隆市',
        '桃園市|桃園縣' => '桃園市',
        '新竹市|新竹縣' => '新竹地區',
        '苗栗縣' => '苗栗縣',
        '台中市|臺中市|台中縣|臺中縣' => '台中市',
        '彰化縣' => '彰化縣',
        '南投縣' => '南投縣',
        '雲林縣' => '雲林縣',
        '嘉義市|嘉義縣' => '嘉義地區',
        '台南市|臺南市|台南縣|臺南縣' => '台南市',
        '高雄市|高雄縣' => '高雄市',
        '屏東縣' => '屏東縣',
        '宜蘭縣' => '宜蘭縣',
        '花蓮縣' => '花蓮縣',
        '台東縣|臺東縣' => '台東縣'
    ];
    
    $detected_city = '';
    foreach ($city_patterns as $pattern => $standardized) {
        if (preg_match("/($pattern)/u", $address)) {
            $detected_city = $standardized;
            break;
        }
    }
    
    // 判斷地址類型
    $type = 'general';
    if (strpos($address, '機場') !== false) {
        $type = 'airport';
    } elseif (strpos($address, '車站') !== false) {
        $type = 'station';
    } elseif (strpos($address, '醫院') !== false) {
        $type = 'hospital';
    } elseif (strpos($address, '學校') !== false || strpos($address, '大學') !== false) {
        $type = 'school';
    }
    
    return [
        'city' => $detected_city,
        'formatted' => $address,
        'type' => $type,
        'original' => $address
    ];
}

/**
 * 格式化電話號碼
 */
function nineo_format_phone($phone) {
    // 移除所有非數字字符
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // 台灣手機號碼格式化
    if (preg_match('/^09\d{8}$/', $phone)) {
        return substr($phone, 0, 4) . '-' . substr($phone, 4, 3) . '-' . substr($phone, 7);
    }
    
    // 台灣市話號碼格式化
    if (preg_match('/^0[2-8]\d{7,8}$/', $phone)) {
        $area = substr($phone, 0, 2);
        $number = substr($phone, 2);
        if (strlen($number) === 7) {
            return $area . '-' . substr($number, 0, 3) . '-' . substr($number, 3);
        } else {
            return $area . '-' . substr($number, 0, 4) . '-' . substr($number, 4);
        }
    }
    
    return $phone;
}

/**
 * 驗證電子郵件格式
 */
function nineo_validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * 驗證台灣電話號碼
 */
function nineo_validate_phone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // 手機號碼：09xxxxxxxx
    if (preg_match('/^09\d{8}$/', $phone)) {
        return true;
    }
    
    // 市話號碼：0x-xxxxxxx 或 0x-xxxxxxxx
    if (preg_match('/^0[2-8]\d{7,8}$/', $phone)) {
        return true;
    }
    
    return false;
}

/**
 * 生成預約編號
 */
function nineo_generate_booking_number($type = 'booking') {
    $prefix = strtoupper(substr($type, 0, 1));
    $timestamp = date('ymdHis');
    $random = sprintf('%04d', wp_rand(1000, 9999));
    
    return $prefix . $timestamp . $random;
}

/**
 * 取得系統狀態
 */
function nineo_get_system_status() {
    return [
        'version' => NINEO_BOOKING_VERSION,
        'wordpress_version' => get_bloginfo('version'),
        'php_version' => PHP_VERSION,
        'google_maps_api' => !empty(GOOGLE_MAPS_API_KEY),
        'database_version' => get_option('9o_booking_db_version', '0.0.0'),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'upload_max_filesize' => ini_get('upload_max_filesize')
    ];
}

/**
 * 記錄系統日誌
 */
function nineo_log($message, $level = 'info', $context = []) {
    if (!ENABLE_DEBUG) {
        return;
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $context_str = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    
    $log_message = "[{$timestamp}] 9O-Booking [{$level}]: {$message}{$context_str}";
    
    error_log($log_message);
    
    // 可以擴展到其他日誌系統
    do_action('9o_booking_log', $message, $level, $context);
}

/**
 * 清理快取
 */
function nineo_clear_cache($type = 'all') {
    global $wpdb;
    
    switch ($type) {
        case 'geocode':
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_9o_geocode_%'");
            break;
            
        case 'distance':
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_9o_distance_%'");
            break;
            
        case 'elevation':
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_9o_elevation_%'");
            break;
            
        case 'all':
        default:
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_9o_%'");
            break;
    }
    
    nineo_log("清理快取: {$type}", 'info');
}

/**
 * 計算包車停靠點費用
 */
function calculate_charter_stopover_fee($stopover_address, $daily_mountain_count = 0) {
    // 包車停靠點基本免費
    $base_fee = 0;
    
    // 檢查是否為山區（每日限加一次）
    $is_mountain = check_mountain_location($stopover_address);
    $mountain_fee = ($is_mountain && $daily_mountain_count === 0) ? MOUNTAIN_SURCHARGE : 0;
    
    return [
        'base_fee' => $base_fee,
        'mountain_fee' => $mountain_fee,
        'total' => $base_fee + $mountain_fee,
        'is_mountain' => $is_mountain,
        'mountain_applied' => $mountain_fee > 0
    ];
}

/**
 * 取得加值服務費用配置
 */
function get_addon_service_config($service_type = 'both') {
    $common_services = [
        'child_seat' => CHILD_SEAT_FEE,
        'booster_seat' => BOOSTER_SEAT_FEE
    ];
    
    $airport_services = [
        'holding_sign' => HOLDING_SIGN_FEE,
        'premium_car' => PREMIUM_CAR_FEE
    ];
    
    switch ($service_type) {
        case 'airport':
            return array_merge($common_services, $airport_services);
        case 'charter':
            return $common_services;
        case 'both':
        default:
            return [
                'common' => $common_services,
                'airport' => $airport_services
            ];
    }
}

// 向後相容的別名函數
function get_address_coordinates($address) {
    return nineo_get_address_coordinates($address);
}

function calculate_distance_google($origin, $destination) {
    return nineo_calculate_distance($origin, $destination);
}

function is_point_in_polygon($point, $polygon) {
    return nineo_point_in_polygon($point, $polygon);
}

// 除錯日誌
if (defined('NINEO_DEBUG_MODE') && NINEO_DEBUG_MODE) {
    error_log('[9O Booking] Utilities loaded - Priority: 15');
}
