/**
 * Code Snippet: [083] 9O Booking - Charter Ajax Handlers
 * 
 * Code Snippets 設定:
 * - Title: [083] 9O Booking - Charter Ajax Handlers
 * - Description: 包車旅遊 AJAX 處理器，包含價格計算、預約提交、地址自動完成、山區檢測等功能
 * - Tags: 9o-booking, charter, ajax, api
 * - Priority: 83
 * - Run snippet: Run snippet everywhere
 */

// 確保核心設定已載入
if (!defined('NINEO_BOOKING_VERSION')) {
    error_log('[9O Booking] Charter Ajax Handlers: Core Setup not loaded');
    return;
}

/**
 * Initialize AJAX handlers
 */
add_action('init', 'nineo_charter_ajax_init', 15);
function nineo_charter_ajax_init() {
    // Price calculation
    add_action('wp_ajax_calculate_charter_price', 'nineo_charter_calculate_price');
    add_action('wp_ajax_nopriv_calculate_charter_price', 'nineo_charter_calculate_price');
    
    // Booking submission
    add_action('wp_ajax_submit_charter_booking', 'nineo_charter_submit_booking');
    add_action('wp_ajax_nopriv_submit_charter_booking', 'nineo_charter_submit_booking');
    
    // Address autocomplete
    add_action('wp_ajax_autocomplete_address', 'nineo_charter_autocomplete_address');
    add_action('wp_ajax_nopriv_autocomplete_address', 'nineo_charter_autocomplete_address');
    
    // Address validation and mountain check
    add_action('wp_ajax_validate_address', 'nineo_charter_validate_address');
    add_action('wp_ajax_nopriv_validate_address', 'nineo_charter_validate_address');
    
    // Mountain route check
    add_action('wp_ajax_check_mountain_route', 'nineo_charter_check_mountain_route');
    add_action('wp_ajax_nopriv_check_mountain_route', 'nineo_charter_check_mountain_route');
}

/**
 * Calculate charter tour price
 * Main price calculation endpoint
 */
function nineo_charter_calculate_price() {
    try {
        // Verify nonce (optional for logged-out users)
        if (is_user_logged_in() && isset($_POST['nonce'])) {
            check_ajax_referer('charter_booking_nonce', 'nonce');
        }
        
        // Collect and sanitize parameters
        $trip_days = intval($_POST['trip_days'] ?? 1);
        $trip_days = max(1, min(MAX_CHARTER_DAYS, $trip_days)); // Limit 1-7 days
        
        $params = [
            'days' => $trip_days,
            'trip_days' => $trip_days, // For compatibility
            'passengers' => intval($_POST['passengers'] ?? 1),
            'child_seats' => intval($_POST['child_seats'] ?? 0),
            'booster_seats' => intval($_POST['booster_seats'] ?? 0),
            'driver_accommodation' => sanitize_text_field($_POST['driver_accommodation'] ?? 'self'),
            'driver_meals' => sanitize_text_field($_POST['driver_meals'] ?? 'provided'),
            'start_date' => sanitize_text_field($_POST['start_date'] ?? ''),
            'routes' => nineo_charter_process_routes($_POST['daily_routes'] ?? []),
            'daily_routes' => nineo_charter_process_routes($_POST['daily_routes'] ?? []) // For compatibility
        ];
        
        // Log calculation request
        if (defined('NINEO_DEBUG_MODE') && NINEO_DEBUG_MODE) {
            error_log('Charter price calculation request: ' . wp_json_encode($params));
        }
        
        // Use nineo_charter_calculate_total function to calculate price
        if (function_exists('nineo_charter_calculate_total')) {
            $result = nineo_charter_calculate_total($params);
        } else {
            throw new Exception('Charter calculator not loaded');
        }
        
        if ($result['success']) {
            // Add formatted currency for frontend display
            $result['formatted_total'] = 'NT$ ' . number_format($result['total']);
            $result['formatted_subtotal'] = 'NT$ ' . number_format($result['subtotal']);
            $result['formatted_deposit'] = 'NT$ ' . number_format($result['deposit']);
            $result['formatted_balance'] = 'NT$ ' . number_format($result['balance']);
            
            // Add additional metadata for frontend
            $result['trip_days'] = $trip_days;
            $result['daily_routes'] = $params['routes'];
            
            wp_send_json_success($result);
        } else {
            wp_send_json_error([
                'message' => $result['error'] ?? __('計算錯誤，請稍後再試', '9o-booking')
            ]);
        }
        
    } catch (Exception $e) {
        error_log('Charter Price Calculation Error: ' . $e->getMessage());
        wp_send_json_error([
            'message' => __('計算錯誤：', '9o-booking') . $e->getMessage()
        ]);
    }
    
    wp_die();
}

