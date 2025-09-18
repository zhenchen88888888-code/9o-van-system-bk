add_action('wp_head', function() {
    global $post;
    $content = isset($post->post_content) ? $post->post_content : '';
    
    if (!is_page('包車旅遊預約') && !is_page('charter-booking') && !has_shortcode($content, 'charter_booking_form')) {
        return;
    }
    ?>
    
    <!-- 載入 Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD7m-umnnMHhXTi11_uvI14Rel6mCN1Dz4&libraries=places&language=zh-TW"></script>
    
    <style type="text/css">
    /* 包車旅遊系統 CSS V6 - 完整優化版 */
    :root {
        --primary: #94989C;
        --primary-dark: #7a7e82;
        --secondary: #A8A094;
        --danger: #dc2626;
        --warning: #f59e0b;
        --success: #16a34a;
        --border: #D4CFC4;
        --bg-light: #F5F2EE;
        --text-main: #3C3834;
        --text-sub: #5C5854;
        --accent: #C8C2B8;
        --radius: 8px;
        --shadow: 0 1px 3px rgba(148,152,156,0.1);
    }

    #charter-booking-app {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }

    .booking-container {
        display: grid;
        grid-template-columns: 2.5fr 1fr;
        gap: 30px;
    }

    #charter-booking-form {
        background: white;
        border-radius: 12px;
        box-shadow: var(--shadow);
        overflow: hidden;
        border: 1px solid var(--border);
    }

    .form-loading {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 500px;
        color: var(--text-sub);
        font-size: 18px;
    }

    .form-header {
        background: var(--primary);
        color: white;
        padding: 30px;
        text-align: center;
    }

    .form-header h2 {
        margin: 0 0 10px;
        font-size: 28px;
        font-weight: 600;
    }

    .form-header p {
        margin: 0;
        opacity: 0.95;
        font-size: 16px;
    }

    .form-section {
        padding: 25px;
        border-bottom: 1px solid #f0f0f0;
    }

    .form-section:last-child {
        border: none;
    }

    .form-section h3 {
        margin: 0 0 20px;
        font-size: 20px;
        color: var(--text-main);
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }

    .form-row.full-width {
        grid-template-columns: 1fr;
    }

    .form-row.triple {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        margin-bottom: 6px;
        color: var(--text-sub);
        font-size: 14px;
        font-weight: 500;
    }

    .form-group label .required {
        color: var(--danger);
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        padding: 10px 12px;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        font-size: 15px;
        transition: all 0.2s;
        min-height: 44px;
        width: 100%;
        box-sizing: border-box;
        background: white;
        -webkit-appearance: none;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(148,152,156,0.1);
    }

    /* 日期時間輸入優化 */
    input[type="date"],
    input[type="time"] {
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        -webkit-appearance: none;
        appearance: none;
    }

    .day-route-container {
        background: var(--bg-light);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 15px;
        border: 1px solid var(--border);
        transition: all 0.2s;
    }

    .day-route-container:hover {
        border-color: var(--primary);
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .day-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        flex-wrap: wrap;
        gap: 10px;
    }

    .day-title {
        font-size: 16px;
        font-weight: 600;
        color: var(--primary);
    }

    .day-badge {
        background: var(--primary);
        color: white;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 11px;
        display: inline-block;
        margin-left: 8px;
    }

    .day-date {
        color: var(--text-sub);
        font-size: 14px;
        white-space: nowrap;
    }

    .stops-container {
        margin-top: 12px;
    }

    .stop-item {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
        background: white;
        padding: 8px;
        border-radius: var(--radius);
        border: 1px solid var(--border);
    }

    .stop-item input {
        flex: 1;
        min-width: 0;
        border: none !important;
        padding: 8px !important;
        background: transparent !important;
    }

    .stop-controls {
        display: flex;
        gap: 4px;
    }

    .stop-btn {
        width: 32px;
        height: 32px;
        padding: 0;
        background: white;
        border: 1px solid var(--border);
        border-radius: 4px;
        color: var(--text-sub);
        cursor: pointer;
        font-size: 16px;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .stop-btn:hover:not(:disabled) {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    .stop-btn:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }

    .stop-btn.delete {
        color: var(--danger);
    }

    .stop-btn.delete:hover {
        background: var(--danger);
        color: white;
        border-color: var(--danger);
    }

    .address-input-wrapper {
        position: relative;
    }

    .address-suggestions {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid var(--border);
        border-top: none;
        border-radius: 0 0 var(--radius) var(--radius);
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .address-suggestions.show {
        display: block;
    }

    .suggestion-item {
        padding: 10px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.2s;
    }

    .suggestion-item:hover {
        background: var(--bg-light);
    }

    .suggestion-main {
        font-weight: 500;
        color: var(--text-main);
    }

    .suggestion-secondary {
        font-size: 12px;
        color: var(--text-sub);
        margin-top: 2px;
    }

    /* 司機選項按鈕 - 參考圖片的卡片式設計 */
    .radio-group {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        max-width: 400px;
    }

    .radio-item {
        position: relative;
        background: white;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 12px 14px;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
    }

    .radio-item:hover {
        border-color: var(--primary);
        background: var(--bg-light);
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .radio-item input[type="radio"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }

    .radio-item label {
        display: block;
        cursor: pointer;
        font-size: 13px;
        color: var(--text-main);
        line-height: 1.4;
        margin: 0;
        font-weight: 500;
    }

    .radio-item label .price {
        display: block;
        font-size: 11px;
        color: var(--text-sub);
        margin-top: 4px;
        font-weight: 400;
    }

    .radio-item.selected {
        border-color: var(--primary);
        background: var(--bg-light);
        box-shadow: 0 0 0 2px rgba(148,152,156,0.15);
    }

    .radio-item.selected::after {
        content: "✓";
        position: absolute;
        top: 8px;
        right: 8px;
        width: 20px;
        height: 20px;
        background: var(--primary);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
    }

    .btn-add-stop {
        padding: 10px 16px;
        background: var(--secondary);
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s;
        font-weight: 500;
    }

    .btn-add-stop:hover {
        background: #968876;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .btn-submit {
        background: var(--primary);
        color: white;
        width: 100%;
        padding: 15px;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        margin-top: 20px;
        min-height: 50px;
    }

    .btn-submit:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(148,152,156,0.3);
    }

    .btn-submit:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .alert-box {
        padding: 12px 16px;
        border-radius: 6px;
        margin: 10px 0;
        font-size: 14px;
        line-height: 1.5;
        display: none;
    }

    .alert-box.show {
        display: block;
    }

    .mountain-alert {
        background: #fef3c7;
        border-left: 4px solid var(--warning);
        color: #92400e;
    }

    .excluded-alert {
        background: #fee2e2;
        border-left: 4px solid var(--danger);
        color: #991b1b;
    }

    .info-box, .safety-alert {
        background: var(--bg-light);
        border-left: 4px solid var(--secondary);
        padding: 14px 18px;
        border-radius: 6px;
        font-size: 14px;
        line-height: 1.6;
        color: var(--text-main);
        margin-top: 12px;
    }
    
    .safety-alert {
        background: rgba(245, 158, 11, 0.08);
        border-left-color: var(--warning);
        color: #92400e;
        margin-bottom: 15px;
    }
    
    .safety-alert strong {
        color: #b45309;
    }

	.email-notice {
		font-size: 12px;
		color: #6b7280;
		margin-top: 5px;
		font-style: italic;
		display: block;
	}
		   
    /* ========================================
       即時報價面板 V6
       ======================================== */
    #price-panel {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        padding: 0;
        position: sticky;
        top: 20px;
        height: fit-content;
        overflow: hidden;
        border: 1px solid var(--border);
    }

    #price-panel h3 {
        margin: 0;
        padding: 20px 25px;
        background: var(--primary);
        color: white;
        font-size: 18px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    #price-panel h3:before {
        content: "💰";
        font-size: 20px;
    }

    .price-content {
        padding: 20px 25px 25px;
        background: white;
    }

    .price-loading {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 150px;
        color: var(--text-sub);
        font-size: 14px;
    }

    /* 價格項目 */
    .price-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #f3f4f6;
        font-size: 14px;
        transition: all 0.2s;
    }

    .price-item:hover {
        padding-left: 5px;
    }

    .price-item span:first-child {
        color: var(--text-main);
        font-weight: 500;
    }

    .price-item span:last-child {
        color: var(--primary);
        font-weight: 600;
        white-space: nowrap;
    }

    /* 細節項目 */
    .price-item.detail {
        padding-left: 20px;
        font-size: 13px;
        border-bottom: 1px dashed #f9fafb;
    }

    .price-item.detail span:first-child {
        color: var(--text-sub);
        font-weight: 400;
    }

    .price-item.detail span:last-child {
        color: var(--text-sub);
    }

    /* 地區標記 */
    .price-region {
        background: #f3f4f6;
        padding: 10px 14px;
        margin: 8px 0;
        border-radius: 8px;
        font-size: 14px;
        color: var(--text-main);
        display: flex;
        align-items: center;
        gap: 6px;
        font-weight: 500;
    }

    .price-region:before {
        content: "📍";
        font-size: 16px;
    }

    .price-region.south {
        background: #fef3c7;
        color: #92400e;
    }

    /* 山區項目 */
    .price-item.mountain {
        background: #fef3c7;
        padding: 10px 14px;
        margin: 8px 0;
        border-radius: 8px;
        border-left: 3px solid var(--warning);
        border-bottom: none;
    }

    .price-item.mountain span:first-child {
        color: #92400e;
        font-weight: 600;
    }

    .price-item.mountain span:last-child {
        color: #92400e;
        font-weight: 700;
    }

    /* 總計區塊 - 優化文字大小 */
    .price-total {
        margin-top: 20px;
        padding: 16px 14px;
        background: var(--bg-light);
        border-radius: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: 2px solid var(--primary);
    }

    .price-total span:first-child {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-main);
        white-space: nowrap;
    }

    .price-total span:last-child {
        font-size: 24px;
        font-weight: bold;
        color: var(--primary);
        white-space: nowrap;
    }

    /* 訂金資訊 */
    .deposit-info {
        margin-top: 15px;
        padding: 15px;
        background: white;
        border-radius: 8px;
        border: 1px solid var(--border);
    }

    .deposit-item {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        font-size: 14px;
    }

    .deposit-item:first-child {
        border-bottom: 1px solid var(--border);
        padding-bottom: 12px;
    }

    .deposit-item:last-child {
        padding-top: 12px;
    }

    .deposit-item span:first-child {
        color: var(--text-sub);
    }

    .deposit-item span:last-child {
        font-weight: 600;
        color: var(--text-main);
    }

    /* 提醒框 */
    .notice-box {
        background: #fef3c7;
        border-left: 4px solid var(--warning);
        padding: 12px 15px;
        margin-top: 15px;
        border-radius: 6px;
        font-size: 13px;
        color: #92400e;
        line-height: 1.5;
    }

    .notice-box strong {
        color: #b45309;
    }

    .spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255,255,255,.3);
        border-radius: 50%;
        border-top-color: #fff;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* 響應式設計 */
    @media (max-width: 1024px) {
        .booking-container {
            grid-template-columns: 1fr;
        }
        
        #price-panel {
            position: relative;
            margin-top: 20px;
        }
    }

    @media (max-width: 768px) {
        #charter-booking-app {
            padding: 10px;
        }
        
        .form-section {
            padding: 15px;
        }
        
        .form-header {
            padding: 20px 15px;
        }
        
        .form-header h2 {
            font-size: 22px;
        }
        
        /* 表單列改為單欄 */
        .form-row,
        .form-row.triple {
            grid-template-columns: 1fr !important;
        }
        
        /* 手機版radio按鈕保持2列 */
        .radio-group {
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        
        .radio-item {
            padding: 10px 8px;
        }
        
        .radio-item label {
            font-size: 12px;
        }
        
        .day-route-container {
            padding: 15px;
        }
        
        /* 日期欄位優化 */
        .day-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .day-date {
            font-size: 13px;
            word-break: break-word;
        }
        
        .stop-item {
            padding: 6px;
            gap: 6px;
        }
        
        .stop-btn {
            width: 30px;
            height: 30px;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            font-size: 16px;
            padding: 12px;
            min-height: 48px;
        }
        
        /* 特別處理日期輸入 */
        input[type="date"],
        input[type="time"] {
            font-size: 16px;
        }
        
        .btn-add-stop {
            width: 100%;
            padding: 12px;
            font-size: 15px;
        }
        
        .btn-submit {
            padding: 16px;
            font-size: 17px;
            min-height: 54px;
        }
        
        /* 價格面板手機版調整 */
        #price-panel h3 {
            padding: 15px 20px;
            font-size: 16px;
        }
        
        .price-content {
            padding: 15px 20px 20px;
        }
        
        .price-total {
            padding: 15px 12px;
        }
        
        .price-total span:first-child {
            font-size: 12px;
        }
        
        .price-total span:last-child {
            font-size: 20px;
        }
    }

    @media (max-width: 380px) {
        .form-section {
            padding: 12px;
        }
        
        .form-section h3 {
            font-size: 18px;
        }
        
        .radio-item {
            padding: 8px 6px;
        }
        
        .radio-item label {
            font-size: 11px;
        }
        
        .stop-btn {
            width: 28px;
            height: 28px;
            font-size: 12px;
        }
        
        .form-group label {
            font-size: 13px;
        }
        
        .price-total span:last-child {
            font-size: 18px;
        }
    }
    </style>
    <?php
});

