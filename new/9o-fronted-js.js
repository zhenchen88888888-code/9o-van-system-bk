/* ========================================
   9O預約系統前端腳本 V7.0
   整合機場接送與包車旅遊系統
   ======================================== */

(function($) {
    'use strict';
    
    // 等待jQuery載入
    if (typeof jQuery === 'undefined') {
        setTimeout(arguments.callee, 100);
        return;
    }
    
    // 動態載入Google Maps API
    if (typeof google === 'undefined' && !window.googleMapsLoading) {
        window.googleMapsLoading = true;
        const script = document.createElement('script');
        script.src = 'https://maps.googleapis.com/maps/api/js?key=AIzaSyD7m-umnnMHhXTi11_uvI14Rel6mCN1Dz4&libraries=places&language=zh-TW';
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    }
    
    // 動態計算最小日期（提前2天）
    function getMinDate() {
        const date = new Date();
        date.setDate(date.getDate() + 2);
        return date.toISOString().split('T')[0];
    }
    
    // 全域配置
    const CONFIG = {
        ajaxUrl: '/wp-admin/admin-ajax.php',
        minDate: getMinDate(),
        maxStopsAirport: 5,
        maxStopsCharter: 6,
        maxDays: 7,
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
    
    /* ========================================
       機場接送系統
       ======================================== */
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
            
            console.log('✅ 機場接送系統 V7.0 已啟動');
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
                        <label class="stop-label">停靠點（選填，最多${CONFIG.maxStopsAirport}個）</label>
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
                        <label class="stop-label">回程停靠點（選填，最多${CONFIG.maxStopsAirport}個）</label>
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
            
            if (stops >= CONFIG.maxStopsAirport) {
                alert(`最多只能新增${CONFIG.maxStopsAirport}個停靠點`);
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
        
        // 綁定事件
        bindEvents() {
            const self = this;
            
            // 使用事件委派減少綁定數量
            $(document)
                // 服務類型切換
                .on('change', '[name="service_type"]', function() {
                    const isPickup = $(this).val() === 'pickup';
                    
                    // 去程欄位顯示邏輯
                    $('.pickup-fields').toggle(isPickup);
                    $('.dropoff-fields').toggle(!isPickup);
                    
                    // 回程欄位顯示邏輯（相反）
                    $('.return-pickup-fields').toggle(!isPickup);
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
    
    /* ========================================
       包車旅遊系統
       ======================================== */
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
            
            console.log('✅ 包車旅遊系統 V7.0 已啟動');
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
        
        // 更新司機區塊
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
                        <label>預排停靠點（選填，最多${CONFIG.maxStopsCharter}個）</label>
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
            
            if (stopCount >= CONFIG.maxStopsCharter) {
                alert(`每日最多只能新增${CONFIG.maxStopsCharter}個停靠點`);
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
            
            // 總計
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
    
    /* ========================================
       DOM Ready 初始化
       ======================================== */
    $(document).ready(function() {
        // 初始化機場接送系統
        if ($('#airport-booking-form').length) {
            AirportBooking.init();
        }
        
        // 初始化包車旅遊系統
        if ($('#charter-booking-form').length) {
            CharterBooking.init();
        }
    });
    
})(jQuery);