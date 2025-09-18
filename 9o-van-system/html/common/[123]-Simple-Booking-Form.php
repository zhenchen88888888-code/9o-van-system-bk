/**
 * Code Snippet: [123] 9O Booking - Simple Booking Form HTML
 * 
 * Code Snippets 設定:
 * - Title: [123] 9O Booking - Simple Booking Form HTML
 * - Description: 簡易預約表單 HTML 模板（No-JS 後備）
 * - Tags: 9o-booking, html, template, simple, fallback
 * - Priority: 123
 * - Run snippet: Run snippet everywhere
 */

// 確保核心設定已載入
if (!defined('NINEO_BOOKING_VERSION')) {
    return;
}

// 註冊簡易預約表單HTML模板
add_action('nineo_register_html_templates', 'nineo_register_simple_form_template');
function nineo_register_simple_form_template() {
    
    $simple_form_html = '<!-- 簡易預約表單 - No-JavaScript 後備方案 -->
<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" class="simple-booking-form">
    <input type="hidden" name="action" value="simple_booking_submission">
    <input type="hidden" name="booking_type" value="{{ booking_type }}">
    ' . wp_nonce_field('simple_booking_nonce', 'simple_booking_nonce', true, false) . '
    
    <h3>簡易預約表單</h3>
    <p>請填寫基本資訊，我們將盡快與您聯繫</p>
    
    <div class="form-group">
        <label for="simple-name">姓名 <span class="required">*</span></label>
        <input type="text" id="simple-name" name="customer_name" required>
    </div>
    
    <div class="form-group">
        <label for="simple-phone">電話 <span class="required">*</span></label>
        <input type="tel" id="simple-phone" name="customer_phone" 
               placeholder="09XX-XXX-XXX" required>
    </div>
    
    <div class="form-group">
        <label for="simple-email">Email</label>
        <input type="email" id="simple-email" name="customer_email">
    </div>
    
    <!-- 機場接送相關欄位 -->
    <div class="airport-fields" style="display: none;">
        <div class="form-group">
            <label for="simple-date">預約日期 <span class="required">*</span></label>
            <input type="date" id="simple-date" name="booking_date" required>
        </div>
        
        <div class="form-group">
            <label for="simple-airport">機場</label>
            <select id="simple-airport" name="airport">
                <option value="">請選擇</option>
                <option value="TPE">桃園國際機場</option>
                <option value="TSA">台北松山機場</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="simple-service">服務類型</label>
            <select id="simple-service" name="service_type">
                <option value="">請選擇</option>
                <option value="pickup">接機</option>
                <option value="dropoff">送機</option>
                <option value="roundtrip">來回接送</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="simple-message">需求說明 <span class="required">*</span></label>
            <textarea id="simple-message" name="message" rows="4" 
                      placeholder="請說明您的機場接送需求（上車地址、時間等）" required></textarea>
        </div>
    </div>
    
    <!-- 包車旅遊相關欄位 -->
    <div class="charter-fields" style="display: none;">
        <div class="form-group">
            <label for="simple-days">旅遊天數 <span class="required">*</span></label>
            <select id="simple-days" name="trip_days" required>
                <option value="">請選擇</option>
                <option value="1">1天</option>
                <option value="2">2天</option>
                <option value="3">3天</option>
                <option value="4">4天</option>
                <option value="5">5天</option>
                <option value="6">6天</option>
                <option value="7">7天</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="simple-date-charter">出發日期 <span class="required">*</span></label>
            <input type="date" id="simple-date-charter" name="start_date" required>
        </div>
        
        <div class="form-group">
            <label for="simple-starting">出發地點</label>
            <input type="text" id="simple-starting" name="starting_point" 
                   placeholder="例如：台北、台中、高雄">
        </div>
        
        <div class="form-group">
            <label for="simple-message-charter">行程需求 <span class="required">*</span></label>
            <textarea id="simple-message-charter" name="message" rows="5" 
                      placeholder="請詳細說明您的包車旅遊需求（想去的景點、天數、人數等）" required></textarea>
        </div>
    </div>
    
    <button type="submit" class="btn-submit">提交預約需求</button>
    
    <small class="form-help">
        提交後我們將在24小時內與您聯繫確認詳細資訊
    </small>
</form>

<script>
// 根據 booking_type 顯示對應欄位
(function() {
    var bookingType = document.querySelector(\'input[name="booking_type"]\').value;
    var airportFields = document.querySelector(\'.airport-fields\');
    var charterFields = document.querySelector(\'.charter-fields\');
    
    if (bookingType === \'airport\' && airportFields) {
        airportFields.style.display = \'block\';
    } else if (bookingType === \'charter\' && charterFields) {
        charterFields.style.display = \'block\';
    }
})();
</script>';

    // 註冊到全域模板變數
    nineo_register_html_template('shared/components/simple-booking-form', $simple_form_html);
    
    if (defined('NINEO_DEBUG_MODE') && NINEO_DEBUG_MODE) {
        error_log('[9O Booking] Simple Form HTML template registered');
    }
}