// ====================================
// 載入JavaScript
// ====================================
add_action('wp_footer', function() {
    global $post;
    $content = isset($post->post_content) ? $post->post_content : '';
    
    if (!is_page('包車旅遊預約') && !is_page('charter-booking') && !has_shortcode($content, 'charter_booking_form')) {
        return;
    }
    ?>
    
    <script type="text/javascript">
    (function($) {
        'use strict';
        
        // 等待jQuery載入
        if (typeof jQuery === 'undefined') {
            setTimeout(arguments.callee, 100);
            return;
        }
        
        $(document).ready(function() {
            // 全域配置
            const CONFIG = {
                ajaxUrl: '<?php echo admin_url("admin-ajax.php"); ?>',
                minDate: '<?php echo date("Y-m-d", strtotime("+2 days")); ?>',
                maxDays: 7,
                maxStops: 6
            };
            
            // 包車預約應用程式
            const CharterBooking = {
                // 狀態管理
                state: {
                    tripDays: 1,
                    dailyRoutes: [],
                    isSubmitting: false,
                    lastTotalPrice: 0,
                    calcTimeout: null,
                    mountainDetection: {}
                },
                
                // 初始化
                init() {
                    if (!$('#charter-booking-form').length) return false;
                    
                    this.buildForm();
                    this.bindEvents();
                    
                    // 延遲初始計算
                    setTimeout(() => this.calculatePrice(), 1000);
                    
                    return true;
                },
                
                // 建立表單
                buildForm() {
                    const formHTML = this.getFormTemplate();
                    $('#charter-booking-form').html(formHTML);
                    
                    this.updateDriverSection();
                    this.updateDailyRoutes();
                },
                
                // 表單模板
                getFormTemplate() {
                    return `
                        <div class="form-header">
                            <h2>🚐 包車旅遊預約</h2>
                            <p>專業司機·舒適九人座·全台走透透</p>
                        </div>
                        
                        ${this.getSectionTemplate('basic')}
                        ${this.getSectionTemplate('routes')}
                        ${this.getSectionTemplate('driver')}
                        ${this.getSectionTemplate('addon')}
                        ${this.getSectionTemplate('contact')}
                        
                        <button type="submit" class="btn-submit">提交預約</button>
                    `;
                },
                
                // 取得區塊模板
                getSectionTemplate(type) {
                    const templates = {
                        basic: `
                            <div class="form-section">
                                <h3>📅 基本資訊</h3>
                                <div class="form-row triple">
                                    <div class="form-group">
                                        <label>使用天數 <span class="required">*</span></label>
                                        <select name="trip_days" id="trip_days" required>
                                            ${[...Array(7)].map((_, i) => 
                                                `<option value="${i+1}">${i+1}天</option>`
                                            ).join('')}
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>出發日期 <span class="required">*</span></label>
                                        <input type="date" name="start_date" id="start_date" 
                                               min="${CONFIG.minDate}" value="${CONFIG.minDate}" required>
                                    </div>
                                    <div class="form-group">
                                        <label>出發時間 <span class="required">*</span></label>
                                        <input type="time" name="start_time" id="start_time" value="08:00" required>
                                    </div>
                                </div>
                                <div class="form-row full-width">
                                    <div class="form-group">
                                        <label>乘客人數 <span class="required">*</span></label>
                                        <input type="number" name="passengers" id="passengers" 
                                               min="1" max="8" value="4" required>
                                    </div>
                                </div>
                            </div>
                        `,
                        routes: `
                            <div class="form-section">
                                <h3>🗺️ 每日行程規劃</h3>
                                <div id="daily-routes-container"></div>
                            </div>
                        `,
                        driver: `
                            <div class="form-section" id="driver-section">
                                <h3>🏨 司機安排</h3>
                                <div class="form-row" id="driver-options"></div>
                                <div class="info-box" id="driver-info"></div>
                            </div>
                        `,
                        addon: `
                            <div class="form-section">
                                <h3>🔧 加購項目</h3>
                                <div class="safety-alert">
                                    根據《小型車附載幼童安全乘坐實施及宣導辦法》，<strong>4歲以下孩童皆需使用安全座椅</strong>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>嬰兒安全座椅 (每張NT$100)</label>
                                        <input type="number" name="child_seats" id="child_seats" min="0" max="4" value="0">
                                    </div>
                                    <div class="form-group">
                                        <label>兒童增高墊 (每張NT$100)</label>
                                        <input type="number" name="booster_seats" id="booster_seats" min="0" max="4" value="0">
                                    </div>
                                </div>
                            </div>
                        `,
                        contact: `
							<div class="form-section">
								<h3>📞 聯絡資訊</h3>
								<div class="form-row">
									<div class="form-group">
										<label>聯絡人姓名 <span class="required">*</span></label>
										<input type="text" name="customer_name" id="customer_name" 
											   placeholder="請輸入您的姓名" required>
									</div>
									<div class="form-group">
										<label>電話 <span class="required">*</span> (含國際區號)</label>
										<input type="tel" name="customer_phone" id="customer_phone" 
											   placeholder="+886912345678 或 0912345678" required
											   pattern="(\\+[0-9]{1,4})?[0-9]{9,15}"
											   title="請輸入有效電話號碼，可含國際區號">
									</div>
								</div>
								<div class="form-row full-width">
									<div class="form-group">
										<label>Email（選填）</label>
										<input type="email" name="customer_email" id="customer_email"
											   placeholder="example@email.com">
										<span class="email-notice">※ 未填寫將不會收到確認信件通知</span>
									</div>
								</div>
								<div class="form-row full-width">
									<div class="form-group">
										<label>備註說明</label>
										<textarea name="notes" id="notes" rows="3"></textarea>
									</div>
								</div>
							</div>
			`
                                          
                    };
                    return templates[type] || '';
                },
                
                // 更新司機區塊 - 卡片式按鈕設計
                updateDriverSection() {
                    const tripDays = parseInt($('#trip_days').val()) || 1;
                    const isMultiDay = tripDays > 1;
                    
                    const optionsHTML = `
                        ${isMultiDay ? `
                            <div class="form-group">
                                <label>司機住宿</label>
                                <div class="radio-group">
                                    <div class="radio-item">
                                        <input type="radio" name="driver_accommodation" value="self" id="accommodation_self" checked>
                                        <label for="accommodation_self">
                                            提供住宿
                                            <span class="price">由客戶安排</span>
                                        </label>
                                    </div>
                                    <div class="radio-item">
                                        <input type="radio" name="driver_accommodation" value="book" id="accommodation_book">
                                        <label for="accommodation_book">
                                            代訂住宿
                                            <span class="price">+2,000/晚</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        ` : '<input type="hidden" name="driver_accommodation" value="self">'}
                        
                        <div class="form-group">
                            <label>司機用餐</label>
                            <div class="radio-group">
                                <div class="radio-item">
                                    <input type="radio" name="driver_meals" value="provided" id="meals_provided" checked>
                                    <label for="meals_provided">
                                        提供餐點
                                        <span class="price">由客戶安排</span>
                                    </label>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" name="driver_meals" value="allowance" id="meals_allowance">
                                    <label for="meals_allowance">
                                        餐費補貼
                                        <span class="price">+400/日</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    $('#driver-options').html(optionsHTML);
                    $('#driver-info').html(isMultiDay ? 
                        '多日行程需安排司機住宿，可選擇由我們代訂或提供住宿。' : 
                        '餐點部分可直接提供或給予餐費補貼。'
                    );
                    
                    this.updateRadioStyles();
                },
                
                // 更新每日路線
                updateDailyRoutes() {
                    const days = parseInt($('#trip_days').val()) || 1;
                    const container = $('#daily-routes-container');
                    
                    // 確保路線陣列大小正確
                    while (this.state.dailyRoutes.length < days) {
                        this.state.dailyRoutes.push({ origin: '', destination: '', stops: [] });
                    }
                    
                    // 生成每日行程HTML
                    const routesHTML = Array.from({length: days}, (_, i) => 
                        this.getDayRouteTemplate(i)
                    ).join('');
                    
                    container.html(routesHTML);
                    
                    // 恢復停靠點
                    this.state.dailyRoutes.forEach((route, dayIndex) => {
                        route.stops?.forEach(stop => this.addStop(dayIndex, stop));
                    });
                    
                    // 初始化地址自動完成
                    this.initAllAutocomplete();
                    this.updateDayDates();
                },
                
                // 取得單日路線模板
                getDayRouteTemplate(dayIndex) {
                    const dayNum = dayIndex + 1;
                    const route = this.state.dailyRoutes[dayIndex] || {};
                    const tripDays = parseInt($('#trip_days').val()) || 1;
                    
                    return `
                        <div class="day-route-container" data-day="${dayIndex}">
                            <div class="day-header">
                                <div class="day-title">
                                    <span>Day ${dayNum}</span>
                                    ${dayIndex === 0 ? '<span class="day-badge">出發日</span>' : ''}
                                    ${dayIndex === tripDays - 1 && tripDays > 1 ? '<span class="day-badge">返程日</span>' : ''}
                                </div>
                                <div class="day-date" id="day-date-${dayIndex}"></div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>起點地址 <span class="required">*</span></label>
                                    <div class="address-input-wrapper">
                                        <input type="text" name="origin_${dayIndex}" id="origin_${dayIndex}"
                                               class="address-autocomplete" placeholder="輸入地址或地標"
                                               value="${route.origin || ''}" required>
                                        <div class="address-suggestions" id="origin_${dayIndex}_suggestions"></div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>終點地址 <span class="required">*</span></label>
                                    <div class="address-input-wrapper">
                                        <input type="text" name="destination_${dayIndex}" id="destination_${dayIndex}"
                                               class="address-autocomplete" placeholder="輸入地址或地標"
                                               value="${route.destination || ''}" required>
                                        <div class="address-suggestions" id="destination_${dayIndex}_suggestions"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>預排停靠點（選填，最多${CONFIG.maxStops}個）</label>
                                <div class="stops-container" id="stops-container-${dayIndex}"></div>
                                <button type="button" class="btn-add-stop" data-day="${dayIndex}">
                                    + 新增停靠點
                                </button>
                            </div>
                            
                            <div class="mountain-alert alert-box" id="mountain-alert-${dayIndex}">
                                <strong>⛰️ 山區路線提醒：</strong>
                                系統偵測到可能包含山區路線，將自動加收山區服務費 NT$1,000/日。
                            </div>
                            
                            <div class="excluded-alert alert-box" id="excluded-alert-${dayIndex}">
                                <strong>❌ 服務範圍提醒：</strong>
                                很抱歉，我們不提供司馬庫斯地區的服務。
                            </div>
                        </div>
                    `;
                },
                
                // 新增停靠點
                addStop(dayIndex, address = '') {
                    const container = $(`#stops-container-${dayIndex}`);
                    const stopCount = container.find('.stop-item').length;
                    
                    if (stopCount >= CONFIG.maxStops) {
                        alert(`每日最多只能新增${CONFIG.maxStops}個停靠點`);
                        return;
                    }
                    
                    const stopId = `stop_${dayIndex}_${stopCount}`;
                    const stopHTML = `
                        <div class="stop-item" data-index="${stopCount}" data-day="${dayIndex}">
                            <div class="address-input-wrapper" style="flex: 1;">
                                <input type="text" id="${stopId}" class="address-autocomplete stop-input"
                                       placeholder="停靠點 ${stopCount + 1}" value="${address}">
                                <div class="address-suggestions" id="${stopId}_suggestions"></div>
                            </div>
                            <div class="stop-controls">
                                <button type="button" class="stop-btn" data-action="up" data-day="${dayIndex}" 
                                        ${stopCount === 0 ? 'disabled' : ''}>↑</button>
                                <button type="button" class="stop-btn" data-action="down" data-day="${dayIndex}" 
                                        disabled>↓</button>
                                <button type="button" class="stop-btn delete" data-action="delete" data-day="${dayIndex}">×</button>
                            </div>
                        </div>
                    `;
                    
                    container.append(stopHTML);
                    this.updateStopButtons(dayIndex);
                    this.initAutocomplete(stopId);
                },
                
                // 更新停靠點按鈕狀態
                updateStopButtons(dayIndex) {
                    const items = $(`#stops-container-${dayIndex} .stop-item`);
                    
                    items.each(function(index) {
                        const $item = $(this);
                        $item.attr('data-index', index);
                        $item.find('.stop-input').attr('placeholder', `停靠點 ${index + 1}`);
                        
                        const $upBtn = $item.find('[data-action="up"]');
                        const $downBtn = $item.find('[data-action="down"]');
                        
                        $upBtn.prop('disabled', index === 0);
                        $downBtn.prop('disabled', index === items.length - 1);
                    });
                },
                
                // 初始化所有自動完成
                initAllAutocomplete() {
                    $('.address-autocomplete').each((_, el) => {
                        this.initAutocomplete(el.id);
                    });
                },
                
                // 初始化地址自動完成
                initAutocomplete(inputId) {
                    const $input = $(`#${inputId}`);
                    const $suggestions = $(`#${inputId}_suggestions`);
                    let debounceTimer;
                    
                    $input
                        .off('input blur')
                        .on('input', function() {
                            clearTimeout(debounceTimer);
                            const value = $(this).val();
                            
                            if (value.length < 2) {
                                $suggestions.removeClass('show');
                                return;
                            }
                            
                            debounceTimer = setTimeout(() => {
                                CharterBooking.fetchSuggestions(value, $suggestions);
                            }, 300);
                        })
                        .on('blur', function() {
                            const address = $(this).val();
                            if (address) CharterBooking.validateAddress(address, $(this));
                            
                            setTimeout(() => $suggestions.removeClass('show'), 200);
                        });
                    
                    // 選擇建議
                    $suggestions.off('click').on('click', '.suggestion-item', function() {
                        const address = $(this).data('description');
                        $input.val(address);
                        $suggestions.removeClass('show');
                        CharterBooking.validateAddress(address, $input);
                        CharterBooking.debouncedCalc();
                    });
                },
                
                // 取得地址建議
                fetchSuggestions(input, $container) {
                    $.post(CONFIG.ajaxUrl, {
                        action: 'autocomplete_address',
                        input: input
                    }).done(response => {
                        if (response.success && response.data) {
                            const html = response.data.map(s => `
                                <div class="suggestion-item" data-description="${s.description}">
                                    <div class="suggestion-main">${s.main_text || s.description}</div>
                                    ${s.secondary_text ? `<div class="suggestion-secondary">${s.secondary_text}</div>` : ''}
                                </div>
                            `).join('');
                            
                            $container.html(html).addClass('show');
                        } else {
                            $container.removeClass('show');
                        }
                    });
                },
                
                // 驗證地址
                validateAddress(address, $input) {
                    $.post(CONFIG.ajaxUrl, {
                        action: 'validate_address',
                        address: address
                    }).done(response => {
                        if (response.success && response.data) {
                            const dayIndex = this.getDayIndex($input);
                            if (dayIndex === -1) return;
                            
                            // 更新山區提醒
                            const $alert = $(`#mountain-alert-${dayIndex}`);
                            if (response.data.is_mountain && response.data.mountain_confidence > 70) {
                                $alert.addClass('show');
                            } else {
                                $alert.removeClass('show');
                            }
                            
                            this.debouncedCalc();
                        }
                    });
                },
                
                // 取得日期索引
                getDayIndex($input) {
                    const id = $input.attr('id');
                    const match = id.match(/_(\d+)(?:_\d+)?$/);
                    return match ? parseInt(match[1]) : -1;
                },
                
                // 更新日期顯示
                updateDayDates() {
                    const startDate = $('#start_date').val();
                    if (!startDate) return;
                    
                    const days = parseInt($('#trip_days').val()) || 1;
                    const start = new Date(startDate);
                    
                    for (let i = 0; i < days; i++) {
                        const dayDate = new Date(start);
                        dayDate.setDate(start.getDate() + i);
                        
                        const dateStr = dayDate.toLocaleDateString('zh-TW', {
                            month: 'short',
                            day: 'numeric',
                            weekday: 'short'
                        });
                        
                        $(`#day-date-${i}`).text(dateStr);
                    }
                },
                
                // 更新Radio樣式
                updateRadioStyles() {
                    $('.radio-item input[type="radio"]:checked').closest('.radio-item').addClass('selected');
                },
                
                // 防抖計算
                debouncedCalc() {
                    clearTimeout(this.state.calcTimeout);
                    this.state.calcTimeout = setTimeout(() => this.calculatePrice(), 500);
                },
                
                // 計算價格
                calculatePrice() {
                    // 收集每日行程資料
                    const tripDays = parseInt($('#trip_days').val()) || 1;
                    const dailyRoutes = [];
                    
                    for (let i = 0; i < tripDays; i++) {
                        const stops = [];
                        $(`#stops-container-${i} .stop-input`).each(function() {
                            const val = $(this).val().trim();
                            if (val) stops.push(val);
                        });
                        
                        dailyRoutes.push({
                            origin: $(`#origin_${i}`).val() || '',
                            destination: $(`#destination_${i}`).val() || '',
                            stops: stops
                        });
                    }
                    
                    // 顯示載入中
                    $('#price-panel .price-content').html('<div class="price-loading">計算中...</div>');
                    
                    // AJAX 請求
                    const formData = $('#charter-booking-form').serialize();
                    $.post(CONFIG.ajaxUrl, 
                        formData + '&action=calculate_charter_price&daily_routes=' + 
                        encodeURIComponent(JSON.stringify(dailyRoutes))
                    ).done(response => {
                        if (response?.success && response.data) {
                            this.displayPrice(response.data);
                            this.state.lastTotalPrice = response.data.total;
                            this.state.mountainDetection = response.data.breakdown.mountain_detection || {};
                        } else {
                            $('#price-panel .price-content').html('<div>計算失敗</div>');
                        }
                    }).fail(() => {
                        $('#price-panel .price-content').html('<div>系統錯誤</div>');
                    });
                },
                
                // 顯示價格
                displayPrice(data) {
                    const breakdown = data.breakdown || {};
                    let html = '';
                    
                    // 基本費用
                    if (breakdown.base_price > 0) {
                        html += `
                            <div class="price-item">
                                <span>基本費用 (${data.trip_days}天)</span>
                                <span>NT$ ${breakdown.base_price.toLocaleString()}</span>
                            </div>
                        `;
                        
                        // 每日費率細節
                        if (breakdown.daily_rates) {
                            Object.entries(breakdown.daily_rates).forEach(([day, rate]) => {
                                html += `
                                    <div class="price-item detail">
                                        <span>Day ${day}</span>
                                        <span>NT$ ${rate.toLocaleString()}</span>
                                    </div>
                                `;
                            });
                        }
                        
                        // 地區價格差異
                        if (breakdown.is_south) {
                            html += `
                                <div class="price-region south">
                                    嘉義以南/花東 NT$14,000/日
                                </div>
                            `;
                        } else {
                            html += `
                                <div class="price-region">
                                    嘉義以北/宜蘭 NT$12,000/日
                                </div>
                            `;
                        }
                        
                        // 顯示偵測到的地區
                        if (breakdown.detected_areas && breakdown.detected_areas.length > 0) {
                            const areas = [...new Set(breakdown.detected_areas)].join('、');
                            html += `
                                <div class="price-item detail" style="font-size: 12px; color: #6b7280;">
                                    <span>偵測地區：${areas}</span>
                                    <span></span>
                                </div>
                            `;
                        }
                    }
                    
                    // 山區加價
                    if (breakdown.mountain_surcharge > 0 && breakdown.mountain_days && breakdown.mountain_days.length > 0) {
                        html += `
                            <div class="price-item mountain">
                                <span>⛰️ 山區服務費</span>
                                <span>NT$ ${breakdown.mountain_surcharge.toLocaleString()}</span>
                            </div>
                        `;
                        
                        breakdown.mountain_days.forEach(function(mountain) {
                            html += `
                                <div class="price-item detail">
                                    <span>Day ${mountain.day} 山區行程</span>
                                    <span>海拔 ${mountain.elevation}公尺</span>
                                </div>
                            `;
                            
                            // 顯示山區地點
                            if (mountain.area && mountain.area.length > 0) {
                                const areas = mountain.area.length > 3 
                                    ? mountain.area.slice(0, 3).join('、') + '...'
                                    : mountain.area.join('、');
                                    
                                html += `
                                    <div class="price-item detail">
                                        <span style="font-size: 12px; color: #92400e;">
                                            山區地點：${areas}
                                        </span>
                                        <span></span>
                                    </div>
                                `;
                            }
                        });
                    }
                    
                    // 司機補貼
                    if (breakdown.driver_allowance > 0) {
                        html += `
                            <div class="price-item">
                                <span>司機補貼</span>
                                <span>NT$ ${breakdown.driver_allowance.toLocaleString()}</span>
                            </div>
                        `;
                        
                        if (breakdown.driver_accommodation > 0) {
                            html += `
                                <div class="price-item detail">
                                    <span>住宿代訂 (${data.trip_days - 1}晚)</span>
                                    <span>NT$ ${breakdown.driver_accommodation.toLocaleString()}</span>
                                </div>
                            `;
                        }
                        
                        if (breakdown.driver_meals > 0) {
                            html += `
                                <div class="price-item detail">
                                    <span>餐費補貼 (${data.trip_days}日)</span>
                                    <span>NT$ ${breakdown.driver_meals.toLocaleString()}</span>
                                </div>
                            `;
                        }
                    }
                    
                    // 加購項目
                    if (breakdown.addon_charge > 0) {
                        html += `
                            <div class="price-item">
                                <span>加購項目</span>
                                <span>NT$ ${breakdown.addon_charge.toLocaleString()}</span>
                            </div>
                        `;
                        
                        const childSeats = parseInt($('#child_seats').val()) || 0;
                        const boosterSeats = parseInt($('#booster_seats').val()) || 0;
                        
                        if (childSeats > 0) {
                            html += `
                                <div class="price-item detail">
                                    <span>嬰兒座椅 x${childSeats}</span>
                                    <span>NT$ ${(childSeats * 100).toLocaleString()}</span>
                                </div>
                            `;
                        }
                        
                        if (boosterSeats > 0) {
                            html += `
                                <div class="price-item detail">
                                    <span>增高墊 x${boosterSeats}</span>
                                    <span>NT$ ${(boosterSeats * 100).toLocaleString()}</span>
                                </div>
                            `;
                        }
                    }
                    
                    // 總計 - 簡化文字
                    html += `
                        <div class="price-total">
                            <span>總計(含稅)</span>
                            <span>NT$ ${(data.total || 0).toLocaleString()}</span>
                        </div>
                    `;
                    
                    // 訂金資訊
                    if (data.deposit && data.balance) {
                        html += `
                            <div class="deposit-info">
                                <div class="deposit-item">
                                    <span>訂金 (30%)</span>
                                    <span style="color: var(--danger); font-weight: bold;">
                                        NT$ ${data.deposit.toLocaleString()}
                                    </span>
                                </div>
                                <div class="deposit-item">
                                    <span>尾款 (70%)</span>
                                    <span>NT$ ${data.balance.toLocaleString()}</span>
                                </div>
                            </div>
                        `;
                    }
                    
                    // 提醒事項
                    if (breakdown.mountain_needs_check) {
                        html += `
                            <div class="notice-box">
                                <strong>提醒：</strong>您的行程可能包含山區路線，
                                我們將在確認後告知是否需要額外的山區服務費。
                            </div>
                        `;
                    }
                    
                    $('#price-panel .price-content').html(html);
                },
                
                // 綁定事件
                bindEvents() {
                    const self = this;
                    
                    // 使用事件委派減少綁定數量
                    $(document)
                        // 表單欄位變更
                        .on('change', '#trip_days', () => {
                            self.state.tripDays = parseInt($('#trip_days').val());
                            self.updateDailyRoutes();
                            self.updateDriverSection();
                            self.debouncedCalc();
                        })
                        .on('change', '#start_date', () => {
                            self.updateDayDates();
                            self.debouncedCalc();
                        })
                        .on('change', '#passengers, #child_seats, #booster_seats, [name^="driver_"]', () => {
                            self.debouncedCalc();
                        })
                        
                        // 停靠點操作
                        .on('click', '.btn-add-stop', function() {
                            self.addStop($(this).data('day'));
                        })
                        .on('click', '.stop-btn', function() {
                            const $btn = $(this);
                            const action = $btn.data('action');
                            const dayIndex = $btn.data('day');
                            const $item = $btn.closest('.stop-item');
                            const itemIndex = $item.index();
                            
                            if (action === 'delete') {
                                $item.fadeOut(200, function() {
                                    $(this).remove();
                                    self.updateStopButtons(dayIndex);
                                    self.debouncedCalc();
                                });
                            } else if (action === 'up' && itemIndex > 0) {
                                $item.insertBefore($item.prev());
                                self.updateStopButtons(dayIndex);
                                self.debouncedCalc();
                            } else if (action === 'down' && itemIndex < $item.siblings().length) {
                                $item.insertAfter($item.next());
                                self.updateStopButtons(dayIndex);
                                self.debouncedCalc();
                            }
                        })
                        
                        // Radio 樣式
                        .on('change', '.radio-item input[type="radio"]', function() {
                            const name = $(this).attr('name');
                            $(`.radio-item input[name="${name}"]`).closest('.radio-item').removeClass('selected');
                            $(this).closest('.radio-item').addClass('selected');
                        });
                    
                    // 表單提交
                    $('#charter-booking-form').on('submit', function(e) {
                        e.preventDefault();
                        self.submitBooking();
                    });
                },
                
                // 提交預約
                submitBooking() {
                    if (this.state.isSubmitting) return;
                    
                    // 驗證表單
                    const form = $('#charter-booking-form')[0];
                    if (!form.checkValidity()) {
                        form.reportValidity();
                        return;
                    }
                    
                    // 檢查排除地址
                    if ($('.excluded-alert.show').length > 0) {
                        alert('您的行程包含不提供服務的地區，請修改後再提交。');
                        return;
                    }
                    
                    this.state.isSubmitting = true;
                    $('.btn-submit').prop('disabled', true).html('<span class="spinner"></span> 處理中...');
                    
                    // 收集資料
                    const tripDays = parseInt($('#trip_days').val()) || 1;
                    const dailyRoutes = [];
                    
                    for (let i = 0; i < tripDays; i++) {
                        const stops = [];
                        $(`#stops-container-${i} .stop-input`).each(function() {
                            const val = $(this).val().trim();
                            if (val) stops.push(val);
                        });
                        
                        dailyRoutes.push({
                            origin: $(`#origin_${i}`).val() || '',
                            destination: $(`#destination_${i}`).val() || '',
                            stops: stops
                        });
                    }
                    
                    // 提交
                    const formData = $('#charter-booking-form').serialize();
                    $.post(CONFIG.ajaxUrl,
                        formData + 
                        '&action=submit_charter_booking' +
                        '&daily_routes=' + encodeURIComponent(JSON.stringify(dailyRoutes)) +
                        '&total_price=' + this.state.lastTotalPrice +
                        '&deposit=' + Math.round(this.state.lastTotalPrice * 0.3) +
                        '&mountain_detection=' + encodeURIComponent(JSON.stringify(this.state.mountainDetection))
                    ).done(res => {
                        if (res?.success) {
                            let message = res.data?.message || '預約成功！請立即支付訂金，我們將在24小時內與您聯繫確認。';
                            
                            if (!$('#customer_email').val()) {
                                message += '\n\n提醒：您未填寫 Email，將無法收到預約確認信。';
                            }
                            
                            alert(message);
                            
                            // 重置表單
                            form.reset();
                            this.state = {
                                tripDays: 1,
                                dailyRoutes: [],
                                isSubmitting: false,
                                lastTotalPrice: 0,
                                calcTimeout: null,
                                mountainDetection: {}
                            };
                            this.updateDailyRoutes();
                            this.updateDriverSection();
                            this.calculatePrice();
                        } else {
                            alert(res?.data?.message || '預約失敗，請檢查所有必填欄位');
                        }
                    }).fail(() => {
                        alert('系統錯誤，請稍後再試');
                    }).always(() => {
                        this.state.isSubmitting = false;
                        $('.btn-submit').prop('disabled', false).text('提交預約');
                    });
                }
            };
            
            // 初始化
            if (CharterBooking.init()) {
                console.log('✅ 包車旅遊系統 V6 已啟動');
            }
        });
        
    })(jQuery);
    </script>
    
    <?php
}, 100);