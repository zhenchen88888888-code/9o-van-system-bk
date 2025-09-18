/**
 * Code Snippet: [081] 9O Booking - Charter Post Type
 * 
 * Code Snippets 設定:
 * - Title: [081] 9O Booking - Charter Post Type
 * - Description: 包車旅遊預約的自訂文章類型管理
 * - Tags: 9o-booking, charter, post-type, admin
 * - Priority: 81
 * - Run snippet: Only run in administration area
 */

// 確保核心設定已載入
if (!defined('NINEO_BOOKING_VERSION')) {
    error_log('[9O Booking] Charter Post Type: Core Setup not loaded');
    return;
}

/**
 * 初始化包車旅遊文章類型功能
 */
add_action('init', 'nineo_charter_post_type_init', 20);
function nineo_charter_post_type_init() {
    // Post Type 已在 System Integration 中註冊
    // 這裡只處理額外功能
    
    // 註冊 Meta Boxes
    add_action('add_meta_boxes', 'nineo_charter_add_meta_boxes');
    add_action('save_post', 'nineo_charter_save_post');
    
    // 自訂欄位
    add_filter('manage_charter_booking_posts_columns', 'nineo_charter_set_custom_columns');
    add_action('manage_charter_booking_posts_custom_column', 'nineo_charter_custom_column', 10, 2);
    
    // 排序功能
    add_filter('manage_edit-charter_booking_sortable_columns', 'nineo_charter_sortable_columns');
    add_action('pre_get_posts', 'nineo_charter_posts_orderby');
}

/**
 * 加入 Meta Boxes
 */
function nineo_charter_add_meta_boxes() {
    add_meta_box(
        'charter_booking_details',
        __('包車旅遊詳細資料', '9o-booking'),
        'nineo_charter_render_meta_box',
        'charter_booking',
        'normal',
        'high'
    );
    
    // 價格計算 Meta Box
    add_meta_box(
        'charter_booking_pricing',
        __('價格計算', '9o-booking'),
        'nineo_charter_render_pricing_meta_box',
        'charter_booking',
        'side',
        'default'
    );
    
    // 客戶資訊 Meta Box
    add_meta_box(
        'charter_booking_customer',
        __('客戶資訊', '9o-booking'),
        'nineo_charter_render_customer_meta_box',
        'charter_booking',
        'normal',
        'default'
    );
}

/**
 * 顯示主要 Meta Box 內容
 */