/**
 * Submit charter tour booking
 * Complete booking submission with validation and email notifications
 */
function nineo_charter_submit_booking() {
    try {
        // Verify nonce (optional for logged-out users)
        if (is_user_logged_in() && isset($_POST['nonce'])) {
            check_ajax_referer('charter_booking_nonce', 'nonce');
        }
        
        // Collect and sanitize booking data
        $trip_days = intval($_POST['trip_days'] ?? 1);
        $start_date = sanitize_text_field($_POST['start_date'] ?? '');
        $start_time = sanitize_text_field($_POST['start_time'] ?? '');
        $passengers = intval($_POST['passengers'] ?? 1);
        
        // Customer data
        $customer_name = sanitize_text_field($_POST['customer_name'] ?? '');
        $customer_phone = nineo_charter_sanitize_phone($_POST['customer_phone'] ?? '');
        $customer_email = sanitize_email($_POST['customer_email'] ?? '');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        
        // Process daily routes
        $daily_routes = nineo_charter_process_routes($_POST['daily_routes'] ?? '');
        
        // Price information
        $total_price = floatval($_POST['total_price'] ?? 0);
        $deposit = floatval($_POST['deposit'] ?? 0);
        
        // Mountain detection results
        $mountain_detection = null;
        if (!empty($_POST['mountain_detection'])) {
            $mountain_detection = json_decode(stripslashes($_POST['mountain_detection']), true);
        }
        
        // Driver options
        $driver_accommodation = sanitize_text_field($_POST['driver_accommodation'] ?? 'self');
        $driver_meals = sanitize_text_field($_POST['driver_meals'] ?? 'provided');
        $child_seats = intval($_POST['child_seats'] ?? 0);
        $booster_seats = intval($_POST['booster_seats'] ?? 0);
        
        // Build booking data
        $booking_data = [
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
            'driver_accommodation' => $driver_accommodation,
            'driver_meals' => $driver_meals,
            'child_seats' => $child_seats,
            'booster_seats' => $booster_seats,
            'booking_time' => current_time('mysql'),
            'booking_status' => 'pending',
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ];
        
        // Validate booking data
        $validation_errors = nineo_charter_validate_booking_data($booking_data);
        if (!empty($validation_errors)) {
            wp_send_json_error([
                'message' => implode('、', $validation_errors),
                'errors' => $validation_errors
            ]);
            return;
        }
        
        // Format phone number for storage
        $booking_data['customer_phone'] = nineo_charter_format_phone_number($booking_data['customer_phone']);
        
        // Save booking to database
        $booking_id = nineo_charter_save_booking($booking_data);
        
        if ($booking_id && !is_wp_error($booking_id)) {
            // Generate booking number
            $booking_number = 'CHT' . str_pad($booking_id, 6, '0', STR_PAD_LEFT);
            
            // Send confirmation email (if email provided)
            if (!empty($booking_data['customer_email'])) {
                $email_sent = nineo_charter_send_confirmation_email($booking_id, $booking_data);
                if (defined('NINEO_DEBUG_MODE') && NINEO_DEBUG_MODE) {
                    error_log('確認信發送狀態: ' . ($email_sent ? '成功' : '失敗'));
                }
            } else {
                if (defined('NINEO_DEBUG_MODE') && NINEO_DEBUG_MODE) {
                    error_log('客戶未提供 Email，跳過發送確認信');
                }
                update_post_meta($booking_id, '_no_email_notification', true);
            }
            
            // Send admin notification
            nineo_charter_send_admin_notification($booking_id, $booking_data);
            
            // Log successful booking
            if (function_exists('nineo_log')) {
                nineo_log("Charter booking created: ID={$booking_id}, Customer={$booking_data['customer_name']}", 'info');
            }
            
            wp_send_json_success([
                'message' => __('預約成功！我們將在24小時內與您聯繫確認。', '9o-booking'),
                'booking_id' => $booking_id,
                'booking_number' => $booking_number
            ]);
            
        } else {
            throw new Exception(__('預約儲存失敗', '9o-booking'));
        }
        
    } catch (Exception $e) {
        error_log('Charter Booking Submission Error: ' . $e->getMessage());
        wp_send_json_error([
            'message' => __('預約失敗：', '9o-booking') . $e->getMessage()
        ]);
    }
    
    wp_die();
}

