/**
 * Code Snippet: [051] 9O Booking - Airport Post Type
 * 
 * Code Snippets 設定:
 * - Title: [051] 9O Booking - Airport Post Type
 * - Description: 機場接送預約的自訂文章類型管理
 * - Tags: 9o-booking, airport, post-type, admin
 * - Priority: 51
 * - Run snippet: Only run in administration area
 */

// 確保核心設定已載入
if (!defined('NINEO_BOOKING_VERSION')) {
    error_log('[9O Booking] Airport Post Type: Core Setup not loaded');
    return;
}

/**
 * 初始化機場接送文章類型功能
 */
add_action('init', 'nineo_airport_post_type_init', 20);
function nineo_airport_post_type_init() {
    // Post Type 已在 System Integration 中註冊
    // 這裡只處理額外功能
    
    // 註冊 Meta Boxes
    add_action('add_meta_boxes', 'nineo_airport_add_meta_boxes');
    add_action('save_post', 'nineo_airport_save_post');
    
    // 自訂欄位
    add_filter('manage_airport_booking_posts_columns', 'nineo_airport_set_custom_columns');
    add_action('manage_airport_booking_posts_custom_column', 'nineo_airport_custom_column', 10, 2);
    
    // 排序功能
    add_filter('manage_edit-airport_booking_sortable_columns', 'nineo_airport_sortable_columns');
    add_action('pre_get_posts', 'nineo_airport_posts_orderby');
}

/**
 * 加入 Meta Boxes
 */
function nineo_airport_add_meta_boxes() {
    add_meta_box(
        'airport_booking_details',
        __('預訂詳情', '9o-booking'),
        'nineo_airport_render_meta_box',
        'airport_booking',
        'normal',
        'high'
    );
    
    // 額外的價格明細 Meta Box
    add_meta_box(
        'airport_booking_pricing',
        __('價格明細', '9o-booking'),
        'nineo_airport_render_pricing_meta_box',
        'airport_booking',
        'side',
        'default'
    );
}

/**
 * 顯示主要 Meta Box 內容
 */
