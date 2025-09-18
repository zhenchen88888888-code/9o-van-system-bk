/* ========================================
   9O 預約系統 - 共用 JavaScript 工具
   ======================================== */

(function($) {
    'use strict';

    // 改進的依賴檢查
    let retryCount = 0;
    const maxRetries = 100;
    
    if (typeof jQuery === 'undefined') {
        if (retryCount++ < maxRetries) {
            setTimeout(arguments.callee, 50);
        } else {
            console.error('9O Booking: jQuery failed to load');
        }
        return;
    }
    
    // 共用預約系統工具
    window.BookingCommon = {
        
        // AJAX 請求處理器
        ajax: {
            config: {
                url: window.wpBooking?.ajaxUrl || '/wp-admin/admin-ajax.php',
                nonce: window.wpBooking?.nonce || '',
                timeout: 30000
            },
            
            /**
             * 統一的 AJAX 請求方法
             * @param {string} action - WordPress AJAX action
             * @param {Object} data - 請求資料
             * @param {Object} options - 額外選項
             * @returns {Promise}
             */
            call: function(action, data = {}, options = {}) {
                const requestData = {
                    action: action,
                    nonce: this.config.nonce,
                    ...data
                };
                
                const ajaxOptions = {
                    url: this.config.url,
                    type: 'POST',
                    data: requestData,
                    timeout: options.timeout || this.config.timeout,
                    dataType: 'json',
		    headers: {  // 加入這個
			'X-Requested-With': 'XMLHttpRequest',
			'Cache-Control': 'no-cache'
		   };
                    ...options
                };
                
                return new Promise((resolve, reject) => {
                    $.ajax(ajaxOptions)
                        .done(function(response) {
                            if (response && typeof response === 'object') {
                                resolve(response);
                            } else {
                                reject(new Error('Invalid response format'));
                            }
                        })
                        .fail(function(xhr, status, error) {
                            console.error('AJAX Request Failed:', {
                                action: action,
                                status: status,
                                error: error,
                                responseText: xhr.responseText
                            });
                            
                            reject(new Error(`AJAX Error: ${status} - ${error}`));
                        });
                });
            }
        },
        
        // 表單驗證工具
        validation: {
            /**
             * 驗證電話號碼
             * @param {string} phone - 電話號碼
             * @returns {boolean}
             */
            phone: function(phone) {
                const phoneRegex = /^09\d{8}$|^0\d{1,2}-?\d{6,8}$/;
                return phoneRegex.test(phone.replace(/\D/g, ''));
            },
            
            /**
             * 驗證Email
             * @param {string} email - Email地址
             * @returns {boolean}
             */
            email: function(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            },
            
            /**
             * 驗證日期
             * @param {string} date - 日期字串
             * @param {number} minDaysFromNow - 最少幾天後
             * @returns {boolean}
             */
            date: function(date, minDaysFromNow = 2) {
                if (!date) return false;
                
                const selectedDate = new Date(date);
                const minDate = new Date();
                minDate.setDate(minDate.getDate() + minDaysFromNow);
                
                return selectedDate >= minDate;
            },
            
            /**
             * 顯示欄位錯誤
             * @param {jQuery} $field - 欄位元素
             * @param {string} message - 錯誤訊息
             */
            showFieldError: function($field, message) {
                $field.addClass('error');
                
                // 移除舊的錯誤訊息
                $field.siblings('.field-error').remove();
                
                // 添加新的錯誤訊息
                const errorHTML = `<div class="field-error">${message}</div>`;
                $field.after(errorHTML);
            },
            
            /**
             * 隱藏欄位錯誤
             * @param {jQuery} $field - 欄位元素
             */
            hideFieldError: function($field) {
                $field.removeClass('error');
                $field.siblings('.field-error').remove();
            }
        },
        
        // 格式化工具
        format: {
            /**
             * 格式化價格
             * @param {number} price - 價格
             * @param {string} currency - 幣別
             * @returns {string}
             */
            price: function(price, currency = 'NT$') {
                if (isNaN(price)) return currency + ' 0';
                return currency + ' ' + parseInt(price).toLocaleString();
            },
            
            /**
             * 格式化電話號碼
             * @param {string} phone - 電話號碼
             * @returns {string}
             */
            phone: function(phone) {
                const digits = phone.replace(/\D/g, '');
                if (digits.length === 10 && digits.startsWith('09')) {
                    return digits.replace(/^(\d{4})(\d{3})(\d{3})$/, '$1-$2-$3');
                } else if (digits.length >= 9) {
                    return digits.replace(/^(\d{2,3})(\d{3,4})(\d{4})$/, '$1-$2-$3');
                }
                return phone;
            },
            
            /**
             * 格式化日期
             * @param {string} date - 日期字串
             * @param {Object} options - 格式選項
             * @returns {string}
             */
            date: function(date, options = {}) {
                if (!date) return '';
                
                const defaultOptions = {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    weekday: 'long'
                };
                
                const formatOptions = { ...defaultOptions, ...options };
                return new Date(date).toLocaleDateString('zh-TW', formatOptions);
            }
        },
        
        // UI 反饋工具
        loading: {
            /**
             * 顯示載入狀態
             * @param {jQuery} $container - 容器元素
             * @param {string} message - 載入訊息
             */
            show: function($container, message = '載入中...') {
                const loadingHTML = `
                    <div class="loading-state">
                        <span class="spinner"></span>
                        <span>${message}</span>
                    </div>
                `;
                $container.html(loadingHTML);
            },
            
            /**
             * 隱藏載入狀態
             * @param {jQuery} $container - 容器元素
             */
            hide: function($container) {
                $container.find('.loading-state').remove();
            }
        },
        
        error: {
            /**
             * 顯示錯誤狀態
             * @param {jQuery} $container - 容器元素
             * @param {string} message - 錯誤訊息
             */
            show: function($container, message = '發生錯誤') {
                const errorHTML = `
                    <div class="error-state">
                        <span>⚠️ ${message}</span>
                    </div>
                `;
                $container.html(errorHTML);
            }
        },
        
        success: {
            /**
             * 顯示成功狀態
             * @param {jQuery} $container - 容器元素
             * @param {string} message - 成功訊息
             */
            show: function($container, message = '操作成功') {
                const successHTML = `
                    <div class="success-state">
                        <span>✅ ${message}</span>
                    </div>
                `;
                $container.html(successHTML);
            }
        },
        
        // 通知系統
        notifications: {
            /**
             * 顯示通知
             * @param {string} message - 訊息內容
             * @param {string} type - 訊息類型 (success, error, warning, info)
             * @param {number} duration - 顯示時間 (毫秒)
             */
            show: function(message, type = 'info', duration = 5000) {
                const notificationId = 'notification-' + Date.now();
                const iconMap = {
                    success: '✅',
                    error: '❌',
                    warning: '⚠️',
                    info: 'ℹ️'
                };
                
                const notificationHTML = `
                    <div id="${notificationId}" class="notification notification-${type}">
                        <span class="notification-icon">${iconMap[type] || 'ℹ️'}</span>
                        <span class="notification-message">${message}</span>
                        <button class="notification-close" onclick="this.parentElement.remove()">×</button>
                    </div>
                `;
                
                // 添加到頁面
                if (!$('#notifications-container').length) {
                    $('body').append('<div id="notifications-container"></div>');
                }
                
                $('#notifications-container').append(notificationHTML);
                
                // 自動移除
                if (duration > 0) {
                    setTimeout(() => {
                        $('#' + notificationId).fadeOut(300, function() {
                            $(this).remove();
                        });
                    }, duration);
                }
            },
            
            success: function(message, duration) {
                this.show(message, 'success', duration);
            },
            
            error: function(message, duration) {
                this.show(message, 'error', duration);
            },
            
            warning: function(message, duration) {
                this.show(message, 'warning', duration);
            },
            
            info: function(message, duration) {
                this.show(message, 'info', duration);
            }
        },
        
        // 工具函數
        utils: {
            /**
             * 防抖函數
             * @param {Function} func - 要執行的函數
             * @param {number} wait - 延遲時間
             * @returns {Function}
             */
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
            
            /**
             * 節流函數
             * @param {Function} func - 要執行的函數
             * @param {number} limit - 時間限制
             * @returns {Function}
             */
            throttle: function(func, limit) {
                let inThrottle;
                return function() {
                    const args = arguments;
                    const context = this;
                    if (!inThrottle) {
                        func.apply(context, args);
                        inThrottle = true;
                        setTimeout(() => inThrottle = false, limit);
                    }
                };
            },
            
            /**
             * 深度複製物件
             * @param {Object} obj - 要複製的物件
             * @returns {Object}
             */
            deepClone: function(obj) {
                if (obj === null || typeof obj !== 'object') return obj;
                if (obj instanceof Date) return new Date(obj.getTime());
                if (obj instanceof Array) return obj.map(item => this.deepClone(item));
                
                const clonedObj = {};
                for (let key in obj) {
                    if (obj.hasOwnProperty(key)) {
                        clonedObj[key] = this.deepClone(obj[key]);
                    }
                }
                return clonedObj;
            },
            
            /**
             * 生成隨機ID
             * @param {number} length - ID長度
             * @returns {string}
             */
            generateId: function(length = 8) {
                const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                let result = '';
                for (let i = 0; i < length; i++) {
                    result += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                return result;
            }
        },
        
        // 地址處理工具
        address: {
            /**
             * 標準化地址格式
             * @param {string} address - 原始地址
             * @returns {string}
             */
            normalize: function(address) {
                if (!address) return '';
                
                // 移除多餘空格
                address = address.trim().replace(/\s+/g, ' ');
                
                // 標準化縣市名稱
                const cityMap = {
                    '臺北': '台北',
                    '臺中': '台中',
                    '臺南': '台南',
                    '臺東': '台東'
                };
                
                Object.keys(cityMap).forEach(oldName => {
                    address = address.replace(oldName, cityMap[oldName]);
                });
                
                return address;
            },
            
            /**
             * 提取縣市資訊
             * @param {string} address - 地址
             * @returns {string|null}
             */
            extractCity: function(address) {
                if (!address) return null;
                
                const cityPattern = /(台北市|新北市|桃園市|台中市|台南市|高雄市|基隆市|新竹市|嘉義市|新竹縣|苗栗縣|彰化縣|南投縣|雲林縣|嘉義縣|屏東縣|宜蘭縣|花蓮縣|台東縣|澎湖縣|金門縣|連江縣)/;
                const match = address.match(cityPattern);
                
                return match ? match[1] : null;
            }
        }
    };
    
    // 全域錯誤處理
    window.addEventListener('error', function(e) {
        console.error('9O Booking System Error:', e.error);
    });
    
    // 全域 Promise 錯誤處理
    window.addEventListener('unhandledrejection', function(e) {
        console.error('9O Booking System Promise Rejection:', e.reason);
    });
    
    // 當 DOM 準備好時初始化
    $(document).ready(function() {
        // 初始化通知系統樣式
        if (!$('#booking-common-styles').length) {
            const styles = `
                <style id="booking-common-styles">
                #notifications-container {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 10000;
                    max-width: 400px;
                }
                
                .notification {
                    background: white;
                    border-radius: 8px;
                    padding: 15px;
                    margin-bottom: 10px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    border-left: 4px solid #ccc;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    animation: slideInRight 0.3s ease-out;
                }
                
                .notification-success { border-left-color: #16a34a; }
                .notification-error { border-left-color: #dc2626; }
                .notification-warning { border-left-color: #f59e0b; }
                .notification-info { border-left-color: #0ea5e9; }
                
                .notification-message {
                    flex: 1;
                    font-size: 14px;
                    line-height: 1.4;
                }
                
                .notification-close {
                    background: none;
                    border: none;
                    font-size: 18px;
                    cursor: pointer;
                    color: #666;
                    padding: 0;
                    width: 20px;
                    height: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                .notification-close:hover {
                    color: #333;
                }
                
                @keyframes slideInRight {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                
                @media (max-width: 768px) {
                    #notifications-container {
                        top: 10px;
                        right: 10px;
                        left: 10px;
                        max-width: none;
                    }
                }
                </style>
            `;
            
            $('head').append(styles);
        }
        
        console.log('9O Booking Common Library Loaded');
    });
    
})(jQuery);
