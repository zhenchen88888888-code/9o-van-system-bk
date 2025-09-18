/**
 * Code Snippet: [002] 9O Booking - Constants
 * 
 * Code Snippets 設定:
 * - Title: [002] 9O Booking - Constants
 * - Description: 定義 9O Booking System 所有系統常數
 * - Tags: 9o-booking, constants, config
 * - Priority: 2
 */

// 確保核心設定已載入
if (!defined('NINEO_BOOKING_VERSION')) {
    error_log('[9O Booking] Constants: Core Setup not loaded');
    return;
}

// Google Maps API 金鑰
if (!defined('GOOGLE_MAPS_API_KEY')) {
    define('GOOGLE_MAPS_API_KEY', get_option('9o_google_maps_api_key', ''));
}

// 基本價格設定（根據原始檔案修正）
define('AIRPORT_BASE_PRICE_TPE_TAIPEI', 3000);
define('AIRPORT_BASE_PRICE_TPE_TAOYUAN', 2800);
define('AIRPORT_BASE_PRICE_TSA_TAIPEI', 3000);
define('AIRPORT_NIGHT_SURCHARGE', 200);

// 加值服務費用
define('CHILD_SEAT_FEE', 100);
define('BOOSTER_SEAT_FEE', 100);
define('HOLDING_SIGN_FEE', 200);
define('PREMIUM_CAR_FEE', 800);

// 包車旅遊價格設定
define('CHARTER_NORTH_DAY1', 12000);
define('CHARTER_NORTH_DAY2PLUS', 11000);
define('CHARTER_SOUTH_DAY1', 14000);
define('CHARTER_SOUTH_DAY2PLUS', 13000);
define('MOUNTAIN_SURCHARGE', 1000);
define('DRIVER_ACCOMMODATION_FEE', 2000);
define('DRIVER_MEAL_FEE', 400);

// 時間設定
define('NIGHT_START_HOUR', 22);
define('NIGHT_END_HOUR', 8);

// 預約限制
define('MIN_BOOKING_DAYS', 2);
define('MAX_BOOKING_DAYS', 90);
define('MAX_STOPOVERS', 5);
define('MAX_CHARTER_DAYS', 7);

// Email 設定
define('ADMIN_EMAIL', get_option('admin_email'));
define('FROM_EMAIL', get_option('9o_from_email', 'noreply@' . parse_url(home_url(), PHP_URL_HOST)));
define('FROM_NAME', get_option('9o_from_name', '9O 租車服務'));

// 系統設定
define('ENABLE_DEBUG', get_option('9o_debug_mode', false));
define('CACHE_DURATION', 86400);
define('API_TIMEOUT', 30);

// 縣市對應（使用全域變數，因為 PHP 不能用 define 定義陣列）
global $CITY_MAPPING;
$CITY_MAPPING = [
    'taipei-city' => '台北市',
    'new-taipei' => '新北市',
    'keelung' => '基隆市',
    'taoyuan' => '桃園市',
    'yilan' => '宜蘭縣',
    'hsinchu-area' => '新竹地區',
    'miaoli' => '苗栗縣',
    'taichung' => '台中市',
    'changhua' => '彰化縣',
    'nantou' => '南投縣',
    'yunlin' => '雲林縣',
    'chiayi-area' => '嘉義地區',
    'tainan' => '台南市',
    'kaohsiung' => '高雄市',
    'pingtung' => '屏東縣',
    'hualien' => '花蓮縣',
    'taitung' => '台東縣'
];