function nineo_airport_render_meta_box($post) {
    // 安全性檢查
    wp_nonce_field('airport_booking_save', 'airport_booking_nonce');
    
    // 取得所有 meta data
    $booking_data = get_post_meta($post->ID, '_booking_data', true);
    if (!is_array($booking_data)) {
        $booking_data = [];
    }
    
    // 預設值
    $defaults = [
        'booking_number' => '',
        'customer_name' => '',
        'customer_phone' => '',
        'customer_email' => '',
        'airport' => '',
        'service_type' => 'pickup',
        'service_date' => '',
        'service_time' => '',
        'flight_number' => '',
        'main_address' => '',
        'stopovers' => [],
        'passengers' => 1,
        'luggage' => 1,
        'child_seats' => 0,
        'booster_seats' => 0,
        'total_price' => 0,
        'status' => 'pending',
        'notes' => ''
    ];
    
    $booking_data = array_merge($defaults, $booking_data);
    ?>
    <table class="form-table">
        <tr>
            <th><label><?php _e('預訂編號', '9o-booking'); ?></label></th>
            <td>
                <input type="text" name="booking[booking_number]" 
                       value="<?php echo esc_attr($booking_data['booking_number']); ?>" 
                       readonly style="background:#f9f9f9;" />
            </td>
        </tr>
        
        <!-- 客戶資訊 -->
        <tr>
            <th colspan="2"><h3><?php _e('客戶資訊', '9o-booking'); ?></h3></th>
        </tr>
        <tr>
            <th><label><?php _e('姓名', '9o-booking'); ?></label></th>
            <td>
                <input type="text" name="booking[customer_name]" 
                       value="<?php echo esc_attr($booking_data['customer_name']); ?>" 
                       class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label><?php _e('電話', '9o-booking'); ?></label></th>
            <td>
                <input type="text" name="booking[customer_phone]" 
                       value="<?php echo esc_attr($booking_data['customer_phone']); ?>" 
                       class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label><?php _e('Email', '9o-booking'); ?></label></th>
            <td>
                <input type="email" name="booking[customer_email]" 
                       value="<?php echo esc_attr($booking_data['customer_email']); ?>" 
                       class="regular-text" />
            </td>
        </tr>
        
        <!-- 服務資訊 -->
        <tr>
            <th colspan="2"><h3><?php _e('服務資訊', '9o-booking'); ?></h3></th>
        </tr>
        <tr>
            <th><label><?php _e('機場', '9o-booking'); ?></label></th>
            <td>
                <select name="booking[airport]">
                    <option value="TPE" <?php selected($booking_data['airport'], 'TPE'); ?>>
                        <?php _e('桃園國際機場', '9o-booking'); ?>
                    </option>
                    <option value="TSA" <?php selected($booking_data['airport'], 'TSA'); ?>>
                        <?php _e('台北松山機場', '9o-booking'); ?>
                    </option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label><?php _e('服務類型', '9o-booking'); ?></label></th>
            <td>
                <select name="booking[service_type]">
                    <option value="pickup" <?php selected($booking_data['service_type'], 'pickup'); ?>>
                        <?php _e('接機', '9o-booking'); ?>
                    </option>
                    <option value="dropoff" <?php selected($booking_data['service_type'], 'dropoff'); ?>>
                        <?php _e('送機', '9o-booking'); ?>
                    </option>
                    <option value="roundtrip" <?php selected($booking_data['service_type'], 'roundtrip'); ?>>
                        <?php _e('來回接送', '9o-booking'); ?>
                    </option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label><?php _e('服務日期', '9o-booking'); ?></label></th>
            <td>
                <input type="date" name="booking[service_date]" 
                       value="<?php echo esc_attr($booking_data['service_date']); ?>" />
            </td>
        </tr>
        <tr>
            <th><label><?php _e('服務時間', '9o-booking'); ?></label></th>
            <td>
                <input type="time" name="booking[service_time]" 
                       value="<?php echo esc_attr($booking_data['service_time']); ?>" />
            </td>
        </tr>
        <tr>
            <th><label><?php _e('航班編號', '9o-booking'); ?></label></th>
            <td>
                <input type="text" name="booking[flight_number]" 
                       value="<?php echo esc_attr($booking_data['flight_number']); ?>" 
                       class="regular-text" />
            </td>
        </tr>
        
        <!-- 地址資訊 -->
        <tr>
            <th colspan="2"><h3><?php _e('地址資訊', '9o-booking'); ?></h3></th>
        </tr>
        <tr>
            <th><label><?php _e('主要地址', '9o-booking'); ?></label></th>
            <td>
                <input type="text" name="booking[main_address]" 
                       value="<?php echo esc_attr($booking_data['main_address']); ?>" 
                       class="large-text" />
            </td>
        </tr>
        <tr>
            <th><label><?php _e('停靠點', '9o-booking'); ?></label></th>
            <td>
                <div id="stopovers-container">
                    <?php
                    $stopovers = is_array($booking_data['stopovers']) ? $booking_data['stopovers'] : [];
                    foreach ($stopovers as $index => $stopover) {
                        echo '<input type="text" name="booking[stopovers][]" value="' . esc_attr($stopover) . '" class="large-text" style="margin-bottom: 5px;" /><br>';
                    }
                    ?>
                </div>
                <button type="button" class="button" onclick="addStopover()">
                    <?php _e('新增停靠點', '9o-booking'); ?>
                </button>
            </td>
        </tr>
        
        <!-- 其他資訊 -->
        <tr>
            <th><label><?php _e('狀態', '9o-booking'); ?></label></th>
            <td>
                <select name="booking[status]">
                    <?php
                    $statuses = [
                        'pending'   => __('待處理', '9o-booking'),
                        'confirmed' => __('已確認', '9o-booking'),
                        'completed' => __('已完成', '9o-booking'),
                        'cancelled' => __('已取消', '9o-booking')
                    ];
                    foreach ($statuses as $value => $label) {
                        echo '<option value="' . $value . '" ' . selected($booking_data['status'], $value, false) . '>' . $label . '</option>';
                    }
                    ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label><?php _e('備註', '9o-booking'); ?></label></th>
            <td>
                <textarea name="booking[notes]" rows="3" class="large-text"><?php echo esc_textarea($booking_data['notes']); ?></textarea>
            </td>
        </tr>
    </table>
    
    <script>
    function addStopover() {
        var container = document.getElementById('stopovers-container');
        var input = document.createElement('input');
        input.type = 'text';
        input.name = 'booking[stopovers][]';
        input.className = 'large-text';
        input.style.marginBottom = '5px';
        container.appendChild(input);
        container.appendChild(document.createElement('br'));
    }
    </script>
    <?php
}

