/**
 * Code Snippet: [053] 9O Booking - Airport Ajax Handlers
 * 
 * Code Snippets 設定:
 * - Title: [053] 9O Booking - Airport Ajax Handlers
 * - Description: 機場接送 AJAX 處理器，包含價格計算、預約提交、地址驗證等功能
 * - Tags: 9o-booking, airport, ajax, api
 * - Priority: 053
 * - Run snippet: Run snippet everywhere
 */

// 確保核心設定已載入
if (!defined('NINEO_BOOKING_VERSION')) {
    error_log('[9O Booking] Airport Ajax Handlers: Core Setup not loaded');
    return;
}

/**
 * Initialize AJAX handlers
 */
add_action('init', 'nineo_airport_ajax_init', 15);
function nineo_airport_ajax_init() {
    // Price calculation
    add_action('wp_ajax_calculate_airport_price', 'nineo_airport_calculate_price');
    add_action('wp_ajax_nopriv_calculate_airport_price', 'nineo_airport_calculate_price');
    
    // Booking submission
    add_action('wp_ajax_submit_airport_booking', 'nineo_airport_submit_booking');
    add_action('wp_ajax_nopriv_submit_airport_booking', 'nineo_airport_submit_booking');
    
    // Address validation
    add_action('wp_ajax_validate_airport_address', 'nineo_airport_validate_address');
    add_action('wp_ajax_nopriv_validate_airport_address', 'nineo_airport_validate_address');
    
    // System testing
    add_action('wp_ajax_test_airport_ajax', 'nineo_airport_test_system');
    add_action('wp_ajax_nopriv_test_airport_ajax', 'nineo_airport_test_system');
    
    // Additional endpoints
    add_action('wp_ajax_check_stopover_distance', 'nineo_airport_check_stopover_distance');
    add_action('wp_ajax_nopriv_check_stopover_distance', 'nineo_airport_check_stopover_distance');
}

/**
 * Calculate airport transfer price
 * Main price calculation endpoint
 */
function nineo_airport_calculate_price() {
    try {
        // Verify nonce (optional for logged-out users)
        if (is_user_logged_in() && isset($_POST['nonce'])) {
            check_ajax_referer('airport_booking_nonce', 'nonce');
        }
        
        // Collect and sanitize parameters
        $params = [
            'airport' => strtoupper(sanitize_text_field($_POST['airport'] ?? 'TPE')),
            'destination' => sanitize_text_field($_POST['destination'] ?? 'taipei-city'),
            'service_type' => sanitize_text_field($_POST['service_type'] ?? 'pickup'),
            'trip_type' => sanitize_text_field($_POST['trip_type'] ?? 'oneway'),
            'date' => sanitize_text_field($_POST['date'] ?? ''),
            'time' => sanitize_text_field($_POST['time'] ?? ''),
            'pickup_address' => sanitize_text_field($_POST['pickup_address'] ?? ''),
            'dropoff_address' => sanitize_text_field($_POST['dropoff_address'] ?? ''),
            'stopovers' => nineo_airport_process_stopovers($_POST['stopovers'] ?? ''),
            'child_seats' => intval($_POST['child_seats'] ?? 0),
            'booster_seats' => intval($_POST['booster_seats'] ?? 0),
            'name_board' => sanitize_text_field($_POST['name_board'] ?? 'no'),
            'passengers' => intval($_POST['passengers'] ?? 1)
        ];
        
        // Handle round-trip parameters
        if ($params['trip_type'] === 'roundtrip') {
            $params['return_date'] = sanitize_text_field($_POST['return_date'] ?? '');
            $params['return_time'] = sanitize_text_field($_POST['return_time'] ?? '');
            $params['return_pickup_address'] = sanitize_text_field($_POST['return_pickup_address'] ?? '');
            $params['return_dropoff_address'] = sanitize_text_field($_POST['return_dropoff_address'] ?? '');
            $params['return_stopovers'] = nineo_airport_process_stopovers($_POST['return_stopovers'] ?? '');
            $params['return_child_seats'] = intval($_POST['return_child_seats'] ?? $params['child_seats']);
            $params['return_booster_seats'] = intval($_POST['return_booster_seats'] ?? $params['booster_seats']);
            $params['return_name_board'] = sanitize_text_field($_POST['return_name_board'] ?? $params['name_board']);
        }
        
        // Log calculation request
        if (defined('NINEO_DEBUG_MODE') && NINEO_DEBUG_MODE) {
            error_log('Airport price calculation request: ' . wp_json_encode($params));
        }
        
        // Use nineo_airport_calculate_total function to calculate price
        if (function_exists('nineo_airport_calculate_total')) {
            $result = nineo_airport_calculate_total($params);
        } else {
            throw new Exception('Airport calculator not loaded');
        }
        
        if ($result['success']) {
            // Add formatted currency for frontend display
            $result['formatted_total'] = 'NT$ ' . number_format($result['total']);
            $result['formatted_subtotal'] = 'NT$ ' . number_format($result['subtotal']);
            
            wp_send_json_success($result);
        } else {
            wp_send_json_error([
                'message' => $result['error'] ?? __('計算錯誤，請稍後再試', '9o-booking')
            ]);
        }
        
    } catch (Exception $e) {
        error_log('Airport Price Calculation Error: ' . $e->getMessage());
        wp_send_json_error([
            'message' => __('計算錯誤：', '9o-booking') . $e->getMessage()
        ]);
    }
    
    wp_die();
}

