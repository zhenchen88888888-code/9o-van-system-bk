/**
 * Code Snippet: [015] 9O Booking - Module Loader
 * 
 * Code Snippets 設定:
 * - Title: [015] 9O Booking - Module Loader
 * - Description: 核心模組管理系統，負責載入所有業務模組
 * - Tags: 9o-booking, module, loader
 * - Priority: 15
 * - Run snippet: Run snippet everywhere
 */

// 確保核心設定已載入
if (!defined('NINEO_BOOKING_VERSION')) {
    error_log('[9O Booking] Module Loader: Core Setup not loaded');
    return;
}

// 全域變數儲存模組資訊
global $nineo_loaded_modules, $nineo_module_configs;
$nineo_loaded_modules = [];
$nineo_module_configs = [];

/**
 * 初始化模組載入器
 */
add_action('plugins_loaded', 'nineo_module_loader_init', 5);
function nineo_module_loader_init() {
    // 註冊模組配置
    nineo_register_modules();
    
    // 設定 hooks
    add_action('init', 'nineo_load_modules', 5);
    add_action('wp_enqueue_scripts', 'nineo_enqueue_shared_assets', 5);
    add_action('admin_init', 'nineo_module_admin_init');
}

/**
 * 註冊所有可用模組
 */
function nineo_register_modules() {
    global $nineo_module_configs;
    
    $nineo_module_configs = [
        'shared' => [
            'name' => '共用組件',
            'path' => NINEO_BOOKING_PATH . 'modules/shared/',
            'required' => true,
            'dependencies' => [],
            'load_order' => 1
        ],
        'airport' => [
            'name' => '機場接送模組',
            'path' => NINEO_BOOKING_PATH . 'modules/airport/',
            'required' => false,
            'dependencies' => ['shared'],
            'load_order' => 10
        ],
        'charter' => [
            'name' => '包車旅遊模組',
            'path' => NINEO_BOOKING_PATH . 'modules/charter/',
            'required' => false,
            'dependencies' => ['shared'],
            'load_order' => 10
        ]
    ];
}

/**
 * 載入所有模組
 */
function nineo_load_modules() {
    global $nineo_module_configs;
    
    // 按照載入順序排序
    uasort($nineo_module_configs, function($a, $b) {
        return $a['load_order'] <=> $b['load_order'];
    });
    
    foreach ($nineo_module_configs as $module_id => $config) {
        nineo_load_module($module_id, $config);
    }
    
    // 載入完成後的處理
    nineo_after_modules_loaded();
}

/**
 * 載入單一模組
 */
function nineo_load_module($module_id, $config) {
    global $nineo_loaded_modules;
    
    // 檢查相依性
    if (!nineo_check_module_dependencies($module_id, $config)) {
        error_log("[9O Booking] Module '{$module_id}' dependencies not met");
        return false;
    }
    
    // 檢查模組路徑
    if (!is_dir($config['path'])) {
        if ($config['required']) {
            error_log("[9O Booking] Required module '{$module_id}' path not found: {$config['path']}");
        }
        return false;
    }
    
    try {
        // 載入共用模組
        if ($module_id === 'shared') {
            nineo_load_shared_module($config);
        } else {
            // 載入業務模組
            nineo_load_business_module($module_id, $config);
        }
        
        $nineo_loaded_modules[$module_id] = $config;
        
        do_action("9o_module_loaded_{$module_id}", $config);
        
        return true;
        
    } catch (Exception $e) {
        error_log("[9O Booking] Failed to load module '{$module_id}': " . $e->getMessage());
        return false;
    }
}

/**
 * 載入共用模組
 */
function nineo_load_shared_module($config) {
    global $nineo_loaded_modules;
    
    // 共用模組主要是資源文件，不需要載入 PHP 類別
    // 註冊模板路徑
    if (function_exists('nineo_register_template_path')) {
        nineo_register_template_path('shared', $config['path'] . 'templates/');
    }
    
    // 記錄共用模組已載入
    $nineo_loaded_modules['shared'] = $config;
}

/**
 * 載入業務模組
 */
function nineo_load_business_module($module_id, $config) {
    // 機場和包車模組會在優先順序 90 和 95 載入
    // 這裡只做標記
    error_log("[9O Booking] Business module '{$module_id}' registered, will be loaded at priority " . 
              ($module_id === 'airport' ? '90' : '95'));
}

/**
 * 檢查模組相依性
 */
function nineo_check_module_dependencies($module_id, $config) {
    global $nineo_loaded_modules;
    
    if (empty($config['dependencies'])) {
        return true;
    }
    
    foreach ($config['dependencies'] as $dependency) {
        if (!isset($nineo_loaded_modules[$dependency])) {
            return false;
        }
    }
    
    return true;
}

/**
 * 模組載入完成後的處理
 */
function nineo_after_modules_loaded() {
    global $nineo_loaded_modules;
    
    // 觸發所有模組載入完成事件
    do_action('9o_all_modules_loaded', $nineo_loaded_modules);
    
    // 設定模組間的整合
    nineo_setup_module_integrations();
}

/**
 * 設定模組間的整合
 */