/**
 * 顯示價格明細 Meta Box
 */
function nineo_airport_render_pricing_meta_box($post) {
    $booking_data = get_post_meta($post->ID, '_booking_data', true);
    $pricing = get_post_meta($post->ID, '_booking_pricing', true);
    
    if (!is_array($pricing)) {
        $pricing = [
            'base_price' => 0,
            'night_surcharge' => 0,
            'remote_surcharge' => 0,
            'stopover_charge' => 0,
            'addon_charge' => 0,
            'total_price' => 0
        ];
    }
    ?>
    <p>
        <label><?php _e('基本價格', '9o-booking'); ?>:</label><br>
        NT$ <input type="number" name="pricing[base_price]" 
                   value="<?php echo esc_attr($pricing['base_price']); ?>" 
                   min="0" style="width: 100px;" />
    </p>
    <p>
        <label><?php _e('夜間加價', '9o-booking'); ?>:</label><br>
        NT$ <input type="number" name="pricing[night_surcharge]" 
                   value="<?php echo esc_attr($pricing['night_surcharge']); ?>" 
                   min="0" style="width: 100px;" />
    </p>
    <p>
        <label><?php _e('偏遠地區加價', '9o-booking'); ?>:</label><br>
        NT$ <input type="number" name="pricing[remote_surcharge]" 
                   value="<?php echo esc_attr($pricing['remote_surcharge']); ?>" 
                   min="0" style="width: 100px;" />
    </p>
    <p>
        <label><?php _e('停靠點費用', '9o-booking'); ?>:</label><br>
        NT$ <input type="number" name="pricing[stopover_charge]" 
                   value="<?php echo esc_attr($pricing['stopover_charge']); ?>" 
                   min="0" style="width: 100px;" />
    </p>
    <p>
        <label><?php _e('加值服務', '9o-booking'); ?>:</label><br>
        NT$ <input type="number" name="pricing[addon_charge]" 
                   value="<?php echo esc_attr($pricing['addon_charge']); ?>" 
                   min="0" style="width: 100px;" />
    </p>
    <hr>
    <p>
        <strong><?php _e('總計', '9o-booking'); ?>:</strong><br>
        NT$ <input type="number" name="pricing[total_price]" 
                   value="<?php echo esc_attr($pricing['total_price']); ?>" 
                   min="0" style="width: 100px; font-weight: bold;" />
    </p>
    <?php
}

/**
 * 儲存 Post
 */
function nineo_airport_save_post($post_id) {
    // 安全性檢查
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['airport_booking_nonce']) || !wp_verify_nonce($_POST['airport_booking_nonce'], 'airport_booking_save')) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (get_post_type($post_id) !== 'airport_booking') return;
    
    // 儲存預約資料
    if (isset($_POST['booking'])) {
        $booking_data = $_POST['booking'];
        
        // 處理停靠點陣列
        if (isset($booking_data['stopovers'])) {
            $booking_data['stopovers'] = array_filter(array_map('sanitize_text_field', $booking_data['stopovers']));
        }
        
        // 清理其他資料
        foreach ($booking_data as $key => $value) {
            if (!is_array($value)) {
                $booking_data[$key] = sanitize_text_field($value);
            }
        }
        
        update_post_meta($post_id, '_booking_data', $booking_data);
        
        // 更新標題
        if (!empty($booking_data['booking_number'])) {
            remove_action('save_post', 'nineo_airport_save_post');
            wp_update_post([
                'ID' => $post_id,
                'post_title' => $booking_data['booking_number'] . ' - ' . $booking_data['customer_name']
            ]);
            add_action('save_post', 'nineo_airport_save_post');
        }
    }
    
    // 儲存價格資料
    if (isset($_POST['pricing'])) {
        $pricing_data = array_map('intval', $_POST['pricing']);
        update_post_meta($post_id, '_booking_pricing', $pricing_data);
    }
}

