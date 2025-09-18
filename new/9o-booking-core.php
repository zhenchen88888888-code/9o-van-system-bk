<?php
/**
 * Code Snippet: [001] 9O Booking - core
 * 
 * 這是模組化的第一步：建立穩定的核心基礎
 * 保留所有原始功能，但使用更好的架構
 * 
 * Code Snippets 設定:
 * - Title: [001] 9o-booking-core
 * - Description: 系統核心、Post Types、共用函數
 * - Tags: 9o-booking, core
 * - Priority: 1
 * - Run snippet: Run snippet everywhere
 */

// =====================================
// 1. 系統常數定義
// =====================================
class NineoConfig {
    const VERSION = '3.0';
    const DB_VERSION = '1.1';
    const GOOGLE_MAPS_KEY = 'AIzaSyD7m-umnnMHhXTi11_uvI14Rel6mCN1Dz4';
    const MAX_STOPS = 5;
    const NIGHT_SURCHARGE = 200;
    
    // 價格表
    public static $PRICE_TABLE = [
        'TPE' => [
            '台北市' => 3000,
            '新北市' => 3000,
            '基隆市' => 3500,
            '桃園市' => 2800,
            '宜蘭縣' => 4900,
            '新竹地區' => 3500,
            '苗栗縣' => 4900,
            '台中市' => 6000,
            '彰化縣' => 7600,
            '南投縣' => 8500,
            '雲林縣' => 9400,
            '嘉義地區' => 9800,
            '台南市' => 11000,
            '高雄市' => 12000,
            '屏東縣' => 13000,
            '花蓮縣' => 13000,
            '台東縣' => 14000
        ],
        'TSA' => [
            '台北市' => 3000,
            '新北市' => 3000,
            '基隆市' => 3500,
            '桃園市' => 3100,
            '宜蘭縣' => 4900,
            '新竹地區' => 3800,
            '苗栗縣' => 5400,
            '台中市' => 6400,
            '彰化縣' => 8000,
            '南投縣' => 8900,
            '雲林縣' => 9800,
            '嘉義地區' => 10300,
            '台南市' => 11500,
            '高雄市' => 12500,
            '屏東縣' => 13000,
            '花蓮縣' => 13000,
            '台東縣' => 14000
        ]
    ];
    
    // 縣市對應
    public static $CITY_MAPPING = [
        'taipei-city' => '台北市',
        'new-taipei' => '新北市',
        'keelung' => '基隆市',
        'taoyuan' => '桃園市',
        'yilan' => '宜蘭縣',
        'hsinchu-city' => '新竹市',
        'miaoli' => '苗栗縣',
        'taichung' => '台中市',
        'changhua' => '彰化縣',
        'nantou' => '南投縣',
        'yunlin' => '雲林縣',
        'chiayi' => '嘉義縣',
        'tainan' => '台南市',
        'kaohsiung' => '高雄市',
        'pingtung' => '屏東縣',
        'hualien' => '花蓮縣',
        'taitung' => '台東縣'
    ];
}

// =====================================
// 2. Post Types 註冊
// =====================================
class NineoPostTypes {
    
    public static function init() {
        add_action('init', [__CLASS__, 'register_post_types']);
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_boxes']);
        add_filter('manage_airport_booking_posts_columns', [__CLASS__, 'set_airport_columns']);
        add_action('manage_airport_booking_posts_custom_column', [__CLASS__, 'airport_column'], 10, 2);
        add_filter('manage_charter_booking_posts_columns', [__CLASS__, 'set_charter_columns']);
        add_action('manage_charter_booking_posts_custom_column', [__CLASS__, 'charter_column'], 10, 2);
    }
    