function nineo_charter_render_meta_box($post) {
    // 安全性檢查
    wp_nonce_field('charter_booking_save', 'charter_booking_nonce');
    
    // 取得儲存的資料
    $booking_data = get_post_meta($post->ID, '_booking_data', true);
    if (!is_array($booking_data)) {
        $booking_data = [];
    }
    
    // 預設值
    $defaults = [
        'booking_number' => '',
        'trip_days' => 1,
        'start_date' => '',
        'routes' => [],
        'has_mountain' => false,
        'has_driver_accommodation' => false,
        'addon_services' => [],
        'special_requests' => '',
        'status' => 'pending'
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
        
        <tr>
            <th><label><?php _e('行程天數', '9o-booking'); ?></label></th>
            <td>
                <input type="number" id="trip_days" name="booking[trip_days]" 
                       value="<?php echo esc_attr($booking_data['trip_days']); ?>" 
                       min="1" max="7" style="width: 60px;" />
                <span class="description"><?php _e('最多7天', '9o-booking'); ?></span>
            </td>
        </tr>
        
        <tr>
            <th><label><?php _e('出發日期', '9o-booking'); ?></label></th>
            <td>
                <input type="date" name="booking[start_date]" 
                       value="<?php echo esc_attr($booking_data['start_date']); ?>" />
            </td>
        </tr>
    </table>
    
    <h3><?php _e('每日行程', '9o-booking'); ?></h3>
    <div id="routes-container">
        <?php
        $trip_days = intval($booking_data['trip_days']);
        $routes = is_array($booking_data['routes']) ? $booking_data['routes'] : [];
        
        for ($i = 0; $i < $trip_days; $i++) {
            $day = $i + 1;
            $route = isset($routes[$i]) ? $routes[$i] : ['origin' => '', 'destination' => '', 'stops' => []];
            ?>
            <div class="route-day" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; background: #f9f9f9;">
                <h4><?php printf(__('第 %d 天', '9o-booking'), $day); ?></h4>
                
                <table class="form-table">
                    <tr>
                        <th><label><?php _e('出發地', '9o-booking'); ?></label></th>
                        <td>
                            <input type="text" name="booking[routes][<?php echo $i; ?>][origin]" 
                                   value="<?php echo esc_attr($route['origin']); ?>" 
                                   class="large-text" />
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('目的地', '9o-booking'); ?></label></th>
                        <td>
                            <input type="text" name="booking[routes][<?php echo $i; ?>][destination]" 
                                   value="<?php echo esc_attr($route['destination']); ?>" 
                                   class="large-text" />
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('停靠點', '9o-booking'); ?></label></th>
                        <td>
                            <div class="stops-container" data-day="<?php echo $i; ?>">
                                <?php
                                $stops = is_array($route['stops']) ? $route['stops'] : [];
                                foreach ($stops as $stop_index => $stop) {
                                    ?>
                                    <div style="margin-bottom: 5px;">
                                        <input type="text" name="booking[routes][<?php echo $i; ?>][stops][]" 
                                               value="<?php echo esc_attr($stop); ?>" 
                                               class="regular-text" />
                                        <button type="button" class="button remove-stop"><?php _e('移除', '9o-booking'); ?></button>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                            <button type="button" class="button add-stop" data-day="<?php echo $i; ?>">
                                <?php _e('新增停靠點', '9o-booking'); ?>
                            </button>
                        </td>
                    </tr>
                </table>
            </div>
            <?php
        }
        ?>
    </div>
    
    <h3><?php _e('特殊標記', '9o-booking'); ?></h3>
    <table class="form-table">
        <tr>
            <th><?php _e('山區路線', '9o-booking'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="booking[has_mountain]" value="1" 
                           <?php checked($booking_data['has_mountain'], true); ?> />
                    <?php _e('此行程包含山區路線（每天加收 NT$ 1,000）', '9o-booking'); ?>
                </label>
            </td>
        </tr>
        <tr>
            <th><?php _e('司機住宿', '9o-booking'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="booking[has_driver_accommodation]" value="1" 
                           <?php checked($booking_data['has_driver_accommodation'], true); ?> />
                    <?php _e('需要司機住宿（每晚 NT$ 2,000）', '9o-booking'); ?>
                </label>
            </td>
        </tr>
    </table>
    
    <h3><?php _e('特殊要求', '9o-booking'); ?></h3>
    <textarea name="booking[special_requests]" rows="3" class="large-text"><?php 
        echo esc_textarea($booking_data['special_requests']); 
    ?></textarea>
    
    <script>
    jQuery(document).ready(function($) {
        // 動態調整天數
        $('#trip_days').on('change', function() {
            var days = parseInt($(this).val());
            var container = $('#routes-container');
            var currentDays = container.find('.route-day').length;
            
            if (days > currentDays) {
                // 新增天數
                for (var i = currentDays; i < days; i++) {
                    // 複製模板或建立新的
                    var newDay = createNewDayTemplate(i);
                    container.append(newDay);
                }
            } else if (days < currentDays) {
                // 移除多餘天數
                container.find('.route-day').slice(days).remove();
            }
        });
        
        // 新增停靠點
        $(document).on('click', '.add-stop', function() {
            var day = $(this).data('day');
            var container = $(this).siblings('.stops-container');
            var stopHtml = '<div style="margin-bottom: 5px;">' +
                          '<input type="text" name="booking[routes][' + day + '][stops][]" class="regular-text" />' +
                          ' <button type="button" class="button remove-stop">移除</button>' +
                          '</div>';
            container.append(stopHtml);
        });
        
        // 移除停靠點
        $(document).on('click', '.remove-stop', function() {
            $(this).parent().remove();
        });
        
        function createNewDayTemplate(index) {
            var day = index + 1;
            var html = '<div class="route-day" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; background: #f9f9f9;">' +
                      '<h4>第 ' + day + ' 天</h4>' +
                      '<table class="form-table">' +
                      '<tr><th><label>出發地</label></th><td><input type="text" name="booking[routes][' + index + '][origin]" class="large-text" /></td></tr>' +
                      '<tr><th><label>目的地</label></th><td><input type="text" name="booking[routes][' + index + '][destination]" class="large-text" /></td></tr>' +
                      '<tr><th><label>停靠點</label></th><td>' +
                      '<div class="stops-container" data-day="' + index + '"></div>' +
                      '<button type="button" class="button add-stop" data-day="' + index + '">新增停靠點</button>' +
                      '</td></tr>' +
                      '</table>' +
                      '</div>';
            return html;
        }
    });
    </script>
    <?php
}

/**
 * 顯示客戶資訊 Meta Box
 */
function nineo_charter_render_customer_meta_box($post) {
    $customer_data = get_post_meta($post->ID, '_customer_data', true);
    if (!is_array($customer_data)) {
        $customer_data = [];
    }
    
    $defaults = [
        'name' => '',
        'phone' => '',
        'email' => '',
        'line_id' => '',
        'passengers' => 1,
        'luggage' => 0,
        'child_seats' => 0,
        'booster_seats' => 0
    ];
    
    $customer_data = array_merge($defaults, $customer_data);
    ?>
    <table class="form-table">
        <tr>
            <th><label><?php _e('姓名', '9o-booking'); ?></label></th>
            <td>
                <input type="text" name="customer[name]" 
                       value="<?php echo esc_attr($customer_data['name']); ?>" 
                       class="regular-text" required />
            </td>
        </tr>
        <tr>
            <th><label><?php _e('電話', '9o-booking'); ?></label></th>
            <td>
                <input type="text" name="customer[phone]" 
                       value="<?php echo esc_attr($customer_data['phone']); ?>" 
                       class="regular-text" required />
            </td>
        </tr>
        <tr>
            <th><label><?php _e('Email', '9o-booking'); ?></label></th>
            <td>
                <input type="email" name="customer[email]" 
                       value="<?php echo esc_attr($customer_data['email']); ?>" 
                       class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label><?php _e('LINE ID', '9o-booking'); ?></label></th>
            <td>
                <input type="text" name="customer[line_id]" 
                       value="<?php echo esc_attr($customer_data['line_id']); ?>" 
                       class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label><?php _e('乘客人數', '9o-booking'); ?></label></th>
            <td>
                <input type="number" name="customer[passengers]" 
                       value="<?php echo esc_attr($customer_data['passengers']); ?>" 
                       min="1" max="9" style="width: 60px;" />
                <span class="description"><?php _e('最多9人', '9o-booking'); ?></span>
            </td>
        </tr>
        <tr>
            <th><label><?php _e('行李件數', '9o-booking'); ?></label></th>
            <td>
                <input type="number" name="customer[luggage]" 
                       value="<?php echo esc_attr($customer_data['luggage']); ?>" 
                       min="0" style="width: 60px;" />
            </td>
        </tr>
        <tr>
            <th><label><?php _e('兒童安全座椅', '9o-booking'); ?></label></th>
            <td>
                <input type="number" name="customer[child_seats]" 
                       value="<?php echo esc_attr($customer_data['child_seats']); ?>" 
                       min="0" max="4" style="width: 60px;" />
                <span class="description"><?php _e('每張 NT$ 100', '9o-booking'); ?></span>
            </td>
        </tr>
        <tr>
            <th><label><?php _e('增高墊', '9o-booking'); ?></label></th>
            <td>
                <input type="number" name="customer[booster_seats]" 
                       value="<?php echo esc_attr($customer_data['booster_seats']); ?>" 
                       min="0" max="4" style="width: 60px;" />
                <span class="description"><?php _e('每張 NT$ 100', '9o-booking'); ?></span>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * 顯示價格計算 Meta Box
 */
function nineo_charter_render_pricing_meta_box($post) {
    $pricing_data = get_post_meta($post->ID, '_pricing_data', true);
    if (!is_array($pricing_data)) {
        $pricing_data = [];
    }
    
    $defaults = [
        'base_price' => 0,
        'mountain_surcharge' => 0,
        'driver_accommodation' => 0,
        'driver_meals' => 0,
        'addon_services' => 0,
        'total_price' => 0,
        'deposit' => 0,
        'balance' => 0
    ];
    
    $pricing_data = array_merge($defaults, $pricing_data);
    ?>
    <div style="padding: 10px;">
        <p>
            <label><?php _e('基本費用', '9o-booking'); ?>:</label><br>
            NT$ <input type="number" name="pricing[base_price]" 
                       value="<?php echo esc_attr($pricing_data['base_price']); ?>" 
                       style="width: 100px;" readonly />
        </p>
        <p>
            <label><?php _e('山區加價', '9o-booking'); ?>:</label><br>
            NT$ <input type="number" name="pricing[mountain_surcharge]" 
                       value="<?php echo esc_attr($pricing_data['mountain_surcharge']); ?>" 
                       style="width: 100px;" readonly />
        </p>
        <p>
            <label><?php _e('司機住宿', '9o-booking'); ?>:</label><br>
            NT$ <input type="number" name="pricing[driver_accommodation]" 
                       value="<?php echo esc_attr($pricing_data['driver_accommodation']); ?>" 
                       style="width: 100px;" readonly />
        </p>
        <p>
            <label><?php _e('司機餐費', '9o-booking'); ?>:</label><br>
            NT$ <input type="number" name="pricing[driver_meals]" 
                       value="<?php echo esc_attr($pricing_data['driver_meals']); ?>" 
                       style="width: 100px;" readonly />
        </p>
        <p>
            <label><?php _e('加值服務', '9o-booking'); ?>:</label><br>
            NT$ <input type="number" name="pricing[addon_services]" 
                       value="<?php echo esc_attr($pricing_data['addon_services']); ?>" 
                       style="width: 100px;" readonly />
        </p>
        <hr>
        <p>
            <strong><?php _e('總計', '9o-booking'); ?>:</strong><br>
            NT$ <input type="number" name="pricing[total_price]" 
                       value="<?php echo esc_attr($pricing_data['total_price']); ?>" 
                       style="width: 100px; font-weight: bold;" />
        </p>
        <p>
            <label><?php _e('訂金(30%)', '9o-booking'); ?>:</label><br>
            NT$ <input type="number" name="pricing[deposit]" 
                       value="<?php echo esc_attr($pricing_data['deposit']); ?>" 
                       style="width: 100px;" />
        </p>
        <p>
            <label><?php _e('尾款', '9o-booking'); ?>:</label><br>
            NT$ <input type="number" name="pricing[balance]" 
                       value="<?php echo esc_attr($pricing_data['balance']); ?>" 
                       style="width: 100px;" />
        </p>
        
        <p style="text-align: center; margin-top: 15px;">
            <button type="button" class="button button-primary" onclick="recalculatePrice()">
                <?php _e('重新計算', '9o-booking'); ?>
            </button>
        </p>
    </div>
    
    <script>
    function recalculatePrice() {
        // 這裡可以加入 AJAX 呼叫來重新計算價格
        alert('重新計算功能需要整合計算器');
    }
    </script>
    <?php
}

/**
 * 儲存 Post
 */
function nineo_charter_save_post($post_id) {
    // 安全性檢查
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['charter_booking_nonce']) || !wp_verify_nonce($_POST['charter_booking_nonce'], 'charter_booking_save')) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (get_post_type($post_id) !== 'charter_booking') return;
    
    // 儲存預約資料
    if (isset($_POST['booking'])) {
        $booking_data = $_POST['booking'];
        
        // 處理路線資料
        if (isset($booking_data['routes'])) {
            foreach ($booking_data['routes'] as &$route) {
                if (isset($route['stops'])) {
                    $route['stops'] = array_filter(array_map('sanitize_text_field', $route['stops']));
                }
            }
        }
        
        // 處理布林值
        $booking_data['has_mountain'] = isset($booking_data['has_mountain']);
        $booking_data['has_driver_accommodation'] = isset($booking_data['has_driver_accommodation']);
        
        update_post_meta($post_id, '_booking_data', $booking_data);
        
        // 更新標題
        if (!empty($booking_data['booking_number'])) {
            $customer_data = get_post_meta($post_id, '_customer_data', true);
            $customer_name = is_array($customer_data) && isset($customer_data['name']) ? $customer_data['name'] : '';
            
            remove_action('save_post', 'nineo_charter_save_post');
            wp_update_post([
                'ID' => $post_id,
                'post_title' => $booking_data['booking_number'] . ' - ' . $customer_name
            ]);
            add_action('save_post', 'nineo_charter_save_post');
        }
    }
    
    // 儲存客戶資料
    if (isset($_POST['customer'])) {
        $customer_data = array_map('sanitize_text_field', $_POST['customer']);
        update_post_meta($post_id, '_customer_data', $customer_data);
    }
    
    // 儲存價格資料
    if (isset($_POST['pricing'])) {
        $pricing_data = array_map('intval', $_POST['pricing']);
        update_post_meta($post_id, '_pricing_data', $pricing_data);
    }
}

/**
 * 設定自訂欄位
 */
function nineo_charter_set_custom_columns($columns) {
    $new_columns = [
        'cb' => $columns['cb'],
        'title' => __('預訂', '9o-booking'),
        'booking_number' => __('預訂編號', '9o-booking'),
        'customer' => __('客戶', '9o-booking'),
        'start_date' => __('出發日期', '9o-booking'),
        'days' => __('天數', '9o-booking'),
        'route' => __('路線摘要', '9o-booking'),
        'mountain' => __('山區', '9o-booking'),
        'price' => __('總價', '9o-booking'),
        'status' => __('狀態', '9o-booking'),
        'date' => __('建立日期', '9o-booking')
    ];
    return $new_columns;
}

/**
 * 顯示自訂欄位內容
 */
function nineo_charter_custom_column($column, $post_id) {
    $booking_data = get_post_meta($post_id, '_booking_data', true);
    $customer_data = get_post_meta($post_id, '_customer_data', true);
    $pricing_data = get_post_meta($post_id, '_pricing_data', true);
    
    if (!is_array($booking_data)) $booking_data = [];
    if (!is_array($customer_data)) $customer_data = [];
    if (!is_array($pricing_data)) $pricing_data = [];
    
    switch ($column) {
        case 'booking_number':
            echo esc_html($booking_data['booking_number'] ?? '');
            break;
            
        case 'customer':
            echo '<strong>' . esc_html($customer_data['name'] ?? '') . '</strong>';
            if (!empty($customer_data['phone'])) {
                echo '<br>' . esc_html($customer_data['phone']);
            }
            break;
            
        case 'start_date':
            echo esc_html($booking_data['start_date'] ?? '');
            break;
            
        case 'days':
            $days = $booking_data['trip_days'] ?? 1;
            echo sprintf(_n('%d 天', '%d 天', $days, '9o-booking'), $days);
            break;
            
        case 'route':
            if (!empty($booking_data['routes']) && is_array($booking_data['routes'])) {
                $summary = [];
                foreach ($booking_data['routes'] as $i => $route) {
                    $day = $i + 1;
                    $from = $route['origin'] ?? '';
                    $to = $route['destination'] ?? '';
                    if ($from && $to) {
                        $summary[] = sprintf('D%d: %s→%s', $day, $from, $to);
                    }
                }
                echo esc_html(implode(' | ', array_slice($summary, 0, 2)));
                if (count($summary) > 2) {
                    echo ' ...';
                }
            } else {
                echo '-';
            }
            break;
            
        case 'mountain':
            $has_mountain = $booking_data['has_mountain'] ?? false;
            echo $has_mountain ? '<span style="color: #d9534f;">●</span>' : '<span style="color: #5cb85c;">○</span>';
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
function nineo_charter_sortable_columns($columns) {
    $columns['booking_number'] = 'booking_number';
    $columns['start_date'] = 'start_date';
    $columns['days'] = 'days';
    $columns['price'] = 'price';
    $columns['status'] = 'status';
    return $columns;
}

/**
 * 處理排序查詢
 */
function nineo_charter_posts_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    if ($query->get('post_type') !== 'charter_booking') {
        return;
    }
    
    $orderby = $query->get('orderby');
    
    switch ($orderby) {
        case 'booking_number':
        case 'start_date':
        case 'days':
        case 'status':
            $query->set('meta_key', '_booking_data');
            $query->set('orderby', 'meta_value');
            break;
            
        case 'price':
            $query->set('meta_key', '_pricing_data');
            $query->set('orderby', 'meta_value_num');
            break;
    }
}

// 除錯日誌
if (defined('NINEO_DEBUG_MODE') && NINEO_DEBUG_MODE) {
    error_log('[9O Booking] Charter Post Type loaded - Priority: 105');
}
