/**
 * Code Snippet: [003] 9O Booking - Security Headers
 * 
 * Code Snippets 設定:
 * - Title: [003] 9O Booking - Security Headers
 * - Description: 添加HTTP安全標頭以提升網站安全性
 * - Tags: 9o-booking, security, headers
 * - Priority: 3
 * - Run snippet: Run snippet everywhere
 */

// 確保在其他代碼之前載入
if (!defined('NINEO_BOOKING_VERSION')) {
    return;
}

add_action('init', function() {
    // 確保在所有輸出前執行
    ob_start(function($buffer) {
        header_remove('Pragma');
        return $buffer;
    });
}, 1);

/**
 * 移除不需要的標頭並添加安全標頭
 */
add_action('send_headers', 'nineo_add_security_headers');
function nineo_add_security_headers() {
    if (!headers_sent()) {
        // 移除可能洩露資訊的標頭
        header_remove('X-Powered-By');
        header_remove('Server');
	header_remove('Pragma');
        
        // 防止 MIME 類型嗅探攻擊
        header('X-Content-Type-Options: nosniff');
        
        // 使用 CSP 取代 X-Frame-Options（更強大且一致）
        $csp_directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://maps.googleapis.com https://www.google.com https://www.gstatic.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data: https: http:",
            "connect-src 'self' https://maps.googleapis.com",
            "frame-ancestors 'self'",  // 取代 X-Frame-Options
            "frame-src 'self' https://www.google.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'"
        ];
        
        header('Content-Security-Policy: ' . implode('; ', $csp_directives));
        
        // 其他安全標頭
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        
        // 僅在HTTPS時添加HSTS
        if (is_ssl()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }
}

/**
 * 更早期移除 X-Powered-By（透過 ini 設定）
 */
add_action('init', 'nineo_remove_powered_by_early', 1);
function nineo_remove_powered_by_early() {
    if (function_exists('ini_set')) {
        ini_set('expose_php', 'off');
    }
}

/**
 * 為AJAX請求添加安全標頭
 */
add_action('wp_ajax_*', 'nineo_ajax_security_headers', 1);
add_action('wp_ajax_nopriv_*', 'nineo_ajax_security_headers', 1);
function nineo_ajax_security_headers() {
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Robots-Tag: noindex, nofollow');
    }
}

/**
 * 為預約系統相關的AJAX請求添加特定標頭
 */
add_action('wp_ajax_airport_calculate_price', 'nineo_booking_ajax_headers', 1);
add_action('wp_ajax_nopriv_airport_calculate_price', 'nineo_booking_ajax_headers', 1);
add_action('wp_ajax_charter_calculate_price', 'nineo_booking_ajax_headers', 1);
add_action('wp_ajax_nopriv_charter_calculate_price', 'nineo_booking_ajax_headers', 1);
add_action('wp_ajax_airport_submit_booking', 'nineo_booking_ajax_headers', 1);
add_action('wp_ajax_nopriv_airport_submit_booking', 'nineo_booking_ajax_headers', 1);
add_action('wp_ajax_charter_submit_booking', 'nineo_booking_ajax_headers', 1);
add_action('wp_ajax_nopriv_charter_submit_booking', 'nineo_booking_ajax_headers', 1);

function nineo_booking_ajax_headers() {
    if (!headers_sent()) {
	header_remove('Pragma');
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-cache, must-revalidate, max-age=0');
    }
}

/**
 * 針對預約頁面的額外安全設定
 */
add_action('wp_head', 'nineo_booking_page_security', 1);
function nineo_booking_page_security() {
    // 在預約頁面添加額外的安全 meta 標籤
    if (is_page() && (has_shortcode(get_the_content(), 'airport_booking') || 
                      has_shortcode(get_the_content(), 'charter_booking'))) {
        echo '<meta name="robots" content="noindex, nofollow">' . "\n";
        echo '<meta name="referrer" content="strict-origin-when-cross-origin">' . "\n";
        // CSP 已在 HTTP 標頭中設定，不需要重複
    }
}

/**
 * 清理和驗證文件上傳（如果有的話）
 */
add_filter('wp_check_filetype_and_ext', 'nineo_secure_file_upload', 10, 4);
function nineo_secure_file_upload($data, $file, $filename, $mimes) {
    // 如果是預約系統相關的上傳，添加額外驗證
    if (strpos($filename, '9o-booking') !== false) {
        // 限制文件類型
        $allowed_types = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        $file_ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (!in_array(strtolower($file_ext), $allowed_types)) {
            $data['ext'] = false;
            $data['type'] = false;
        }
    }
    
    return $data;
}

// 除錯日誌
if (defined('NINEO_DEBUG_MODE') && NINEO_DEBUG_MODE) {
    add_action('wp_footer', 'nineo_security_debug_info');
    function nineo_security_debug_info() {
        if (current_user_can('manage_options')) {
            echo "<!-- 9O Booking Security Headers loaded -->\n";
        }
    }
    
    error_log('[9O Booking] Security Headers loaded - Priority: 3');
}
