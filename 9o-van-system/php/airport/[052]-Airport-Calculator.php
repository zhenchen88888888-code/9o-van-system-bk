/**
 * Code Snippet: [052] 9O Booking - Airport Calculator (更新版)
 * 
 * Code Snippets 設定:
 * - Title: [052] 9O Booking - Airport Calculator
 * - Description: 機場接送價格計算核心邏輯（支援偏遠地區）
 * - Tags: 9o-booking, airport, calculator
 * - Priority: 52
 * - Run snippet: Run snippet everywhere
 */

// 確保核心設定和偏遠地區資料已載入
if (!defined('NINEO_BOOKING_VERSION')) {
    error_log('[9O Booking] Airport Calculator: Core Setup not loaded');
    return;
}

// 機場計算器類別（轉換為函數集合）
global $airport_calculator_cache;
$airport_calculator_cache = [];

/**
 * 計算機場接送基本價格
 */
function airport_calculate_base_price($airport, $city) {
    global $AIRPORT_PRICE_MATRIX;
    
    if (!isset($AIRPORT_PRICE_MATRIX[$airport])) {
        error_log('[9O Booking] Airport Calculator: 未知機場 ' . $airport);
        return 0;
    }
    
    if (!isset($AIRPORT_PRICE_MATRIX[$airport][$city])) {
        error_log('[9O Booking] Airport Calculator: 未定義路線 ' . $airport . ' -> ' . $city);
        return 0;
    }
    
    return $AIRPORT_PRICE_MATRIX[$airport][$city];
}

/**
 * 計算夜間加價
 */
function airport_calculate_night_surcharge($time) {
    if (empty($time)) {
        return 0;
    }
    
    $hour = intval(explode(':', $time)[0]);
    
    // 夜間時段：22:00 - 08:00
    if ($hour >= NIGHT_START_HOUR || $hour < NIGHT_END_HOUR) {
        return AIRPORT_NIGHT_SURCHARGE;
    }
    
    return 0;
}

/**
 * 計算偏遠地區加價（使用新的全域變數）
 */
function airport_calculate_remote_surcharge($address) {
    global $REMOTE_AREAS;
    
    if (empty($address)) {
        return 0;
    }
    
    error_log('[9O Booking] 偏遠地區檢查: ' . $address);
    
    // 取得地址座標
    $coords = nineo_get_address_coordinates($address);
    if (!$coords) {
        error_log('[9O Booking] 無法轉換地址為座標');
        return 0;
    }
    
    error_log('[9O Booking] 地址座標: lat=' . $coords['lat'] . ', lng=' . $coords['lng']);
    
    // 檢查是否有偏遠地區資料
    if (empty($REMOTE_AREAS)) {
        error_log('[9O Booking] 偏遠地區資料未載入');
        return 0;
    }
    
    // 檢查偏遠地區
    foreach ($REMOTE_AREAS as $area) {
        if (empty($area['polygons'])) {
            continue;
        }
        
        foreach ($area['polygons'] as $polygon) {
            if (!empty($polygon) && nineo_point_in_polygon($coords, $polygon)) {
                error_log('[9O Booking] 找到匹配區域: ' . $area['name'] . ', 加價: NT$' . $area['surcharge']);
                return $area['surcharge'];
            }
        }
    }
    
    error_log('[9O Booking] 地址不在任何偏遠地區內');
    return 0;
}

/**
 * 計算停靠點費用
 */
function airport_calculate_stopover_charges($stopovers, $main_address) {
    $total_stopover_charge = 0;
    $stopover_details = [];
    
    if (empty($stopovers) || !is_array($stopovers)) {
        return [
            'total' => 0,
            'details' => []
        ];
    }
    
    foreach ($stopovers as $index => $stopover) {
        if (empty($stopover['address'])) {
            continue;
        }
        
        // 計算距離
        $distance = nineo_calculate_distance($main_address, $stopover['address']);
        
        if ($distance === false) {
            error_log('[9O Booking] 無法計算停靠點距離');
            $distance = 0;
        }
        
        // 根據距離計算費用
        $fee = calculate_airport_stopover_fee($distance);
        
        $stopover_details[] = [
            'address' => $stopover['address'],
            'distance' => round($distance, 1),
            'fee' => $fee
        ];
        
        $total_stopover_charge += $fee;
    }
    
    return [
        'total' => $total_stopover_charge,
        'details' => $stopover_details
    ];
}

/**
 * 計算加值服務費用
 */
function airport_calculate_addon_charges($addons) {
    $total_addon_charge = 0;
    $addon_details = [];
    
    if (empty($addons) || !is_array($addons)) {
        return [
            'total' => 0,
            'details' => []
        ];
    }
    
    $addon_config = get_addon_service_config('airport');
    
    foreach ($addons as $addon_key => $value) {
        if (empty($value) || $value === '0') {
            continue;
        }
        
        if (isset($addon_config[$addon_key])) {
            $unit_price = $addon_config[$addon_key];
            $quantity = intval($value);
            $subtotal = $unit_price * $quantity;
            
            $addon_details[] = [
                'service' => $addon_key,
                'quantity' => $quantity,
                'unit_price' => $unit_price,
                'subtotal' => $subtotal
            ];
            
            $total_addon_charge += $subtotal;
        }
    }
    
    return [
        'total' => $total_addon_charge,
        'details' => $addon_details
    ];
}