/**
 * Submit airport transfer booking
 * Complete booking submission with validation and email notifications
 */
function nineo_airport_submit_booking() {
    try {
        // Verify nonce (optional for logged-out users)
        if (is_user_logged_in() && isset($_POST['nonce'])) {
            check_ajax_referer('airport_booking_nonce', 'nonce');
        }
        
        // Collect and sanitize booking data
        $booking_data = [
            'booking_type' => 'airport_transfer',
            'airport' => strtoupper(sanitize_text_field($_POST['airport'] ?? '')),
            'destination' => sanitize_text_field($_POST['destination'] ?? ''),
            'service_type' => sanitize_text_field($_POST['service_type'] ?? ''),
            'trip_type' => sanitize_text_field($_POST['trip_type'] ?? ''),
            'date' => sanitize_text_field($_POST['date'] ?? ''),
            'time' => sanitize_text_field($_POST['time'] ?? ''),
            'flight' => sanitize_text_field($_POST['flight'] ?? ''),
            'passengers' => intval($_POST['passengers'] ?? 1),
            'child_seats' => intval($_POST['child_seats'] ?? 0),
            'booster_seats' => intval($_POST['booster_seats'] ?? 0),
            'name_board' => sanitize_text_field($_POST['name_board'] ?? 'no'),
            'pickup_address' => sanitize_text_field($_POST['pickup_address'] ?? ''),
            'dropoff_address' => sanitize_text_field($_POST['dropoff_address'] ?? ''),
            'stopovers' => nineo_airport_process_stopovers($_POST['stopovers'] ?? ''),
            'customer_name' => sanitize_text_field($_POST['customer_name'] ?? ''),
            'customer_phone' => nineo_airport_sanitize_phone($_POST['customer_phone'] ?? ''),
            'customer_email' => sanitize_email($_POST['customer_email'] ?? ''),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
            'total_price' => floatval($_POST['total_price'] ?? 0),
            'booking_time' => current_time('mysql'),
            'booking_status' => 'pending',
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ];
        
        // Handle round-trip data
        $return_data = null;
        if ($booking_data['trip_type'] === 'roundtrip') {
            $return_data = [
                'date' => sanitize_text_field($_POST['return_date'] ?? ''),
                'time' => sanitize_text_field($_POST['return_time'] ?? ''),
                'flight' => sanitize_text_field($_POST['return_flight'] ?? ''),
                'passengers' => intval($_POST['return_passengers'] ?? $booking_data['passengers']),
                'child_seats' => intval($_POST['return_child_seats'] ?? $booking_data['child_seats']),
                'booster_seats' => intval($_POST['return_booster_seats'] ?? $booking_data['booster_seats']),
                'name_board' => sanitize_text_field($_POST['return_name_board'] ?? $booking_data['name_board']),
                'pickup_address' => sanitize_text_field($_POST['return_pickup_address'] ?? ''),
                'dropoff_address' => sanitize_text_field($_POST['return_dropoff_address'] ?? ''),
                'stopovers' => nineo_airport_process_stopovers($_POST['return_stopovers'] ?? '')
            ];
            $booking_data['return_data'] = $return_data;
        }
        
        // Validate required fields
        $validation_errors = nineo_airport_validate_booking_data($booking_data);
        if (!empty($validation_errors)) {
            wp_send_json_error([
                'message' => implode('、', $validation_errors),
                'errors' => $validation_errors
            ]);
            return;
        }
        
        // Format phone number for storage
        $booking_data['customer_phone'] = nineo_airport_format_phone_number($booking_data['customer_phone']);
        
        // Save booking to database
        $booking_id = nineo_airport_save_booking($booking_data);
        
        if ($booking_id && !is_wp_error($booking_id)) {
            // Generate booking number
            $booking_number = 'APT' . str_pad($booking_id, 6, '0', STR_PAD_LEFT);
            
            // Send confirmation email (if email provided)
            if (!empty($booking_data['customer_email'])) {
                $email_sent = nineo_airport_send_confirmation_email($booking_id, $booking_data);
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
            nineo_airport_send_admin_notification($booking_id, $booking_data);
            
            // Log successful booking
            if (function_exists('nineo_log')) {
                nineo_log("Airport booking created: ID={$booking_id}, Customer={$booking_data['customer_name']}", 'info');
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
        error_log('Airport Booking Submission Error: ' . $e->getMessage());
        wp_send_json_error([
            'message' => __('預約失敗：', '9o-booking') . $e->getMessage()
        ]);
    }
    
    wp_die();
}

/**
 * Validate address against selected city
 */
function nineo_airport_validate_address() {
    try {
        $address = sanitize_text_field($_POST['address'] ?? '');
        $selected_city_key = sanitize_text_field($_POST['city_key'] ?? '');
        
        if (empty($address) || empty($selected_city_key)) {
            wp_send_json_error(['message' => __('地址和縣市參數不能為空', '9o-booking')]);
            return;
        }
        
        $validation_result = nineo_airport_validate_address_city($address, $selected_city_key);
        
        if ($validation_result['valid']) {
            wp_send_json_success([
                'valid' => true,
                'city' => $validation_result['city'],
                'message' => __('地址驗證通過', '9o-booking')
            ]);
        } else {
            wp_send_json_error([
                'valid' => false,
                'message' => $validation_result['message']
            ]);
        }
        
    } catch (Exception $e) {
        error_log('Address Validation Error: ' . $e->getMessage());
        wp_send_json_error(['message' => __('地址驗證失敗', '9o-booking')]);
    }
    
    wp_die();
}

/**
 * Check stopover distance and calculate fees
 */
function nineo_airport_check_stopover_distance() {
    try {
        $origin = sanitize_text_field($_POST['origin'] ?? '');
        $destination = sanitize_text_field($_POST['destination'] ?? '');
        
        if (empty($origin) || empty($destination)) {
            wp_send_json_error(['message' => __('起點和終點不能為空', '9o-booking')]);
            return;
        }
        
        // Calculate distance
        if (function_exists('nineo_calculate_distance')) {
            $distance = nineo_calculate_distance($origin, $destination);
        } else {
            throw new Exception('Distance calculation function not available');
        }
        
        // Calculate fee
        if (function_exists('nineo_get_airport_stopover_distance_fee')) {
            $fee = nineo_get_airport_stopover_distance_fee($distance);
        } else {
            // Fallback calculation
            if ($distance > 30) {
                $fee = 1500;
            } elseif ($distance > 20) {
                $fee = 1000;
            } elseif ($distance > 10) {
                $fee = 500;
            } elseif ($distance > 5) {
                $fee = 300;
            } else {
                $fee = 0;
            }
        }
        
        wp_send_json_success([
            'distance' => round($distance, 1),
            'fee' => $fee,
            'formatted_fee' => 'NT$ ' . number_format($fee)
        ]);
        
    } catch (Exception $e) {
        error_log('Stopover Distance Check Error: ' . $e->getMessage());
        wp_send_json_error(['message' => __('距離計算失敗', '9o-booking')]);
    }
    
    wp_die();
}

/**
 * Test system functionality
 */
function nineo_airport_test_system() {
    wp_send_json_success([
        'message' => __('AJAX 系統正常運作', '9o-booking'),
        'timestamp' => current_time('mysql'),
        'api_key_set' => defined('GOOGLE_MAPS_API_KEY') && !empty(GOOGLE_MAPS_API_KEY),
        'php_version' => phpversion(),
        'wp_version' => get_bloginfo('version'),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'calculator_loaded' => function_exists('nineo_airport_calculate_total'),
        'utilities_loaded' => function_exists('nineo_calculate_distance'),
        'constants_loaded' => defined('AIRPORT_BASE_PRICE_TPE_TAIPEI')
    ]);
    
    wp_die();
}

// ====================================
// Helper Functions
// ====================================

/**
 * Process and sanitize stopovers data
 * 
 * @param string|array $stopovers
 * @return array
 */
function nineo_airport_process_stopovers($stopovers) {
    if (empty($stopovers)) {
        return [];
    }
    
    // Handle JSON string input
    if (is_string($stopovers)) {
        $decoded = json_decode(stripslashes($stopovers), true);
        if (is_array($decoded)) {
            $stopovers = $decoded;
        } else {
            return [];
        }
    }
    
    if (!is_array($stopovers)) {
        return [];
    }
    
    $processed = [];
    foreach ($stopovers as $stop) {
        if (!empty($stop['address'])) {
            $processed[] = [
                'address' => sanitize_text_field($stop['address']),
                'lat' => floatval($stop['lat'] ?? 0),
                'lng' => floatval($stop['lng'] ?? 0)
            ];
        }
    }
    
    return $processed;
}

/**
 * Sanitize phone number
 * 
 * @param string $phone
 * @return string
 */
function nineo_airport_sanitize_phone($phone) {
    return preg_replace('/[^0-9+]/', '', $phone);
}

/**
 * Format phone number for storage
 * 
 * @param string $phone
 * @return string
 */
function nineo_airport_format_phone_number($phone) {
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
function nineo_airport_validate_booking_data($booking_data) {
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
    
    if (empty($booking_data['date'])) {
        $errors[] = __('請選擇日期', '9o-booking');
    }
    
    if (empty($booking_data['time'])) {
        $errors[] = __('請選擇時間', '9o-booking');
    }
    
    // Address validation based on service type
    $destination = $booking_data['destination'];
    
    if ($booking_data['service_type'] === 'pickup' && !empty($booking_data['dropoff_address'])) {
        $validation_result = nineo_airport_validate_address_city($booking_data['dropoff_address'], $destination);
        if (!$validation_result['valid']) {
            $errors[] = __('下車地址錯誤：', '9o-booking') . $validation_result['message'];
        }
    } elseif ($booking_data['service_type'] === 'dropoff' && !empty($booking_data['pickup_address'])) {
        $validation_result = nineo_airport_validate_address_city($booking_data['pickup_address'], $destination);
        if (!$validation_result['valid']) {
            $errors[] = __('上車地址錯誤：', '9o-booking') . $validation_result['message'];
        }
    }
    
    // Round-trip validation
    if ($booking_data['trip_type'] === 'roundtrip' && !empty($booking_data['return_data'])) {
        $return_data = $booking_data['return_data'];
        
        if (empty($return_data['date'])) {
            $errors[] = __('請選擇回程日期', '9o-booking');
        }
        if (empty($return_data['time'])) {
            $errors[] = __('請選擇回程時間', '9o-booking');
        }
        
        // Return address validation (service type is reversed)
        if ($booking_data['service_type'] === 'pickup') {
            if (empty($return_data['pickup_address'])) {
                $errors[] = __('請填寫回程上車地址', '9o-booking');
            } else {
                $validation_result = nineo_airport_validate_address_city($return_data['pickup_address'], $destination);
                if (!$validation_result['valid']) {
                    $errors[] = __('回程上車地址錯誤：', '9o-booking') . $validation_result['message'];
                }
            }
        } elseif ($booking_data['service_type'] === 'dropoff') {
            if (empty($return_data['dropoff_address'])) {
                $errors[] = __('請填寫回程下車地址', '9o-booking');
            } else {
                $validation_result = nineo_airport_validate_address_city($return_data['dropoff_address'], $destination);
                if (!$validation_result['valid']) {
                    $errors[] = __('回程下車地址錯誤：', '9o-booking') . $validation_result['message'];
                }
            }
        }
    }
    
    return $errors;
}

/**
 * Validate address against selected city
 * 
 * @param string $address
 * @param string $selected_city_key
 * @return array
 */
function nineo_airport_validate_address_city($address, $selected_city_key) {
    if (function_exists('nineo_get_city_mapping')) {
        $city_mapping = nineo_get_city_mapping();
    } else {
        // Fallback city mapping
        $city_mapping = [
            'taipei-city' => '台北市',
            'new-taipei-city' => '新北市',
            'keelung-city' => '基隆市',
            'taoyuan-city' => '桃園市',
            'hsinchu-city' => '新竹市',
            'hsinchu-county' => '新竹縣',
            'miaoli-county' => '苗栗縣'
        ];
    }
    
    $expected_city = isset($city_mapping[$selected_city_key]) ? $city_mapping[$selected_city_key] : '';
    
    if (empty($expected_city)) {
        return ['valid' => false, 'message' => __('無效的縣市選擇', '9o-booking')];
    }
    
    if (strpos($address, $expected_city) !== false) {
        return ['valid' => true, 'city' => $expected_city];
    }
    
    // Special handling: Hsinchu City/County
    if ($expected_city === '新竹市' && strpos($address, '新竹縣') !== false) {
        return ['valid' => false, 'message' => __('您選擇的是新竹市，但地址為新竹縣', '9o-booking')];
    }
    if ($expected_city === '新竹縣' && strpos($address, '新竹市') !== false) {
        return ['valid' => false, 'message' => __('您選擇的是新竹縣，但地址為新竹市', '9o-booking')];
    }
    
    // Special handling: Chiayi City/County
    if ($expected_city === '嘉義市' && strpos($address, '嘉義縣') !== false) {
        return ['valid' => false, 'message' => __('您選擇的是嘉義市，但地址為嘉義縣', '9o-booking')];
    }
    if ($expected_city === '嘉義縣' && strpos($address, '嘉義市') !== false) {
        return ['valid' => false, 'message' => __('您選擇的是嘉義縣，但地址為嘉義市', '9o-booking')];
    }
    
    // Check if address contains other cities
    $all_cities = array_values($city_mapping);
    foreach ($all_cities as $city) {
        if ($city !== $expected_city && strpos($address, $city) !== false) {
            return ['valid' => false, 'message' => sprintf(__('地址中的%s與您選擇的%s不符', '9o-booking'), $city, $expected_city)];
        }
    }
    
    // Check if address contains any city
    $has_any_city = false;
    foreach ($all_cities as $city) {
        if (strpos($address, $city) !== false) {
            $has_any_city = true;
            break;
        }
    }
    
    if (!$has_any_city) {
        return ['valid' => false, 'message' => sprintf(__('請在地址中包含縣市名稱（%s）', '9o-booking'), $expected_city)];
    }
    
    return ['valid' => false, 'message' => sprintf(__('地址與選擇的%s不符', '9o-booking'), $expected_city)];
}

/**
 * Save booking to database
 * 
 * @param array $booking_data
 * @return int|WP_Error Post ID or error
 */
function nineo_airport_save_booking($booking_data) {
    $service_label = $booking_data['service_type'] === 'pickup' ? __('接機', '9o-booking') : __('送機', '9o-booking');
    
    $post_data = [
        'post_title' => sprintf('[%s] %s - %s %s', 
            $booking_data['airport'], 
            $booking_data['customer_name'], 
            $booking_data['date'],
            $service_label
        ),
        'post_content' => wp_json_encode($booking_data, JSON_UNESCAPED_UNICODE),
        'post_status' => 'private',
        'post_type' => 'airport_booking',
        'meta_input' => [
            '_booking_data' => $booking_data,
            '_booking_number' => '', // Will be updated after insert
            '_customer_data' => [
                'name' => $booking_data['customer_name'],
                'phone' => $booking_data['customer_phone'],
                'email' => $booking_data['customer_email']
            ],
            '_booking_date' => $booking_data['date'],
            '_booking_time' => $booking_data['time'],
            '_pricing_data' => [
                'total_price' => $booking_data['total_price']
            ],
            '_booking_status' => 'pending'
        ]
    ];
    
    $post_id = wp_insert_post($post_data);
    
    if ($post_id && !is_wp_error($post_id)) {
        // Update booking number
        $booking_number = 'APT' . str_pad($post_id, 6, '0', STR_PAD_LEFT);
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
function nineo_airport_send_confirmation_email($booking_id, $booking_data) {
    if (empty($booking_data['customer_email'])) {
        return false;
    }
    
    $to = $booking_data['customer_email'];
    $booking_number = 'APT' . str_pad($booking_id, 6, '0', STR_PAD_LEFT);
    $subject = sprintf(__('機場接送預約確認 - 訂單編號 %s', '9o-booking'), $booking_number);
    
    // Build email content
    $message = sprintf(__("親愛的 %s 您好，\n\n", '9o-booking'), $booking_data['customer_name']);
    $message .= __("您的機場接送預約已成功提交，以下是您的預約詳情：\n\n", '9o-booking');
    $message .= sprintf(__("【預約編號】%s\n", '9o-booking'), $booking_number);
    $message .= sprintf(__("【服務類型】%s\n", '9o-booking'), ($booking_data['service_type'] === 'pickup' ? __('接機', '9o-booking') : __('送機', '9o-booking')));
    $message .= sprintf(__("【機場】%s\n", '9o-booking'), ($booking_data['airport'] === 'TPE' ? __('桃園國際機場', '9o-booking') : __('台北松山機場', '9o-booking')));
    $message .= sprintf(__("【日期時間】%s %s\n", '9o-booking'), $booking_data['date'], $booking_data['time']);
    
    if (!empty($booking_data['flight'])) {
        $message .= sprintf(__("【航班號碼】%s\n", '9o-booking'), $booking_data['flight']);
    }
    
    // Address information
    if ($booking_data['service_type'] === 'pickup') {
        $message .= sprintf(__("【下車地址】%s\n", '9o-booking'), $booking_data['dropoff_address']);
    } else {
        $message .= sprintf(__("【上車地址】%s\n", '9o-booking'), $booking_data['pickup_address']);
    }
    
    // Stopovers
    if (!empty($booking_data['stopovers'])) {
        $message .= __("【停靠點】\n", '9o-booking');
        foreach ($booking_data['stopovers'] as $i => $stop) {
            $message .= "  " . ($i + 1) . ". {$stop['address']}\n";
        }
    }
    
    $message .= sprintf(__("【乘客人數】%d 人\n", '9o-booking'), $booking_data['passengers']);
    
    // Add-ons
    if ($booking_data['child_seats'] > 0) {
        $message .= sprintf(__("【嬰兒座椅】%d 張\n", '9o-booking'), $booking_data['child_seats']);
    }
    if ($booking_data['booster_seats'] > 0) {
        $message .= sprintf(__("【兒童增高墊】%d 張\n", '9o-booking'), $booking_data['booster_seats']);
    }
    if ($booking_data['name_board'] === 'yes') {
        $message .= __("【舉牌服務】是\n", '9o-booking');
    }
    
    // Round-trip information
    if ($booking_data['trip_type'] === 'roundtrip' && !empty($booking_data['return_data'])) {
        $return = $booking_data['return_data'];
        $message .= "\n" . __("【回程資訊】\n", '9o-booking');
        $message .= sprintf(__("日期時間：%s %s\n", '9o-booking'), $return['date'], $return['time']);
        if (!empty($return['flight'])) {
            $message .= sprintf(__("航班號碼：%s\n", '9o-booking'), $return['flight']);
        }
        if ($booking_data['service_type'] === 'pickup') {
            $message .= sprintf(__("上車地址：%s\n", '9o-booking'), $return['pickup_address']);
        } else {
            $message .= sprintf(__("下車地址：%s\n", '9o-booking'), $return['dropoff_address']);
        }
        if ($return['name_board'] === 'yes') {
            $message .= __("回程舉牌服務：是\n", '9o-booking');
        }
    }
    
    $message .= "\n" . sprintf(__("【總金額】NT$ %s\n", '9o-booking'), number_format($booking_data['total_price']));
    
    if (!empty($booking_data['notes'])) {
        $message .= "\n" . sprintf(__("【備註】%s\n", '9o-booking'), $booking_data['notes']);
    }
    
    $message .= "\n" . __("我們將在24小時內與您電話確認預約詳情。\n", '9o-booking');
    $message .= __("如有任何問題，請聯繫我們的客服專線。\n\n", '9o-booking');
    $message .= __("感謝您的預約！\n", '9o-booking');
    $message .= __("9o Van Strip 機場接送服務", '9o-booking');
    
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
function nineo_airport_send_admin_notification($booking_id, $booking_data) {
    $admin_email = get_option('admin_email');
    $booking_number = 'APT' . str_pad($booking_id, 6, '0', STR_PAD_LEFT);
    
    $subject = sprintf(__('新預約通知 - %s - %s', '9o-booking'), $booking_data['customer_name'], $booking_number);
    
    $message = __("有新的機場接送預約：\n\n", '9o-booking');
    $message .= sprintf(__("【預約編號】%s\n", '9o-booking'), $booking_number);
    $message .= sprintf(__("【客戶姓名】%s\n", '9o-booking'), $booking_data['customer_name']);
    $message .= sprintf(__("【客戶電話】%s\n", '9o-booking'), $booking_data['customer_phone']);
    
    if (!empty($booking_data['customer_email'])) {
        $message .= sprintf(__("【客戶Email】%s\n", '9o-booking'), $booking_data['customer_email']);
    } else {
        $message .= __("【客戶Email】未提供\n", '9o-booking');
    }
    
    $message .= sprintf(__("【服務類型】%s", '9o-booking'), ($booking_data['service_type'] === 'pickup' ? __('接機', '9o-booking') : __('送機', '9o-booking')));
    $message .= " / " . ($booking_data['trip_type'] === 'roundtrip' ? __('來回', '9o-booking') : __('單程', '9o-booking')) . "\n";
    $message .= sprintf(__("【日期時間】%s %s\n", '9o-booking'), $booking_data['date'], $booking_data['time']);
    
    if ($booking_data['name_board'] === 'yes') {
        $message .= __("【舉牌服務】需要舉牌\n", '9o-booking');
    }
    
    $message .= "\n" . sprintf(__("【總金額】NT$ %s\n", '9o-booking'), number_format($booking_data['total_price']));
    
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
if (!function_exists('handle_airport_price_calc')) {
    function handle_airport_price_calc() {
        nineo_airport_calculate_price();
    }
}

/**
 * Legacy function for backward compatibility
 */
if (!function_exists('handle_airport_booking_submit')) {
    function handle_airport_booking_submit() {
        nineo_airport_submit_booking();
    }
}

/**
 * Legacy function for backward compatibility
 */
if (!function_exists('validate_address_city')) {
    function validate_address_city($address, $selected_city_key) {
        return nineo_airport_validate_address_city($address, $selected_city_key);
    }
}

/**
 * Legacy function for backward compatibility
 */
if (!function_exists('test_airport_ajax')) {
    function test_airport_ajax() {
        nineo_airport_test_system();
    }
}

// 除錯日誌
if (defined('NINEO_DEBUG_MODE') && NINEO_DEBUG_MODE) {
    error_log('[9O Booking] Airport Ajax Handlers loaded - Priority: 150');
}
