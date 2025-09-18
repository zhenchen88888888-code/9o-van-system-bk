/**
 * Code Snippet: [121] 9O Booking - Airport Booking Form HTML
 * 
 * Code Snippets 設定:
 * - Title: [121] 9O Booking - Airport Booking Form HTML
 * - Description: 機場接送預約表單 HTML 模板
 * - Tags: 9o-booking, html, template, airport
 * - Priority: 121
 * - Run snippet: Run snippet everywhere
 */

// 確保核心設定已載入
if (!defined('NINEO_BOOKING_VERSION')) {
    return;
}

// 註冊機場預約表單HTML模板
add_action('nineo_register_html_templates', 'nineo_register_airport_form_template');
function nineo_register_airport_form_template() {
    
    $airport_form_html = '<!-- 機場接送預約表單 HTML 模板 -->
<div id="airport-booking-app" class="booking-app airport-module" role="main" aria-label="機場接送預約系統">
    
    <!-- No-JavaScript 後備方案 -->
    <noscript>
        <div class="no-js-fallback">
            <h2>需要 JavaScript 支援</h2>
            <p>為了使用完整的線上預約功能，請啟用瀏覽器的 JavaScript。</p>
            <p>或者您可以直接撥打我們的服務專線：<a href="tel:{{ contact_phone }}">{{ contact_phone }}</a></p>
            
            {{> shared/components/simple-booking-form }}
        </div>
    </noscript>
    
    <!-- 主要預約界面 -->
    <div class="js-booking-interface">
        <div class="booking-container">
            
            <!-- 表單容器 -->
            <form id="airport-booking-form" class="booking-form" 
                  role="form" aria-label="機場接送詳細預約表單" novalidate>
                
                <!-- 表單標題 -->
                <div class="form-header">
                    <h2>✈️ 機場接送預約</h2>
                    <p>專業司機·舒適乘坐·準時安全</p>
                </div>
                
                <!-- 載入狀態 -->
                <div class="form-loading" aria-live="polite">
                    <div class="loading-spinner">
                        <span class="spinner" aria-hidden="true"></span>
                        <span class="sr-only">載入預約表單中...</span>
                    </div>
                    <p>載入預約表單...</p>
                </div>
                
                <!-- 表單內容區 -->
                <div class="form-content" style="display: none;">
                    
                    <!-- 機場選擇 -->
                    <div class="form-section" id="airport-section">
                        <h3>🏢 選擇機場</h3>
                        <div class="airport-selection">
                            <div class="airport-option">
                                <input type="radio" name="airport" value="TPE" id="airport-TPE" required>
                                <label class="airport-label" for="airport-TPE">桃園國際機場</label>
                                <span class="airport-code">TPE</span>
                            </div>
                            <div class="airport-option">
                                <input type="radio" name="airport" value="TSA" id="airport-TSA" required>
                                <label class="airport-label" for="airport-TSA">台北松山機場</label>
                                <span class="airport-code">TSA</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 服務類型選擇 -->
                    <div class="form-section" id="service-type-section">
                        <h3>🚗 服務類型</h3>
                        <div class="service-type-selection">
                            <div class="service-type-option">
                                <input type="radio" name="service_type" value="pickup" id="service-pickup" required>
                                <label for="service-pickup">
                                    <span class="service-icon">🛬</span>
                                    接機
                                </label>
                            </div>
                            <div class="service-type-option">
                                <input type="radio" name="service_type" value="dropoff" id="service-dropoff">
                                <label for="service-dropoff">
                                    <span class="service-icon">🛫</span>
                                    送機
                                </label>
                            </div>
                            <div class="service-type-option">
                                <input type="radio" name="service_type" value="roundtrip" id="service-roundtrip">
                                <label for="service-roundtrip">
                                    <span class="service-icon">🔄</span>
                                    來回接送
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 地址資訊 -->
                    <div class="form-section" id="address-section">
                        <h3>📍 地址資訊</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="pickup_address">上車地址 <span class="required">*</span></label>
                                <input type="text" id="pickup_address" name="pickup_address" 
                                       placeholder="請輸入完整地址（含縣市區）" required
                                       autocomplete="street-address">
                                <div class="address-suggestions" id="pickup-suggestions"></div>
                                <small class="form-help">例如：台北市信義區市府路45號3樓</small>
                            </div>
                        </div>
                        
                        <div class="form-row" id="destination-row" style="display: none;">
                            <div class="form-group">
                                <label for="destination_address">目的地地址 <span class="required">*</span></label>
                                <input type="text" id="destination_address" name="destination_address" 
                                       placeholder="請輸入目的地地址" autocomplete="street-address">
                                <div class="address-suggestions" id="destination-suggestions"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 預約時間 -->
                    <div class="form-section" id="datetime-section">
                        <h3>📅 預約時間</h3>
                        <div class="time-selection">
                            <div class="form-group">
                                <label for="booking_date">日期 <span class="required">*</span></label>
                                <input type="date" id="booking_date" name="booking_date" required>
                            </div>
                            <div class="form-group">
                                <label for="booking_time">時間 <span class="required">*</span></label>
                                <input type="time" id="booking_time" name="booking_time" required>
                                <div class="night-surcharge-info" style="display: none;">
                                    <small>⚠️ 夜間時段 (22:00-08:00) 加收 NT$ 200</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 聯絡資訊 -->
                    <div class="form-section" id="customer-info-section">
                        <h3>👤 聯絡資訊</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="customer_name">姓名 <span class="required">*</span></label>
                                <input type="text" id="customer_name" name="customer_name" 
                                       required autocomplete="name">
                            </div>
                            <div class="form-group">
                                <label for="customer_phone">電話 <span class="required">*</span></label>
                                <input type="tel" id="customer_phone" name="customer_phone" 
                                       placeholder="09XX-XXX-XXX" required autocomplete="tel">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="customer_email">Email</label>
                                <input type="email" id="customer_email" name="customer_email"
                                       autocomplete="email">
                                <small class="form-help">填寫 Email 可收到預約確認信</small>
                            </div>
                            <div class="form-group">
                                <label for="passenger_count">乘客人數</label>
                                <select id="passenger_count" name="passenger_count">
                                    <option value="1">1人</option>
                                    <option value="2">2人</option>
                                    <option value="3">3人</option>
                                    <option value="4">4人</option>
                                    <option value="5">5人以上</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 提交區域 -->
                    <div class="form-section submit-section">
                        <div class="terms-agreement">
                            <input type="checkbox" id="agree_terms" name="agree_terms" required>
                            <label for="agree_terms">
                                我已閱讀並同意服務條款
                            </label>
                        </div>
                        <button type="submit" class="btn-submit" id="submit-booking">
                            <span class="btn-text">確認預約</span>
                            <span class="btn-loading" style="display: none;">
                                <span class="spinner"></span> 處理中...
                            </span>
                        </button>
                    </div>
                </div>
                
            </form>
            
            <!-- 即時報價面板 -->
            {{> shared/components/price-panel }}
            
        </div>
    </div>
    
    <!-- 錯誤狀態顯示 -->
    <div id="booking-error" class="booking-error" style="display: none;" role="alert">
        <h3>載入發生問題</h3>
        <p>預約系統暫時無法載入，請嘗試重新整理頁面或使用以下方式聯繫我們：</p>
        <ul>
            <li>電話：<a href="tel:{{ contact_phone }}">{{ contact_phone }}</a></li>
            <li>Email：<a href="mailto:{{ contact_email }}">{{ contact_email }}</a></li>
        </ul>
    </div>
</div>';

    // 註冊到全域模板變數
    nineo_register_html_template('airport/forms/booking-form', $airport_form_html);
    
    if (defined('NINEO_DEBUG_MODE') && NINEO_DEBUG_MODE) {
        error_log('[9O Booking] Airport HTML template registered');
    }
}
