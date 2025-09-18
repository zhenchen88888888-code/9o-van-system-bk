/* ========================================
   包車旅遊模組 JavaScript
   ======================================== */

(function($) {
    'use strict';
    
    let initRetries = 0;
    const maxInitRetries = 100;
    
    function initCheck() {
        if (typeof jQuery !== 'undefined' && typeof BookingCommon !== 'undefined') {
            // 繼續初始化
            window.CharterBookingModule = { /* ... */ };
        } else if (initRetries++ < maxInitRetries) {
            setTimeout(initCheck, 100);
        } else {
            console.error('Charter Module: Dependencies failed to load');
            document.getElementById('charter-booking-app')?.classList.add('loading-failed');
        }
    }
    
    initCheck();
})(window.jQuery);
    
    // 包車旅遊模組
    window.CharterBookingModule = {
        
        // 模組配置
        config: {
            ajaxUrl: window.charterConfig?.ajaxUrl || '/wp-admin/admin-ajax.php',
            nonce: window.charterConfig?.nonce || '',
            maxDays: window.charterConfig?.maxDays || 7,
            minDays: window.charterConfig?.minDays || 1,
            maxStops: window.charterConfig?.maxStops || 6,
            mountainKeywords: window.charterConfig?.mountainKeywords || [],
            excludedAreas: window.charterConfig?.excludedAreas || [],
            basePrices: window.charterConfig?.basePrices || {},
            texts: window.charterConfig?.texts || {},
            minDate: new Date(Date.now() + 3 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
        },
        
        // 狀態管理
        state: {
            tripDays: 1,
            dailyRoutes: [],
            isSubmitting: false,
            currentPrice: 0,
            calcTimeout: null,
            mountainDetection: {},
            formData: {}
        },
        
        // 初始化
	init: function() {
   	 // 記錄初始化時間
   	 const startTime = performance.now();
    
    	this.bindEvents();
    	this.buildForm();
    	this.initializeComponents();
    
   	 // 記錄載入完成時間
    	const loadTime = performance.now() - startTime;
    	console.log(`Module loaded in ${loadTime.toFixed(2)}ms`);
	}
        
        // 綁定事件
        bindEvents: function() {
            $(document).on('change', '#charter-booking-form input, #charter-booking-form select', 
                this.debounce(this.calculatePrice.bind(this), 500));
            $(document).on('submit', '#charter-booking-form', this.handleSubmit.bind(this));
            $(document).on('change', 'input[name="trip_days"]', this.handleDaysChange.bind(this));
            $(document).on('change', 'input[name="starting_point"]', this.handleStartingPointChange.bind(this));
            $(document).on('click', '.add-destination', this.addDestination.bind(this));
            $(document).on('click', '.remove-destination', this.removeDestination.bind(this));
            $(document).on('click', '.suggestion-tag', this.selectSuggestion.bind(this));
            $(document).on('input', '.destination-input', this.debounce(this.checkDestination.bind(this), 300));
        },
        
        // 建立表單
        buildForm: function() {
            const $form = $('#charter-booking-form');
            if (!$form.length) return;
            
            const formHTML = this.generateFormHTML();
            $form.html(formHTML);
            
            // 初始化第一天
            this.generateDailyItinerary();
        },
        
        // 生成表單HTML
        generateFormHTML: function() {
            return `
                <div class="form-header">
                    <h2>🚐 包車旅遊預約</h2>
                    <p>舒適九人座·專業司機·全台走透透</p>
                </div>
                
                <div class="form-content">
                    ${this.generateDaysSelection()}
                    ${this.generateDepartureSection()}
                    ${this.generateItinerarySection()}
                    ${this.generateRequirementsSection()}
                    ${this.generateCustomerInfoSection()}
                    ${this.generateDriverFeesSection()}
                    ${this.generateSubmitSection()}
                </div>
            `;
        },
        
        // 生成天數選擇
        generateDaysSelection: function() {
            let html = '<div class="form-section" id="days-section">';
            html += '<h3>📅 選擇旅遊天數</h3>';
            html += '<div class="days-selection">';
            
            for (let day = 1; day <= this.config.maxDays; day++) {
                html += `
                    <div class="day-option">
                        <input type="radio" name="trip_days" value="${day}" id="day-${day}" required>
                        <label class="day-option-label" for="day-${day}">
                            <span class="day-count">${day}</span>
                            <span class="day-text">天</span>
                        </label>
                    </div>
                `;
            }
            
            html += '</div>';
            html += `
                <div class="multi-day-discount">
                    <div class="discount-title">💰 多日優惠</div>
                    <div class="discount-description">
                        第1天原價，第2天起每天減少NT$ 1,000
                    </div>
                </div>
            `;
            html += '</div>';
            
            return html;
        },
        
        // 生成出發資訊區段
        generateDepartureSection: function() {
            return `
                <div class="form-section" id="departure-section">
                    <h3>🚩 出發資訊</h3>
                    
                    <div class="starting-point-section">
                        <label class="section-label">出發地點</label>
                        <div class="starting-point-options">
                            <div class="starting-point-option">
                                <input type="radio" name="starting_point" value="taipei" id="start-taipei" required>
                                <label class="starting-point-label" for="start-taipei">台北</label>
                            </div>
                            <div class="starting-point-option">
                                <input type="radio" name="starting_point" value="taichung" id="start-taichung">
                                <label class="starting-point-label" for="start-taichung">台中</label>
                            </div>
                            <div class="starting-point-option">
                                <input type="radio" name="starting_point" value="kaohsiung" id="start-kaohsiung">
                                <label class="starting-point-label" for="start-kaohsiung">高雄</label>
                            </div>
                            <div class="starting-point-option">
                                <input type="radio" name="starting_point" value="custom" id="start-custom">
                                <label class="starting-point-label" for="start-custom">其他地點</label>
                            </div>
                        </div>
                        
                        <div class="custom-starting-point" id="custom-starting-point">
                            <label for="custom_starting_address">自訂出發地址</label>
                            <input type="text" id="custom_starting_address" name="custom_starting_address" 
                                   placeholder="請輸入詳細地址">
                            <div class="address-suggestions" id="starting-suggestions"></div>
                        </div>
                    </div>
                    
                    <div class="itinerary-timing">
                        <div class="timing-item">
                            <label for="start_date">出發日期 <span class="required">*</span></label>
                            <input type="date" id="start_date" name="start_date" 
                                   min="${this.config.minDate}" required>
                        </div>
                        <div class="timing-item">
                            <label for="departure_time">預計出發時間</label>
                            <select id="departure_time" name="departure_time">
                                <option value="08:00">上午 08:00</option>
                                <option value="09:00" selected>上午 09:00</option>
                                <option value="10:00">上午 10:00</option>
                                <option value="11:00">上午 11:00</option>
                                <option value="12:00">中午 12:00</option>
                                <option value="13:00">下午 13:00</option>
                                <option value="14:00">下午 14:00</option>
                            </select>
                        </div>
                    </div>
                </div>
            `;
        },
        
        // 生成行程規劃區段
        generateItinerarySection: function() {
            return `
                <div class="form-section" id="itinerary-section">
                    <h3>🗺️ 行程規劃</h3>
                    <div id="daily-itinerary-container">
                        <!-- 動態產生每日行程 -->
                    </div>
                    ${this.generateDestinationSuggestions()}
                </div>
            `;
        },
        
        // 生成目的地推薦
        generateDestinationSuggestions: function() {
            const suggestions = {
                '台北': ['九份', '淡水', '野柳', '陽明山'],
                '台中': ['日月潭', '溪頭', '合歡山', '武嶺'],
                '高雄': ['墾丁', '茂林', '美濃', '旗津'],
                '花蓮': ['太魯閣', '七星潭', '清水斷崖', '瑞穗'],
                '台東': ['知本', '池上', '台東市', '鹿野']
            };
            
            let html = '<div class="destination-suggestions">';
            html += '<h4>🎯 熱門目的地推薦</h4>';
            
            Object.entries(suggestions).forEach(([region, destinations]) => {
                html += `
                    <div class="suggestion-category">
                        <div class="suggestion-category-title">${region}</div>
                        <div class="suggestion-tags">
                `;
                
                destinations.forEach(destination => {
                    const isMountain = this.config.mountainKeywords.some(keyword => 
                        destination.includes(keyword));
                    const cssClass = isMountain ? 'suggestion-tag mountain' : 'suggestion-tag';
                    html += `<span class="${cssClass}" data-destination="${destination}">${destination}</span>`;
                });
                
                html += '</div></div>';
            });
            
            html += '</div>';
            return html;
        },
        
        // 生成需求區段
        generateRequirementsSection: function() {
            return `
                <div class="form-section" id="requirements-section">
                    <h3>📋 旅遊需求</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="accommodation_preference">住宿偏好</label>
                            <select id="accommodation_preference" name="accommodation_preference">
                                <option value="">無特別要求</option>
                                <option value="hotel">飯店</option>
                                <option value="bnb">民宿</option>
                                <option value="resort">度假村</option>
                                <option value="hostel">青年旅館</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="budget_range">預算範圍 (不含車費)</label>
                            <select id="budget_range" name="budget_range">
                                <option value="">無預算限制</option>
                                <option value="budget">經濟型 (NT$ 2,000以下/天)</option>
                                <option value="mid">舒適型 (NT$ 2,000-5,000/天)</option>
                                <option value="luxury">豪華型 (NT$ 5,000以上/天)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="travel_style">旅遊風格</label>
                            <div class="checkbox-group">
                                <div class="checkbox-item">
                                    <input type="checkbox" id="style_nature" name="travel_style[]" value="nature">
                                    <label for="style_nature">親近自然</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="style_culture" name="travel_style[]" value="culture">
                                    <label for="style_culture">文化體驗</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="style_food" name="travel_style[]" value="food">
                                    <label for="style_food">美食之旅</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="style_photo" name="travel_style[]" value="photography">
                                    <label for="style_photo">攝影旅行</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="special_requests">特殊需求或建議</label>
                            <textarea id="special_requests" name="special_requests" 
                                      rows="4" placeholder="例如：需要輪椅無障礙、素食需求、想避開人潮較多的景點等..."></textarea>
                        </div>
                    </div>
                </div>
            `;
        },
        
        // 生成客戶資訊區段
        generateCustomerInfoSection: function() {
            return `
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
                            <small class="form-help">填寫 Email 可收到行程確認信和旅遊建議</small>
                        </div>
                        <div class="form-group">
                            <label for="group_size">團體人數</label>
                            <select id="group_size" name="group_size">
                                <option value="1">1人</option>
                                <option value="2">2人</option>
                                <option value="3">3人</option>
                                <option value="4">4人</option>
                                <option value="5">5人</option>
                                <option value="6">6人</option>
                                <option value="7">7人</option>
                                <option value="8">8人</option>
                            </select>
                            <small class="form-help">九人座車輛最多可載8位乘客</small>
                        </div>
                    </div>
                </div>
            `;
        },
        
        // 生成司機費用說明
        generateDriverFeesSection: function() {
            return `
                <div class="driver-fees-section">
                    <div class="driver-fees-title">
                        <span>👨‍✈️ 司機費用說明</span>
                    </div>
                    <div class="driver-fee-item">
                        <span>司機住宿費</span>
                        <span>NT$ 2,000 / 晚</span>
                    </div>
                    <div class="driver-fee-item">
                        <span>司機餐費</span>
                        <span>NT$ 400 / 天</span>
                    </div>
                    <div class="driver-fee-item">
                        <span>總司機費用</span>
                        <span id="driver-total-fee">計算中...</span>
                    </div>
                </div>
            `;
        },
        
        // 生成提交區段
        generateSubmitSection: function() {
            return `
                <div class="form-section submit-section">
                    <div class="terms-agreement">
                        <input type="checkbox" id="agree_terms" name="agree_terms" required>
                        <label for="agree_terms">
                            我已閱讀並同意 <a href="#" target="_blank">服務條款</a> 和 <a href="#" target="_blank">隱私政策</a>
                        </label>
                    </div>
                    <button type="submit" class="btn-submit" id="submit-booking">
                        <span class="btn-text">確認預約</span>
                        <span class="btn-loading" style="display: none;">
                            <span class="spinner"></span> 處理中...
                        </span>
                    </button>
                    <small class="submit-note">
                        提交後我們將在24小時內與您聯繫確認行程細節
                    </small>
                </div>
            `;
        },
        
        // 初始化組件
        initializeComponents: function() {
            this.initAddressAutocomplete();
            this.initDateValidation();
            this.initPhoneValidation();
        },
        
        // 處理天數改變
        handleDaysChange: function(e) {
            this.state.tripDays = parseInt(e.target.value);
            this.generateDailyItinerary();
            this.updateDriverFees();
            this.calculatePrice();
        },
        
        // 處理出發地點改變
        handleStartingPointChange: function(e) {
            const isCustom = e.target.value === 'custom';
            $('#custom-starting-point').toggleClass('active', isCustom);
            
            if (isCustom) {
                $('#custom_starting_address').focus();
            }
        },
        
        // 生成每日行程
        generateDailyItinerary: function() {
            const container = $('#daily-itinerary-container');
            let html = '';
            
            for (let day = 1; day <= this.state.tripDays; day++) {
                html += this.generateDayContainer(day);
            }
            
            container.html(html);
            this.state.dailyRoutes = Array(this.state.tripDays).fill().map(() => []);
        },
        
        // 生成單日容器
        generateDayContainer: function(day) {
            const dayPrice = day === 1 ? this.config.basePrices.day1 : this.config.basePrices.day2plus;
            
            return `
                <div class="day-route-container" data-day="${day}">
                    <div class="day-header">
                        <div class="day-badge" id="day-badge-${day}">
                            <span>第 ${day} 天</span>
                        </div>
                        <div class="day-date" id="day-date-${day}">
                            <!-- 日期會自動計算 -->
                        </div>
                        <div class="day-price">
                            基本費用: NT$ ${dayPrice?.toLocaleString() || 0}
                        </div>
                    </div>
                    
                    <div class="destinations-container">
                        <div class="destination-item">
                            <label for="day_${day}_dest_0" class="destination-number">1</label>
                            <input type="text" class="destination-input" 
                                   id="day_${day}_dest_0"
                                   name="day_${day}_destinations[]" 
                                   placeholder="請輸入第${day}天的目的地或景點"
                                   aria-label="第${day}天第1個目的地"
                                   data-day="${day}" data-stop="0">
                            <button type="button" class="remove-destination" style="display: none;" aria-label="移除此目的地">×</button>
                        </div>
                    </div>
                    
                    <button type="button" class="add-destination" data-day="${day}" aria-label="為第${day}天新增目的地">
                        ➕ 新增目的地
                    </button>
                    
                    <div class="day-alerts" id="day-alerts-${day}">
                        <!-- 山區或排除地區警告 -->
                    </div>
                </div>
            `;
        },
        
        // 新增目的地
        addDestination: function(e) {
            const day = $(e.target).data('day');
            const container = $(e.target).siblings('.destinations-container');
            const currentCount = container.find('.destination-item').length;
            
            if (currentCount >= this.config.maxStops) {
                alert(this.config.texts.maxStopsReached || '已達到最大停靠點數量');
                return;
            }
            
            const html = `
                <div class="destination-item">
                    <label for="day_${day}_dest_${currentCount}" class="destination-number">${currentCount + 1}</label>
                    <input type="text" class="destination-input" 
                           id="day_${day}_dest_${currentCount}"
                           name="day_${day}_destinations[]" 
                           placeholder="請輸入目的地或景點"
                           aria-label="第${day}天第${currentCount + 1}個目的地"
                           data-day="${day}" data-stop="${currentCount}">
                    <button type="button" class="remove-destination" aria-label="移除此目的地">×</button>
                </div>
            `;
            
            container.append(html);
            this.updateDestinationNumbers(day);
        },
        
        // 移除目的地
        removeDestination: function(e) {
            const $item = $(e.target).closest('.destination-item');
            const day = $item.find('.destination-input').data('day');
            
            $item.remove();
            this.updateDestinationNumbers(day);
            this.checkDayMountainStatus(day);
            this.calculatePrice();
        },
        
        // 更新目的地編號
        updateDestinationNumbers: function(day) {
            const container = $(`.day-route-container[data-day="${day}"] .destinations-container`);
            
            container.find('.destination-item').each((index, item) => {
                $(item).find('.destination-number').text(index + 1);
                $(item).find('.destination-input').attr('data-stop', index);
                
                // 第一個目的地不能刪除
                const removeBtn = $(item).find('.remove-destination');
                removeBtn.toggle(index > 0);
            });
        },
        
        // 選擇推薦目的地
        selectSuggestion: function(e) {
            const destination = $(e.target).data('destination');
            const $inputs = $('.destination-input');
            
            // 找到第一個空的輸入框
            for (let i = 0; i < $inputs.length; i++) {
                if (!$inputs.eq(i).val().trim()) {
                    $inputs.eq(i).val(destination);
                    $inputs.eq(i).trigger('input');
                    break;
                }
            }
        },
        
        // 檢查目的地
        checkDestination: function(e) {
            const $input = $(e.target);
            const destination = $input.val().trim();
            const day = $input.data('day');
            
            if (!destination) return;
            
            // 檢查是否為山區
            const isMountain = this.config.mountainKeywords.some(keyword => 
                destination.includes(keyword));
            
            // 檢查是否為排除地區
            const isExcluded = this.config.excludedAreas.some(area => 
                destination.includes(area));
            
            // 更新視覺狀態
            $input.removeClass('mountain-detected excluded-area');
            if (isMountain) {
                $input.addClass('mountain-detected');
            }
            if (isExcluded) {
                $input.addClass('excluded-area');
            }
            
            this.checkDayMountainStatus(day);
            this.calculatePrice();
        },
        
        // 檢查某天是否有山區景點
        checkDayMountainStatus: function(day) {
            const $container = $(`.day-route-container[data-day="${day}"]`);
            const $inputs = $container.find('.destination-input');
            const $alertsContainer = $container.find('.day-alerts');
            
            let hasMountain = false;
            let hasExcluded = false;
            
            $inputs.each((index, input) => {
                const destination = $(input).val().trim();
                if (destination) {
                    if (this.config.mountainKeywords.some(keyword => destination.includes(keyword))) {
                        hasMountain = true;
                    }
                    if (this.config.excludedAreas.some(area => destination.includes(area))) {
                        hasExcluded = true;
                    }
                }
            });
            
            // 更新容器樣式
            $container.toggleClass('mountain-detected', hasMountain);
            
            // 更新天數標籤
            const $badge = $container.find('.day-badge');
            $badge.toggleClass('mountain', hasMountain);
            
            // 更新警告訊息
            let alertsHTML = '';
            if (hasMountain) {
                alertsHTML += `
                    <div class="mountain-alert">
                        <span class="alert-icon">🏔️</span>
                        此日行程包含山區景點，將加收山區費用 NT$ ${this.config.basePrices.mountain_surcharge?.toLocaleString() || 1000}
                    </div>
                `;
            }
            if (hasExcluded) {
                alertsHTML += `
                    <div class="excluded-alert">
                        <span class="alert-icon">⚠️</span>
                        此地區不在服務範圍內，請選擇其他目的地
                    </div>
                `;
            }
            
            $alertsContainer.html(alertsHTML);
            
            // 儲存山區檢測狀態
            this.state.mountainDetection[day] = hasMountain;
        },
        
        // 更新司機費用
        updateDriverFees: function() {
            const nights = Math.max(0, this.state.tripDays - 1);
            const days = this.state.tripDays;
            
            const accommodationFee = nights * (this.config.basePrices.driver_accommodation || 2000);
            const mealFee = days * (this.config.basePrices.driver_meals || 400);
            const totalDriverFee = accommodationFee + mealFee;
            
            $('#driver-total-fee').text(`NT$ ${totalDriverFee.toLocaleString()}`);
        },
        
        // 計算價格
        calculatePrice: function() {
            clearTimeout(this.state.calcTimeout);
            
            this.state.calcTimeout = setTimeout(() => {
                const formData = this.collectFormData();
                
                if (!this.isFormValidForCalculation(formData)) {
                    return;
                }
                
                this.showPriceLoading();
                
                BookingCommon.ajax.call('calculate_charter_fee', formData)
                    .then(response => {
                        if (response.success) {
                            this.displayPrice(response.data);
                            this.state.currentPrice = response.data.total;
                        } else {
                            this.showPriceError(response.data.message);
                        }
                    })
                    .catch(error => {
                        this.showPriceError('計算失敗，請重試');
                        console.error('Price calculation error:', error);
                    });
            }, 500);
        },
        
        // 收集表單資料
        collectFormData: function() {
            const formData = {};
            
            // 基本資料
            $('#charter-booking-form input, #charter-booking-form select, #charter-booking-form textarea').each(function() {
                const $this = $(this);
                const name = $this.attr('name');
                const type = $this.attr('type');
                
                if (name) {
                    if (type === 'checkbox') {
                        if (name.includes('[]')) {
                            const baseName = name.replace('[]', '');
                            if (!formData[baseName]) formData[baseName] = [];
                            if ($this.is(':checked')) {
                                formData[baseName].push($this.val());
                            }
                        } else {
                            formData[name] = $this.is(':checked') ? $this.val() : '';
                        }
                    } else if (type === 'radio') {
                        if ($this.is(':checked')) {
                            formData[name] = $this.val();
                        }
                    } else {
                        formData[name] = $this.val();
                    }
                }
            });
            
            // 每日路線資料
            formData.daily_routes = [];
            for (let day = 1; day <= this.state.tripDays; day++) {
                const destinations = [];
                $(`.day-route-container[data-day="${day}"] .destination-input`).each(function() {
                    const destination = $(this).val().trim();
                    if (destination) {
                        destinations.push(destination);
                    }
                });
                formData.daily_routes.push(destinations);
            }
            
            formData.mountain_detection = this.state.mountainDetection;
            
            return formData;
        },
        
        // 表單提交處理
        handleSubmit: function(e) {
            e.preventDefault();
            
            if (this.state.isSubmitting) return;
            
            const formData = this.collectFormData();
            
            if (!this.validateForm(formData)) {
                return;
            }
            
            this.state.isSubmitting = true;
            this.showSubmitLoading();
            
            BookingCommon.ajax.call('submit_charter_booking', formData)
                .then(response => {
                    if (response.success) {
                        this.showSubmitSuccess(response.data);
                        this.resetForm();
                    } else {
                        this.showSubmitError(response.data.message);
                    }
                })
                .catch(error => {
                    this.showSubmitError('提交失敗，請重試');
                    console.error('Submit error:', error);
                })
                .finally(() => {
                    this.state.isSubmitting = false;
                    this.hideSubmitLoading();
                });
        },
        
        // 初始化地址自動完成
        initAddressAutocomplete: function() {
            if (window.GoogleMapsManager) {
                window.GoogleMapsManager.initAutocomplete('#custom_starting_address');
            }
        },
        
        // 初始化日期驗證
        initDateValidation: function() {
            $(document).on('change', '#start_date', (e) => {
                this.updateDayDates();
            });
        },
        
        // 更新每日日期顯示
        updateDayDates: function() {
            const startDate = $('#start_date').val();
            if (!startDate) return;
            
            const baseDate = new Date(startDate);
            
            for (let day = 1; day <= this.state.tripDays; day++) {
                const currentDate = new Date(baseDate);
                currentDate.setDate(baseDate.getDate() + day - 1);
                
                const dateStr = currentDate.toLocaleDateString('zh-TW', {
                    month: 'numeric',
                    day: 'numeric',
                    weekday: 'short'
                });
                
                $(`#day-date-${day}`).text(dateStr);
            }
        },
        
        // 工具函數
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        // 表單驗證
        validateForm: function(formData) {
            const errors = [];
            
            // 基本欄位驗證
            if (!formData.trip_days) errors.push('請選擇旅遊天數');
            if (!formData.starting_point) errors.push('請選擇出發地點');
            if (formData.starting_point === 'custom' && !formData.custom_starting_address) {
                errors.push('請填寫自訂出發地址');
            }
            if (!formData.start_date) errors.push('請選擇出發日期');
            if (!formData.customer_name) errors.push('請填寫姓名');
            if (!formData.customer_phone) errors.push('請填寫電話');
            if (!formData.agree_terms) errors.push('請同意服務條款');
            
            // 檢查每日行程
            let hasEmptyRoute = false;
            formData.daily_routes.forEach((routes, index) => {
                if (routes.length === 0) {
                    hasEmptyRoute = true;
                }
            });
            if (hasEmptyRoute) errors.push('請填寫每日行程的目的地');
            
            // 電話格式驗證
            if (formData.customer_phone && !BookingCommon.validation.phone(formData.customer_phone)) {
                errors.push('請填寫正確的電話格式');
            }
            
            // Email格式驗證
            if (formData.customer_email && !BookingCommon.validation.email(formData.customer_email)) {
                errors.push('請填寫正確的Email格式');
            }
            
            if (errors.length > 0) {
                alert('請修正以下錯誤：\n' + errors.join('\n'));
                return false;
            }
            
            return true;
        },
        
        // 檢查表單是否可以計算價格
        isFormValidForCalculation: function(formData) {
            return formData.trip_days && 
                   formData.starting_point && 
                   formData.start_date && 
                   formData.daily_routes && 
                   formData.daily_routes.some(routes => routes.length > 0);
        },
        
        // UI 更新方法
        showPriceLoading: function() {
            $('#price-panel .price-content').html('<div class="price-loading">計算中...</div>');
        },
        
        displayPrice: function(priceData) {
            const html = `
                <div class="price-breakdown">
                    ${priceData.daily_breakdown ? priceData.daily_breakdown.map((day, index) => `
                    <div class="price-item ${day.has_mountain ? 'mountain' : ''}">
                        <span>第${index + 1}天 ${day.has_mountain ? '(山區)' : ''}</span>
                        <span>NT$ ${day.total?.toLocaleString() || 0}</span>
                    </div>`).join('') : ''}
                    
                    ${priceData.driver_fees ? `
                    <div class="price-item">
                        <span>司機費用</span>
                        <span>NT$ ${priceData.driver_fees.toLocaleString()}</span>
                    </div>` : ''}
                    
                    ${priceData.multi_day_discount ? `
                    <div class="price-item discount">
                        <span>多日優惠</span>
                        <span>-NT$ ${priceData.multi_day_discount.toLocaleString()}</span>
                    </div>` : ''}
                    
                    <div class="price-total">
                        <span>總計</span>
                        <span>NT$ ${priceData.total?.toLocaleString() || 0}</span>
                    </div>
                </div>
            `;
            
            $('#price-panel .price-content').html(html);
        },
        
        showPriceError: function(message) {
            $('#price-panel .price-content').html(`
                <div class="price-error">
                    <span class="error-icon">⚠️</span>
                    <span>${message || '計算失敗'}</span>
                </div>
            `);
        },
        
        showSubmitLoading: function() {
            $('#submit-booking .btn-text').hide();
            $('#submit-booking .btn-loading').show();
            $('#submit-booking').prop('disabled', true);
        },
        
        hideSubmitLoading: function() {
            $('#submit-booking .btn-text').show();
            $('#submit-booking .btn-loading').hide();
            $('#submit-booking').prop('disabled', false);
        },
        
        showSubmitSuccess: function(data) {
            const message = `
                預約成功！
                訂單編號：${data.booking_number}
                我們將在24小時內與您聯繫確認行程細節。
            `;
            
            BookingCommon.notifications.success(message, 5000);
        },
        
        showSubmitError: function(message) {
            BookingCommon.notifications.error(message || '提交失敗，請重試');
        },
        
	resetForm: function() {
	// 清理事件監聽器
	$(document).off('change.charter');
	$(document).off('submit.charter');
    
	// 清理計時器
	clearTimeout(this.state.calcTimeout);
    
	// 重置表單
 	$('#charter-booking-form')[0].reset();
    
	// 重置狀態
	this.state = {
 		tripDays: 1,
 		dailyRoutes: [],
 		isSubmitting: false,
 		currentPrice: 0,
 		calcTimeout: null,
 		mountainDetection: {},
		formData: {}
	};
    
   	 // 重新初始化
   	 this.generateDailyItinerary();
    	this.updateDriverFees();
	}
        
        // 初始化電話驗證
        initPhoneValidation: function() {
            $('#customer_phone').on('input', function() {
                const value = $(this).val();
                const formatted = BookingCommon.format.phone(value);
                if (formatted !== value) {
                    $(this).val(formatted);
                }
            });
        }
    };
    
    // 當 DOM 準備好時初始化
    $(document).ready(function() {
        if ($('#charter-booking-form').length) {
            window.CharterBookingModule.init();
        }
    });
    
})(jQuery);
