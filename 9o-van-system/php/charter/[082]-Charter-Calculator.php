/**
 * Code Snippet: [082] 9O Booking - Charter Calculator
 * 
 * Code Snippets 設定:
 * - Title: [082] 9O Booking - Charter Calculator
 * - Description: 包車旅遊價格計算核心邏輯
 * - Tags: 9o-booking, charter, calculator
 * - Priority: 82
 * - Run snippet: Run snippet everywhere
 */

// 確保核心設定已載入
if (!defined('NINEO_BOOKING_VERSION')) {
    error_log('[9O Booking] Charter Calculator: Core Setup not loaded');
    return;
}

/**
 * 計算包車旅遊總價格
 * 
 * @param array $params 預約參數
 * @return array 完整價格明細
 */
function charter_calculate_total($params) {
    try {
        $trip_days = intval($params['days'] ?? $params['trip_days'] ?? 1);
        $routes = $params['routes'] ?? $params['daily_routes'] ?? [];
        
        // 1. 驗證預約
        charter_validate_booking($params);
        
        // 2. 計算每日費用（含多日優惠）
        $daily_fees = charter_calculate_daily_fees($trip_days, $routes);
        
        // 3. 山區檢測和加價
        $mountain_data = charter_check_mountain_routes($routes);
        
        // 4. 司機費用（住宿 + 餐費）
        $driver_fees = charter_calculate_driver_fees($trip_days, $params);
        
        // 5. 加值服務費用
        $addons = charter_calculate_addons($params);
        
        // 6. 計算總計
        $base_total = array_sum($daily_fees['rates']);
        $mountain_total = array_sum(array_column($mountain_data['breakdown'], 'charge'));
        $total = $base_total + $mountain_total + $driver_fees['total'] + $addons['total'];
        
        // 7. 計算訂金（30%）
        $deposit = round($total * 0.3);
        $balance = $total - $deposit;
        
        return [
            'success' => true,
            'trip_days' => $trip_days,
            'subtotal' => $total,
            'total' => $total,
            'deposit' => $deposit,
            'balance' => $balance,
            'breakdown' => [
                'base_total' => $base_total,
                'daily_rates' => $daily_fees['rates'],
                'base_daily_rate' => $daily_fees['base_rate'],
                'is_south' => $daily_fees['is_south'],
                'detected_areas' => $daily_fees['detected_areas'],
                'mountain_surcharge' => $mountain_total,
                'mountain_breakdown' => $mountain_data['breakdown'],
                'mountain_needs_check' => $mountain_data['needs_manual_check'],
                'driver_fees' => $driver_fees,
                'addons' => $addons
            ]
        ];
        
    } catch (Exception $e) {
        error_log('[9O Booking] Charter Calculator Error: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * 驗證預約參數
 */
function charter_validate_booking($params) {
    $trip_days = intval($params['days'] ?? $params['trip_days'] ?? 1);
    $routes = $params['routes'] ?? $params['daily_routes'] ?? [];
    
    // 檢查天數限制
    if ($trip_days < 1 || $trip_days > MAX_CHARTER_DAYS) {
        throw new Exception("行程天數必須在1-" . MAX_CHARTER_DAYS . "天之間");
    }
    
    // 檢查路線數量
    if (count($routes) !== $trip_days) {
        throw new Exception('請完整填寫每日行程');
    }
    
    // 檢查最少預約天數
    if (isset($params['start_date'])) {
        $min_date = date('Y-m-d', strtotime('+' . MIN_BOOKING_DAYS . ' days'));
        if ($params['start_date'] < $min_date) {
            throw new Exception('請至少提前' . MIN_BOOKING_DAYS . '天預約，以便安排車輛與司機');
        }
    }
    
    // 檢查排除地區
    foreach ($routes as $day => $route) {
        $locations = charter_get_route_locations($route);
        foreach ($locations as $location) {
            if (is_excluded_area($location)) {
                throw new Exception(get_excluded_area_message($location));
            }
        }
    }
}

/**
 * 計算每日費用（含多日優惠）
 * 第2天起每天減1000元，非累積
 * 例：北部3天 = 第1天:12000, 第2天:11000, 第3天:11000 = 總計34000
 */
function charter_calculate_daily_fees($days, $routes) {
    $base_rate_day1 = CHARTER_NORTH_DAY1; // 預設: 12000
    $base_rate_day2plus = CHARTER_NORTH_DAY2PLUS; // 預設: 11000
    $is_south = false;
    $detected_areas = [];
    
    // 檢查是否有任何路線在南部地區
    foreach ($routes as $route) {
        $locations = charter_get_route_locations($route);
        
        foreach ($locations as $location) {
            if (charter_is_south_region($location)) {
                $is_south = true;
                $base_rate_day1 = CHARTER_SOUTH_DAY1; // 14000
                $base_rate_day2plus = CHARTER_SOUTH_DAY2PLUS; // 13000
                
                // 取得地區資訊
                $location_info = nineo_smart_address_processing($location);
                if (!empty($location_info['city'])) {
                    $detected_areas[] = $location_info['city'];
                }
                break 2; // 跳出兩層迴圈
            }
        }
    }
    
    // 計算每日費率（含多日優惠）
    $daily_rates = [];
    for ($day = 1; $day <= $days; $day++) {
        if ($day === 1) {
            $daily_rates[$day] = $base_rate_day1;
        } else {
            // 第2天起使用優惠價
            $daily_rates[$day] = $base_rate_day2plus;
        }
    }
    
    return [
        'rates' => $daily_rates,
        'base_rate' => $base_rate_day1,
        'base_rate_day1' => $base_rate_day1,
        'base_rate_day2plus' => $base_rate_day2plus,
        'is_south' => $is_south,
        'detected_areas' => array_unique($detected_areas)
    ];
}

/**
 * 檢查山區路線並計算加價
 * 每個山區天各加1000元
 */
function charter_check_mountain_routes($routes) {
    $mountain_breakdown = [];
    $needs_manual_check = false;
    
    foreach ($routes as $index => $route) {
        $day_num = $index + 1;
        
        // 跳過沒有地址的天數
        if (empty($route['origin']) && empty($route['destination']) && empty($route['stops'])) {
            $mountain_breakdown[$day_num] = [
                'day' => $day_num,
                'is_mountain' => false,
                'charge' => 0,
                'confidence' => 0,
                'elevation' => 0,
                'detected_areas' => []
            ];
            continue;
        }
        
        // 檢查當天所有地點
        $day_mountain_check = charter_check_day_mountain_status($route);
        
        $mountain_breakdown[$day_num] = [
            'day' => $day_num,
            'is_mountain' => $day_mountain_check['is_mountain'],
            'charge' => $day_mountain_check['is_mountain'] ? MOUNTAIN_SURCHARGE : 0,
            'confidence' => $day_mountain_check['confidence'],
            'elevation' => $day_mountain_check['elevation'],
            'detected_areas' => $day_mountain_check['detected_areas']
        ];
        
        if ($day_mountain_check['needs_manual_check']) {
            $needs_manual_check = true;
        }
    }
    
    return [
        'breakdown' => $mountain_breakdown,
        'needs_manual_check' => $needs_manual_check
    ];
}

/**
 * 檢查某天的路線是否為山區
 */
function charter_check_day_mountain_status($route) {
    $is_mountain = false;
    $max_confidence = 0;
    $max_elevation = 0;
    $detected_areas = [];
    $needs_manual_check = false;
    
    // 收集當天所有地點
    $locations = charter_get_route_locations($route);
    
    // 檢查每個地點
    foreach ($locations as $location) {
        $mountain_check = charter_check_mountain_route($location);
        
        // 如果任何地點確認是山區，整天都算山區
        if ($mountain_check['confirmed'] || $mountain_check['is_mountain']) {
            $is_mountain = true;
            $max_confidence = max($max_confidence, $mountain_check['confidence']);
            $max_elevation = max($max_elevation, $mountain_check['elevation']);
            
            if (!empty($mountain_check['detected_areas'])) {
                $detected_areas = array_merge($detected_areas, $mountain_check['detected_areas']);
            }
        }
        
        if ($mountain_check['requires_manual_check']) {
            $needs_manual_check = true;
        }
    }
    
    return [
        'is_mountain' => $is_mountain,
        'confidence' => $max_confidence,
        'elevation' => round($max_elevation),
        'detected_areas' => array_unique($detected_areas),
        'needs_manual_check' => $needs_manual_check
    ];
}

/**
 * 計算司機費用（住宿 + 餐費）
 */
function charter_calculate_driver_fees($trip_days, $params) {
    $accommodation_days = max(0, $trip_days - 1);
    $meal_days = $trip_days;
    
    $accommodation_total = $accommodation_days * DRIVER_ACCOMMODATION_FEE;
    $meal_total = $meal_days * DRIVER_MEAL_FEE;
    
    return [
        'accommodation_days' => $accommodation_days,
        'accommodation_fee' => DRIVER_ACCOMMODATION_FEE,
        'accommodation_total' => $accommodation_total,
        'meal_days' => $meal_days,
        'meal_fee' => DRIVER_MEAL_FEE,
        'meal_total' => $meal_total,
        'total' => $accommodation_total + $meal_total
    ];
}

/**
 * 計算加值服務
 */
function charter_calculate_addons($params) {
    $addons = $params['addons'] ?? [];
    $total = 0;
    $details = [];
    
    $addon_config = get_addon_service_config('charter');
    
    foreach ($addon_config as $key => $price) {
        if (!empty($addons[$key])) {
            $quantity = intval($addons[$key]);
            $subtotal = $quantity * $price;
            $total += $subtotal;
            $details[$key] = [
                'quantity' => $quantity,
                'unit_price' => $price,
                'subtotal' => $subtotal
            ];
        }
    }
    
    return [
        'total' => $total,
        'details' => $details
    ];
}

/**
 * 檢查是否為南部地區
 */
function charter_is_south_region($location) {
    global $NINEO_CHARTER_SOUTH_AREAS;
    
    // 智能地址處理
    $location_info = nineo_smart_address_processing($location);
    
    if (!empty($location_info['city'])) {
        // 檢查是否在南部地區列表中
        foreach ($NINEO_CHARTER_SOUTH_AREAS as $south_area) {
            if (strpos($location_info['city'], $south_area) !== false) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * 檢查山區路線
 */
function charter_check_mountain_route($location) {
    global $NINEO_MOUNTAIN_KEYWORDS;
    
    $is_mountain = false;
    $confidence = 0;
    $elevation = 0;
    $detected_areas = [];
    $requires_manual_check = false;
    
    // 1. 關鍵字檢測
    foreach ($NINEO_MOUNTAIN_KEYWORDS as $keyword) {
        if (mb_strpos($location, $keyword) !== false) {
            $is_mountain = true;
            $confidence = 90;
            $detected_areas[] = $keyword;
        }
    }
    
    // 2. 嘗試獲取座標和海拔
    if (defined('GOOGLE_MAPS_API_KEY') && !empty(GOOGLE_MAPS_API_KEY)) {
        $coords = nineo_get_address_coordinates($location);
        if ($coords) {
            // 可以在這裡加入海拔檢測邏輯
            // 暫時簡化處理
            if ($coords['lat'] && $coords['lng']) {
                // 模擬海拔數據（實際應該調用 Google Elevation API）
                $elevation = 0;
            }
        }
    }
    
    return [
        'is_mountain' => $is_mountain,
        'confirmed' => $confidence >= 90,
        'confidence' => $confidence,
        'elevation' => $elevation,
        'detected_areas' => $detected_areas,
        'requires_manual_check' => $requires_manual_check
    ];
}

/**
 * 從路線中取得所有地點
 */
function charter_get_route_locations($route) {
    $locations = [];
    
    if (!empty($route['origin'])) {
        $locations[] = $route['origin'];
    }
    
    if (!empty($route['destination'])) {
        $locations[] = $route['destination'];
    }
    
    if (!empty($route['stops']) && is_array($route['stops'])) {
        $locations = array_merge($locations, array_filter($route['stops']));
    }
    
    return $locations;
}

/**
 * 檢查是否為排除地區
 */
function is_excluded_area($location) {
    global $NINEO_EXCLUDED_AREAS;
    
    foreach ($NINEO_EXCLUDED_AREAS as $area) {
        if (mb_strpos($location, $area) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * 取得排除地區訊息
 */
function get_excluded_area_message($location) {
    $area_messages = [
        '綠島' => '綠島需要特殊安排，請直接聯絡客服',
        '蘭嶼' => '蘭嶼需要特殊安排，請直接聯絡客服',
        '澎湖' => '澎湖地區暫不提供服務',
        '金門' => '金門地區暫不提供服務',
        '馬祖' => '馬祖地區暫不提供服務',
        '小琉球' => '小琉球需要特殊安排，請直接聯絡客服'
    ];
    
    foreach ($area_messages as $area => $message) {
        if (mb_strpos($location, $area) !== false) {
            return $message;
        }
    }
    
    return '此地區暫不提供服務，請聯絡客服';
}

// 初始化
add_action('init', 'charter_calculator_init');
function charter_calculator_init() {
    // 確保相依函數存在
    if (!function_exists('nineo_smart_address_processing')) {
        error_log('[9O Booking] Charter Calculator: Utilities not loaded');
    }
}

// 除錯日誌
if (defined('NINEO_DEBUG_MODE') && NINEO_DEBUG_MODE) {
    error_log('[9O Booking] Charter Calculator loaded - Priority: 55');
}
