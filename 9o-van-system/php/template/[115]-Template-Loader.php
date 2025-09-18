/**
 * Code Snippet: [115] 9O Booking - Template Loader
 * 
 * Code Snippets 設定:
 * - Title: [115] 9O Booking - Template Loader
 * - Description: 自訂頁面模板載入和後備內容生成系統
 * - Tags: 9o-booking, templates, loader, fallback
 * - Priority: 115
 * - Run snippet: Only run on site front-end
 */

// 確保核心設定已載入
if (!defined('NINEO_BOOKING_VERSION')) {
    error_log('[9O Booking] Template Loader: Core Setup not loaded');
    return;
}

/**
 * Initialize template loader
 */
add_filter('template_include', 'nineo_template_loader');
add_action('wp_head', 'nineo_add_body_classes');
add_action('wp_head', 'nineo_add_meta_tags');

/**
 * Load custom templates for booking pages
 * 
 * @param string $template
 * @return string
 */
function nineo_template_loader($template) {
    global $post;
    
    if (!is_page()) {
        return $template;
    }
    
    $page_slug = $post->post_name;
    $custom_template = null;
    
    // Check for airport booking pages
    if (nineo_is_airport_booking_page($page_slug)) {
        $custom_template = nineo_locate_template('page-airport-booking.php');
    }
    
    // Check for charter booking pages
    if (nineo_is_charter_booking_page($page_slug)) {
        $custom_template = nineo_locate_template('page-charter-booking.php');
    }
    
    // Return custom template if found, otherwise use default
    return $custom_template ? $custom_template : $template;
}

/**
 * Check if page is airport booking
 * 
 * @param string $page_slug
 * @return bool
 */
function nineo_is_airport_booking_page($page_slug) {
    $airport_slugs = [
        'airport-booking',
        'airport-transfer',
        'airport-shuttle',
        '機場接送',
        '機場接送預約',
        '桃園機場接送',
        '松山機場接送'
    ];
    
    return in_array($page_slug, $airport_slugs) || 
           strpos($page_slug, 'airport') !== false ||
           strpos($page_slug, '機場') !== false;
}

/**
 * Check if page is charter booking
 * 
 * @param string $page_slug
 * @return bool
 */
function nineo_is_charter_booking_page($page_slug) {
    $charter_slugs = [
        'charter-booking',
        'charter-tour',
        'tour-booking',
        '包車旅遊',
        '包車旅遊預約',
        '包車服務',
        '包車行程'
    ];
    
    return in_array($page_slug, $charter_slugs) || 
           strpos($page_slug, 'charter') !== false ||
           strpos($page_slug, '包車') !== false;
}

/**
 * Locate template file
 * 
 * @param string $template_name
 * @return string|false
 */
function nineo_locate_template($template_name) {
    // Check theme directory first
    $theme_template = get_template_directory() . '/9o-booking/' . $template_name;
    if (file_exists($theme_template)) {
        return $theme_template;
    }
    
    // Check plugin templates directory
    // In Code Snippets environment, templates might be in uploads directory
    if (defined('NINEO_BOOKING_PATH')) {
        $plugin_template = NINEO_BOOKING_PATH . 'templates/' . $template_name;
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }
    
    // Check alternative locations
    $upload_dir = wp_upload_dir();
    $upload_template = $upload_dir['basedir'] . '/9o-booking-templates/' . $template_name;
    if (file_exists($upload_template)) {
        return $upload_template;
    }
    
    return false;
}

/**
 * Add body classes for styling
 */
function nineo_add_body_classes() {
    global $post;
    
    if (!is_page()) {
        return;
    }
    
    $classes = [];
    $page_slug = $post->post_name;
    
    if (nineo_is_airport_booking_page($page_slug)) {
        $classes[] = 'airport-booking-page';
        $classes[] = 'booking-page';
    }
    
    if (nineo_is_charter_booking_page($page_slug)) {
        $classes[] = 'charter-booking-page';
        $classes[] = 'booking-page';
    }
    
    // Check for shortcodes
    if (isset($post->post_content)) {
        if (has_shortcode($post->post_content, 'airport_booking_form')) {
            $classes[] = 'has-airport-form';
        }
        if (has_shortcode($post->post_content, 'charter_booking_form')) {
            $classes[] = 'has-charter-form';
        }
    }
    
    if (!empty($classes)) {
        ?>
        <script>
        (function() {
            document.addEventListener('DOMContentLoaded', function() {
                var classes = <?php echo wp_json_encode($classes); ?>;
                classes.forEach(function(className) {
                    document.body.classList.add(className);
                });
                // Remove no-js class if present
                document.body.classList.remove('no-js');
            });
        })();
        </script>
        <?php
    }
}

