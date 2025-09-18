/**
 * Code Snippet: [050] 9O Booking - Airport Module
 * 
 * Code Snippets 設定:
 * - Title: [050] 9O Booking - Airport Module
 * - Description: 機場接送主模組，整合所有機場相關功能
 * - Tags: 9o-booking, airport, module
 * - Priority: 50
 * - Run snippet: Run snippet everywhere
 */

// 確保核心設定已載入
if (!defined('NINEO_BOOKING_VERSION')) {
    error_log('[9O Booking] Airport Module: Core Setup not loaded');
    return;
}

// 全域變數儲存模組資訊
global $nineo_airport_module_path, $nineo_airport_module_url;
$nineo_airport_module_path = NINEO_BOOKING_PATH . 'modules/airport/';
$nineo_airport_module_url = NINEO_BOOKING_URL . 'modules/airport/';

/**
 * 初始化機場接送模組
 */
add_action('init', 'nineo_airport_module_init', 10);
function nineo_airport_module_init() {
    // 註冊模板路徑
    if (function_exists('nineo_register_template_path')) {
        nineo_register_template_path('airport', $GLOBALS['nineo_airport_module_path'] . 'templates/');
    }
    
    // 註冊短代碼
    add_shortcode('airport_booking_form', 'nineo_airport_render_booking_form');
    add_shortcode('airport_price_calculator', 'nineo_airport_render_price_calculator');
    
    // 載入前端資源
    add_action('wp_enqueue_scripts', 'nineo_airport_enqueue_assets');
    
    // 通知模組載入器此模組已載入
    do_action('9o_module_loaded_airport', [
        'name' => '機場接送模組',
        'version' => '1.0.0',
        'path' => $GLOBALS['nineo_airport_module_path']
    ]);
}

/**
 * 載入前端資源
 */
function nineo_airport_enqueue_assets() {
    // 只在需要時載入
    if (!nineo_airport_should_load_assets()) {
        return;
    }
    
    global $nineo_airport_module_url;
    
    // CSS
    wp_enqueue_style(
        'airport-module-css',
        $nineo_airport_module_url . 'assets/css/airport.css',
        ['shared-components-css'],
        NINEO_BOOKING_VERSION
    );
    
    // JavaScript
    wp_enqueue_script(
        'airport-module-js',
        $nineo_airport_module_url . 'assets/js/airport.js',
        ['jquery', 'shared-components-js'],
        NINEO_BOOKING_VERSION,
        true
    );
    
    // 本地化腳本
    wp_localize_script('airport-module-js', 'airportConfig', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('airport_nonce'),
        'airports' => [
            'TPE' => __('桃園國際機場', '9o-booking'),
            'TSA' => __('台北松山機場', '9o-booking')
        ],
        'serviceTypes' => [
            'pickup' => __('接機', '9o-booking'),
            'dropoff' => __('送機', '9o-booking'),
            'roundtrip' => __('來回接送', '9o-booking')
        ],
        'maxStops' => 5,
        'texts' => [
            'calculating' => __('計算中...', '9o-booking'),
            'selectAirport' => __('請選擇機場', '9o-booking'),
            'selectService' => __('請選擇服務類型', '9o-booking'),
            'enterDestination' => __('請輸入目的地', '9o-booking')
        ]
    ]);
}

/**
 * 判斷是否需要載入資源
 */
function nineo_airport_should_load_assets() {
    global $post;
    
    // 檢查頁面類型
    if (is_page('airport-booking') || 
        is_page('機場接送') ||
        is_page_template('page-airport-booking.php')) {
        return true;
    }
    
    // 檢查短代碼
    if (isset($post->post_content) && 
        (has_shortcode($post->post_content, 'airport_booking_form') ||
         has_shortcode($post->post_content, 'airport_price_calculator'))) {
        return true;
    }
    
    return false;
}

/**
 * 渲染預約表單短代碼
 */