function nineo_setup_module_integrations() {
    // 共享配置給所有模組
    $shared_config = [
        'google_maps_api_key' => defined('GOOGLE_MAPS_API_KEY') ? GOOGLE_MAPS_API_KEY : '',
        'contact_phone' => get_option('9o_contact_phone', '+886xxxxxxxxx'),
        'contact_email' => get_option('9o_contact_email', 'service@9ovanstrip.com'),
        'debug_mode' => get_option('9o_debug_mode', false),
        'plugin_version' => NINEO_BOOKING_VERSION
    ];
    
    // 將配置傳遞給 JavaScript
    add_action('wp_footer', function() use ($shared_config) {
        echo '<script>';
        echo 'window.nineoBookingConfig = ' . wp_json_encode($shared_config) . ';';
        echo '</script>';
    });
}

/**
 * 載入共用前端資源
 */
function nineo_enqueue_shared_assets() {
    if (!nineo_is_booking_page()) {
        return;
    }
    
    $shared_url = nineo_get_module_url('shared');
    
    // 載入共用 CSS
    wp_enqueue_style(
        'shared-components-css',
        $shared_url . 'assets/css/common.css',
        [],
        NINEO_BOOKING_VERSION
    );
    
    wp_enqueue_style(
        'booking-components-css',
        $shared_url . 'assets/css/components.css',
        ['shared-components-css'],
        NINEO_BOOKING_VERSION
    );
    
    // 載入共用 JavaScript
    wp_enqueue_script(
        'shared-components-js',
        $shared_url . 'assets/js/common.js',
        ['jquery'],
        NINEO_BOOKING_VERSION,
        true
    );
    
    // 載入 Google Maps 相關 JavaScript
    if (defined('GOOGLE_MAPS_API_KEY') && !empty(GOOGLE_MAPS_API_KEY)) {
        wp_enqueue_script(
            'google-maps-api',
            'https://maps.googleapis.com/maps/api/js?key=' . GOOGLE_MAPS_API_KEY . '&libraries=places,geometry',
            [],
            null,
            true
        );
    }
}

/**
 * 取得模組 URL
 */
function nineo_get_module_url($module_id) {
    global $nineo_module_configs;
    
    if (!isset($nineo_module_configs[$module_id])) {
        return '';
    }
    
    $relative_path = str_replace(NINEO_BOOKING_PATH, '', $nineo_module_configs[$module_id]['path']);
    return NINEO_BOOKING_URL . $relative_path;
}

/**
 * 檢查是否為預約頁面
 */
function nineo_is_booking_page() {
    global $post;
    
    if (!is_object($post)) {
        return false;
    }
    
    // 檢查頁面模板
    if (is_page_template(['page-airport-booking.php', 'page-charter-booking.php'])) {
        return true;
    }
    
    // 檢查短代碼
    if (isset($post->post_content)) {
        if (has_shortcode($post->post_content, 'airport_booking_form') ||
            has_shortcode($post->post_content, 'charter_booking_form')) {
            return true;
        }
    }
    
    // 檢查頁面slug
    $booking_slugs = ['airport', 'charter', '機場', '包車', 'booking', '預約'];
    foreach ($booking_slugs as $slug) {
        if (strpos($post->post_name, $slug) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * 管理後台初始化
 */
function nineo_module_admin_init() {
    // 註冊模組管理設定
    register_setting('9o_module_settings', '9o_enabled_modules');
    register_setting('9o_module_settings', '9o_module_debug');
}

/**
 * 取得已載入的模組
 */
function nineo_get_loaded_modules() {
    global $nineo_loaded_modules;
    return $nineo_loaded_modules;
}

/**
 * 檢查模組是否已載入
 */
function nineo_is_module_loaded($module_id) {
    global $nineo_loaded_modules;
    return isset($nineo_loaded_modules[$module_id]);
}

/**
 * 取得模組資訊
 */
function nineo_get_module_info($module_id) {
    global $nineo_module_configs;
    return isset($nineo_module_configs[$module_id]) ? $nineo_module_configs[$module_id] : null;
}

/**
 * 取得所有模組狀態
 */
function nineo_get_modules_status() {
    global $nineo_module_configs, $nineo_loaded_modules;
    $status = [];
    
    foreach ($nineo_module_configs as $module_id => $config) {
        $is_loaded = isset($nineo_loaded_modules[$module_id]);
        $health_status = 'unknown';
        
        if ($is_loaded && $module_id === 'shared') {
            $health_status = 'ok';
        } elseif ($is_loaded) {
            // 業務模組會在各自的 Snippet 中處理健康檢查
            $health_status = apply_filters("9o_module_health_{$module_id}", 'ok');
        }
        
        $status[$module_id] = [
            'name' => $config['name'],
            'loaded' => $is_loaded,
            'required' => $config['required'],
            'health' => $health_status,
            'path_exists' => is_dir($config['path'])
        ];
    }
    
    return $status;
}

/**
 * 重新載入模組
 */
function nineo_reload_module($module_id) {
    global $nineo_module_configs, $nineo_loaded_modules;
    
    if (!isset($nineo_module_configs[$module_id])) {
        return false;
    }
    
    // 移除已載入的模組
    unset($nineo_loaded_modules[$module_id]);
    
    // 重新載入
    return nineo_load_module($module_id, $nineo_module_configs[$module_id]);
}

// 除錯日誌
if (defined('NINEO_DEBUG_MODE') && NINEO_DEBUG_MODE) {
    error_log('[9O Booking] Module Loader loaded - Priority: 30');
}
