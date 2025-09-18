/**
 * Code Snippet: [080] 9O Booking - Charter Module
 * 
 * Code Snippets 設定:
 * - Title: [080] 9O Booking - Charter Module
 * - Description: 包車旅遊主模組，整合所有包車相關功能
 * - Tags: 9o-booking, charter, module
 * - Priority: 80
 * - Run snippet: Run snippet everywhere
 */

// 確保核心設定已載入
if (!defined('NINEO_BOOKING_VERSION')) {
    error_log('[9O Booking] Charter Module: Core Setup not loaded');
    return;
}

// 全域變數儲存模組資訊
global $nineo_charter_module_path, $nineo_charter_module_url;
$nineo_charter_module_path = NINEO_BOOKING_PATH . 'modules/charter/';
$nineo_charter_module_url = NINEO_BOOKING_URL . 'modules/charter/';

/**
 * 初始化包車旅遊模組
 */
add_action('init', 'nineo_charter_module_init', 10);
function nineo_charter_module_init() {
    // 註冊模板路徑
    if (function_exists('nineo_register_template_path')) {
        nineo_register_template_path('charter', $GLOBALS['nineo_charter_module_path'] . 'templates/');
    }
    
    // 註冊短代碼
    add_shortcode('charter_booking_form', 'nineo_charter_render_booking_form');
    add_shortcode('charter_popular_routes', 'nineo_charter_render_popular_routes');
    add_shortcode('charter_price_calculator', 'nineo_charter_render_price_calculator');
    
    // 載入前端資源
    add_action('wp_enqueue_scripts', 'nineo_charter_enqueue_assets');
    
    // 通知模組載入器此模組已載入
    do_action('9o_module_loaded_charter', [
        'name' => '包車旅遊模組',
        'version' => '1.0.0',
        'path' => $GLOBALS['nineo_charter_module_path']
    ]);
}

/**
 * 載入前端資源
 */
function nineo_charter_enqueue_assets() {
    // 只在需要時載入
    if (!nineo_charter_should_load_assets()) {
        return;
    }
    
    global $nineo_charter_module_url;
    
    // CSS
    wp_enqueue_style(
        'charter-module-css',
        $nineo_charter_module_url . 'assets/css/charter.css',
        ['shared-components-css'],
        NINEO_BOOKING_VERSION
    );
    
    // JavaScript
    wp_enqueue_script(
        'charter-module-js',
        $nineo_charter_module_url . 'assets/js/charter.js',
        ['jquery', 'shared-components-js'],
        NINEO_BOOKING_VERSION,
        true
    );
    
    // 本地化腳本
    wp_localize_script('charter-module-js', 'charterConfig', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('charter_nonce'),
        'maxDays' => 7,
        'minDays' => 1,
        'maxStops' => 6,
        'mountainKeywords' => nineo_charter_get_mountain_keywords(),
        'excludedAreas' => nineo_charter_get_excluded_areas(),
        'basePrices' => [
            'north_day1' => CHARTER_NORTH_DAY1,
            'north_day2plus' => CHARTER_NORTH_DAY2PLUS,
            'south_day1' => CHARTER_SOUTH_DAY1,
            'south_day2plus' => CHARTER_SOUTH_DAY2PLUS,
            'mountain_surcharge' => MOUNTAIN_SURCHARGE,
            'driver_accommodation' => DRIVER_ACCOMMODATION_FEE,
            'driver_meals' => DRIVER_MEAL_FEE
        ],
        'texts' => [
            'calculating' => __('計算中...', '9o-booking'),
            'selectDays' => __('請選擇天數', '9o-booking'),
            'addDestination' => __('請新增目的地', '9o-booking'),
            'mountainAlert' => __('此路線包含山區景點，將加收山區費用', '9o-booking'),
            'excludedAlert' => __('此地區不在服務範圍內', '9o-booking'),
            'maxStopsReached' => __('已達到最大停靠點數量', '9o-booking'),
            'invalidDate' => __('請選擇有效的日期', '9o-booking')
        ]
    ]);
}

/**
 * 判斷是否需要載入資源
 */
function nineo_charter_should_load_assets() {
    global $post;
    
    // 檢查頁面類型
    if (is_page('charter-booking') || 
        is_page('包車旅遊') ||
        is_page_template('page-charter-booking.php')) {
        return true;
    }
    
    // 檢查短代碼
    if (isset($post->post_content) && 
        (has_shortcode($post->post_content, 'charter_booking_form') ||
         has_shortcode($post->post_content, 'charter_popular_routes') ||
         has_shortcode($post->post_content, 'charter_price_calculator'))) {
        return true;
    }
    
    return false;
}

/**
 * 渲染預約表單短代碼
 */
