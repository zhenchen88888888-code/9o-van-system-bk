/**
 * Code Snippet: [110] 9O Booking - Shortcodes
 * 
 * Code Snippets 設定:
 * - Title: [110] 9O Booking - Shortcodes
 * - Description: 所有簡碼實作，包含預約表單、服務資訊、熱門路線等
 * - Tags: 9o-booking, shortcodes, forms, frontend
 * - Priority: 110
 * - Run snippet: Only run on site front-end
 */

// 確保核心設定已載入
if (!defined('NINEO_BOOKING_VERSION')) {
    error_log('[9O Booking] Shortcodes: Core Setup not loaded');
    return;
}

/**
 * Airport Booking Form Shortcode
 * Usage: [airport_booking_form]
 */
function render_airport_booking_shortcode($atts) {
    // Parse attributes with defaults
    $atts = shortcode_atts([
        'style' => 'default',
        'show_header' => 'true',
        'compact' => 'false'
    ], $atts, 'airport_booking_form');
    
    // Start output buffering
    ob_start();
    
    // Add structured data for SEO
    ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Service",
        "name": "機場接送服務",
        "description": "專業機場接送預約服務，提供台北桃園機場、松山機場接送",
        "provider": {
            "@type": "LocalBusiness",
            "name": "9O Van Strip"
        },
        "areaServed": {
            "@type": "Country",
            "name": "台灣"
        },
        "serviceType": "機場接送"
    }
    </script>
    
    <div id="airport-booking-app" class="booking-app" role="main" aria-label="機場接送預約系統">
        <!-- No-JavaScript 後備方案 -->
        <noscript>
            <div class="no-js-fallback">
                <h2>需要 JavaScript 支援</h2>
                <p>為了使用完整的線上預約功能，請啟用瀏覽器的 JavaScript。</p>
                <p>或者您可以直接撥打我們的服務專線：<a href="tel:<?php echo esc_attr(get_option('9o_contact_phone', '+886xxxxxxxxx')); ?>"><?php echo esc_html(get_option('9o_contact_phone', '+886-xxx-xxx-xxx')); ?></a></p>
                
                <!-- 簡易預約表單 -->
                <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="simple-booking-form">
                    <input type="hidden" name="action" value="simple_booking_submission">
                    <input type="hidden" name="booking_type" value="airport">
                    <?php wp_nonce_field('simple_booking_nonce', 'simple_booking_nonce'); ?>
                    
                    <div class="form-group">
                        <label for="simple-name"><?php _e('姓名', '9o-booking'); ?> *</label>
                        <input type="text" id="simple-name" name="customer_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="simple-phone"><?php _e('電話', '9o-booking'); ?> *</label>
                        <input type="tel" id="simple-phone" name="customer_phone" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="simple-date"><?php _e('預約日期', '9o-booking'); ?> *</label>
                        <input type="date" id="simple-date" name="booking_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="simple-message"><?php _e('需求說明', '9o-booking'); ?> *</label>
                        <textarea id="simple-message" name="message" rows="4" 
                                  placeholder="<?php esc_attr_e('請說明您的機場接送需求（機場、地址、時間等）', '9o-booking'); ?>" required></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit"><?php _e('提交預約需求', '9o-booking'); ?></button>
                </form>
            </div>
        </noscript>
        
        <!-- 主要預約界面 -->
        <div class="js-booking-interface">
            <div class="booking-container">
                <!-- 表單容器 -->
                <form id="airport-booking-form" class="booking-form" 
                      role="form" aria-label="<?php esc_attr_e('機場接送詳細預約表單', '9o-booking'); ?>" novalidate>
                    
                    <!-- 載入狀態 -->
                    <div class="form-loading" aria-live="polite">
                        <div class="loading-spinner">
                            <span class="spinner" aria-hidden="true"></span>
                            <span class="sr-only"><?php _e('載入預約表單中...', '9o-booking'); ?></span>
                        </div>
                        <p><?php _e('載入預約表單...', '9o-booking'); ?></p>
                    </div>
                    
                    <!-- 表單內容將由 JavaScript 動態建立 -->
                </form>
                
                <!-- 即時報價面板 -->
                <aside id="price-panel" class="price-panel" 
                       role="complementary" aria-label="<?php esc_attr_e('即時報價資訊', '9o-booking'); ?>">
                    <h2>💰 <?php _e('即時報價', '9o-booking'); ?></h2>
                    <div class="price-content" role="region" aria-live="polite">
                        <div class="price-loading">
                            <p><?php _e('請填寫表單資訊以獲得即時報價', '9o-booking'); ?></p>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
        
        <!-- 錯誤狀態顯示 -->
        <div id="booking-error" class="booking-error" style="display: none;" role="alert">
            <h3><?php _e('載入發生問題', '9o-booking'); ?></h3>
            <p><?php _e('預約系統暫時無法載入，請嘗試重新整理頁面或使用以下方式聯繫我們：', '9o-booking'); ?></p>
            <ul>
                <li><?php _e('電話', '9o-booking'); ?>：<a href="tel:<?php echo esc_attr(get_option('9o_contact_phone', '+886xxxxxxxxx')); ?>"><?php echo esc_html(get_option('9o_contact_phone', '+886-xxx-xxx-xxx')); ?></a></li>
                <li><?php _e('Email', '9o-booking'); ?>：<a href="mailto:<?php echo esc_attr(get_option('9o_contact_email', 'service@9ovanstrip.com')); ?>"><?php echo esc_html(get_option('9o_contact_email', 'service@9ovanstrip.com')); ?></a></li>
            </ul>
        </div>
    </div>
    
    <!-- 載入錯誤檢測 -->
    <script>
    // 檢測 JavaScript 載入情況
    setTimeout(function() {
        if (typeof jQuery === 'undefined' || !window.BookingCommon) {
            document.getElementById('booking-error').style.display = 'block';
            document.querySelector('.js-booking-interface').style.display = 'none';
        }
    }, 5000);
    </script>
    
    <?php
    return ob_get_clean();
}
add_shortcode('airport_booking_form', 'render_airport_booking_shortcode');

