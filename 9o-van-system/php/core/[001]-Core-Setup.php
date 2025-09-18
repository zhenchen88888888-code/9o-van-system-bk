/**
 * Code Snippet: [001] 9O Booking - Core Setup
 * 
 * Code Snippets 設定:
 * - Title: [001] 9O Booking - Core Setup
 * - Description: 初始化 9O Booking System 核心常數和目錄結構
 * - Tags: 9o-booking, core, setup
 * - Priority: 1
 */

// 定義系統版本
if (!defined('NINEO_BOOKING_VERSION')) {
    define('NINEO_BOOKING_VERSION', '2.0.0');
    define('NINEO_BOOKING_DB_VERSION', '1.0.0');
}

// 定義系統路徑（適用於 Code Snippets 環境）
if (!defined('NINEO_BOOKING_PATH')) {
    // 使用 uploads 目錄來存放系統檔案
    $upload_dir = wp_upload_dir();
    $booking_base = $upload_dir['basedir'] . '/9o-booking-system/';
    $booking_url = $upload_dir['baseurl'] . '/9o-booking-system/';
    
    define('NINEO_BOOKING_PATH', $booking_base);
    define('NINEO_BOOKING_URL', $booking_url);
    
    // 定義除錯模式
    if (!defined('NINEO_DEBUG_MODE')) {
        define('NINEO_DEBUG_MODE', WP_DEBUG);
    }
}

// 建立必要的目錄結構
add_action('init', 'nineo_booking_create_directories', 1);
function nineo_booking_create_directories() {
    $directories = [
        NINEO_BOOKING_PATH,
        NINEO_BOOKING_PATH . 'templates/',
        NINEO_BOOKING_PATH . 'cache/',
        NINEO_BOOKING_PATH . 'logs/',
        NINEO_BOOKING_PATH . 'exports/'
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
            
            // 為敏感目錄加入 .htaccess 保護
            if (strpos($dir, 'logs') !== false || strpos($dir, 'cache') !== false) {
                $htaccess = $dir . '.htaccess';
                if (!file_exists($htaccess)) {
                    file_put_contents($htaccess, 'Deny from all');
                }
            }
        }
    }
}

// 註冊系統狀態檢查
add_action('admin_init', 'nineo_booking_system_check');
function nineo_booking_system_check() {
    // 檢查系統需求
    $issues = [];
    
    // PHP 版本檢查
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        $issues[] = sprintf('需要 PHP 7.4 或更高版本，目前版本：%s', PHP_VERSION);
    }
    
    // WordPress 版本檢查
    global $wp_version;
    if (version_compare($wp_version, '5.0', '<')) {
        $issues[] = sprintf('需要 WordPress 5.0 或更高版本，目前版本：%s', $wp_version);
    }
    
    // 必要函數檢查
    $required_functions = ['curl_init', 'json_encode', 'json_decode'];
    foreach ($required_functions as $func) {
        if (!function_exists($func)) {
            $issues[] = sprintf('需要啟用 %s 函數', $func);
        }
    }
    
    // 儲存系統狀態
    if (!empty($issues)) {
        update_option('9o_booking_system_issues', $issues);
    } else {
        delete_option('9o_booking_system_issues');
    }
}

// 顯示系統通知
add_action('admin_notices', 'nineo_booking_admin_notices');
function nineo_booking_admin_notices() {
    $issues = get_option('9o_booking_system_issues', []);
    
    if (!empty($issues) && current_user_can('manage_options')) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>9O Booking System 系統需求檢查：</strong></p>';
        echo '<ul>';
        foreach ($issues as $issue) {
            echo '<li>' . esc_html($issue) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
}

// 初始化系統（在 Code Snippets 中替代 activation hook）
add_action('admin_init', 'nineo_booking_initialize_system');
function nineo_booking_initialize_system() {
    // 檢查是否已初始化
    if (get_option('9o_booking_initialized', false)) {
        return;
    }
    
    // 建立預設選項
    add_option('9o_booking_version', NINEO_BOOKING_VERSION);
    add_option('9o_contact_phone', '+886xxxxxxxxx');
    add_option('9o_contact_email', 'service@9ovanstrip.com');
    add_option('9o_debug_mode', false);
    add_option('9o_enabled_modules', ['airport', 'charter']);
    
    // 建立資料表
    nineo_booking_create_tables();
    
    // 標記為已初始化
    update_option('9o_booking_initialized', true);
    
    // 清除重寫規則
    flush_rewrite_rules();
}

// 建立資料表
function nineo_booking_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // 預約日誌表
    $table_name = $wpdb->prefix . '9o_booking_logs';
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        booking_id bigint(20) NOT NULL,
        booking_type varchar(50) NOT NULL,
        action varchar(50) NOT NULL,
        user_id bigint(20) DEFAULT NULL,
        details longtext,
        ip_address varchar(45),
        user_agent text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY booking_id (booking_id),
        KEY booking_type (booking_type),
        KEY action (action),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // 更新資料庫版本
    update_option('9o_booking_db_version', NINEO_BOOKING_DB_VERSION);
}

// 資料庫版本檢查
add_action('plugins_loaded', 'nineo_booking_check_db_version', 1);
function nineo_booking_check_db_version() {
    $current_db_version = get_option('9o_booking_db_version', '0.0.0');
    
    if (version_compare($current_db_version, NINEO_BOOKING_DB_VERSION, '<')) {
        // 執行資料庫升級
        nineo_booking_create_tables();
    }
}

// 除錯日誌
if (defined('NINEO_DEBUG_MODE') && NINEO_DEBUG_MODE) {
    error_log('[9O Booking] Core Setup loaded - Priority: 5');
}
