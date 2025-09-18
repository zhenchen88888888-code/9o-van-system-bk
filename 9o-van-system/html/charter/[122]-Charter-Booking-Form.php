/**
 * Code Snippet: [122] 9O Booking - Charter Booking Form HTML
 * 
 * Code Snippets 設定:
 * - Title: [122] 9O Booking - Charter Booking Form HTML
 * - Description: 包車旅遊預約表單 HTML 模板
 * - Tags: 9o-booking, html, template, charter
 * - Priority: 122
 * - Run snippet: Run snippet everywhere
 */

// 確保核心設定已載入
if (!defined('NINEO_BOOKING_VERSION')) {
    return;
}

// 註冊包車預約表單HTML模板
add_action('nineo_register_html_templates', 'nineo_register_charter_form_template');
function nineo_register_charter_form_template() {
    
    $charter_form_html = '<!-- 包車旅遊預約表單 HTML 模板 -->
<div id="charter-booking-app" class="booking-app charter-module" role="main" aria-label="包車旅遊預約系統">
    
    <!-- No-JavaScript 後備方案 -->
    <noscript>
        <div class="no-js-fallback">
            <h2>需要 JavaScript 支援</h2>
            <p>為了使用完整的多日行程規劃功能，請啟用瀏覽器的 JavaScript。</p>
            <p>或者您可以直接撥打我們的服務專線：<a href="tel:{{ contact_phone }}">{{ contact_phone }}</a></p>
            
            {{> shared/components/simple-booking-form }}
        </div>
    </noscript>
    
    <!-- 主要預約界面 -->
    <div class="js-booking-interface">
        <div class="booking-container">
            
            <!-- 表單容器 -->
            <form id="charter-booking-form" class="booking-form" 
                  role="form" aria-label="包車旅遊詳細預約表單" novalidate>
                
                <!-- 表單標題 -->
                <div class="form-header">
                    <h2>🚐 包車旅遊預約</h2>
                    <p>舒適九人座·專業司機·全台走透透</p>
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
                    
                    <!-- 旅遊天數選擇 -->
                    <div class="form-section" id="days-section">
                        <h3>📅 選擇旅遊天數</h3>
                        <div class="days-selection">
                            <div class="day-option">
                                <input type="radio" name="trip_days" value="1" id="day-1" required>
                                <label class="day-option-label" for="day-1">
                                    <span class="day-number">1</span>
                                    <span class="day-text">天</span>
                                    <small>單日遊</small>
                                </label>
                            </div>
                            <div class="day-option">
                                <input type="radio" name="trip_days" value="2" id="day-2">
                                <label class="day-option-label" for="day-2">
                                    <span class="day-number">2</span>
                                    <span class="day-text">天</span>
                                    <small>兩日遊</small>
                                </label>
                            </div>
                            <div class="day-option">
                                <input type="radio" name="trip_days" value="3" id="day-3">
                                <label class="day-option-label" for="day-3">
                                    <span class="day-number">3</span>
                                    <span class="day-text">天</span>
                                    <small>三日遊</small>
                                </label>
                            </div>
                            <div class="day-option">
                                <input type="radio" name="trip_days" value="4" id="day-4">
                                <label class="day-option-label" for="day-4">
                                    <span class="day-number">4</span>
                                    <span class="day-text">天</span>
                                    <small>深度遊</small>
                                </label>
                            </div>
                            <div class="day-option">
                                <input type="radio" name="trip_days" value="5" id="day-5">
                                <label class="day-option-label" for="day-5">
                                    <span class="day-number">5</span>
                                    <span class="day-text">天</span>
                                    <small>環島遊</small>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 行程規劃區 -->
                    <div class="form-section" id="itinerary-section">
                        <h3>🗺️ 行程規劃</h3>
                        <div id="daily-routes-container">
                            <!-- 動態產生每日行程 -->
                        </div>
                    </div>
                    
                    <!-- 出發資訊 -->
                    <div class="form-section" id="departure-section">
                        <h3>🏃 出發資訊</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="start_date">出發日期 <span class="required">*</span></label>
                                <input type="date" id="start_date" name="start_date" required>
                            </div>
                            <div class="form-group">
                                <label for="start_time">出發時間 <span class="required">*</span></label>
                                <input type="time" id="start_time" name="start_time" value="08:00" required>
                                <small class="form-help">建議早上8點出發</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="starting_point">出發地點 <span class="required">*</span></label>
                                <input type="text" id="starting_point" name="starting_point" 
                                       placeholder="請輸入完整地址" required autocomplete="street-address">
                                <div class="address-suggestions" id="starting-suggestions"></div>
                                <small class="form-help">例如：台北車站、高雄市政府等</small>
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
                                <small class="form-help">填寫 Email 可收到行程確認信</small>
                            </div>
                            <div class="form-group">
                                <label for="passenger_count">乘客人數</label>
                                <select id="passenger_count" name="passenger_count">
                                    <option value="1">1人</option>
                                    <option value="2">2人</option>
                                    <option value="3">3人</option>
                                    <option value="4">4人</option>
                                    <option value="5">5人</option>
                                    <option value="6">6人</option>
                                    <option value="7">7人</option>
                                    <option value="8">8人</option>
                                    <option value="9">9人（滿載）</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="special_requirements">特殊需求</label>
                                <textarea id="special_requirements" name="special_requirements" 
                                          rows="3" placeholder="兒童座椅、輪椅服務、素食需求、特殊景點要求..."></textarea>
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
    nineo_register_html_template('charter/forms/booking-form', $charter_form_html);
    
    if (defined('NINEO_DEBUG_MODE') && NINEO_DEBUG_MODE) {
        error_log('[9O Booking] Charter HTML template registered');
    }
}
