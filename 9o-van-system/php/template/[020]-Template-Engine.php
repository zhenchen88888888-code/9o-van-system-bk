/**
 * Code Snippet: [020] 9O Booking - Template Engine (更新版)
 * 
 * Code Snippets 設定:
 * - Title: [020] 9O Booking - Template Engine (更新版)
 * - Description: HTML 模板管理系統，支援從Code Snippets載入模板
 * - Tags: 9o-booking, template, engine, updated
 * - Priority: 20
 * - Run snippet: Run snippet everywhere
 */

// 確保核心設定已載入
if (!defined('NINEO_BOOKING_VERSION')) {
    error_log('[9O Booking] Template Engine: Core Setup not loaded');
    return;
}

// 全域變數儲存模板路徑和記憶體模板
global $nineo_template_paths, $nineo_html_templates;
$nineo_template_paths = [];
$nineo_html_templates = [];

/**
 * 註冊模板路徑
 */
function nineo_register_template_path($namespace, $path) {
    global $nineo_template_paths;
    $nineo_template_paths[$namespace] = trailingslashit($path);
}

/**
 * 註冊記憶體中的HTML模板
 */
function nineo_register_html_template($template_path, $html_content) {
    global $nineo_html_templates;
    $nineo_html_templates[$template_path] = $html_content;
}

/**
 * 載入 HTML 模板
 * 
 * @param string $template 模板路徑 (例如: 'airport/forms/booking-form')
 * @param array $data 傳入模板的資料
 * @param string $namespace 命名空間 (預設: modules)
 * @return string 渲染後的 HTML
 */
function nineo_load_template($template, $data = [], $namespace = 'modules') {
    $html = nineo_get_template_content($template, $namespace);
    
    if (!$html) {
        return "<!-- Template not found: {$template} -->";
    }
    
    // 處理組件引入 {{> component_path }}
    $html = nineo_process_template_includes($html, $data);
    
    // 處理變數替換 {{ variable }}
    $html = nineo_process_template_variables($html, $data);
    
    return $html;
}

/**
 * 取得模板內容（優先從記憶體，再從文件系統）
 */
function nineo_get_template_content($template, $namespace) {
    global $nineo_html_templates;
    
    // 1. 首先檢查記憶體中的模板
    if (isset($nineo_html_templates[$template])) {
        return $nineo_html_templates[$template];
    }
    
    // 2. 檢查文件系統
    $template_file = nineo_find_template_file($template, $namespace);
    if ($template_file && file_exists($template_file)) {
        return file_get_contents($template_file);
    }
    
    // 3. 允許其他插件提供模板
    $content = apply_filters('nineo_template_content', '', $template, $namespace);
    if (!empty($content)) {
        return $content;
    }
    
    return false;
}

/**
 * 尋找模板檔案
 */
function nineo_find_template_file($template, $namespace) {
    global $nineo_template_paths;
    
    $base_path = isset($nineo_template_paths[$namespace]) ? $nineo_template_paths[$namespace] : '';
    if (!$base_path) {
        return false;
    }
    
    $template_file = $base_path . $template . '.html';
    return file_exists($template_file) ? $template_file : false;
}

/**
 * 處理組件引入 {{> component_path }}
 */
function nineo_process_template_includes($html, $data) {
    return preg_replace_callback(
        '/\{\{>\s*([^}]+)\s*\}\}/',
        function($matches) use ($data) {
            $include_path = trim($matches[1]);
            return nineo_load_template($include_path, $data, 'modules');
        },
        $html
    );
}

/**
 * 處理變數替換 {{ variable }}
 */
function nineo_process_template_variables($html, $data) {
    return preg_replace_callback(
        '/\{\{\s*([^}]+)\s*\}\}/',
        function($matches) use ($data) {
            $variable = trim($matches[1]);
            
            // 處理條件語句 {{ if variable }}
            if (strpos($variable, 'if ') === 0) {
                $condition_var = trim(substr($variable, 3));
                return isset($data[$condition_var]) && $data[$condition_var] ? '' : '<!-- if-start -->';
            }
            
            // 處理結束條件 {{ endif }}
            if ($variable === 'endif') {
                return '<!-- if-end -->';
            }
            
            // 處理迴圈 {{ airports }} ... {{ endairports }}
            if (strpos($variable, 'end') === 0) {
                return '<!-- loop-end -->';
            }
            
            // 一般變數替換
            return isset($data[$variable]) ? esc_html($data[$variable]) : '';
        },
        $html
    );
}

/**
 * 處理條件區塊和迴圈
 */
function nineo_process_template_logic($html, $data) {
    // 處理條件區塊
    $html = preg_replace_callback(
        '/<!-- if-start -->(.*?)<!-- if-end -->/s',
        function($matches) {
            return ''; // 如果條件為false，移除整個區塊
        },
        $html
    );
    
    return $html;
}

/**
 * 渲染表單組件
 */
function nineo_render_form($module, $form_name, $data = []) {
    return nineo_load_template("{$module}/forms/{$form_name}", $data);
}

/**
 * 渲染頁面模板
 */
function nineo_render_page($module, $page_name, $data = []) {
    return nineo_load_template("{$module}/pages/{$page_name}", $data);
}

/**
 * 渲染共用組件
 */
function nineo_render_component($component_name, $data = []) {
    return nineo_load_template("shared/components/{$component_name}", $data);
}

/**
 * 初始化模板引擎
 */
add_action('init', 'nineo_template_engine_init', 15);
function nineo_template_engine_init() {
    // 註冊預設模板路徑（如果存在文件系統）
    if (defined('NINEO_BOOKING_PATH')) {
        nineo_register_template_path('modules', NINEO_BOOKING_PATH . 'modules/');
        nineo_register_template_path('core', NINEO_BOOKING_PATH . 'core/templates/');
    }
    
    // 讓其他模組可以註冊HTML模板
    do_action('nineo_register_html_templates');
}

/**
 * 取得已註冊的模板列表
 */
function nineo_get_registered_templates() {
    global $nineo_html_templates;
    return array_keys($nineo_html_templates);
}

/**
 * 檢查模板是否存在
 */
function nineo_template_exists($template, $namespace = 'modules') {
    global $nineo_html_templates;
    
    // 檢查記憶體模板
    if (isset($nineo_html_templates[$template])) {
        return true;
    }
    
    // 檢查文件系統
    $template_file = nineo_find_template_file($template, $namespace);
    return $template_file && file_exists($template_file);
}

// 除錯日誌
if (defined('NINEO_DEBUG_MODE') && NINEO_DEBUG_MODE) {
    error_log('[9O Booking] Template Engine (Updated) loaded - Priority: 20');
}

// 提供除錯資訊
add_action('wp_footer', 'nineo_template_debug_info');
function nineo_template_debug_info() {
    if (defined('NINEO_DEBUG_MODE') && NINEO_DEBUG_MODE && current_user_can('manage_options')) {
        global $nineo_html_templates;
        echo "<!-- 9O Booking Templates Debug: ";
        echo "Registered templates: " . implode(', ', array_keys($nineo_html_templates));
        echo " -->";
    }
}