function nineo_charter_render_booking_form($atts) {
    $atts = shortcode_atts([
        'style' => 'default',
        'show_header' => 'true',
        'max_days' => '7',
        'compact' => 'false'
    ], $atts);
    
    $data = [
        'max_days' => intval($atts['max_days']),
        'default_date' => date('Y-m-d', strtotime('+3 day')),
        'show_header' => $atts['show_header'] === 'true',
        'compact_mode' => $atts['compact'] === 'true',
        'form_style' => $atts['style'],
        'popular_destinations' => [
            '台北' => ['清境農場', '九份', '野柳', '淡水'],
            '台中' => ['日月潭', '溪頭', '合歡山', '武嶺'],
            '高雄' => ['墾丁', '茂林', '美濃', '旗津'],
            '花蓮' => ['太魯閣', '七星潭', '清水斷崖', '瑞穗'],
            '台東' => ['知本', '綠島', '台東市', '池上']
        ]
    ];
    
    if (function_exists('nineo_render_form')) {
        return nineo_render_form('charter', 'booking-form', $data);
    }
    
    return '<p>' . __('包車預約表單載入失敗', '9o-booking') . '</p>';
}

/**
 * 渲染熱門路線短代碼
 */
function nineo_charter_render_popular_routes($atts) {
    $atts = shortcode_atts([
        'limit' => '6',
        'style' => 'grid'
    ], $atts);
    
    $routes = nineo_charter_get_popular_routes();
    $data = [
        'routes' => array_slice($routes, 0, intval($atts['limit'])),
        'display_style' => $atts['style']
    ];
    
    if (function_exists('nineo_render_component')) {
        return nineo_render_component('popular-routes', $data);
    }
    
    return '<p>' . __('熱門路線載入失敗', '9o-booking') . '</p>';
}

/**
 * 渲染價格計算器短代碼
 */
function nineo_charter_render_price_calculator($atts) {
    $atts = shortcode_atts([
        'standalone' => 'false'
    ], $atts);
    
    $data = [
        'standalone' => $atts['standalone'] === 'true',
        'max_days' => 7,
        'base_prices' => [
            'north_day1' => CHARTER_NORTH_DAY1,
            'north_day2plus' => CHARTER_NORTH_DAY2PLUS,
            'south_day1' => CHARTER_SOUTH_DAY1,
            'south_day2plus' => CHARTER_SOUTH_DAY2PLUS,
            'mountain_surcharge' => MOUNTAIN_SURCHARGE
        ]
    ];
    
    if (function_exists('nineo_render_form')) {
        return nineo_render_form('charter', 'price-calculator', $data);
    }
    
    return '<p>' . __('價格計算器載入失敗', '9o-booking') . '</p>';
}

/**
 * 渲染完整預約頁面
 */
function nineo_charter_render_booking_page($data = []) {
    $default_data = [
        'page_title' => __('包車旅遊預約服務', '9o-booking'),
        'page_description' => __('舒適九人座車輛，專業司機陪同，帶您暢遊台灣美景', '9o-booking'),
        'contact_phone' => get_option('9o_contact_phone', '+886xxxxxxxxx'),
        'contact_email' => get_option('9o_contact_email', 'service@9ovanstrip.com'),
        'service_features' => [
            [
                'icon' => '🚐',
                'title' => __('舒適車輛', '9o-booking'),
                'description' => __('九人座豪華車輛，寬敞舒適，適合家庭或團體旅遊', '9o-booking')
            ],
            [
                'icon' => '👨‍✈️',
                'title' => __('專業司機', '9o-booking'),
                'description' => __('經驗豐富的專業司機，熟悉全台路況和景點', '9o-booking')
            ],
            [
                'icon' => '🗺️',
                'title' => __('客製行程', '9o-booking'),
                'description' => __('可依您的需求量身規劃行程，彈性安排景點', '9o-booking')
            ],
            [
                'icon' => '🏔️',
                'title' => __('山區專業', '9o-booking'),
                'description' => __('提供山區旅遊服務，安全駕駛技術值得信賴', '9o-booking')
            ]
        ],
        'popular_routes' => nineo_charter_get_popular_routes()
    ];
    
    $data = array_merge($default_data, $data);
    
    if (function_exists('nineo_render_page')) {
        return nineo_render_page('charter', 'charter-booking-page', $data);
    }
    
    return '<p>' . __('頁面載入失敗', '9o-booking') . '</p>';
}

/**
 * 取得熱門路線
 */