/**
 * Address autocomplete using Google Places API
 */
function nineo_charter_autocomplete_address() {
    try {
        $input = sanitize_text_field($_POST['input'] ?? '');
        
        if (mb_strlen($input) < 2) {
            wp_send_json_error(['message' => __('輸入太短，請至少輸入2個字元', '9o-booking')]);
            return;
        }
        
        if (!defined('GOOGLE_MAPS_API_KEY') || empty(GOOGLE_MAPS_API_KEY)) {
            wp_send_json_error(['message' => __('Google Maps API 未設定', '9o-booking')]);
            return;
        }
        
        $api_key = GOOGLE_MAPS_API_KEY;
        
        // Use Google Places Autocomplete API
        $url = 'https://maps.googleapis.com/maps/api/place/autocomplete/json';
        $params = [
            'input' => $input,
            'language' => 'zh-TW',
            'components' => 'country:TW', // Restrict to Taiwan
            'key' => $api_key
        ];
        
        $response = wp_remote_get($url . '?' . http_build_query($params));
        
        if (is_wp_error($response)) {
            error_log('Google Places Autocomplete API Error: ' . $response->get_error_message());
            wp_send_json_error(['message' => __('API 錯誤，請稍後再試', '9o-booking')]);
            return;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($data['status'] === 'OK' && !empty($data['predictions'])) {
            $suggestions = [];
            foreach ($data['predictions'] as $prediction) {
                $suggestions[] = [
                    'description' => $prediction['description'],
                    'place_id' => $prediction['place_id'],
                    'main_text' => $prediction['structured_formatting']['main_text'] ?? '',
                    'secondary_text' => $prediction['structured_formatting']['secondary_text'] ?? '',
                    'types' => $prediction['types'] ?? []
                ];
            }
            
            wp_send_json_success([
                'suggestions' => $suggestions,
                'count' => count($suggestions)
            ]);
        } else {
            wp_send_json_success([
                'suggestions' => [],
                'count' => 0,
                'message' => __('無相關建議', '9o-booking')
            ]);
        }
        
    } catch (Exception $e) {
        error_log('Address Autocomplete Error: ' . $e->getMessage());
        wp_send_json_error(['message' => __('自動完成功能暫時無法使用', '9o-booking')]);
    }
    
    wp_die();
}

/**
 * Validate address and get location details
 */
function nineo_charter_validate_address() {
    try {
        $address = sanitize_text_field($_POST['address'] ?? '');
        
        if (empty($address)) {
            wp_send_json_error(['message' => __('請提供地址', '9o-booking')]);
            return;
        }
        
        // Use smart address processing
        if (function_exists('nineo_smart_address_processing')) {
            $processed = nineo_smart_address_processing($address);
        } else {
            // Fallback processing
            $processed = [
                'method' => 'fallback',
                'formatted' => $address,
                'city' => '',
                'district' => '',
                'elevation' => 0,
                'lat' => 0,
                'lng' => 0
            ];
        }
        
        if ($processed['method'] === 'fallback' && empty($processed['city'])) {
            wp_send_json_error([
                'message' => __('無法識別地址，請確認地址格式正確', '9o-booking'),
                'original' => $address
            ]);
            return;
        }
        
        // Check mountain area
        if (function_exists('nineo_charter_check_mountain_route_address')) {
            $mountain_check = nineo_charter_check_mountain_route_address($address);
        } else {
            $mountain_check = [
                'is_mountain' => false,
                'confidence' => 0,
                'detected_areas' => [],
                'requires_manual_check' => false
            ];
        }
        
        // Get base rate for this location
        if (function_exists('nineo_charter_is_south_region')) {
            $is_south = nineo_charter_is_south_region($address);
        } else {
            $is_south = false;
        }
        
        $base_rate = $is_south ? CHARTER_BASE_SOUTH : CHARTER_BASE_NORTH;
        
        wp_send_json_success([
            'formatted' => $processed['formatted'],
            'city' => $processed['city'],
            'district' => $processed['district'],
            'elevation' => round($processed['elevation']),
            'coordinates' => [
                'lat' => $processed['lat'] ?? 0,
                'lng' => $processed['lng'] ?? 0
            ],
            'is_mountain' => $mountain_check['is_mountain'],
            'mountain_confidence' => $mountain_check['confidence'],
            'mountain_detected_areas' => $mountain_check['detected_areas'],
            'requires_manual_check' => $mountain_check['requires_manual_check'],
            'base_rate' => $base_rate,
            'is_south_region' => $is_south,
            'processing_method' => $processed['method']
        ]);
        
    } catch (Exception $e) {
        error_log('Address Validation Error: ' . $e->getMessage());
        wp_send_json_error(['message' => __('地址驗證失敗，請稍後再試', '9o-booking')]);
    }
    
    wp_die();
}

/**
 * Check if a route/address is in mountain area
 */
function nineo_charter_check_mountain_route() {
    try {
        $address = sanitize_text_field($_POST['address'] ?? '');
        
        if (empty($address)) {
            wp_send_json_error(['message' => __('請提供地址', '9o-booking')]);
            return;
        }
        
        // Use charter mountain check function
        if (function_exists('nineo_charter_check_mountain_route_address')) {
            $result = nineo_charter_check_mountain_route_address($address);
        } else {
            // Fallback result
            $result = [
                'is_mountain' => false,
                'confidence' => 0,
                'elevation' => 0,
                'detected_areas' => [],
                'requires_manual_check' => false
            ];
        }
        
        // Add formatted elevation for display
        if ($result['elevation'] > 0) {
            $result['formatted_elevation'] = number_format($result['elevation']) . ' ' . __('公尺', '9o-booking');
        } else {
            $result['formatted_elevation'] = __('無法取得', '9o-booking');
        }
        
        wp_send_json_success($result);
        
    } catch (Exception $e) {
        error_log('Mountain Route Check Error: ' . $e->getMessage());
        wp_send_json_error(['message' => __('山區檢測失敗，請稍後再試', '9o-booking')]);
    }
    
    wp_die();
}

// ====================================
// Helper Functions
// ====================================

/**
 * Process and sanitize daily routes data
 * 
 * @param string|array $routes
 * @return array
 */
function nineo_charter_process_routes($routes) {
    if (empty($routes)) {
        return [];
    }
    
    // Handle JSON string input
    if (is_string($routes)) {
        $decoded = json_decode(stripslashes($routes), true);
        if (is_array($decoded)) {
            $routes = $decoded;
        } else {
            return [];
        }
    }
    
    if (!is_array($routes)) {
        return [];
    }
    
    $processed = [];
    foreach ($routes as $day => $route) {
        $processed[] = [
            'origin' => sanitize_text_field($route['origin'] ?? ''),
            'destination' => sanitize_text_field($route['destination'] ?? ''),
            'stops' => array_filter(array_map('sanitize_text_field', $route['stops'] ?? []))
        ];
    }
    
    return $processed;
}

/**
 * Sanitize phone number
 * 
 * @param string $phone
 * @return string
 */
function nineo_charter_sanitize_phone($phone) {
    return preg_replace('/[^0-9+]/', '', $phone);
}

/**
 * Format phone number for storage
 * 
 * @param string $phone
 * @return string
 */
function nineo_charter_format_phone_number($phone) {
    // Convert Taiwan local number to international format
    if (strpos($phone, '+') !== 0 && strlen($phone) == 10) {
        return '+886' . ltrim($phone, '0');
    }
    return $phone;
}

/**
 * Validate booking data
 * 
 * @param array $booking_data
 * @return array Array of error messages
 */
function nineo_charter_validate_booking_data($booking_data) {
    $errors = [];
    
    // Required fields validation
    if (empty($booking_data['customer_name'])) {
        $errors[] = __('請填寫姓名', '9o-booking');
    }
    
    if (empty($booking_data['customer_phone'])) {
        $errors[] = __('請填寫電話', '9o-booking');
    } elseif (!preg_match('/^(\+[0-9]{1,4})?[0-9]{9,15}$/', $booking_data['customer_phone'])) {
        $errors[] = __('電話格式不正確', '9o-booking');
    }
    
    // Email validation (optional but must be valid if provided)
    if (!empty($booking_data['customer_email']) && !filter_var($booking_data['customer_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = __('Email 格式不正確', '9o-booking');
    }
    
    if (empty($booking_data['start_date'])) {
        $errors[] = __('請選擇出發日期', '9o-booking');
    }
    
    $trip_days = $booking_data['trip_days'];
    if ($trip_days < 1 || $trip_days > MAX_CHARTER_DAYS) {
        $errors[] = sprintf(__('行程天數必須在1-%d天之間', '9o-booking'), MAX_CHARTER_DAYS);
    }
    
    if (count($booking_data['daily_routes']) !== $trip_days) {
        $errors[] = __('請完整填寫每日行程', '9o-booking');
    }
    
    // Validate each day's route
    foreach ($booking_data['daily_routes'] as $day => $route) {
        if (empty($route['origin']) || empty($route['destination'])) {
            $errors[] = sprintf(__('第%d天的起點或終點未填寫', '9o-booking'), ($day + 1));
        }
        
        // Check for excluded areas
        $locations = [$route['origin'], $route['destination']];
        if (!empty($route['stops']) && is_array($route['stops'])) {
            $locations = array_merge($locations, array_filter($route['stops']));
        }
        
        foreach ($locations as $location) {
            if (!empty($location) && function_exists('nineo_is_excluded_area')) {
                if (nineo_is_excluded_area($location)) {
                    $message = nineo_get_excluded_area_message($location);
                    $errors[] = $message;
                }
            }
        }
    }
    
    // Check minimum booking days
    if (!empty($booking_data['start_date'])) {
        if (defined('MIN_BOOKING_DAYS')) {
            $min_date = date('Y-m-d', strtotime('+' . MIN_BOOKING_DAYS . ' days'));
            if ($booking_data['start_date'] < $min_date) {
                $errors[] = sprintf(__('請至少提前%d天預約，以便安排車輛與司機', '9o-booking'), MIN_BOOKING_DAYS);
            }
        }
    }
    
    return $errors;
}

/**
 * Save booking to database
 * 
 * @param array $booking_data
 * @return int|WP_Error Post ID or error
 */
function nineo_charter_save_booking($booking_data) {
    $post_data = [
        'post_title' => sprintf('[%s] %s - %s (%d%s)',
            __('包車', '9o-booking'),
            $booking_data['customer_name'],
            $booking_data['start_date'],
            $booking_data['trip_days'],
            __('天', '9o-booking')
        ),
        'post_content' => wp_json_encode($booking_data, JSON_UNESCAPED_UNICODE),
        'post_status' => 'private',
        'post_type' => 'charter_booking',
        'meta_input' => [
            '_booking_data' => $booking_data,
            '_booking_number' => '', // Will be updated after insert
            '_customer_data' => [
                'name' => $booking_data['customer_name'],
                'phone' => $booking_data['customer_phone'],
                'email' => $booking_data['customer_email'],
                'passengers' => $booking_data['passengers']
            ],
            '_booking_start_date' => $booking_data['start_date'],
            '_booking_trip_days' => $booking_data['trip_days'],
            '_pricing_data' => [
                'total_price' => $booking_data['total_price'],
                'deposit' => $booking_data['deposit'],
                'balance' => $booking_data['balance']
            ],
            '_mountain_needs_check' => !empty($booking_data['mountain_detection']['needs_manual_check']),
            '_booking_status' => 'pending'
        ]
    ];
    
    $post_id = wp_insert_post($post_data);
    
    if ($post_id && !is_wp_error($post_id)) {
        // Update booking number
        $booking_number = 'CHT' . str_pad($post_id, 6, '0', STR_PAD_LEFT);
        update_post_meta($post_id, '_booking_number', $booking_number);
    }
    
    return $post_id;
}

/**
 * Send confirmation email to customer
 * 
 * @param int $booking_id
 * @param array $booking_data
 * @return bool
 */
function nineo_charter_send_confirmation_email($booking_id, $booking_data) {
    if (empty($booking_data['customer_email'])) {
        return false;
    }
    
    $to = $booking_data['customer_email'];
    $booking_number = 'CHT' . str_pad($booking_id, 6, '0', STR_PAD_LEFT);
    $subject = sprintf(__('包車旅遊預約確認 - 訂單編號 %s', '9o-booking'), $booking_number);
    
    // Build email content
    $message = sprintf(__("親愛的 %s 您好，\n\n", '9o-booking'), $booking_data['customer_name']);
    $message .= __("您的包車旅遊預約已成功提交，以下是您的預約詳情：\n\n", '9o-booking');
    $message .= sprintf(__("【預約編號】%s\n", '9o-booking'), $booking_number);
    $message .= sprintf(__("【行程天數】%d 天\n", '9o-booking'), $booking_data['trip_days']);
    $message .= sprintf(__("【出發日期】%s\n", '9o-booking'), $booking_data['start_date']);
    
    if (!empty($booking_data['start_time'])) {
        $message .= sprintf(__("【出發時間】%s\n", '9o-booking'), $booking_data['start_time']);
    }
    
    $message .= sprintf(__("【結束日期】%s\n", '9o-booking'), $booking_data['end_date']);
    $message .= sprintf(__("【乘客人數】%d 人\n", '9o-booking'), $booking_data['passengers']);
    
    // Daily routes
    $message .= "\n" . __("【每日行程】\n", '9o-booking');
    foreach ($booking_data['daily_routes'] as $day => $route) {
        $day_num = $day + 1;
        $message .= sprintf(__("第%d天：%s → %s\n", '9o-booking'), $day_num, $route['origin'], $route['destination']);
        
        if (!empty($route['stops'])) {
            $message .= __("停靠點：", '9o-booking') . implode('、', $route['stops']) . "\n";
        }
    }
    
    // Driver options
    $message .= "\n" . __("【司機安排】\n", '9o-booking');
    $accommodation_text = ($booking_data['driver_accommodation'] === 'book') ? __('由我們安排', '9o-booking') : __('司機自理', '9o-booking');
    $message .= sprintf(__("住宿：%s\n", '9o-booking'), $accommodation_text);
    $meals_text = ($booking_data['driver_meals'] === 'allowance') ? __('提供餐費補貼', '9o-booking') : __('司機自理', '9o-booking');
    $message .= sprintf(__("餐費：%s\n", '9o-booking'), $meals_text);
    
    // Add-ons
    if ($booking_data['child_seats'] > 0) {
        $message .= sprintf(__("【嬰兒座椅】%d 張\n", '9o-booking'), $booking_data['child_seats']);
    }
    if ($booking_data['booster_seats'] > 0) {
        $message .= sprintf(__("【兒童增高墊】%d 張\n", '9o-booking'), $booking_data['booster_seats']);
    }
    
    // Pricing
    $message .= "\n" . __("【費用明細】\n", '9o-booking');
    $message .= sprintf(__("總金額：NT$ %s\n", '9o-booking'), number_format($booking_data['total_price']));
    $message .= sprintf(__("訂金：NT$ %s\n", '9o-booking'), number_format($booking_data['deposit']));
    $message .= sprintf(__("尾款：NT$ %s\n", '9o-booking'), number_format($booking_data['balance']));
    
    if (!empty($booking_data['notes'])) {
        $message .= "\n" . sprintf(__("【備註】%s\n", '9o-booking'), $booking_data['notes']);
    }
    
    $message .= "\n" . __("我們將在24小時內與您電話確認預約詳情。\n", '9o-booking');
    $message .= __("如有任何問題，請聯繫我們的客服專線。\n\n", '9o-booking');
    $message .= __("感謝您的預約！\n", '9o-booking');
    $message .= __("9o Van Strip 包車旅遊服務", '9o-booking');
    
    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'From: 9o Van Strip <noreply@' . $_SERVER['SERVER_NAME'] . '>'
    ];
    
    return wp_mail($to, $subject, $message, $headers);
}

/**
 * Send admin notification email
 * 
 * @param int $booking_id
 * @param array $booking_data
 * @return bool
 */
function nineo_charter_send_admin_notification($booking_id, $booking_data) {
    $admin_email = get_option('admin_email');
    $booking_number = 'CHT' . str_pad($booking_id, 6, '0', STR_PAD_LEFT);
    
    $subject = sprintf(__('新包車預約 - %s - %s', '9o-booking'), $booking_data['customer_name'], $booking_number);
    
    $message = __("有新的包車旅遊預約：\n\n", '9o-booking');
    $message .= sprintf(__("【預約編號】%s\n", '9o-booking'), $booking_number);
    $message .= sprintf(__("【客戶姓名】%s\n", '9o-booking'), $booking_data['customer_name']);
    $message .= sprintf(__("【客戶電話】%s\n", '9o-booking'), $booking_data['customer_phone']);
    
    if (!empty($booking_data['customer_email'])) {
        $message .= sprintf(__("【客戶Email】%s\n", '9o-booking'), $booking_data['customer_email']);
    } else {
        $message .= __("【客戶Email】未提供\n", '9o-booking');
    }
    
    $message .= sprintf(__("【行程天數】%d 天\n", '9o-booking'), $booking_data['trip_days']);
    $message .= sprintf(__("【出發日期】%s\n", '9o-booking'), $booking_data['start_date']);
    $message .= sprintf(__("【乘客人數】%d 人\n", '9o-booking'), $booking_data['passengers']);
    $message .= sprintf(__("【總金額】NT$ %s\n", '9o-booking'), number_format($booking_data['total_price']));
    
    // Mountain areas check
    if (!empty($booking_data['mountain_detection']['needs_manual_check'])) {
        $message .= __("【注意】此行程可能包含山區，需人工確認\n", '9o-booking');
    }
    
    $edit_link = admin_url('post.php?post=' . $booking_id . '&action=edit');
    $message .= "\n" . sprintf(__("【管理連結】%s\n", '9o-booking'), $edit_link);
    
    $headers = [
        'Content-Type: text/plain; charset=UTF-8'
    ];
    
    return wp_mail($admin_email, $subject, $message, $headers);
}

// ====================================
// Legacy Function Support
// ====================================

/**
 * Legacy function for backward compatibility
 */
if (!function_exists('handle_charter_price_calculation')) {
    function handle_charter_price_calculation() {
        nineo_charter_calculate_price();
    }
}

/**
 * Legacy function for backward compatibility
 */
if (!function_exists('handle_charter_booking_submission')) {
    function handle_charter_booking_submission() {
        nineo_charter_submit_booking();
    }
}

/**
 * Legacy function for backward compatibility
 */
if (!function_exists('handle_address_autocomplete')) {
    function handle_address_autocomplete() {
        nineo_charter_autocomplete_address();
    }
}

/**
 * Legacy function for backward compatibility
 */
if (!function_exists('handle_address_validation')) {
    function handle_address_validation() {
        nineo_charter_validate_address();
    }
}

// 除錯日誌
if (defined('NINEO_DEBUG_MODE') && NINEO_DEBUG_MODE) {
    error_log('[9O Booking] Charter Ajax Handlers loaded - Priority: 155');
}