/**
 * Charter Booking Form Shortcode  
 * Usage: [charter_booking_form]
 */
function render_charter_booking_shortcode($atts) {
    // Parse attributes with defaults
    $atts = shortcode_atts([
        'style' => 'default',
        'show_header' => 'true',
        'compact' => 'false'
    ], $atts, 'charter_booking_form');
    
    // Start output buffering
    ob_start();
    
    // Add structured data for SEO
    ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "TouristTrip",
        "name": "包車旅遊服務",
        "description": "專業包車旅遊服務，提供全台灣多日包車旅遊行程",
        "provider": {
            "@type": "LocalBusiness",
            "name": "9O Van Strip"
        },
        "touristType": "家庭旅遊, 商務旅行, 觀光旅遊",
        "itinerary": {
            "@type": "ItemList",
            "name": "包車旅遊路線"
        }
    }
    </script>
    
    <div id="charter-booking-app" class="booking-app" role="main" aria-label="包車旅遊預約系統">
        <!-- No-JavaScript 後備方案 -->
        <noscript>
            <div class="no-js-fallback">
                <h2>需要 JavaScript 支援</h2>
                <p>為了使用完整的多日行程規劃功能，請啟用瀏覽器的 JavaScript。</p>
                <p>或者您可以直接撥打我們的服務專線：<a href="tel:<?php echo esc_attr(get_option('9o_contact_phone', '+886xxxxxxxxx')); ?>"><?php echo esc_html(get_option('9o_contact_phone', '+886-xxx-xxx-xxx')); ?></a></p>
                
                <!-- 簡易預約表單 -->
                <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="simple-booking-form">
                    <input type="hidden" name="action" value="simple_booking_submission">
                    <input type="hidden" name="booking_type" value="charter">
                    <?php wp_nonce_field('simple_booking_nonce', 'simple_booking_nonce'); ?>
                    
                    <div class="form-group">
                        <label for="simple-name-charter"><?php _e('姓名', '9o-booking'); ?> *</label>
                        <input type="text" id="simple-name-charter" name="customer_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="simple-phone-charter"><?php _e('電話', '9o-booking'); ?> *</label>
                        <input type="tel" id="simple-phone-charter" name="customer_phone" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="simple-days"><?php _e('旅遊天數', '9o-booking'); ?> *</label>
                        <select id="simple-days" name="trip_days" required>
                            <option value=""><?php _e('請選擇', '9o-booking'); ?></option>
                            <?php for ($i = 1; $i <= 7; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php printf(_n('%d天', '%d天', $i, '9o-booking'), $i); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="simple-date-charter"><?php _e('出發日期', '9o-booking'); ?> *</label>
                        <input type="date" id="simple-date-charter" name="start_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="simple-message-charter"><?php _e('行程需求', '9o-booking'); ?> *</label>
                        <textarea id="simple-message-charter" name="message" rows="5" 
                                  placeholder="<?php esc_attr_e('請詳細說明您的包車旅遊需求（想去的景點、天數、人數等）', '9o-booking'); ?>" required></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit"><?php _e('提交預約需求', '9o-booking'); ?></button>
                </form>
            </div>
        </noscript>
        
        <!-- 主要預約界面 -->
        <div class="js-booking-interface">
            <div class="booking-container">
                <!-- 表單容器 -->
                <form id="charter-booking-form" class="booking-form" 
                      role="form" aria-label="<?php esc_attr_e('包車旅遊詳細預約表單', '9o-booking'); ?>" novalidate>
                    
                    <!-- 載入狀態 -->
                    <div class="form-loading" aria-live="polite">
                        <div class="loading-spinner">
                            <span class="spinner" aria-hidden="true"></span>
                            <span class="sr-only"><?php _e('載入預約表單中...', '9o-booking'); ?></span>
                        </div>
                        <p><?php _e('載入預約表單...', '9o-booking'); ?></p>
                    </div>
                    
                    <!-- 表單內容將由 JavaScript 動態建立 -->
                </form>
                
                <!-- 即時報價面板 -->
                <aside id="price-panel" class="price-panel" 
                       role="complementary" aria-label="<?php esc_attr_e('即時報價資訊', '9o-booking'); ?>">
                    <h2>💰 <?php _e('即時報價', '9o-booking'); ?></h2>
                    <div class="price-content" role="region" aria-live="polite">
                        <div class="price-loading">
                            <p><?php _e('請填寫行程資訊以獲得即時報價', '9o-booking'); ?></p>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
        
        <!-- 錯誤狀態顯示 -->
        <div id="booking-error-charter" class="booking-error" style="display: none;" role="alert">
            <h3><?php _e('載入發生問題', '9o-booking'); ?></h3>
            <p><?php _e('預約系統暫時無法載入，請嘗試重新整理頁面或使用以下方式聯繫我們：', '9o-booking'); ?></p>
            <ul>
                <li><?php _e('電話', '9o-booking'); ?>：<a href="tel:<?php echo esc_attr(get_option('9o_contact_phone', '+886xxxxxxxxx')); ?>"><?php echo esc_html(get_option('9o_contact_phone', '+886-xxx-xxx-xxx')); ?></a></li>
                <li><?php _e('Email', '9o-booking'); ?>：<a href="mailto:<?php echo esc_attr(get_option('9o_contact_email', 'service@9ovanstrip.com')); ?>"><?php echo esc_html(get_option('9o_contact_email', 'service@9ovanstrip.com')); ?></a></li>
            </ul>
        </div>
    </div>
    
    <!-- 載入錯誤檢測 -->
    <script>
    // 檢測 JavaScript 載入情況
    setTimeout(function() {
        if (typeof jQuery === 'undefined' || !window.BookingCommon) {
            document.getElementById('booking-error-charter').style.display = 'block';
            document.querySelector('.js-booking-interface').style.display = 'none';
        }
    }, 5000);
    </script>
    
    <?php
    return ob_get_clean();
}
add_shortcode('charter_booking_form', 'render_charter_booking_shortcode');