/**
 * 設定自訂欄位
 */
function nineo_airport_set_custom_columns($columns) {
    $new_columns = [
        'cb' => $columns['cb'],
        'title' => __('預訂', '9o-booking'),
        'booking_number' => __('預訂編號', '9o-booking'),
        'customer' => __('客戶', '9o-booking'),
        'service_date' => __('服務日期', '9o-booking'),
        'route' => __('路線', '9o-booking'),
        'price' => __('金額', '9o-booking'),
        'status' => __('狀態', '9o-booking'),
        'date' => __('建立日期', '9o-booking')
    ];
    return $new_columns;
}

/**
 * 顯示自訂欄位內容
 */
function nineo_airport_custom_column($column, $post_id) {
    $booking_data = get_post_meta($post_id, '_booking_data', true);
    $pricing_data = get_post_meta($post_id, '_booking_pricing', true);
    
    if (!is_array($booking_data)) $booking_data = [];
    if (!is_array($pricing_data)) $pricing_data = [];
    
    switch ($column) {
        case 'booking_number':
            echo esc_html($booking_data['booking_number'] ?? '');
            break;
            
        case 'customer':
            echo '<strong>' . esc_html($booking_data['customer_name'] ?? '') . '</strong>';
            if (!empty($booking_data['customer_phone'])) {
                echo '<br>' . esc_html($booking_data['customer_phone']);
            }
            break;
            
        case 'service_date':
            if (!empty($booking_data['service_date'])) {
                echo esc_html($booking_data['service_date']);
                if (!empty($booking_data['service_time'])) {
                    echo ' ' . esc_html($booking_data['service_time']);
                }
            }
            break;
            
        case 'route':
            $airport = $booking_data['airport'] ?? '';
            $address = $booking_data['main_address'] ?? '';
            $service_type = $booking_data['service_type'] ?? 'pickup';
            
            if ($service_type === 'pickup') {
                echo esc_html($airport . ' → ' . $address);
            } elseif ($service_type === 'dropoff') {
                echo esc_html($address . ' → ' . $airport);
            } else {
                echo esc_html($airport . ' ⇄ ' . $address);
            }
            break;
            
        case 'price':
            $price = $pricing_data['total_price'] ?? 0;
            echo $price ? 'NT$ ' . number_format($price) : '-';
            break;
            
        case 'status':
            $status = $booking_data['status'] ?? 'pending';
            $status_labels = [
                'pending'   => __('待處理', '9o-booking'),
                'confirmed' => __('已確認', '9o-booking'),
                'completed' => __('已完成', '9o-booking'),
                'cancelled' => __('已取消', '9o-booking')
            ];
            $label = $status_labels[$status] ?? $status;
            $color = [
                'pending' => '#f0ad4e',
                'confirmed' => '#5cb85c',
                'completed' => '#337ab7',
                'cancelled' => '#d9534f'
            ][$status] ?? '#999';
            
            echo '<span style="color: ' . $color . ';">● ' . esc_html($label) . '</span>';
            break;
    }
}

/**
 * 設定可排序欄位
 */
function nineo_airport_sortable_columns($columns) {
    $columns['booking_number'] = 'booking_number';
    $columns['service_date'] = 'service_date';
    $columns['price'] = 'price';
    $columns['status'] = 'status';
    return $columns;
}

/**
 * 處理排序查詢
 */
function nineo_airport_posts_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    if ($query->get('post_type') !== 'airport_booking') {
        return;
    }
    
    $orderby = $query->get('orderby');
    
    switch ($orderby) {
        case 'booking_number':
        case 'service_date':
        case 'status':
            $query->set('meta_key', '_booking_data');
            $query->set('orderby', 'meta_value');
            break;
            
        case 'price':
            $query->set('meta_key', '_booking_pricing');
            $query->set('orderby', 'meta_value_num');
            break;
    }
}

// 除錯日誌
if (defined('NINEO_DEBUG_MODE') && NINEO_DEBUG_MODE) {
    error_log('[9O Booking] Airport Post Type loaded - Priority: 100');
}