// 機場價格矩陣
global $AIRPORT_PRICE_MATRIX;
$AIRPORT_PRICE_MATRIX = [
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

// 機場停靠點距離費用
global $AIRPORT_STOPOVER_DISTANCE_FEES;
$AIRPORT_STOPOVER_DISTANCE_FEES = [
    '1km以內' => 0,
    '1-5km' => 200,
    '5-10km' => 300,
    '10-20km' => 400,
    '20-30km' => 600,
    '30-40km' => 800,
    '40-50km' => 1000,
    '50-60km' => 1000,
    '60-70km' => 1200,
    '70-80km' => 1400,
    '80-90km' => 1600,
    '90-100km' => 1800,
    '100km以上' => 2000
];

// 包車停靠點費用
global $CHARTER_STOPOVER_FEES;
$CHARTER_STOPOVER_FEES = [
    'base_fee' => 0,
    'mountain_surcharge' => 1000
];

// 山區關鍵字
global $MOUNTAIN_KEYWORDS;
$MOUNTAIN_KEYWORDS = [
    '武嶺', '合歡山', '阿里山', '太魯閣', '清境', '玉山',
    '雪山', '大雪山', '梨山', '福壽山', '溪頭', '杉林溪',
    '奧萬大', '觀霧', '藤枝', '茂林', '那瑪夏', '六龜',
    '日月潭', '惠蓮', '埔里', '魚池', '國姓', '仁愛',
    '信義', '水里', '鹿谷', '竹山', '中寮'
];

// 排除地區
global $EXCLUDED_AREAS;
$EXCLUDED_AREAS = [
    '綠島', '蘭嶼', '澎湖', '金門', '馬祖', '小琉球',
    '龜山島', '釣魚台'
];

// 包車南部區域
global $CHARTER_SOUTH_AREAS;
$CHARTER_SOUTH_AREAS = [
    '台南市', '臺南市',
    '高雄市',
    '屏東縣',
    '花蓮縣',
    '台東縣', '臺東縣'
];

// 聯絡資訊
global $CONTACT_INFO;
$CONTACT_INFO = [
    'phone' => get_option('9o_contact_phone', '+886xxxxxxxxx'),
    'email' => get_option('9o_contact_email', 'service@9ovanstrip.com'),
    'line_id' => get_option('9o_line_id', '@9ovanstrip'),
    'business_hours' => get_option('9o_business_hours', '24小時服務'),
    'service_area' => get_option('9o_service_area', '全台灣')
];

// 偏遠地區（簡化版）
global $REMOTE_AREAS;
$REMOTE_AREAS = [
    ['name' => '加價300區域', 'surcharge' => 300, 'polygons' => []],
    ['name' => '加價400區域', 'surcharge' => 400, 'polygons' => []],
    ['name' => '加價500區域', 'surcharge' => 500, 'polygons' => []],
    ['name' => '加價600區域', 'surcharge' => 600, 'polygons' => []],
    ['name' => '加價800區域', 'surcharge' => 800, 'polygons' => []],
    ['name' => '加價1000區域', 'surcharge' => 1000, 'polygons' => []],
    ['name' => '加價1500區域', 'surcharge' => 1500, 'polygons' => []]
];

// 輔助函數：取得價格設定
function get_price_config($type) {
    switch ($type) {
        case 'airport':
            global $AIRPORT_PRICE_MATRIX;
            return $AIRPORT_PRICE_MATRIX;
        case 'charter':
            return [
                'north_day1' => CHARTER_NORTH_DAY1,
                'north_day2plus' => CHARTER_NORTH_DAY2PLUS,
                'south_day1' => CHARTER_SOUTH_DAY1,
                'south_day2plus' => CHARTER_SOUTH_DAY2PLUS,
                'mountain_surcharge' => MOUNTAIN_SURCHARGE,
                'driver_accommodation' => DRIVER_ACCOMMODATION_FEE,
                'driver_meals' => DRIVER_MEAL_FEE
            ];
        case 'airport_stopover':
            global $AIRPORT_STOPOVER_DISTANCE_FEES;
            return $AIRPORT_STOPOVER_DISTANCE_FEES;
        case 'charter_stopover':
            global $CHARTER_STOPOVER_FEES;
            return $CHARTER_STOPOVER_FEES;
        default:
            return [];
    }
}

// 輔助函數：取得地區設定
function get_area_config($type) {
    global $CITY_MAPPING, $REMOTE_AREAS, $MOUNTAIN_KEYWORDS, $EXCLUDED_AREAS, $CHARTER_SOUTH_AREAS;
    
    switch ($type) {
        case 'cities':
            return $CITY_MAPPING;
        case 'remote':
            return $REMOTE_AREAS;
        case 'mountain':
            return $MOUNTAIN_KEYWORDS;
        case 'excluded':
            return $EXCLUDED_AREAS;
        case 'charter_south':
            return $CHARTER_SOUTH_AREAS;
        default:
            return [];
    }
}

// 輔助函數：取得聯絡資訊
function get_contact_info() {
    global $CONTACT_INFO;
    return $CONTACT_INFO;
}

// 輔助函數：計算機場停靠點距離費用
function calculate_airport_stopover_fee($distance_km) {
    if ($distance_km <= 1) return 0;
    if ($distance_km <= 5) return 200;
    if ($distance_km <= 10) return 300;
    if ($distance_km <= 20) return 400;
    if ($distance_km <= 30) return 600;
    if ($distance_km <= 40) return 800;
    if ($distance_km <= 50) return 1000;
    if ($distance_km <= 60) return 1000;
    if ($distance_km <= 70) return 1200;
    if ($distance_km <= 80) return 1400;
    if ($distance_km <= 90) return 1600;
    if ($distance_km <= 100) return 1800;
    return 2000;
}

// 輔助函數：檢查是否為夜間時段
function is_night_time($time) {
    if (empty($time)) return false;
    
    $hour = intval(explode(':', $time)[0]);
    return ($hour >= NIGHT_START_HOUR || $hour < NIGHT_END_HOUR);
}

// 輔助函數：檢查是否為包車南部地區
function is_charter_south_area($address) {
    global $CHARTER_SOUTH_AREAS;
    
    foreach ($CHARTER_SOUTH_AREAS as $area) {
        if (mb_strpos($address, $area) !== false) {
            return true;
        }
    }
    
    return false;
}

// 輔助函數：檢查是否為山區位置
function check_mountain_location($address) {
    global $MOUNTAIN_KEYWORDS;
    
    foreach ($MOUNTAIN_KEYWORDS as $keyword) {
        if (mb_strpos($address, $keyword) !== false) {
            return true;
        }
    }
    
    return false;
}

// 除錯日誌
if (defined('NINEO_DEBUG_MODE') && NINEO_DEBUG_MODE) {
    error_log('[9O Booking] Constants loaded - Priority: 10');
}