/**
 * Service Information Shortcode
 * Usage: [booking_service_info type="airport"]
 */
function render_service_info_shortcode($atts) {
    $atts = shortcode_atts([
        'type' => 'airport',
        'layout' => 'grid'
    ], $atts, 'booking_service_info');
    
    ob_start();
    
    if ($atts['type'] === 'charter') {
        ?>
        <section class="service-info charter-info">
            <div class="info-header">
                <h2><?php _e('包車旅遊服務特色', '9o-booking'); ?></h2>
                <p><?php _e('專業司機陪同，舒適九人座車輛，讓您的旅程更輕鬆愉快', '9o-booking'); ?></p>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-icon">🚐</div>
                    <h3><?php _e('舒適車輛', '9o-booking'); ?></h3>
                    <p><?php _e('九人座豪華車輛，寬敞舒適，適合家庭或團體旅遊', '9o-booking'); ?></p>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">👨‍✈️</div>
                    <h3><?php _e('專業司機', '9o-booking'); ?></h3>
                    <p><?php _e('經驗豐富的專業司機，熟悉全台路況和景點', '9o-booking'); ?></p>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">🗺️</div>
                    <h3><?php _e('客製行程', '9o-booking'); ?></h3>
                    <p><?php _e('可依您的需求量身規劃行程，彈性安排景點', '9o-booking'); ?></p>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">💰</div>
                    <h3><?php _e('透明計價', '9o-booking'); ?></h3>
                    <p><?php _e('明確的計價標準，無隱藏費用，線上即時報價', '9o-booking'); ?></p>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">🏔️</div>
                    <h3><?php _e('山區服務', '9o-booking'); ?></h3>
                    <p><?php _e('提供山區旅遊服務，安全駕駛技術值得信賴', '9o-booking'); ?></p>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">📞</div>
                    <h3><?php _e('24小時服務', '9o-booking'); ?></h3>
                    <p><?php _e('全天候客服支援，隨時為您解決旅途中的問題', '9o-booking'); ?></p>
                </div>
            </div>
        </section>
        <?php
    } else {
        ?>
        <section class="service-info airport-info">
            <div class="info-header">
                <h2><?php _e('機場接送服務特色', '9o-booking'); ?></h2>
                <p><?php _e('準時、安全、舒適的機場接送服務，讓您的旅程從容開始', '9o-booking'); ?></p>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-icon">✈️</div>
                    <h3><?php _e('雙機場服務', '9o-booking'); ?></h3>
                    <p><?php _e('提供桃園國際機場和台北松山機場接送服務', '9o-booking'); ?></p>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">⏰</div>
                    <h3><?php _e('準時到達', '9o-booking'); ?></h3>
                    <p><?php _e('專業司機準時接送，確保您不會錯過航班', '9o-booking'); ?></p>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">🚗</div>
                    <h3><?php _e('舒適車輛', '9o-booking'); ?></h3>
                    <p><?php _e('乾淨舒適的車輛，提供安全帶和兒童座椅', '9o-booking'); ?></p>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">📱</div>
                    <h3><?php _e('即時追蹤', '9o-booking'); ?></h3>
                    <p><?php _e('提供司機聯絡方式，可即時掌握接送狀況', '9o-booking'); ?></p>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">💳</div>
                    <h3><?php _e('多元支付', '9o-booking'); ?></h3>
                    <p><?php _e('支援現金、信用卡等多種支付方式', '9o-booking'); ?></p>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">🌙</div>
                    <h3><?php _e('夜間服務', '9o-booking'); ?></h3>
                    <p><?php _e('提供24小時服務，深夜航班也能安心預約', '9o-booking'); ?></p>
                </div>
            </div>
        </section>
        <?php
    }
    
    return ob_get_clean();
}
add_shortcode('booking_service_info', 'render_service_info_shortcode');