function nineo_airport_render_booking_form($atts) {
    $atts = shortcode_atts([
        'style' => 'default',
        'show_header' => 'true',
        'compact' => 'false'
    ], $atts);
    
    $data = [
        'airports' => [
            'TPE' => __('桃園國際機場', '9o-booking'),
            'TSA' => __('台北松山機場', '9o-booking')
        ],
        'service_types' => [
            'pickup' => __('接機', '9o-booking'),
            'dropoff' => __('送機', '9o-booking'),
            'roundtrip' => __('來回接送', '9o-booking')
        ],
        'default_date' => date('Y-m-d', strtotime('+1 day')),
        'show_header' => $atts['show_header'] === 'true',
        'compact_mode' => $atts['compact'] === 'true',
        'form_style' => $atts['style']
    ];
    
    if (function_exists('nineo_render_form')) {
        return nineo_render_form('airport', 'booking-form', $data);
    }
    
    return '<p>' . __('機場預約表單載入失敗', '9o-booking') . '</p>';
}

/**
 * 渲染價格計算器短代碼
 */
function nineo_airport_render_price_calculator($atts) {
    $atts = shortcode_atts([
        'standalone' => 'false'
    ], $atts);
    
    $data = [
        'standalone' => $atts['standalone'] === 'true',
        'airports' => [
            'TPE' => __('桃園國際機場', '9o-booking'),
            'TSA' => __('台北松山機場', '9o-booking')
        ]
    ];
    
    if (function_exists('nineo_render_form')) {
        return nineo_render_form('airport', 'price-calculator', $data);
    }
    
    return '<p>' . __('價格計算器載入失敗', '9o-booking') . '</p>';
}

/**
 * 渲染完整預約頁面
 */
function nineo_airport_render_booking_page($data = []) {
    $default_data = [
        'page_title' => __('機場接送預約服務', '9o-booking'),
        'page_description' => __('專業可靠的機場接送預約服務，24小時服務，準時安全', '9o-booking'),
        'contact_phone' => get_option('9o_contact_phone', '+886xxxxxxxxx'),
        'contact_email' => get_option('9o_contact_email', 'service@9ovanstrip.com'),
        'service_features' => [
            [
                'icon' => '✈️',
                'title' => __('雙機場服務', '9o-booking'),
                'description' => __('提供桃園國際機場和台北松山機場接送服務', '9o-booking')
            ],
            [
                'icon' => '⏰',
                'title' => __('24小時服務', '9o-booking'),
                'description' => __('全天候服務，深夜航班也能安心預約', '9o-booking')
            ],
            [
                'icon' => '💰',
                'title' => __('透明計價', '9o-booking'),
                'description' => __('明確的計價標準，無隱藏費用，線上即時報價', '9o-booking')
            ]
        ]
    ];
    
    $data = array_merge($default_data, $data);
    
    if (function_exists('nineo_render_page')) {
        return nineo_render_page('airport', 'airport-booking-page', $data);
    }
    
    return '<p>' . __('頁面載入失敗', '9o-booking') . '</p>';
}

/**
 * 取得模組資訊
 */
function nineo_get_airport_module_info() {
    return [
        'name' => __('機場接送模組', '9o-booking'),
        'version' => '1.0.0',
        'description' => __('專業的機場接送預約功能', '9o-booking'),
        'author' => '9O Van Strip',
        'dependencies' => ['shared-components']
    ];
}

/**
 * 模組健康檢查
 */
function nineo_airport_health_check() {
    global $nineo_airport_module_path;
    $issues = [];
    
    // 檢查必要檔案
    $required_files = [
        'php/AirportCalculator.php',
        'php/AirportAjaxHandlers.php', 
        'php/AirportPostType.php',
        'templates/forms/booking-form.html',
        'assets/css/airport.css',
        'assets/js/airport.js'
    ];
    
    foreach ($required_files as $file) {
        if (!file_exists($nineo_airport_module_path . $file)) {
            $issues[] = sprintf(__('缺少檔案: %s', '9o-booking'), $file);
        }
    }
    
    // 檢查 Google Maps API
    if (!defined('GOOGLE_MAPS_API_KEY') || empty(GOOGLE_MAPS_API_KEY)) {
        $issues[] = __('未設定 Google Maps API Key', '9o-booking');
    }
    
    return empty($issues) ? ['status' => 'ok'] : ['status' => 'error', 'issues' => $issues];
}

// 提供健康檢查給模組載入器
add_filter('9o_module_health_airport', 'nineo_airport_health_check_filter');
function nineo_airport_health_check_filter($status) {
    $check = nineo_airport_health_check();
    return $check['status'];
}

// 除錯日誌
if (defined('NINEO_DEBUG_MODE') && NINEO_DEBUG_MODE) {
    error_log('[9O Booking] Airport Module loaded - Priority: 90');
}