    public static function register_post_types() {
        // 機場接送
        register_post_type('airport_booking', [
            'labels' => [
                'name' => '機場接送預約',
                'singular_name' => '機場預約',
                'menu_name' => '機場接送',
                'add_new' => '新增預約',
                'edit_item' => '編輯預約',
                'view_item' => '查看預約',
                'all_items' => '所有預約',
                'search_items' => '搜尋預約'
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-airplane',
            'supports' => ['title'],
            'capability_type' => 'post',
            'capabilities' => [
                'create_posts' => 'do_not_allow'
            ],
            'map_meta_cap' => true
        ]);
        
        // 包車旅遊
        register_post_type('charter_booking', [
            'labels' => [
                'name' => '包車旅遊預約',
                'singular_name' => '包車預約',
                'menu_name' => '包車旅遊',
                'add_new' => '新增預約',
                'edit_item' => '編輯預約',
                'view_item' => '查看預約',
                'all_items' => '所有預約',
                'search_items' => '搜尋預約'
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 26,
            'menu_icon' => 'dashicons-car',
            'supports' => ['title'],
            'capability_type' => 'post',
            'capabilities' => [
                'create_posts' => 'do_not_allow'
            ],
            'map_meta_cap' => true
        ]);
    }
    
    public static function add_meta_boxes() {
        // 機場接送 Meta Box
        add_meta_box(
            'airport_booking_details',
            '預約詳情',
            [__CLASS__, 'airport_meta_box'],
            'airport_booking',
            'normal',
            'high'
        );
        
        // 包車旅遊 Meta Box
        add_meta_box(
            'charter_booking_details',
            '預約詳情',
            [__CLASS__, 'charter_meta_box'],
            'charter_booking',
            'normal',
            'high'
        );
    }
    
    public static function airport_meta_box($post) {
        $data = get_post_meta($post->ID, '_booking_data', true);
        if (!is_array($data)) {
            $data = json_decode($post->post_content, true) ?: [];
        }
        ?>
        <style>
            .booking-details { width: 100%; border-collapse: collapse; }
            .booking-details td { padding: 8px; border-bottom: 1px solid #eee; }
            .booking-details td:first-child { width: 150px; font-weight: bold; background: #f9f9f9; }
            .booking-section { margin: 20px 0; padding: 15px; background: #fff; border: 1px solid #ddd; }
            .booking-section h3 { margin: 0 0 15px; color: #23282d; }
        </style>
        
        <div class="booking-section">
            <h3>📋 基本資訊</h3>
            <table class="booking-details">
                <tr>
                    <td>預約編號</td>
                    <td>APT<?php echo str_pad($post->ID, 6, '0', STR_PAD_LEFT); ?></td>
                </tr>
                <tr>
                    <td>預約時間</td>
                    <td><?php echo $data['booking_time'] ?? ''; ?></td>
                </tr>
                <tr>
                    <td>服務類型</td>
                    <td>
                        <?php 
                        $service = $data['service_type'] ?? '';
                        echo $service === 'pickup' ? '接機' : '送機';
                        echo ' / ';
                        echo ($data['trip_type'] ?? '') === 'roundtrip' ? '來回' : '單程';
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>機場</td>
                    <td><?php echo $data['airport'] === 'TPE' ? '桃園國際機場' : '台北松山機場'; ?></td>
                </tr>
                <tr>
                    <td>目的地</td>
                    <td><?php echo NineoConfig::$CITY_MAPPING[$data['destination']] ?? $data['destination']; ?></td>
                </tr>
            </table>
        </div>
        
        <div class="booking-section">
            <h3>✈️ 行程資訊</h3>
            <table class="booking-details">
                <tr>
                    <td>日期</td>
                    <td><?php echo $data['date'] ?? ''; ?></td>
                </tr>
                <tr>
                    <td>時間</td>
                    <td><?php echo $data['time'] ?? ''; ?></td>
                </tr>
                <tr>
                    <td>航班號碼</td>
                    <td><?php echo $data['flight'] ?: '未提供'; ?></td>
                </tr>
                <tr>
                    <td>乘客人數</td>
                    <td><?php echo $data['passengers'] ?? 1; ?> 人</td>
                </tr>
                <?php if ($data['service_type'] === 'pickup'): ?>
                <tr>
                    <td>下車地址</td>
                    <td><?php echo $data['dropoff_address'] ?? ''; ?></td>
                </tr>
                <?php else: ?>
                <tr>
                    <td>上車地址</td>
                    <td><?php echo $data['pickup_address'] ?? ''; ?></td>
                </tr>
                <?php endif; ?>
                
                <?php if (!empty($data['stopovers'])): ?>
                <tr>
                    <td>停靠點</td>
                    <td>
                        <?php foreach ($data['stopovers'] as $i => $stop): ?>
                            <?php echo ($i + 1) . '. ' . $stop['address']; ?><br>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        
        <?php if ($data['trip_type'] === 'roundtrip' && !empty($data['return_data'])): ?>
        <div class="booking-section">
            <h3>🔄 回程資訊</h3>
            <table class="booking-details">
                <tr>
                    <td>回程日期</td>
                    <td><?php echo $data['return_data']['date'] ?? ''; ?></td>
                </tr>
                <tr>
                    <td>回程時間</td>
                    <td><?php echo $data['return_data']['time'] ?? ''; ?></td>
                </tr>
                <tr>
                    <td>回程航班</td>
                    <td><?php echo $data['return_data']['flight'] ?: '未提供'; ?></td>
                </tr>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="booking-section">
            <h3>👤 客戶資訊</h3>
            <table class="booking-details">
                <tr>
                    <td>姓名</td>
                    <td><?php echo $data['customer_name'] ?? ''; ?></td>
                </tr>
                <tr>
                    <td>電話</td>
                    <td><?php echo $data['customer_phone'] ?? ''; ?></td>
                </tr>
                <tr>
                    <td>Email</td>
                    <td><?php echo $data['customer_email'] ?: '未提供'; ?></td>
                </tr>
                <tr>
                    <td>備註</td>
                    <td><?php echo $data['notes'] ?: '無'; ?></td>
                </tr>
            </table>
        </div>
        
        <div class="booking-section">
            <h3>💰 費用明細</h3>
            <table class="booking-details">
                <tr>
                    <td>總金額</td>
                    <td style="font-size: 18px; color: #d63638;">
                        <strong>NT$ <?php echo number_format($data['total_price'] ?? 0); ?></strong>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    public static function charter_meta_box($post) {
        $data = get_post_meta($post->ID, '_booking_data', true);
        if (!is_array($data)) {
            $data = json_decode($post->post_content, true) ?: [];
        }
        ?>
        <div class="booking-section">
            <h3>📋 包車旅遊詳情</h3>
            <table class="booking-details">
                <tr>
                    <td>預約編號</td>
                    <td>CHT<?php echo str_pad($post->ID, 6, '0', STR_PAD_LEFT); ?></td>
                </tr>
                <tr>
                    <td>旅遊天數</td>
                    <td><?php echo $data['trip_days'] ?? 1; ?> 天</td>
                </tr>
                <tr>
                    <td>出發日期</td>
                    <td><?php echo $data['start_date'] ?? ''; ?></td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    public static function set_airport_columns($columns) {
        return [
            'cb' => $columns['cb'],
            'title' => '預約',
            'customer' => '客戶',
            'service' => '服務',
            'date' => '日期',
            'price' => '金額',
            'status' => '狀態'
        ];
    }
    
    public static function airport_column($column, $post_id) {
        $data = get_post_meta($post_id, '_booking_data', true);
        if (!is_array($data)) {
            $post = get_post($post_id);
            $data = json_decode($post->post_content, true) ?: [];
        }
        
        switch ($column) {
            case 'customer':
                echo '<strong>' . ($data['customer_name'] ?? '') . '</strong><br>';
                echo $data['customer_phone'] ?? '';
                break;
            case 'service':
                echo ($data['service_type'] ?? '') === 'pickup' ? '接機' : '送機';
                echo ' / ';
                echo ($data['trip_type'] ?? '') === 'roundtrip' ? '來回' : '單程';
                break;
            case 'date':
                echo $data['date'] ?? '';
                echo ' ' . ($data['time'] ?? '');
                break;
            case 'price':
                echo 'NT$ ' . number_format($data['total_price'] ?? 0);
                break;
            case 'status':
                echo '<span style="color: #f0ad4e;">待處理</span>';
                break;
        }
    }
    
    public static function set_charter_columns($columns) {
        return [
            'cb' => $columns['cb'],
            'title' => '預約',
            'customer' => '客戶',
            'trip' => '行程',
            'date' => '出發日期',
            'price' => '金額',
            'status' => '狀態'
        ];
    }
    
    public static function charter_column($column, $post_id) {
        $data = get_post_meta($post_id, '_booking_data', true);
        if (!is_array($data)) {
            $post = get_post($post_id);
            $data = json_decode($post->post_content, true) ?: [];
        }
        
        switch ($column) {
            case 'customer':
                echo '<strong>' . ($data['customer_name'] ?? '') . '</strong><br>';
                echo $data['customer_phone'] ?? '';
                break;
            case 'trip':
                echo ($data['trip_days'] ?? 1) . ' 天行程';
                break;
            case 'date':
                echo $data['start_date'] ?? '';
                break;
            case 'price':
                echo 'NT$ ' . number_format($data['total_price'] ?? 0);
                break;
            case 'status':
                echo '<span style="color: #f0ad4e;">待處理</span>';
                break;
        }
    }
}

// =====================================
// 3. 短代碼註冊
// =====================================
class NineoShortcodes {
    
    public static function init() {
        add_shortcode('airport_booking_form', [__CLASS__, 'airport_form']);
        add_shortcode('charter_booking_form', [__CLASS__, 'charter_form']);
    }
    
    public static function airport_form($atts) {
        ob_start();
        ?>
        <div id="airport-booking-app">
            <div class="booking-container">
                <form id="airport-booking-form" class="booking-form">
                    <div class="form-loading">
                        <span>載入中...</span>
                    </div>
                </form>
                
                <div id="price-panel" class="price-panel">
                    <h3>即時報價</h3>
                    <div class="price-content">
                        <div class="price-loading">請選擇服務項目...</div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public static function charter_form($atts) {
        ob_start();
        ?>
        <div id="charter-booking-app">
            <div class="booking-container">
                <form id="charter-booking-form" class="booking-form">
                    <div class="form-loading">
                        <span>載入中...</span>
                    </div>
                </form>
                
                <div id="price-panel" class="price-panel">
                    <h3>即時報價</h3>
                    <div class="price-content">
                        <div class="price-loading">請選擇服務項目...</div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

// =====================================
// 4. 共用函數庫
// =====================================
class NineoUtils {
    
    /**
     * 取得縣市對應
     */
    public static function get_city_mapping() {
        return NineoConfig::$CITY_MAPPING;
    }
    
    /**
     * 驗證地址與縣市
     */
    public static function validate_address_city($address, $selected_city_key) {
        $city_mapping = self::get_city_mapping();
        $expected_city = $city_mapping[$selected_city_key] ?? '';
        
        if (empty($expected_city)) {
            return ['valid' => false, 'message' => '無效的縣市選擇'];
        }
        
        if (strpos($address, $expected_city) !== false) {
            return ['valid' => true, 'city' => $expected_city];
        }
        
        // 特殊處理：新竹市/新竹縣
        if ($expected_city === '新竹市' && strpos($address, '新竹縣') !== false) {
            return ['valid' => false, 'message' => '您選擇的是新竹市，但地址為新竹縣'];
        }
        if ($expected_city === '新竹縣' && strpos($address, '新竹市') !== false) {
            return ['valid' => false, 'message' => '您選擇的是新竹縣，但地址為新竹市'];
        }
        
        // 特殊處理：嘉義市/嘉義縣
        if ($expected_city === '嘉義市' && strpos($address, '嘉義縣') !== false) {
            return ['valid' => false, 'message' => '您選擇的是嘉義市，但地址為嘉義縣'];
        }
        if ($expected_city === '嘉義縣' && strpos($address, '嘉義市') !== false) {
            return ['valid' => false, 'message' => '您選擇的是嘉義縣，但地址為嘉義市'];
        }
        
        return ['valid' => false, 'message' => "地址與選擇的{$expected_city}不符"];
    }
    
    /**
     * 計算距離費用
     */
    public static function calculate_distance_fee($distance_km) {
        if ($distance_km <= 1) return 0;
        if ($distance_km <= 5) return 200;
        if ($distance_km <= 10) return 300;
        if ($distance_km <= 20) return 400;
        if ($distance_km <= 30) return 600;
        if ($distance_km <= 40) return 800;
        if ($distance_km <= 50) return 1000;
        return 1000 + ceil(($distance_km - 50) / 10) * 200;
    }
    
    /**
     * 發送確認信
     */
    public static function send_booking_confirmation($booking_id, $booking_data) {
        if (empty($booking_data['customer_email'])) {
            return false;
        }
        
        $to = $booking_data['customer_email'];
        $subject = '預約確認 - 訂單編號 ' . str_pad($booking_id, 6, '0', STR_PAD_LEFT);
        
        $message = "親愛的 {$booking_data['customer_name']} 您好，\n\n";
        $message .= "您的預約已成功提交，我們將在24小時內與您聯繫確認。\n\n";
        $message .= "預約編號：" . str_pad($booking_id, 6, '0', STR_PAD_LEFT) . "\n";
        $message .= "總金額：NT$ " . number_format($booking_data['total_price']) . "\n\n";
        $message .= "感謝您的預約！\n";
        $message .= "9o Van Strip";
        
        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'From: 9o Van Strip <noreply@' . $_SERVER['SERVER_NAME'] . '>'
        ];
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * 發送管理員通知
     */
    public static function send_admin_notification($booking_id, $booking_data) {
        $admin_email = get_option('admin_email');
        
        $subject = '新預約通知 - ' . $booking_data['customer_name'];
        
        $message = "有新的預約：\n\n";
        $message .= "客戶：{$booking_data['customer_name']}\n";
        $message .= "電話：{$booking_data['customer_phone']}\n";
        $message .= "總額：NT$ " . number_format($booking_data['total_price']) . "\n\n";
        
        $edit_link = admin_url('post.php?post=' . $booking_id . '&action=edit');
        $message .= "管理連結：{$edit_link}\n";
        
        wp_mail($admin_email, $subject, $message);
    }
}

// =====================================
// 5. 管理介面優化
// =====================================
class NineoAdmin {
    
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu'], 999);
        add_action('admin_head', [__CLASS__, 'admin_styles']);
        add_action('admin_notices', [__CLASS__, 'admin_notices']);
    }
    
    public static function add_menu() {
        add_menu_page(
            '9O預約系統',
            '9O預約系統',
            'manage_options',
            '9o-booking',
            [__CLASS__, 'dashboard_page'],
            'dashicons-calendar-alt',
            24
        );
        
        add_submenu_page(
            '9o-booking',
            '系統設定',
            '設定',
            'manage_options',
            '9o-settings',
            [__CLASS__, 'settings_page']
        );
    }
    
    public static function dashboard_page() {
        ?>
        <div class="wrap">
            <h1>9O預約系統儀表板</h1>
            
            <div class="card">
                <h2>系統狀態</h2>
                <p>✅ 版本：<?php echo NineoConfig::VERSION; ?></p>
                <p>✅ Google Maps API：已設定</p>
                <p>✅ Post Types：已註冊</p>
            </div>
            
            <div class="card">
                <h2>快速連結</h2>
                <p>
                    <a href="<?php echo admin_url('edit.php?post_type=airport_booking'); ?>" class="button">
                        機場接送預約
                    </a>
                    <a href="<?php echo admin_url('edit.php?post_type=charter_booking'); ?>" class="button">
                        包車旅遊預約
                    </a>
                </p>
            </div>
            
            <div class="card">
                <h2>短代碼使用</h2>
                <ul>
                    <li><code>[airport_booking_form]</code> - 機場接送預約表單</li>
                    <li><code>[charter_booking_form]</code> - 包車旅遊預約表單</li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    public static function settings_page() {
        ?>
        <div class="wrap">
            <h1>系統設定</h1>
            <p>設定頁面開發中...</p>
        </div>
        <?php
    }
    
    public static function admin_styles() {
        ?>
        <style>
            .wp-list-table .column-customer { width: 20%; }
            .wp-list-table .column-service { width: 15%; }
            .wp-list-table .column-date { width: 15%; }
            .wp-list-table .column-price { width: 10%; }
            .wp-list-table .column-status { width: 10%; }
        </style>
        <?php
    }
    
    public static function admin_notices() {
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, '9o') !== false) {
            ?>
            <div class="notice notice-info">
                <p>9O預約系統 v<?php echo NineoConfig::VERSION; ?> - 核心模組已載入</p>
            </div>
            <?php
        }
    }
}

// =====================================
// 6. 系統初始化
// =====================================
class NineoBookingSystem {
    
    public static function init() {
        // 初始化各模組
        NineoPostTypes::init();
        NineoShortcodes::init();
        NineoAdmin::init();
        
        // 載入相依檔案
        add_action('init', [__CLASS__, 'load_dependencies']);
        
        error_log('9O Booking Core System initialized - Version ' . NineoConfig::VERSION);
    }
    
    public static function load_dependencies() {
        // 這裡可以載入其他模組
        // 例如：偏遠地區模組、Google Maps 模組等
    }
}

// 啟動系統
NineoBookingSystem::init();