/**
 * Popular Routes Shortcode for Charter Service
 * Usage: [charter_popular_routes]
 */
function render_popular_routes_shortcode($atts) {
    $atts = shortcode_atts([
        'limit' => 6
    ], $atts, 'charter_popular_routes');
    
    ob_start();
    
    $routes = [
        [
            'title' => __('台北 → 清境農場', '9o-booking'),
            'duration' => __('2日遊', '9o-booking'),
            'highlights' => [__('武嶺', '9o-booking'), __('合歡山', '9o-booking'), __('清境農場', '9o-booking')],
            'price' => 'NT$ 25,000',
            'image' => '🏔️'
        ],
        [
            'title' => __('台北 → 阿里山', '9o-booking'),
            'duration' => __('2日遊', '9o-booking'), 
            'highlights' => [__('阿里山日出', '9o-booking'), __('神木群', '9o-booking'), __('森林鐵路', '9o-booking')],
            'price' => 'NT$ 28,000',
            'image' => '🌄'
        ],
        [
            'title' => __('台北 → 花蓮太魯閣', '9o-booking'),
            'duration' => __('3日遊', '9o-booking'),
            'highlights' => [__('太魯閣國家公園', '9o-booking'), __('七星潭', '9o-booking'), __('清水斷崖', '9o-booking')],
            'price' => 'NT$ 42,000',
            'image' => '🏞️'
        ],
        [
            'title' => __('台北 → 台東知本', '9o-booking'),
            'duration' => __('3日遊', '9o-booking'),
            'highlights' => [__('知本溫泉', '9o-booking'), __('台東市區', '9o-booking'), __('池上便當', '9o-booking')],
            'price' => 'NT$ 45,000',
            'image' => '♨️'
        ],
        [
            'title' => __('台北 → 墾丁', '9o-booking'),
            'duration' => __('3日遊', '9o-booking'),
            'highlights' => [__('墾丁國家公園', '9o-booking'), __('鵝鑾鼻燈塔', '9o-booking'), __('白沙灣', '9o-booking')],
            'price' => 'NT$ 42,000',
            'image' => '🏖️'
        ],
        [
            'title' => __('台北 → 日月潭', '9o-booking'),
            'duration' => __('2日遊', '9o-booking'),
            'highlights' => [__('日月潭', '9o-booking'), __('九族文化村', '9o-booking'), __('埔里酒廠', '9o-booking')],
            'price' => 'NT$ 24,000',
            'image' => '🌊'
        ]
    ];
    
    $routes = array_slice($routes, 0, intval($atts['limit']));
    ?>
    
    <section class="popular-routes">
        <div class="routes-header">
            <h2><?php _e('熱門包車路線', '9o-booking'); ?></h2>
            <p><?php _e('精選台灣熱門旅遊路線，專業司機帶您深度體驗', '9o-booking'); ?></p>
        </div>
        
        <div class="routes-grid">
            <?php foreach ($routes as $route): ?>
            <div class="route-card">
                <div class="route-image">
                    <span class="route-emoji"><?php echo $route['image']; ?></span>
                </div>
                <div class="route-content">
                    <h3 class="route-title"><?php echo esc_html($route['title']); ?></h3>
                    <p class="route-duration"><?php echo esc_html($route['duration']); ?></p>
                    <div class="route-highlights">
                        <?php foreach ($route['highlights'] as $highlight): ?>
                            <span class="highlight-tag"><?php echo esc_html($highlight); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="route-price">
                        <span class="price-label"><?php _e('建議售價', '9o-booking'); ?></span>
                        <span class="price-amount"><?php echo esc_html($route['price']); ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="routes-footer">
            <p class="routes-note">* <?php _e('實際價格依行程內容和天數而定，歡迎來電詢問客製化行程', '9o-booking'); ?></p>
        </div>
    </section>
    
    <?php
    return ob_get_clean();
}
add_shortcode('charter_popular_routes', 'render_popular_routes_shortcode');

