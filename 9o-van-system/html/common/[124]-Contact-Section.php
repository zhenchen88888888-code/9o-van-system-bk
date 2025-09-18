/**
 * Code Snippet: [124] 9O Booking - Contact Section HTML
 * 
 * Code Snippets 設定:
 * - Title: [124] 9O Booking - Contact Section HTML
 * - Description: 聯絡資訊區塊 HTML 模板
 * - Tags: 9o-booking, html, template, contact
 * - Priority: 124
 * - Run snippet: Run snippet everywhere
 */

// 確保核心設定已載入
if (!defined('NINEO_BOOKING_VERSION')) {
    return;
}

// 註冊聯絡資訊區塊HTML模板
add_action('nineo_register_html_templates', 'nineo_register_contact_section_template');
function nineo_register_contact_section_template() {
    
    $contact_section_html = '<!-- 聯絡資訊區塊 - 共用組件 -->
<section class="contact-section">
    <div class="container">
        <h2>聯絡我們</h2>
        <div class="contact-grid">
            <div class="contact-item">
                <div class="contact-icon">📞</div>
                <h3>服務專線</h3>
                <p><a href="tel:{{ contact_phone }}">{{ contact_phone }}</a></p>
                <small>24小時客服支援</small>
            </div>
            
            <div class="contact-item">
                <div class="contact-icon">📧</div>
                <h3>Email</h3>
                <p><a href="mailto:{{ contact_email }}">{{ contact_email }}</a></p>
                <small>一般諮詢與意見回饋</small>
            </div>
            
            <div class="contact-item">
                <div class="contact-icon">💬</div>
                <h3>LINE客服</h3>
                <p><a href="https://line.me/ti/p/@9ovanstrip" target="_blank">@9ovanstrip</a></p>
                <small>即時線上客服</small>
            </div>
        </div>
    </div>
</section>';

    // 註冊到全域模板變數
    nineo_register_html_template('shared/components/contact-section', $contact_section_html);
    
    if (defined('NINEO_DEBUG_MODE') && NINEO_DEBUG_MODE) {
        error_log('[9O Booking] Contact Section HTML template registered');
    }
}