/**
 * 計算機場接送總價格
 */
function airport_calculate_total_price($params) {
    $result = [
        'success' => false,
        'base_price' => 0,
        'night_surcharge' => 0,
        'remote_surcharge' => 0,
        'stopover_charge' => 0,
        'addon_charge' => 0,
        'total' => 0,
        'details' => []
    ];
    
    // 驗證必要參數
    if (empty($params['airport']) || empty($params['destination_city'])) {
        $result['error'] = '缺少必要參數';
        return $result;
    }
    
    // 基本價格
    $result['base_price'] = airport_calculate_base_price(
        $params['airport'],
        $params['destination_city']
    );
    
    if ($result['base_price'] === 0) {
        $result['error'] = '無法計算基本價格';
        return $result;
    }
    
    // 夜間加價
    if (!empty($params['time'])) {
        $result['night_surcharge'] = airport_calculate_night_surcharge($params['time']);
    }
    
    // 偏遠地區加價
    if (!empty($params['main_address'])) {
        $result['remote_surcharge'] = airport_calculate_remote_surcharge($params['main_address']);
    }
    
    // 停靠點費用
    if (!empty($params['stopovers'])) {
        $stopover_data = airport_calculate_stopover_charges(
            $params['stopovers'],
            $params['main_address'] ?? ''
        );
        $result['stopover_charge'] = $stopover_data['total'];
        $result['details']['stopovers'] = $stopover_data['details'];
    }
    
    // 加值服務費用
    if (!empty($params['addons'])) {
        $addon_data = airport_calculate_addon_charges($params['addons']);
        $result['addon_charge'] = $addon_data['total'];
        $result['details']['addons'] = $addon_data['details'];
    }
    
    // 計算總價
    $result['total'] = $result['base_price'] + 
                      $result['night_surcharge'] + 
                      $result['remote_surcharge'] + 
                      $result['stopover_charge'] + 
                      $result['addon_charge'];
    
    // 來回接送處理
    if (!empty($params['service_type']) && $params['service_type'] === 'roundtrip') {
        $return_calculation = airport_calculate_return_charges($params);
        
        $result['return_base_price'] = $result['base_price'];
        $result['return_night_surcharge'] = $return_calculation['night_surcharge'];
        $result['return_remote_surcharge'] = $return_calculation['remote_surcharge'];
        $result['return_stopover_charge'] = $return_calculation['stopover_charge'];
        $result['return_addon_charge'] = $return_calculation['addon_charge'];
        
        $result['total'] += $result['return_base_price'] +
                           $result['return_night_surcharge'] +
                           $result['return_remote_surcharge'] +
                           $result['return_stopover_charge'] +
                           $result['return_addon_charge'];
        
        $result['details']['return'] = $return_calculation['details'];
    }
    
    $result['success'] = true;
    return $result;
}

/**
 * 計算回程費用
 */
function airport_calculate_return_charges($params) {
    $return_charges = [
        'night_surcharge' => 0,
        'remote_surcharge' => 0,
        'stopover_charge' => 0,
        'addon_charge' => 0,
        'details' => []
    ];
    
    // 回程夜間加價
    if (!empty($params['return_time'])) {
        $return_charges['night_surcharge'] = airport_calculate_night_surcharge($params['return_time']);
    }
    
    // 回程偏遠地區加價
    if (!empty($params['return_main_address'])) {
        $return_charges['remote_surcharge'] = airport_calculate_remote_surcharge($params['return_main_address']);
    }
    
    // 回程停靠點
    if (!empty($params['return_stopovers'])) {
        $stopover_data = airport_calculate_stopover_charges(
            $params['return_stopovers'],
            $params['return_main_address'] ?? $params['main_address'] ?? ''
        );
        $return_charges['stopover_charge'] = $stopover_data['total'];
        $return_charges['details']['stopovers'] = $stopover_data['details'];
    }
    
    // 回程加值服務
    if (!empty($params['return_addons'])) {
        $addon_data = airport_calculate_addon_charges($params['return_addons']);
        $return_charges['addon_charge'] = $addon_data['total'];
        $return_charges['details']['addons'] = $addon_data['details'];
    }
    
    return $return_charges;
}

// 初始化
add_action('init', 'airport_calculator_init');
function airport_calculator_init() {
    // 檢查偏遠地區資料是否載入
    global $REMOTE_AREAS;
    if (empty($REMOTE_AREAS)) {
        error_log('[9O Booking] Airport Calculator: 偏遠地區資料未載入，某些功能可能無法正常運作');
    }
}

// 除錯日誌
if (defined('NINEO_DEBUG_MODE') && NINEO_DEBUG_MODE) {
    error_log('[9O Booking] Airport Calculator loaded - Priority: 50');
}