/**
 * Simple booking form handler (for no-JS fallback)
 */
function handle_simple_booking_submission() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['simple_booking_nonce'], 'simple_booking_nonce')) {
        wp_die(__('Security check failed', '9o-booking'));
    }
    
    // Sanitize data
    $booking_data = [
        'booking_type' => sanitize_text_field($_POST['booking_type']),
        'customer_name' => sanitize_text_field($_POST['customer_name']),
        'customer_phone' => sanitize_text_field($_POST['customer_phone']),
        'message' => sanitize_textarea_field($_POST['message']),
        'booking_date' => sanitize_text_field($_POST['booking_date'] ?? ''),
        'start_date' => sanitize_text_field($_POST['start_date'] ?? ''),
        'trip_days' => intval($_POST['trip_days'] ?? 1),
        'submission_time' => current_time('mysql'),
        'ip_address' => $_SERVER['REMOTE_ADDR']
    ];
    
    // Save to database
    $post_data = [
        'post_title' => sprintf('[%s] %s - %s', 
            __('簡易預約', '9o-booking'),
            $booking_data['booking_type'] === 'charter' ? __('包車旅遊', '9o-booking') : __('機場接送', '9o-booking'),
            $booking_data['customer_name']
        ),
        'post_content' => wp_json_encode($booking_data, JSON_UNESCAPED_UNICODE),
        'post_status' => 'private',
        'post_type' => 'simple_booking',
        'meta_input' => [
            '_booking_type' => $booking_data['booking_type'],
            '_customer_name' => $booking_data['customer_name'],
            '_customer_phone' => $booking_data['customer_phone'],
            '_needs_followup' => 'yes'
        ]
    ];
    
    $post_id = wp_insert_post($post_data);
    
    if ($post_id) {
        // Send notification email
        $admin_email = get_option('admin_email');
        $subject = sprintf('[9O預約] %s - %s', __('新的簡易預約', '9o-booking'), $booking_data['customer_name']);
        $message = sprintf(
            __("有新的簡易預約需求：\n\n服務類型：%s\n客戶姓名：%s\n客戶電話：%s\n需求說明：\n%s\n\n預約時間：%s", '9o-booking'),
            $booking_data['booking_type'] === 'charter' ? __('包車旅遊', '9o-booking') : __('機場接送', '9o-booking'),
            $booking_data['customer_name'],
            $booking_data['customer_phone'],
            $booking_data['message'],
            $booking_data['submission_time']
        );
        
        wp_mail($admin_email, $subject, $message);
        
        // Log the booking
        if (function_exists('nineo_log')) {
            nineo_log('New simple booking submission', 'info', $booking_data);
        }
        
        // Redirect with success message
        wp_redirect(add_query_arg('booking_status', 'success', wp_get_referer()));
    } else {
        // Redirect with error message
        wp_redirect(add_query_arg('booking_status', 'error', wp_get_referer()));
    }
    exit;
}
add_action('admin_post_simple_booking_submission', 'handle_simple_booking_submission');
add_action('admin_post_nopriv_simple_booking_submission', 'handle_simple_booking_submission');