function nineo_charter_get_popular_routes() {
    return [
        [
            'title' => __('台北 → 清境農場', '9o-booking'),
            'duration' => __('2日遊', '9o-booking'),
            'highlights' => ['武嶺', '合歡山', '清境農場'],
            'price' => 'NT$ 25,000',
            'image_icon' => '🏔️',
            'is_mountain' => true
        ],
        [
            'title' => __('台北 → 阿里山', '9o-booking'),
            'duration' => __('2日遊', '9o-booking'), 
            'highlights' => ['阿里山日出', '神木群', '森林鐵路'],
            'price' => 'NT$ 28,000',
            'image_icon' => '🌄',
            'is_mountain' => true
        ],
        [
            'title' => __('台北 → 花蓮太魯閣', '9o-booking'),
            'duration' => __('3日遊', '9o-booking'),
            'highlights' => ['太魯閣國家公園', '七星潭', '清水斷崖'],
            'price' => 'NT$ 42,000',
            'image_icon' => '🏞️',
            'is_mountain' => false
        ],
        [
            'title' => __('台北 → 台東知本', '9o-booking'),
            'duration' => __('3日遊', '9o-booking'),
            'highlights' => ['知本溫泉', '台東市區', '池上便當'],
            'price' => 'NT$ 45,000',
            'image_icon' => '♨️',
            'is_mountain' => false
        ],
        [
            'title' => __('台北 → 墾丁', '9o-booking'),
            'duration' => __('3日遊', '9o-booking'),
            'highlights' => ['墾丁國家公園', '鵝鑾鼻燈塔', '白沙灣'],
            'price' => 'NT$ 42,000',
            'image_icon' => '🏖️',
            'is_mountain' => false
        ],
        [
            'title' => __('台北 → 日月潭', '9o-booking'),
            'duration' => __('2日遊', '9o-booking'),
            'highlights' => ['日月潭', '九族文化村', '埔里酒廠'],
            'price' => 'NT$ 24,000',
            'image_icon' => '🌊',
            'is_mountain' => false
        ]
    ];
}

/**
 * 取得模組資訊
 */
function nineo_get_charter_module_info() {
    return [
        'name' => __('包車旅遊模組', '9o-booking'),
        'version' => '1.0.0',
        'description' => __('專業的包車旅遊預約功能，支援多日行程規劃', '9o-booking'),
        'author' => '9O Van Strip',
        'dependencies' => ['shared-components', 'google-maps']
    ];
}

/**
 * 模組健康檢查
 */
function nineo_charter_health_check() {
    global $nineo_charter_module_path;
    $issues = [];
    
    // 檢查必要檔案
    $required_files = [
        'php/CharterCalculator.php',
        'php/CharterAjaxHandlers.php', 
        'php/CharterPostType.php',
        'templates/forms/booking-form.html',
        'assets/css/charter.css',
        'assets/js/charter.js'
    ];
    
    foreach ($required_files as $file) {
        if (!file_exists($nineo_charter_module_path . $file)) {
            $issues[] = sprintf(__('缺少檔案: %s', '9o-booking'), $file);
        }
    }
    
    // 檢查 Google Maps API
    if (!defined('GOOGLE_MAPS_API_KEY') || empty(GOOGLE_MAPS_API_KEY)) {
        $issues[] = __('未設定 Google Maps API Key (需要 Elevation API)', '9o-booking');
    }
    
    // 檢查設定
    $required_constants = ['CHARTER_NORTH_DAY1', 'MOUNTAIN_SURCHARGE'];
    foreach ($required_constants as $constant) {
        if (!defined($constant)) {
            $issues[] = sprintf(__('未設定常數: %s', '9o-booking'), $constant);
        }
    }
    
    return empty($issues) ? ['status' => 'ok'] : ['status' => 'error', 'issues' => $issues];
}

// 提供健康檢查給模組載入器
add_filter('9o_module_health_charter', 'nineo_charter_health_check_filter');
function nineo_charter_health_check_filter($status) {
    $check = nineo_charter_health_check();
    return $check['status'];
}

/**
 * 取得山區關鍵字
 */
function nineo_charter_get_mountain_keywords() {
    global $NINEO_MOUNTAIN_KEYWORDS;
    
    if (!empty($NINEO_MOUNTAIN_KEYWORDS)) {
        return $NINEO_MOUNTAIN_KEYWORDS;
    }
    
    return [
        '武嶺', '合歡山', '阿里山', '太魯閣', '清境', '玉山',
        '雪山', '大雪山', '梨山', '福壽山', '溪頭', '杉林溪',
        '奧萬大', '觀霧', '藤枝', '茂林', '那瑪夏'
    ];
}

/**
 * 取得排除地區
 */
function nineo_charter_get_excluded_areas() {
    global $NINEO_EXCLUDED_AREAS;
    
    if (!empty($NINEO_EXCLUDED_AREAS)) {
        return $NINEO_EXCLUDED_AREAS;
    }
    
    return [
        '綠島', '蘭嶼', '澎湖', '金門', '馬祖', '小琉球'
    ];
}

// 除錯日誌
if (defined('NINEO_DEBUG_MODE') && NINEO_DEBUG_MODE) {
    error_log('[9O Booking] Charter Module loaded - Priority: 95');
}
