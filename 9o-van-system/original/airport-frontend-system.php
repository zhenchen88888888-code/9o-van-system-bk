// CSS 樣式
add_action('wp_head', function() {
    global $post;
    $content = isset($post->post_content) ? $post->post_content : '';
    
    if (!is_page('機場接送預約') && !is_page('airport-booking') && !has_shortcode($content, 'airport_booking_form')) {
        return;
    }
    ?>
    
    <style type="text/css">
    /* 機場接送系統 CSS V6.3 - 包車旅遊風格 */
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

    #airport-booking-app {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }

    .booking-container {
        display: grid;
        grid-template-columns: 2.5fr 1fr;
        gap: 30px;
    }

    #airport-booking-form {
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

    .return-section {
        background: var(--bg-light);
    }

    .return-section h3 {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        color: white;
        padding: 12px 20px;
        margin: -25px -25px 20px -25px;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
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

    /* 修正：增加label與欄位間距 */
    .form-group label {
        margin-bottom: 6px; 
        color: var(--text-sub);
        font-size: 14px;
        font-weight: 500;
        line-height: 1.4;
    }
	.form-group label.stop-label {
		margin-top: 15px;
		margin-bottom: 12px;
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

    /* Radio按鈕卡片式設計 */
    .radio-group {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-top: 12px; /* 增加與label的間距 */
    }

    .radio-item {
        position: relative;
        background: white;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 14px 16px;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
        min-height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
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
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 14px;
        color: var(--text-main);
        line-height: 1.3;
        margin: 0 !important; /* 移除radio內label的margin */
        font-weight: 500;
        width: 100%;
    }

    .radio-item label .price {
        display: block;
        font-size: 11px;
        color: var(--text-sub);
        margin-top: 3px;
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
        top: 6px;
        right: 6px;
        width: 18px;
        height: 18px;
        background: var(--primary);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
    }

    /* 停靠點容器 */
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
        margin-top: 10px;
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

    /* 提示框 */
    .info-box, .safety-alert {
        background: var(--bg-light);
        border-left: 4px solid var(--secondary);
        padding: 14px 18px;
        border-radius: 6px;
        font-size: 14px;
        line-height: 1.6;
        color: var(--text-main);
        margin: 20px 0;
    }
    
    .safety-alert {
        background: rgba(245, 158, 11, 0.08);
        border-left-color: var(--warning);
        color: #92400e;
    }
    
    .safety-alert strong {
        color: #b45309;
    }

    .email-notice {
        font-size: 12px;
        color: #6b7280;
        margin-top: 5px;
        font-style: italic;
    }

    /* 即時報價面板 */
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

    .price-item.free {
        color: var(--success);
    }

    /* 總計區塊 */
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

    .stopover-details {
        margin-top: 10px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 4px;
        font-size: 12px;
    }

    .stopover-details .detail-item {
        padding: 2px 0;
        color: #666;
    }

    .remote-area-notice {
        margin-top: 15px;
        padding: 10px;
        background: #fff4e5;
        border-left: 3px solid #ff9800;
        border-radius: 4px;
        color: #666;
    }

    .remote-area-notice small {
        font-size: 12px;
        line-height: 1.4;
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

// JavaScript
add_action('wp_footer', function() {
    global $post;
    $content = isset($post->post_content) ? $post->post_content : '';
    
    if (!is_page('機場接送預約') && !is_page('airport-booking') && !has_shortcode($content, 'airport_booking_form')) {
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
                maxStops: 5,
                cities: {
                    'taipei-city': '台北市',
                    'new-taipei': '新北市',
                    'keelung': '基隆市',
                    'taoyuan': '桃園市',
                    'yilan': '宜蘭縣',
                    'hsinchu-area': '新竹(市/縣)',
                    'miaoli': '苗栗縣',
                    'taichung': '台中市',
                    'changhua': '彰化縣',
                    'nantou': '南投縣',
                    'yunlin': '雲林縣',
                    'chiayi-area': '嘉義(市/縣)',
                    'tainan': '台南市',
                    'kaohsiung': '高雄市',
                    'pingtung': '屏東縣',
                    'hualien': '花蓮縣',
                    'taitung': '台東縣'
                }
            };
            
            // 機場接送應用程式
            const AirportBooking = {
                // 狀態管理
                state: {
                    stopovers: [],
                    returnStopovers: [],
                    calcTimeout: null,
                    isSubmitting: false,
                    lastTotalPrice: 0
                },
                
                // 初始化
                init() {
                    if (!$('#airport-booking-form').length) return false;
                    
                    this.buildForm();
                    this.bindEvents();
                    
                    // 延遲初始計算
                    setTimeout(() => this.calculatePrice(), 1000);
                    
                    console.log('✅ 機場接送系統 V6.3 已啟動');
                    return true;
                },
                
                // 建立表單
                buildForm() {
                    const formHTML = this.getFormTemplate();
                    $('#airport-booking-form').html(formHTML);
                },
                
                // 表單模板
                getFormTemplate() {
                    let cityOptions = '';
                    for (let key in CONFIG.cities) {
                        cityOptions += `<option value="${key}">${CONFIG.cities[key]}</option>`;
                    }
                    
                    return `
                        <div class="form-header">
                            <h2>✈️ 機場接送預約</h2>
                            <p>專業司機·舒適乘坐·準時安全</p>
                        </div>
                        
                        <div class="form-section">
                            <h3>📍 基本資訊</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>機場選擇 <span class="required">*</span></label>
                                    <select name="airport" id="airport" required>
                                        <option value="tpe">桃園國際機場</option>
                                        <option value="tsa">台北松山機場</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>目的地縣市 <span class="required">*</span></label>
                                    <select name="destination" id="destination" required>
                                        ${cityOptions}
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>行程類型</label>
                                    <div class="radio-group">
                                        <div class="radio-item">
                                            <input type="radio" name="trip_type" value="oneway" id="trip_oneway" checked>
                                            <label for="trip_oneway">單程</label>
                                        </div>
                                        <div class="radio-item">
                                            <input type="radio" name="trip_type" value="roundtrip" id="trip_roundtrip">
                                            <label for="trip_roundtrip">來回</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>服務類型 <span class="required">*</span></label>
                                    <div class="radio-group">
                                        <div class="radio-item">
                                            <input type="radio" name="service_type" value="pickup" id="service_pickup" checked>
                                            <label for="service_pickup">接機</label>
                                        </div>
                                        <div class="radio-item">
                                            <input type="radio" name="service_type" value="dropoff" id="service_dropoff">
                                            <label for="service_dropoff">送機</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3>🗓️ 去程資訊</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>日期 <span class="required">*</span> (至少提前2天)</label>
                                    <input type="date" name="date" id="date" 
                                           min="${CONFIG.minDate}" value="${CONFIG.minDate}" required>
                                </div>
                                <div class="form-group">
                                    <label>時間 <span class="required">*</span></label>
                                    <input type="time" name="time" id="time" value="10:00" required>
                                </div>
                            </div>
                            
                            <div class="form-row full-width">
                                <div class="form-group">
                                    <label>航班號碼</label>
                                    <input type="text" name="flight" id="flight" placeholder="例：BR123">
                                </div>
                            </div>
                            
                            <div class="pickup-fields">
                                <div class="form-group">
                                    <label>下車地址 <span class="required">*</span></label>
                                    <input type="text" name="dropoff_address" id="dropoff_address" 
                                           placeholder="請輸入完整地址（含縣市區），例如：台北市信義區市府路45號3樓">
                                </div>
                            </div>
                            
                            <div class="dropoff-fields" style="display:none;">
                                <div class="form-group">
                                    <label>上車地址 <span class="required">*</span></label>
                                    <input type="text" name="pickup_address" id="pickup_address" 
                                           placeholder="請輸入完整地址（含縣市區），例如：台北市信義區市府路45號3樓">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="stop-label">停靠點（選填，最多${CONFIG.maxStops}個）</label>
                                <div class="stops-container" id="stops-container"></div>
                                <button type="button" class="btn-add-stop" id="add-stopover">
                                    + 新增停靠點
                                </button>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>乘客人數 <span class="required">*</span></label>
                                    <input type="number" name="passengers" id="passengers" 
                                           min="1" max="8" value="1" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section return-section" style="display:none;">
                            <h3>🔄 回程資訊</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>回程日期 <span class="required">*</span></label>
                                    <input type="date" name="return_date" id="return_date" 
                                           min="${CONFIG.minDate}">
                                </div>
                                <div class="form-group">
                                    <label>回程時間 <span class="required">*</span></label>
                                    <input type="time" name="return_time" id="return_time" value="10:00">
                                </div>
                            </div>
                            
                            <div class="form-row full-width">
                                <div class="form-group">
                                    <label>回程航班號碼</label>
                                    <input type="text" name="return_flight" id="return_flight" placeholder="例：BR124">
                                </div>
                            </div>
                            
                            <div class="return-pickup-fields" style="display:none;">
                                <div class="form-group">
                                    <label class="return-pickup-label">回程上車地址 <span class="required">*</span></label>
                                    <input type="text" name="return_pickup_address" id="return_pickup_address" 
                                           placeholder="請輸入完整地址（含縣市區），例如：台北市信義區市府路45號3樓">
                                </div>
                            </div>
                            
                            <div class="return-dropoff-fields">
                                <div class="form-group">
                                    <label class="return-dropoff-label">回程下車地址 <span class="required">*</span></label>
                                    <input type="text" name="return_dropoff_address" id="return_dropoff_address" 
                                           placeholder="請輸入完整地址（含縣市區），例如：台北市信義區市府路45號3樓">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="stop-label">回程停靠點（選填，最多${CONFIG.maxStops}個）</label>
                                <div class="stops-container" id="return-stops-container"></div>
                                <button type="button" class="btn-add-stop" id="add-return-stopover">
                                    + 新增回程停靠點
                                </button>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>回程乘客人數 <span class="required">*</span></label>
                                    <input type="number" name="return_passengers" id="return_passengers" 
                                           min="1" max="8" value="1">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3>🛠️ 加購項目</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>舉牌服務</label>
                                    <div class="radio-group">
                                        <div class="radio-item">
                                            <input type="radio" name="name_board" value="no" id="board_no" checked>
                                            <label for="board_no">
                                                不需要
                                            </label>
                                        </div>
                                        <div class="radio-item">
                                            <input type="radio" name="name_board" value="yes" id="board_yes">
                                            <label for="board_yes">
                                                需要舉牌
                                                <span class="price">+200元</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="safety-alert">
                                根據《小型車附載幼童安全乘坐實施及宣導辦法》，<strong>4歲以下孩童皆需使用安全座椅</strong>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>嬰兒安全座椅 (每張NT$100)</label>
                                    <input type="number" name="child_seats" id="child_seats" 
                                           min="0" max="4" value="0">
                                </div>
                                <div class="form-group">
                                    <label>兒童增高墊 (每張NT$100)</label>
                                    <input type="number" name="booster_seats" id="booster_seats" 
                                           min="0" max="4" value="0">
                                </div>
                            </div>
                            
                            <div class="return-section" style="display:none;">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>回程舉牌服務</label>
                                        <div class="radio-group">
                                            <div class="radio-item">
                                                <input type="radio" name="return_name_board" value="no" id="return_board_no" checked>
                                                <label for="return_board_no">不需要</label>
                                            </div>
                                            <div class="radio-item">
                                                <input type="radio" name="return_name_board" value="yes" id="return_board_yes">
                                                <label for="return_board_yes">
                                                    需要舉牌
                                                    <span class="price">+200元</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>回程嬰兒安全座椅 (每張NT$100)</label>
                                        <input type="number" name="return_child_seats" id="return_child_seats" 
                                               min="0" max="4" value="0">
                                    </div>
                                    <div class="form-group">
                                        <label>回程兒童增高墊 (每張NT$100)</label>
                                        <input type="number" name="return_booster_seats" id="return_booster_seats" 
                                               min="0" max="4" value="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3>📞 聯絡資訊</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>姓名 <span class="required">*</span></label>
                                    <input type="text" name="customer_name" 
                                           placeholder="請輸入您的姓名" required>
                                </div>
                                <div class="form-group">
                                    <label>電話 <span class="required">*</span> (含國際區號)</label>
                                    <input type="tel" name="customer_phone" 
                                           placeholder="+886912345678 或 0912345678" required
                                           pattern="(\\+[0-9]{1,4})?[0-9]{9,15}"
                                           title="請輸入有效電話號碼，可含國際區號">
                                </div>
                            </div>
                            
                            <div class="form-row full-width">
                                <div class="form-group">
                                    <label>Email（選填）</label>
                                    <input type="email" name="customer_email" 
                                           placeholder="example@email.com">
                                    <span class="email-notice">※ 未填寫將不會收到確認信件通知</span>
                                </div>
                            </div>
                            
                            <div class="form-row full-width">
                                <div class="form-group">
                                    <label>備註</label>
                                    <textarea name="notes" rows="3" 
                                             placeholder="特殊需求或備註事項..."></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-submit">立即預約</button>
                    `;
                },
                
                // 新增停靠點
                addStop(containerId, address = '') {
                    const container = $(`#${containerId}`);
                    const stops = container.find('.stop-item').length;
                    
                    if (stops >= CONFIG.maxStops) {
                        alert(`最多只能新增${CONFIG.maxStops}個停靠點`);
                        return;
                    }
                    
                    const stopId = `${containerId}_stop_${stops}`;
                    const stopHTML = `
                        <div class="stop-item" data-index="${stops}">
                            <input type="text" id="${stopId}" class="stop-input"
                                   placeholder="停靠點 ${stops + 1} - 請輸入完整地址（含縣市）" 
                                   value="${address}">
                            <div class="stop-controls">
                                <button type="button" class="stop-btn" data-action="up" 
                                        data-container="${containerId}" ${stops === 0 ? 'disabled' : ''}>↑</button>
                                <button type="button" class="stop-btn" data-action="down" 
                                        data-container="${containerId}" disabled>↓</button>
                                <button type="button" class="stop-btn delete" data-action="delete" 
                                        data-container="${containerId}">×</button>
                            </div>
                        </div>
                    `;
                    
                    container.append(stopHTML);
                    this.updateStopButtons(containerId);
                },
                
                // 更新停靠點按鈕狀態
                updateStopButtons(containerId) {
                    const items = $(`#${containerId} .stop-item`);
                    
                    items.each(function(index) {
                        const $item = $(this);
                        $item.attr('data-index', index);
                        $item.find('.stop-input').attr('placeholder', `停靠點 ${index + 1} - 請輸入完整地址（含縣市）`);
                        
                        const $upBtn = $item.find('[data-action="up"]');
                        const $downBtn = $item.find('[data-action="down"]');
                        
                        $upBtn.prop('disabled', index === 0);
                        $downBtn.prop('disabled', index === items.length - 1);
                    });
                    
                    // 更新狀態
                    if (containerId === 'stops-container') {
                        this.state.stopovers = items.map((i, el) => ({
                            address: $(el).find('.stop-input').val()
                        })).get();
                    } else {
                        this.state.returnStopovers = items.map((i, el) => ({
                            address: $(el).find('.stop-input').val()
                        })).get();
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
                    // 收集停靠點
                    const stopovers = [];
                    $('#stops-container .stop-input').each(function() {
                        const val = $(this).val().trim();
                        if (val) stopovers.push({address: val});
                    });
                    
                    const returnStopovers = [];
                    $('#return-stops-container .stop-input').each(function() {
                        const val = $(this).val().trim();
                        if (val) returnStopovers.push({address: val});
                    });
                    
                    // 準備資料
                    const formData = $('#airport-booking-form').serialize();
                    const ajaxData = formData + 
                        '&action=calculate_airport_price' +
                        '&stopovers=' + encodeURIComponent(JSON.stringify(stopovers)) +
                        '&return_stopovers=' + encodeURIComponent(JSON.stringify(returnStopovers));
                    
                    // 顯示載入中
                    $('#price-panel .price-content').html('<div class="price-loading">計算中...</div>');
                    
                    // AJAX 請求
                    $.post(CONFIG.ajaxUrl, ajaxData)
                        .done(response => {
                            if (response?.success && response.data) {
                                this.displayPrice(response.data);
                                this.state.lastTotalPrice = response.data.total;
                            } else {
                                $('#price-panel .price-content').html('<div>計算失敗</div>');
                            }
                        })
                        .fail(() => {
                            $('#price-panel .price-content').html('<div>系統錯誤</div>');
                        });
                },
                
                // 顯示價格
                displayPrice(data) {
                    let html = '';
                    
                    if (data.breakdown) {
                        // 基本費用
                        if (data.breakdown.base_price > 0) {
                            html += `
                                <div class="price-item">
                                    <span>基本費用</span>
                                    <span>NT$ ${data.breakdown.base_price.toLocaleString()}</span>
                                </div>
                            `;
                        }
                        
                        // 夜間加價
                        if (data.breakdown.night_surcharge > 0) {
                            html += `
                                <div class="price-item">
                                    <span>夜間加價 (22:00-08:00)</span>
                                    <span>NT$ ${data.breakdown.night_surcharge.toLocaleString()}</span>
                                </div>
                            `;
                        }
                        
                        // 偏遠地區加價
                        if (data.breakdown.remote_surcharge > 0) {
                            html += `
                                <div class="price-item">
                                    <span>偏遠地區加價</span>
                                    <span>NT$ ${data.breakdown.remote_surcharge.toLocaleString()}</span>
                                </div>
                            `;
                        }
                        
                        // 去程停靠點費用
                        if (data.breakdown.stopover_charge > 0) {
                            html += `
                                <div class="price-item">
                                    <span>去程停靠點費用</span>
                                    <span>NT$ ${data.breakdown.stopover_charge.toLocaleString()}</span>
                                </div>
                            `;
                            
                            // 顯示去程停靠點詳情
                            if (data.breakdown.stopover_details && data.breakdown.stopover_details.length > 0) {
                                html += '<div class="stopover-details">';
                                data.breakdown.stopover_details.forEach(function(detail) {
                                    const chargeText = detail.charged ? 
                                        'NT$ ' + detail.fee : 
                                        '免費（不計費路段）';
                                    const className = detail.charged ? '' : 'free';
                                    
                                    html += `
                                        <div class="detail-item ${className}">
                                            ${detail.segment}: ${detail.distance}km - ${chargeText}
                                        </div>
                                    `;
                                });
                                html += '</div>';
                            }
                        }
                        
                        // 去程加購項目
                        if (data.breakdown.addon_charge > 0) {
                            html += `
                                <div class="price-item">
                                    <span>去程加購項目</span>
                                    <span>NT$ ${data.breakdown.addon_charge.toLocaleString()}</span>
                                </div>
                            `;
                            
                            // 顯示舉牌服務
                            if (data.breakdown.name_board_charge > 0) {
                                html += `
                                    <div class="price-item detail">
                                        <span>舉牌服務</span>
                                        <span>NT$ 200</span>
                                    </div>
                                `;
                            }
                        }
                        
                        // 如果是來回程
                        if (data.breakdown.return_subtotal !== undefined) {
                            html += `
                                <div class="price-item" style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #ddd;">
                                    <span style="font-weight: 600;">回程費用</span>
                                    <span></span>
                                </div>
                            `;
                            
                            // 回程基本費用
                            html += `
                                <div class="price-item detail">
                                    <span>回程基本費用</span>
                                    <span>NT$ ${data.breakdown.base_price.toLocaleString()}</span>
                                </div>
                            `;
                            
                            // 回程偏遠地區加價
                            if (data.breakdown.return_remote_surcharge > 0) {
                                html += `
                                    <div class="price-item detail">
                                        <span>回程偏遠地區加價</span>
                                        <span>NT$ ${data.breakdown.return_remote_surcharge.toLocaleString()}</span>
                                    </div>
                                `;
                            }
                            
                            // 回程停靠點費用
                            if (data.breakdown.return_stopover_charge > 0) {
                                html += `
                                    <div class="price-item detail">
                                        <span>回程停靠點費用</span>
                                        <span>NT$ ${data.breakdown.return_stopover_charge.toLocaleString()}</span>
                                    </div>
                                `;
                                
                                // 顯示回程停靠點詳情
                                if (data.breakdown.return_stopover_details && data.breakdown.return_stopover_details.length > 0) {
                                    html += '<div class="stopover-details">';
                                    data.breakdown.return_stopover_details.forEach(function(detail) {
                                        const chargeText = detail.charged ? 
                                            'NT$ ' + detail.fee : 
                                            '免費（不計費路段）';
                                        const className = detail.charged ? '' : 'free';
                                        
                                        html += `
                                            <div class="detail-item ${className}">
                                                ${detail.segment}: ${detail.distance}km - ${chargeText}
                                            </div>
                                        `;
                                    });
                                    html += '</div>';
                                }
                            }
                            
                            // 回程加購項目
                            if (data.breakdown.return_addon > 0) {
                                html += `
                                    <div class="price-item detail">
                                        <span>回程加購項目</span>
                                        <span>NT$ ${data.breakdown.return_addon.toLocaleString()}</span>
                                    </div>
                                `;
                            }
                            
                            // 原始總價（來回程折扣前）
                            if (data.breakdown.original_total !== undefined) {
                                html += `
                                    <div class="price-item" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                                        <span>原始總價</span>
                                        <span style="text-decoration: line-through; color: #999;">
                                            NT$ ${data.breakdown.original_total.toLocaleString()}
                                        </span>
                                    </div>
                                `;
                            }
                            
                            // 來回折扣
                            if (data.breakdown.discount && data.breakdown.discount < 0) {
                                html += `
                                    <div class="price-item">
                                        <span style="color: #16a34a; font-weight: 600;">來回程9折優惠</span>
                                        <span style="color: #16a34a;">
                                            -NT$ ${Math.abs(data.breakdown.discount).toLocaleString()}
                                        </span>
                                    </div>
                                `;
                            }
                        }
                    }
                    
                    // 總計
                    html += `
                        <div class="price-total">
                            <span>總計(含稅)</span>
                            <span>NT$ ${(data.total || 0).toLocaleString()}</span>
                        </div>
                    `;
                    
                    // 偏遠地區提示
                    if ((data.breakdown && data.breakdown.remote_surcharge > 0) || 
                        (data.breakdown && data.breakdown.return_remote_surcharge > 0)) {
                        html += `
                            <div class="remote-area-notice">
                                <small>📍 您的目的地位於偏遠地區，已自動加收偏遠地區服務費</small>
                            </div>
                        `;
                    }
                    
                    $('#price-panel .price-content').html(html);
                },
                
                // 綁定事件 - 修正版
                bindEvents() {
                    const self = this;
                    
                    // 使用事件委派減少綁定數量
                    $(document)
                        // 服務類型切換 - 修正回程邏輯
                        .on('change', '[name="service_type"]', function() {
                            const isPickup = $(this).val() === 'pickup';
                            
                            // 去程欄位顯示邏輯
                            $('.pickup-fields').toggle(isPickup);
                            $('.dropoff-fields').toggle(!isPickup);
                            
                            // 回程欄位顯示邏輯（相反）
                            // 如果去程是「接機」，則回程是「送機」，需要使用者填寫回程的「上車地址」
                            $('.return-pickup-fields').toggle(!isPickup);
                            // 如果去程是「送機」，則回程是「接機」，需要使用者填寫回程的「下車地址」
                            $('.return-dropoff-fields').toggle(isPickup);
                            
                            // 更新回程標籤文字
                            if (isPickup) {
                                // 去程接機，回程送機
                                $('.return-pickup-label').html('回程上車地址 <span class="required">*</span>');
                            } else {
                                // 去程送機，回程接機
                                $('.return-dropoff-label').html('回程下車地址 <span class="required">*</span>');
                            }
                            
                            self.debouncedCalc();
                        })
                        
                        // 行程類型切換
                        .on('change', '[name="trip_type"]', function() {
                            const isRoundtrip = $(this).val() === 'roundtrip';
                            $('.return-section').toggle(isRoundtrip);
                            
                            if (isRoundtrip) {
                                const goDate = $('#date').val();
                                if (goDate) {
                                    const returnDate = new Date(goDate);
                                    returnDate.setDate(returnDate.getDate() + 7);
                                    $('#return_date').val(returnDate.toISOString().split('T')[0]);
                                }
                                
                                $('#return_passengers').val($('#passengers').val());
                                $('#return_child_seats').val($('#child_seats').val());
                                $('#return_booster_seats').val($('#booster_seats').val());
                                
                                // 觸發服務類型變更以更新標籤
                                $('[name="service_type"]:checked').trigger('change');
                            }
                            
                            self.debouncedCalc();
                        })
                        
                        // 新增停靠點
                        .on('click', '#add-stopover', function() {
                            self.addStop('stops-container');
                            self.debouncedCalc();
                        })
                        .on('click', '#add-return-stopover', function() {
                            self.addStop('return-stops-container');
                            self.debouncedCalc();
                        })
                        
                        // 停靠點操作按鈕
                        .on('click', '.stop-btn', function() {
                            const $btn = $(this);
                            const action = $btn.data('action');
                            const containerId = $btn.data('container');
                            const $item = $btn.closest('.stop-item');
                            const itemIndex = $item.index();
                            
                            if (action === 'delete') {
                                $item.fadeOut(200, function() {
                                    $(this).remove();
                                    self.updateStopButtons(containerId);
                                    self.debouncedCalc();
                                });
                            } else if (action === 'up' && itemIndex > 0) {
                                $item.insertBefore($item.prev());
                                self.updateStopButtons(containerId);
                                self.debouncedCalc();
                            } else if (action === 'down' && itemIndex < $item.siblings().length) {
                                $item.insertAfter($item.next());
                                self.updateStopButtons(containerId);
                                self.debouncedCalc();
                            }
                        })
                        
                        // Radio樣式更新
                        .on('change', '.radio-item input[type="radio"]', function() {
                            const name = $(this).attr('name');
                            $(`.radio-item input[name="${name}"]`).closest('.radio-item').removeClass('selected');
                            $(this).closest('.radio-item').addClass('selected');
                        })
                        
                        // 價格計算觸發
                        .on('change', '#airport, #destination, #date, #time, #passengers, ' +
                            '#child_seats, #booster_seats, [name="name_board"], ' +
                            '#return_date, #return_time, #return_passengers, ' +
                            '#return_child_seats, #return_booster_seats, [name="return_name_board"], ' +
                            '#pickup_address, #dropoff_address, #return_pickup_address, #return_dropoff_address', 
                            function() {
                                self.debouncedCalc();
                            }
                        )
                        
                        // 停靠點地址變更
                        .on('blur', '.stop-input', function() {
                            self.debouncedCalc();
                        });
                    
                    // 表單提交
                    $('#airport-booking-form').on('submit', function(e) {
                        e.preventDefault();
                        self.submitBooking();
                    });
                    
                    // 初始化Radio樣式
                    setTimeout(() => {
                        $('.radio-item input[type="radio"]:checked').trigger('change');
                    }, 100);
                },
                
                // 提交預約
                submitBooking() {
                    if (this.state.isSubmitting) return;
                    
                    // 驗證表單
                    const form = $('#airport-booking-form')[0];
                    const emailField = $('[name="customer_email"]');
                    
                    emailField.removeAttr('required');
                    
                    if (!form.checkValidity()) {
                        form.reportValidity();
                        return;
                    }
                    
                    // 如果是來回程，驗證回程必填欄位
                    if ($('[name="trip_type"]:checked').val() === 'roundtrip') {
                        const returnRequired = ['#return_date', '#return_time'];
                        let hasError = false;
                        
                        returnRequired.forEach(function(field) {
                            if (!$(field).val()) {
                                $(field).focus();
                                alert('請填寫回程資訊');
                                hasError = true;
                                return false;
                            }
                        });
                        
                        if (hasError) return;
                        
                        // 驗證回程地址
                        const serviceType = $('[name="service_type"]:checked').val();
                        if (serviceType === 'pickup') {
                            // 去程接機，回程需要上車地址
                            if (!$('#return_pickup_address').val()) {
                                $('#return_pickup_address').focus();
                                alert('請填寫回程上車地址');
                                return;
                            }
                        } else {
                            // 去程送機，回程需要下車地址
                            if (!$('#return_dropoff_address').val()) {
                                $('#return_dropoff_address').focus();
                                alert('請填寫回程下車地址');
                                return;
                            }
                        }
                    }
                    
                    this.state.isSubmitting = true;
                    $('.btn-submit').prop('disabled', true).html('<span class="spinner"></span> 處理中...');
                    
                    // 收集停靠點
                    const stopovers = [];
                    $('#stops-container .stop-input').each(function() {
                        const val = $(this).val().trim();
                        if (val) stopovers.push({address: val});
                    });
                    
                    const returnStopovers = [];
                    $('#return-stops-container .stop-input').each(function() {
                        const val = $(this).val().trim();
                        if (val) returnStopovers.push({address: val});
                    });
                    
                    // 準備資料
                    const formData = $('#airport-booking-form').serialize();
                    const ajaxData = formData + 
                        '&action=submit_airport_booking' +
                        '&stopovers=' + encodeURIComponent(JSON.stringify(stopovers)) +
                        '&return_stopovers=' + encodeURIComponent(JSON.stringify(returnStopovers)) +
                        '&total_price=' + this.state.lastTotalPrice;
                    
                    // 提交
                    $.post(CONFIG.ajaxUrl, ajaxData)
                        .done(res => {
                            if (res?.success) {
                                alert(res.data?.message || '預約成功！我們將盡快與您聯繫。');
                                
                                // 重置表單
                                form.reset();
                                this.state = {
                                    stopovers: [],
                                    returnStopovers: [],
                                    calcTimeout: null,
                                    isSubmitting: false,
                                    lastTotalPrice: 0
                                };
                                $('#stops-container').empty();
                                $('#return-stops-container').empty();
                                $('.return-section').hide();
                               
                                // 重新計算價格
                                this.calculatePrice();
                            } else {
                                alert(res?.data?.message || '預約失敗，請檢查所有必填欄位');
                            }
                        })
                        .fail(() => {
                            alert('系統錯誤，請稍後再試');
                        })
                        .always(() => {
                            this.state.isSubmitting = false;
                            $('.btn-submit').prop('disabled', false).text('立即預約');
                        });
                }
            };
            
            // 初始化
            if (AirportBooking.init()) {
                console.log('✅ 機場接送系統 V6.2 已啟動');
            }
        });
        
    })(jQuery);
    </script>
    
    <?php
}, 100);