/**
 * Register simple booking post type
 */
function register_simple_booking_post_type() {
    register_post_type('simple_booking', [
        'labels' => [
            'name' => __('簡易預約', '9o-booking'),
            'singular_name' => __('簡易預約', '9o-booking'),
            'menu_name' => __('簡易預約', '9o-booking'),
            'add_new' => __('新增預約', '9o-booking'),
            'edit_item' => __('編輯預約', '9o-booking')
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'edit.php?post_type=airport_booking',
        'capability_type' => 'post',
        'supports' => ['title', 'editor', 'custom-fields'],
        'menu_icon' => 'dashicons-clipboard'
    ]);
}
add_action('init', 'register_simple_booking_post_type');

/**
 * Display booking status messages
 */
function display_booking_status_message() {
    if (isset($_GET['booking_status'])) {
        $status = sanitize_text_field($_GET['booking_status']);
        
        if ($status === 'success') {
            echo '<div class="booking-status-message success" role="alert">';
            echo '<h3>' . __('預約提交成功！', '9o-booking') . '</h3>';
            echo '<p>' . __('我們已收到您的預約需求，將在24小時內與您聯繫確認詳細資訊。', '9o-booking') . '</p>';
            echo '</div>';
        } elseif ($status === 'error') {
            echo '<div class="booking-status-message error" role="alert">';
            echo '<h3>' . __('提交發生錯誤', '9o-booking') . '</h3>';
            echo '<p>' . sprintf(
                __('很抱歉，系統暫時無法處理您的預約。請直接撥打我們的服務專線：<a href="tel:%1$s">%2$s</a>', '9o-booking'),
                esc_attr(get_option('9o_contact_phone', '+886xxxxxxxxx')),
                esc_html(get_option('9o_contact_phone', '+886-xxx-xxx-xxx'))
            ) . '</p>';
            echo '</div>';
        }
        
        // Add inline CSS for messages
        ?>
        <style>
        .booking-status-message {
            margin: 20px 0;
            padding: 20px;
            border-radius: 5px;
            font-size: 16px;
            line-height: 1.5;
        }
        .booking-status-message.success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .booking-status-message.error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .booking-status-message h3 {
            margin-top: 0;
            margin-bottom: 10px;
        }
        </style>
        <?php
    }
}
add_action('wp_head', 'display_booking_status_message');

// 除錯日誌
if (defined('NINEO_DEBUG_MODE') && NINEO_DEBUG_MODE) {
    error_log('[9O Booking] Shortcodes loaded - Priority: 120');
}