/**
 * Add meta tags for booking pages
 */
function nineo_add_meta_tags() {
    global $post;
    
    if (!is_page()) {
        return;
    }
    
    $page_slug = $post->post_name;
    
    // Common meta tags
    echo '<meta name="format-detection" content="telephone=yes">' . "\n";
    echo '<meta name="format-detection" content="address=yes">' . "\n";
    
    if (nineo_is_airport_booking_page($page_slug)) {
        echo '<meta name="description" content="' . esc_attr__('專業機場接送服務預約，提供桃園機場、松山機場接送，24小時服務，準時可靠', '9o-booking') . '">' . "\n";
        echo '<meta name="keywords" content="' . esc_attr__('機場接送,桃園機場,松山機場,接機,送機,預約,專業司機', '9o-booking') . '">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr__('機場接送預約服務 - 9O Van Strip', '9o-booking') . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr__('專業機場接送服務，準時可靠，24小時服務', '9o-booking') . '">' . "\n";
        echo '<meta property="og:type" content="website">' . "\n";
    }
    
    if (nineo_is_charter_booking_page($page_slug)) {
        echo '<meta name="description" content="' . esc_attr__('專業包車旅遊服務，舒適九人座車輛，專業司機陪同，提供全台灣多日包車旅遊行程', '9o-booking') . '">' . "\n";
        echo '<meta name="keywords" content="' . esc_attr__('包車旅遊,包車服務,九人座,旅遊包車,台灣旅遊,專業司機', '9o-booking') . '">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr__('包車旅遊預約服務 - 9O Van Strip', '9o-booking') . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr__('專業包車旅遊服務，舒適九人座車輛，帶您暢遊台灣美景', '9o-booking') . '">' . "\n";
        echo '<meta property="og:type" content="website">' . "\n";
    }
}

/**
 * Generate fallback airport booking page
 * 
 * @return string
 */
function nineo_airport_booking_fallback() {
    ob_start();
    ?>
    <div class="booking-fallback airport-fallback">
        <div class="container">
            <header class="fallback-header">
                <h1><?php _e('機場接送服務', '9o-booking'); ?></h1>
                <p><?php _e('專業可靠的機場接送預約服務', '9o-booking'); ?></p>
            </header>
            
            <main class="fallback-content">
                <?php echo do_shortcode('[airport_booking_form]'); ?>
                
                <section class="service-highlights">
                    <h2><?php _e('服務特色', '9o-booking'); ?></h2>
                    <div class="highlights-grid">
                        <div class="highlight-item">
                            <h3>✈️ <?php _e('雙機場服務', '9o-booking'); ?></h3>
                            <p><?php _e('提供桃園國際機場和台北松山機場接送服務', '9o-booking'); ?></p>
                        </div>
                        <div class="highlight-item">
                            <h3>⏰ <?php _e('24小時服務', '9o-booking'); ?></h3>
                            <p><?php _e('全天候服務，深夜航班也能安心預約', '9o-booking'); ?></p>
                        </div>
                        <div class="highlight-item">
                            <h3>💰 <?php _e('透明計價', '9o-booking'); ?></h3>
                            <p><?php _e('明確的計價標準，無隱藏費用，線上即時報價', '9o-booking'); ?></p>
                        </div>
                    </div>
                </section>
                
                <section class="contact-info">
                    <h2><?php _e('聯絡我們', '9o-booking'); ?></h2>
                    <p><?php _e('服務專線', '9o-booking'); ?>：<a href="tel:<?php echo esc_attr(get_option('9o_contact_phone', '+886xxxxxxxxx')); ?>"><?php echo esc_html(get_option('9o_contact_phone', '+886-xxx-xxx-xxx')); ?></a></p>
                    <p><?php _e('Email', '9o-booking'); ?>：<a href="mailto:<?php echo esc_attr(get_option('9o_contact_email', 'service@9ovanstrip.com')); ?>"><?php echo esc_html(get_option('9o_contact_email', 'service@9ovanstrip.com')); ?></a></p>
                </section>
            </main>
        </div>
    </div>
    
    <style>
    .booking-fallback {
        padding: 2rem 0;
        background: #fafafa;
    }
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1rem;
    }
    .fallback-header {
        text-align: center;
        margin-bottom: 3rem;
        padding: 2rem 0;
        background: #007bff;
        color: white;
        border-radius: 8px;
    }
    .fallback-header h1 {
        margin: 0 0 1rem 0;
        font-size: 2.5rem;
    }
    .fallback-content {
        background: white;
        border-radius: 8px;
        padding: 2rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .highlights-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
        margin: 2rem 0;
    }
    .highlight-item {
        padding: 1.5rem;
        background: #f8f9fa;
        border-radius: 8px;
        text-align: center;
    }
    .highlight-item h3 {
        margin: 0 0 1rem 0;
        color: #007bff;
    }
    .contact-info {
        text-align: center;
        padding: 2rem 0;
        border-top: 1px solid #dee2e6;
        margin-top: 2rem;
    }
    .contact-info a {
        color: #007bff;
        text-decoration: none;
        font-weight: 600;
    }
    </style>
    <?php
    return ob_get_clean();
}

