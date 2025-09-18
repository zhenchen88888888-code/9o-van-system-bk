/* ========================================
   機場接送模組 JavaScript
   ======================================== */

// 等待依賴載入
(function() {
    'use strict';
    
    function waitForDependencies() {
    let retryCount = 0;
    const maxRetries = 100; // 最多等待 10 秒
    
    function check() {
        if (typeof jQuery !== 'undefined' && typeof BookingCommon !== 'undefined') {
            initAirportModule();
        } else if (retryCount++ < maxRetries) {
            setTimeout(check, 100);
        } else {
            console.error('Airport Module: Dependencies failed to load');
            document.getElementById('airport-booking-app')?.classList.add('loading-failed');
        }
    }
    check();
}
    
    waitForDependencies();
    
    function initAirportModule() {
        (function($) {
            'use strict';
            
            // 機場接送模組
            window.AirportBookingModule = {
                
                // 模組配置
                config: {
                    ajaxUrl: window.airportConfig?.ajaxUrl || '/wp-admin/admin-ajax.php',
                    nonce: window.airportConfig?.nonce || '',
                    airports: window.airportConfig?.airports || [],
                    remoteAreaSurcharge: window.airportConfig?.remoteAreaSurcharge || 400,
                    longDistanceSurcharge: window.airportConfig?.longDistanceSurcharge || 500,
                    nightTimeSurcharge: window.airportConfig?.nightTimeSurcharge || 400,
                    texts: window.airportConfig?.texts || {}
                },
                
                // 狀態管理
                state: {
                    selectedAirport: null,
                    selectedService: null,
                    isReturnTrip: false,
                    returnDate: null,
                    returnTime: null,
                    selectedStopover: null,
                    isRemoteArea: false,
                    remoteSurcharge: 0,
                    isSubmitting: false,
                    currentPrice: 0,
                    stopoverValidationTimer: null
                },
                
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
                    $(document).on('change', '#airport-booking-form input, #airport-booking-form select', 
                        this.debounce(this.calculatePrice.bind(this), 500));
                    $(document).on('submit', '#airport-booking-form', this.handleSubmit.bind(this));
                    $(document).on('change', 'input[name="airport"]', this.handleAirportChange.bind(this));
                    $(document).on('change', 'input[name="service_type"]', this.handleServiceChange.bind(this));
                    $(document).on('change', 'input[name="is_return_trip"]', this.handleReturnTripToggle.bind(this));
                    $(document).on('change', 'input[name="has_stopover"]', this.handleStopoverToggle.bind(this));
                    $(document).on('blur', '#stopover_address', this.validateStopoverDistance.bind(this));
                    $(document).on('blur', '#pickup_address, #dropoff_address', this.validateAddress.bind(this));
                },
                
                // 建立表單
                buildForm: function() {
                    const $form = $('#airport-booking-form');
                    if (!$form.length) return;
                    
                    const formHTML = this.generateFormHTML();
                    $form.html(formHTML);
                    
                    // 設定預設值
                    this.setDefaults();
                },
                
                // 生成表單HTML
                generateFormHTML: function() {
                    return `
                        <div class="form-header">
                            <h2>✈️ 機場接送預約</h2>
                            <p>安全準時·舒適九人座·專業服務</p>
                        </div>
                        
                        <div class="form-content">
                            ${this.generateAirportSelection()}
                            ${this.generateServiceTypeSelection()}
                            ${this.generateLocationSection()}
                            ${this.generateReturnTripSection()}
                            ${this.generateStopoverSection()}
                            ${this.generateFlightSection()}
                            ${this.generateTimeSection()}
                            ${this.generateCustomerInfoSection()}
                            ${this.generatePriceDisplay()}
                            ${this.generateSubmitSection()}
                        </div>
                    `;
                },
                
                // 生成機場選擇
                generateAirportSelection: function() {
                    let html = '<div class="form-section" id="airport-section">';
                    html += '<h3>🛫 選擇機場</h3>';
                    html += '<div class="airport-selection">';
                    
                    this.config.airports.forEach(airport => {
                        html += `
                            <div class="airport-option">
                                <input type="radio" name="airport" value="${airport.value}" id="airport-${airport.value}" required>
                                <label class="airport-label" for="airport-${airport.value}">
                                    <span class="airport-icon">${airport.icon}</span>
                                    <span class="airport-name">${airport.label}</span>
                                    ${airport.description ? `<span class="airport-desc">${airport.description}</span>` : ''}
                                </label>
                            </div>
                        `;
                    });
                    
                    html += '</div></div>';
                    return html;
                },
                
                // 生成服務類型選擇
                generateServiceTypeSelection: function() {
                    return `
                        <div class="form-section" id="service-type-section" style="display: none;">
                            <h3>🚗 選擇服務類型</h3>
                            <div class="service-type-selection">
                                <div class="service-option">
                                    <input type="radio" name="service_type" value="pickup" id="service-pickup" required>
                                    <label class="service-label" for="service-pickup">
                                        <span class="service-icon">🛬</span>
                                        <span class="service-name">機場接機</span>
                                        <span class="service-desc">從機場到您的目的地</span>
                                    </label>
                                </div>
                                <div class="service-option">
                                    <input type="radio" name="service_type" value="dropoff" id="service-dropoff">
                                    <label class="service-label" for="service-dropoff">
                                        <span class="service-icon">🛫</span>
                                        <span class="service-name">機場送機</span>
                                        <span class="service-desc">從您的地點到機場</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    `;
                },
                
                // 生成地點區段
                generateLocationSection: function() {
                    return `
                        <div class="form-section" id="location-section" style="display: none;">
                            <h3>📍 上下車地點</h3>
                            
                            <div class="form-row" id="pickup-location">
                                <div class="form-group">
                                    <label for="pickup_address">
                                        <span class="pickup-label">上車地點</span>
                                        <span class="required">*</span>
                                    </label>
                                    <input type="text" id="pickup_address" name="pickup_address" 
                                           placeholder="請輸入詳細地址" required>
                                    <div class="address-suggestions" id="pickup-suggestions"></div>
                                </div>
                            </div>
                            
                            <div class="form-row" id="dropoff-location">
                                <div class="form-group">
                                    <label for="dropoff_address">
                                        <span class="dropoff-label">下車地點</span>
                                        <span class="required">*</span>
                                    </label>
                                    <input type="text" id="dropoff_address" name="dropoff_address" 
                                           placeholder="請輸入詳細地址" required>
                                    <div class="address-suggestions" id="dropoff-suggestions"></div>
                                </div>
                            </div>
                            
                            <div class="remote-area-notice" id="remote-area-notice" style="display: none;">
                                <div class="notice-icon">⚠️</div>
                                <div class="notice-content">
                                    <div class="notice-title">偏遠地區加價通知</div>
                                    <div class="notice-message">
                                        此地址位於偏遠地區，需加收 NT$ <span id="remote-surcharge">400</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                },
                
                // 生成來回程區段
                generateReturnTripSection: function() {
                    return `
                        <div class="form-section" id="return-trip-section" style="display: none;">
                            <div class="section-header">
                                <h3>🔄 來回接送</h3>
                                <div class="toggle-switch">
                                    <input type="checkbox" id="is_return_trip" name="is_return_trip" value="1" aria-describedby="return-trip-desc">
                                    <label for="is_return_trip" class="switch-label">
                                        <span class="switch-handle"></span>
                                        <span class="switch-text">需要來回接送</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="return-trip-details" id="return-trip-details" style="display: none;">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="return_date">回程日期 <span class="required">*</span></label>
                                        <input type="date" id="return_date" name="return_date">
                                    </div>
                                    <div class="form-group">
                                        <label for="return_time">回程時間 <span class="required">*</span></label>
                                        <input type="time" id="return_time" name="return_time">
                                    </div>
                                </div>
                                <div class="return-trip-note">
                                    💡 來回接送可享優惠價格
                                </div>
                            </div>
                        </div>
                    `;
                },
                
                // 生成中途停靠區段
                generateStopoverSection: function() {
                    return `
                        <div class="form-section" id="stopover-section" style="display: none;">
                            <div class="section-header">
                                <h3>📍 中途停靠</h3>
                                <div class="toggle-switch">
                                    <input type="checkbox" id="has_stopover" name="has_stopover" value="1" aria-describedby="stopover-desc">
                                    <label for="has_stopover" class="switch-label">
                                        <span class="switch-handle"></span>
                                        <span class="switch-text">需要中途停靠</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="stopover-details" id="stopover-details" style="display: none;">
                                <div class="form-group">
                                    <label for="stopover_address">停靠地點 <span class="required">*</span></label>
                                    <input type="text" id="stopover_address" name="stopover_address" 
                                           placeholder="請輸入中途停靠地址">
                                    <div class="address-suggestions" id="stopover-suggestions"></div>
                                    <small class="form-help">中途停靠將依實際繞路距離計費</small>
                                </div>
                                <div class="stopover-validation" id="stopover-validation"></div>
                            </div>
                        </div>
                    `;
                },
                
                // 生成航班資訊區段
                generateFlightSection: function() {
                    return `
                        <div class="form-section" id="flight-section" style="display: none;">
                            <h3>✈️ 航班資訊</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="flight_number">航班編號</label>
                                    <input type="text" id="flight_number" name="flight_number" 
                                           placeholder="例如：BR123">
                                    <small class="form-help">填寫航班編號有助於司機掌握航班動態</small>
                                </div>
                                <div class="form-group">
                                    <label for="terminal">航廈</label>
                                    <select id="terminal" name="terminal">
                                        <option value="">請選擇</option>
                                        <option value="1">第一航廈</option>
                                        <option value="2">第二航廈</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    `;
                },
                
                // 生成時間區段
                generateTimeSection: function() {
                    return `
                        <div class="form-section" id="time-section" style="display: none;">
                            <h3>🕐 接送時間</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="service_date">
                                        <span class="date-label">接送日期</span>
                                        <span class="required">*</span>
                                    </label>
                                    <input type="date" id="service_date" name="service_date" required>
                                </div>
                                <div class="form-group">
                                    <label for="service_time">
                                        <span class="time-label">接送時間</span>
                                        <span class="required">*</span>
                                    </label>
                                    <input type="time" id="service_time" name="service_time" required>
                                    <small class="form-help time-note"></small>
                                </div>
                            </div>
                            <div class="night-time-notice" id="night-time-notice" style="display: none;">
                                <div class="notice-icon">🌙</div>
                                <div class="notice-content">
                                    <div class="notice-title">深夜時段加價</div>
                                    <div class="notice-message">
                                        22:00 - 06:00 為深夜時段，需加收 NT$ 400
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                },
                
                // 生成客戶資訊區段
                generateCustomerInfoSection: function() {
                    return `
                        <div class="form-section" id="customer-info-section" style="display: none;">
                            <h3>👤 聯絡資訊</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="customer_name">姓名 <span class="required">*</span></label>
                                    <input type="text" id="customer_name" name="customer_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="customer_phone">電話 <span class="required">*</span></label>
                                    <input type="tel" id="customer_phone" name="customer_phone" 
                                           placeholder="09XX-XXX-XXX" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="customer_email">Email</label>
                                    <input type="email" id="customer_email" name="customer_email">
                                    <small class="form-help">填寫 Email 可收到訂單確認信</small>
                                </div>
                                <div class="form-group">
                                    <label for="passenger_count">乘客人數 <span class="required">*</span></label>
                                    <select id="passenger_count" name="passenger_count" required>
                                        <option value="1">1人</option>
                                        <option value="2">2人</option>
                                        <option value="3">3人</option>
                                        <option value="4">4人</option>
                                        <option value="5">5人</option>
                                        <option value="6">6人</option>
                                        <option value="7">7人</option>
                                        <option value="8">8人</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="special_requirements">特殊需求</label>
                                <textarea id="special_requirements" name="special_requirements" 
                                          rows="3" placeholder="例如：需要兒童座椅、大件行李、寵物隨行等"></textarea>
                            </div>
                        </div>
                    `;
                },
                
                // 生成價格顯示
                generatePriceDisplay: function() {
                    return `
                        <div class="price-display" id="price-display" style="display: none;">
                            <div class="price-header">
                                <h3>💰 費用明細</h3>
                            </div>
                            <div class="price-content" id="price-content">
                                <div class="price-loading">
                                    <span class="spinner"></span>
                                    計算中...
                                </div>
                            </div>
                        </div>
                    `;
                },
                
                // 生成提交區段
                generateSubmitSection: function() {
                    return `
                        <div class="form-section submit-section" style="display: none;">
                            <div class="terms-agreement">
                                <input type="checkbox" id="agree_terms" name="agree_terms" required>
                                <label for="agree_terms">
                                    我已閱讀並同意 <a href="#" target="_blank">服務條款</a>
                                </label>
                            </div>
                            <button type="submit" class="btn-submit" id="submit-booking">
                                <span class="btn-text">確認預約</span>
                                <span class="btn-loading" style="display: none;">
                                    <span class="spinner"></span> 處理中...
                                </span>
                            </button>
                            <small class="submit-note">
                                預約成功後，我們將在30分鐘內與您聯繫確認
                            </small>
                        </div>
                    `;
                },
                
                // 初始化組件
                initializeComponents: function() {
                    this.initAddressAutocomplete();
                    this.initDateTimePickers();
                    this.initPhoneValidation();
                },
                
                // 處理機場選擇
                handleAirportChange: function(e) {
                    this.state.selectedAirport = e.target.value;
                    $('#service-type-section').slideDown();
                    this.updateAirportPrices();
                },
                
                // 處理服務類型選擇
                handleServiceChange: function(e) {
                    this.state.selectedService = e.target.value;
                    
                    // 顯示相關區段
                    $('#location-section, #return-trip-section, #stopover-section, #flight-section, #time-section, #customer-info-section').slideDown();
                    
                    // 更新標籤文字
                    const isPickup = this.state.selectedService === 'pickup';
                    $('.pickup-label').text(isPickup ? '接機地點（機場）' : '上車地點');
                    $('.dropoff-label').text(isPickup ? '目的地' : '送機地點（機場）');
                    $('.date-label').text(isPickup ? '接機日期' : '送機日期');
                    $('.time-label').text(isPickup ? '航班抵達時間' : '預計出發時間');
                    $('.time-note').text(isPickup ? '請填寫航班預計抵達時間' : '建議預留2小時以上到機場');
                    
                    // 設定機場地址
                    if (isPickup) {
                        $('#pickup_address').val(this.getAirportAddress()).prop('readonly', true);
                        $('#dropoff_address').val('').prop('readonly', false);
                    } else {
                        $('#pickup_address').val('').prop('readonly', false);
                        $('#dropoff_address').val(this.getAirportAddress()).prop('readonly', true);
                    }
                },
                
                // 處理來回程切換
                handleReturnTripToggle: function(e) {
                    this.state.isReturnTrip = e.target.checked;
                    $('#return-trip-details').slideToggle();
                    
                    if (this.state.isReturnTrip) {
                        $('#return_date, #return_time').prop('required', true);
                    } else {
                        $('#return_date, #return_time').prop('required', false);
                    }
                },
                
                // 處理中途停靠切換
                handleStopoverToggle: function(e) {
                    const hasStopover = e.target.checked;
                    $('#stopover-details').slideToggle();
                    
                    if (hasStopover) {
                        $('#stopover_address').prop('required', true);
                    } else {
                        $('#stopover_address').prop('required', false).val('');
                        $('#stopover-validation').empty();
                    }
                },
                
                // 驗證中途停靠距離
                validateStopoverDistance: function() {
                    const stopoverAddress = $('#stopover_address').val().trim();
                    if (!stopoverAddress) return;
                    
                    clearTimeout(this.state.stopoverValidationTimer);
                    
                    this.state.stopoverValidationTimer = setTimeout(() => {
                        const $validation = $('#stopover-validation');
                        $validation.html('<div class="validation-loading">驗證中...</div>');
                        
                        const data = {
                            stopover: stopoverAddress,
                            pickup: $('#pickup_address').val(),
                            dropoff: $('#dropoff_address').val()
                        };
                        
                        BookingCommon.ajax.call('validate_stopover_distance', data)
                            .then(response => {
                                if (response.success) {
                                    const result = response.data;
                                    if (result.is_valid) {
                                        $validation.html(`
                                            <div class="validation-success">
                                                ✅ 繞路距離：${result.detour_distance} 公里
                                                ${result.extra_fee > 0 ? `，加收 NT$ ${result.extra_fee}` : ''}
                                            </div>
                                        `);
                                    } else {
                                        $validation.html(`
                                            <div class="validation-error">
                                                ❌ ${result.message}
                                            </div>
                                        `);
                                    }
                                } else {
                                    $validation.html(`
                                        <div class="validation-error">
                                            ❌ 驗證失敗，請重試
                                        </div>
                                    `);
                                }
                            })
                            .catch(error => {
                                console.error('Stopover validation error:', error);
                                $validation.html(`
                                    <div class="validation-error">
                                        ❌ 驗證失敗，請重試
                                    </div>
                                `);
                            });
                    }, 1000);
                },
                
                // 驗證地址
                validateAddress: function(e) {
                    const $input = $(e.target);
                    const address = $input.val().trim();
                    
                    if (!address || address === this.getAirportAddress()) return;
                    
                    BookingCommon.ajax.call('validate_address', { address: address })
                        .then(response => {
                            if (response.success && response.data.is_remote) {
                                this.state.isRemoteArea = true;
                                this.state.remoteSurcharge = response.data.surcharge;
                                $('#remote-surcharge').text(response.data.surcharge);
                                $('#remote-area-notice').slideDown();
                            } else {
                                if ($input.attr('id') === 'pickup_address' || $input.attr('id') === 'dropoff_address') {
                                    this.state.isRemoteArea = false;
                                    this.state.remoteSurcharge = 0;
                                    $('#remote-area-notice').slideUp();
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Address validation error:', error);
                        });
                },
                
                // 計算價格
                calculatePrice: function() {
                    const data = this.collectFormData();
                    
                    if (!this.validatePriceCalculation(data)) {
                        return;
                    }
                    
                    $('#price-display').show();
                    $('#price-content').html('<div class="price-loading"><span class="spinner"></span> 計算中...</div>');
                    
                    BookingCommon.ajax.call('calculate_airport_fee', data)
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
                },
                
                // 顯示價格
                displayPrice: function(priceData) {
                    let html = '<div class="price-breakdown">';
                    
                    // 基本費用
                    html += `
                        <div class="price-item">
                            <span>基本費用</span>
                            <span>NT$ ${priceData.base_price?.toLocaleString() || 0}</span>
                        </div>
                    `;
                    
                    // 加價項目
                    if (priceData.surcharges) {
                        Object.entries(priceData.surcharges).forEach(([key, value]) => {
                            if (value > 0) {
                                const labels = {
                                    'night_time': '深夜時段加價',
                                    'remote_area': '偏遠地區加價',
                                    'long_distance': '長途加價',
                                    'stopover': '中途停靠費用'
                                };
                                html += `
                                    <div class="price-item surcharge">
                                        <span>${labels[key] || key}</span>
                                        <span>NT$ ${value.toLocaleString()}</span>
                                    </div>
                                `;
                            }
                        });
                    }
                    
                    // 來回優惠
                    if (priceData.return_discount) {
                        html += `
                            <div class="price-item discount">
                                <span>來回優惠</span>
                                <span>-NT$ ${priceData.return_discount.toLocaleString()}</span>
                            </div>
                        `;
                    }
                    
                    // 總計
                    html += `
                        <div class="price-total">
                            <span>總計</span>
                            <span>NT$ ${priceData.total?.toLocaleString() || 0}</span>
                        </div>
                    `;
                    
                    html += '</div>';
                    
                    $('#price-content').html(html);
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
                    
                    BookingCommon.ajax.call('submit_airport_booking', formData)
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
                
                // 收集表單資料
                collectFormData: function() {
                    const formData = {};
                    
                    $('#airport-booking-form').find('input, select, textarea').each(function() {
                        const $this = $(this);
                        const name = $this.attr('name');
                        const type = $this.attr('type');
                        
                        if (name) {
                            if (type === 'checkbox') {
                                formData[name] = $this.is(':checked') ? $this.val() : '';
                            } else if (type === 'radio') {
                                if ($this.is(':checked')) {
                                    formData[name] = $this.val();
                                }
                            } else {
                                formData[name] = $this.val();
                            }
                        }
                    });
                    
                    // 加入計算出的資訊
                    formData.is_remote_area = this.state.isRemoteArea;
                    formData.remote_surcharge = this.state.remoteSurcharge;
                    formData.total_price = this.state.currentPrice;
                    
                    return formData;
                },
                
                // 表單驗證
                validateForm: function(data) {
                    const errors = [];
                    
                    // 基本欄位驗證
                    if (!data.airport) errors.push('請選擇機場');
                    if (!data.service_type) errors.push('請選擇服務類型');
                    if (!data.pickup_address) errors.push('請填寫上車地點');
                    if (!data.dropoff_address) errors.push('請填寫下車地點');
                    if (!data.service_date) errors.push('請選擇日期');
                    if (!data.service_time) errors.push('請選擇時間');
                    if (!data.customer_name) errors.push('請填寫姓名');
                    if (!data.customer_phone) errors.push('請填寫電話');
                    if (!data.passenger_count) errors.push('請選擇乘客人數');
                    if (!data.agree_terms) errors.push('請同意服務條款');
                    
                    // 來回程驗證
                    if (data.is_return_trip === '1') {
                        if (!data.return_date) errors.push('請選擇回程日期');
                        if (!data.return_time) errors.push('請選擇回程時間');
                    }
                    
                    // 中途停靠驗證
                    if (data.has_stopover === '1' && !data.stopover_address) {
                        errors.push('請填寫中途停靠地址');
                    }
                    
                    // 電話格式驗證
                    if (data.customer_phone && !BookingCommon.validation.phone(data.customer_phone)) {
                        errors.push('請填寫正確的電話格式');
                    }
                    
                    // Email格式驗證
                    if (data.customer_email && !BookingCommon.validation.email(data.customer_email)) {
                        errors.push('請填寫正確的Email格式');
                    }
                    
                    if (errors.length > 0) {
                        alert('請修正以下錯誤：\n' + errors.join('\n'));
                        return false;
                    }
                    
                    return true;
                },
                
                // 初始化地址自動完成
                initAddressAutocomplete: function() {
                    if (window.GoogleMapsManager) {
                        window.GoogleMapsManager.initAutocomplete('#pickup_address');
                        window.GoogleMapsManager.initAutocomplete('#dropoff_address');
                        window.GoogleMapsManager.initAutocomplete('#stopover_address');
                    }
                },
                
                // 初始化日期時間選擇器
                initDateTimePickers: function() {
                    // 設定最小日期為明天
                    const tomorrow = new Date();
                    tomorrow.setDate(tomorrow.getDate() + 1);
                    const minDate = tomorrow.toISOString().split('T')[0];
                    
                    $('#service_date, #return_date').attr('min', minDate);
                    
                    // 監聽時間變化以檢查深夜時段
                    $('#service_time').on('change', this.checkNightTime.bind(this));
                },
                
                // 檢查深夜時段
                checkNightTime: function() {
                    const time = $('#service_time').val();
                    if (!time) return;
                    
                    const hour = parseInt(time.split(':')[0]);
                    const isNightTime = hour >= 22 || hour < 6;
                    
                    $('#night-time-notice').toggle(isNightTime);
                },
                
                // 工具函數
                debounce: function(func, wait) {
                    let timeout;
                    function debounced(...args) {
                            clearTimeout(timeout);
                            timeout = setTimeout(() => func(...args), wait);
                        }
                        debounced.cancel = () => clearTimeout(timeout);
                        return debounced;
                }
                
                getAirportAddress: function() {
                    const airport = this.config.airports.find(a => a.value === this.state.selectedAirport);
                    return airport ? airport.address : '';
                },
                
                setDefaults: function() {
                    // 設定預設日期為明天
                    const tomorrow = new Date();
                    tomorrow.setDate(tomorrow.getDate() + 1);
                    $('#service_date').val(tomorrow.toISOString().split('T')[0]);
                    
                    // 設定預設時間為早上9點
                    $('#service_time').val('09:00');
                    
                    // 設定預設乘客數為1
                    $('#passenger_count').val('1');
                },
                
                resetForm: function() {
                    $('#airport-booking-form')[0].reset();
                    this.state = {
                        selectedAirport: null,
                        selectedService: null,
                        isReturnTrip: false,
                        returnDate: null,
                        returnTime: null,
                        selectedStopover: null,
                        isRemoteArea: false,
                        remoteSurcharge: 0,
                        isSubmitting: false,
                        currentPrice: 0,
                        stopoverValidationTimer: null
                    };
                    
                    // 隱藏所有區段
                    $('#service-type-section, #location-section, #return-trip-section, #stopover-section, #flight-section, #time-section, #customer-info-section, #price-display').hide();
                    $('#return-trip-details, #stopover-details, #remote-area-notice, #night-time-notice').hide();
                    
                    this.setDefaults();
                },
                
                // UI更新方法
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
                        我們將在30分鐘內與您聯繫確認。
                    `;
                    
                    BookingCommon.notifications.success(message, 5000);
                },
                
                showSubmitError: function(message) {
                    BookingCommon.notifications.error(message || '提交失敗，請重試');
                },
                
                showPriceError: function(message) {
                    $('#price-content').html(`
                        <div class="price-error">
                            <span class="error-icon">⚠️</span>
                            <span>${message || '計算失敗'}</span>
                        </div>
                    `);
                },
                
                updateAirportPrices: function() {
                    // 可在此更新顯示的基本價格
                    const airport = this.config.airports.find(a => a.value === this.state.selectedAirport);
                    if (airport && airport.base_prices) {
                        // 更新UI顯示基本價格資訊
                    }
                },
                
                validatePriceCalculation: function(data) {
                    // 檢查是否有足夠資料計算價格
                    return data.airport && 
                           data.service_type && 
                           data.pickup_address && 
                           data.dropoff_address && 
                           data.service_date && 
                           data.service_time;
                },
                
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
                if ($('#airport-booking-form').length) {
                    window.AirportBookingModule.init();
                }
            });
            
        })(jQuery);
    }
})();