/**
 * Generate fallback charter booking page
 * 
 * @return string
 */
function nineo_charter_booking_fallback() {
    ob_start();
    ?>
    <div class="booking-fallback charter-fallback">
        <div class="container">
            <header class="fallback-header">
                <h1><?php _e('包車旅遊服務', '9o-booking'); ?></h1>
                <p><?php _e('舒適九人座車輛，專業司機陪同，帶您暢遊台灣美景', '9o-booking'); ?></p>
            </header>
            
            <main class="fallback-content">
                <?php echo do_shortcode('[charter_booking_form]'); ?>
                
                <section class="service-highlights">
                    <h2><?php _e('服務特色', '9o-booking'); ?></h2>
                    <div class="highlights-grid">
                        <div class="highlight-item">
                            <h3>🚐 <?php _e('舒適車輛', '9o-booking'); ?></h3>
                            <p><?php _e('九人座豪華車輛，寬敞舒適，適合家庭或團體旅遊', '9o-booking'); ?></p>
                        </div>
                        <div class="highlight-item">
                            <h3>👨‍✈️ <?php _e('專業司機', '9o-booking'); ?></h3>
                            <p><?php _e('經驗豐富的專業司機，熟悉全台路況和景點', '9o-booking'); ?></p>
                        </div>
                        <div class="highlight-item">
                            <h3>🗺️ <?php _e('客製行程', '9o-booking'); ?></h3>
                            <p><?php _e('可依您的需求量身規劃行程，彈性安排景點', '9o-booking'); ?></p>
                        </div>
                    </div>
                </section>
                
                <?php echo do_shortcode('[charter_popular_routes limit="3"]'); ?>
                
                <section class="contact-info">
                    <h2><?php _e('聯絡我們', '9o-booking'); ?></h2>
                    <p><?php _e('服務專線', '9o-booking'); ?>：<a href="tel:<?php echo esc_attr(get_option('9o_contact_phone', '+886xxxxxxxxx')); ?>"><?php echo esc_html(get_option('9o_contact_phone', '+886-xxx-xxx-xxx')); ?></a></p>
                    <p><?php _e('Email', '9o-booking'); ?>：<a href="mailto:<?php echo esc_attr(get_option('9o_contact_email', 'service@9ovanstrip.com')); ?>"><?php echo esc_html(get_option('9o_contact_email', 'service@9ovanstrip.com')); ?></a></p>
                </section>
            </main>
        </div>
    </div>
    
    <style>
    .charter-fallback .fallback-header {
        background: #28a745;
    }
    .charter-fallback .highlight-item h3 {
        color: #28a745;
    }
    .charter-fallback .contact-info a {
        color: #28a745;
    }
    </style>
    <?php
    return ob_get_clean();
}

/**
 * Get fallback content for booking pages
 * 
 * @param string $type
 * @return string
 */
function nineo_get_booking_fallback_content($type = 'airport') {
    if ($type === 'charter') {
        return nineo_charter_booking_fallback();
    }
    return nineo_airport_booking_fallback();
}

/**
 * Shortcode for displaying fallback content
 * Usage: [booking_fallback type="airport"]
 */
function nineo_booking_fallback_shortcode($atts) {
    $atts = shortcode_atts([
        'type' => 'airport'
    ], $atts, 'booking_fallback');
    
    return nineo_get_booking_fallback_content($atts['type']);
}
add_shortcode('booking_fallback', 'nineo_booking_fallback_shortcode');

// 除錯日誌
if (defined('NINEO_DEBUG_MODE') && NINEO_DEBUG_MODE) {
    error_log('[9O Booking] Template Loader loaded - Priority: 130');
}
