if (!shortcode_exists('airport_booking_form')) {
    add_shortcode('airport_booking_form', 'render_airport_booking_form');
}

function render_airport_booking_form() {
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

// Google Maps API 配置
if (!defined('GOOGLE_MAPS_API_KEY')) {
    define('GOOGLE_MAPS_API_KEY', 'AIzaSyD7m-umnnMHhXTi11_uvI14Rel6mCN1Dz4');
}

// 縣市對應表
function get_city_mapping() {
    return array(
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
    );
}

// 地址縣市驗證函數
function validate_address_city($address, $selected_city_key) {
    $city_mapping = get_city_mapping();
    
    $expected_city = isset($city_mapping[$selected_city_key]) ? $city_mapping[$selected_city_key] : '';
    
    if (empty($expected_city)) {
        return array('valid' => false, 'message' => '無效的縣市選擇');
    }
    
    if (strpos($address, $expected_city) !== false) {
        return array('valid' => true, 'city' => $expected_city);
    }
    
    // 特殊處理：新竹市/新竹縣
    if ($expected_city === '新竹市' && strpos($address, '新竹縣') !== false) {
        return array('valid' => false, 'message' => '您選擇的是新竹市，但地址為新竹縣');
    }
    if ($expected_city === '新竹縣' && strpos($address, '新竹市') !== false) {
        return array('valid' => false, 'message' => '您選擇的是新竹縣，但地址為新竹市');
    }
    
    // 特殊處理：嘉義市/嘉義縣
    if ($expected_city === '嘉義市' && strpos($address, '嘉義縣') !== false) {
        return array('valid' => false, 'message' => '您選擇的是嘉義市，但地址為嘉義縣');
    }
    if ($expected_city === '嘉義縣' && strpos($address, '嘉義市') !== false) {
        return array('valid' => false, 'message' => '您選擇的是嘉義縣，但地址為嘉義市');
    }
    
    // 檢查是否包含其他縣市
    $all_cities = array_values($city_mapping);
    foreach ($all_cities as $city) {
        if ($city !== $expected_city && strpos($address, $city) !== false) {
            return array('valid' => false, 'message' => "地址中的{$city}與您選擇的{$expected_city}不符");
        }
    }
    
    $has_any_city = false;
    foreach ($all_cities as $city) {
        if (strpos($address, $city) !== false) {
            $has_any_city = true;
            break;
        }
    }
    
    if (!$has_any_city) {
        return array('valid' => false, 'message' => "請在地址中包含縣市名稱（{$expected_city}）");
    }
    
    return array('valid' => false, 'message' => "地址與選擇的{$expected_city}不符");
}

// 距離費用計算函數
function calculate_distance_fee($distance_km) {
    if ($distance_km <= 1) return 0;
    if ($distance_km <= 5) return 200;
    if ($distance_km <= 10) return 300;
    if ($distance_km <= 20) return 400;
    if ($distance_km <= 30) return 600;
    if ($distance_km <= 40) return 800;
    if ($distance_km <= 50) return 1000;
    return 1000 + ceil(($distance_km - 50) / 10) * 200;
}

// Google Maps Distance Matrix 函數
function calculate_distance_google($origin, $destination) {
    $api_key = GOOGLE_MAPS_API_KEY;
    
    $origin = $origin . ', 台灣';
    $destination = $destination . ', 台灣';
    
    $url = 'https://maps.googleapis.com/maps/api/distancematrix/json';
    $params = array(
        'origins' => $origin,
        'destinations' => $destination,
        'units' => 'metric',
        'language' => 'zh-TW',
        'region' => 'TW',
        'key' => $api_key
    );
    
    $query_string = http_build_query($params);
    $full_url = $url . '?' . $query_string;
    
    error_log('Google Maps API Request: ' . $full_url);
    
    $response = wp_remote_get($full_url, array('timeout' => 10));
    
    if (is_wp_error($response)) {
        error_log('Google Maps API Error: ' . $response->get_error_message());
        return estimate_distance_fallback($origin, $destination);
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    error_log('Google Maps API Response Status: ' . ($data['status'] ?? 'NO STATUS'));
    
    if ($data['status'] !== 'OK') {
        error_log('Google Maps API Status Error: ' . $body);
        return estimate_distance_fallback($origin, $destination);
    }
    
    if (!isset($data['rows'][0]['elements'][0]['distance']['value'])) {
        error_log('Google Maps API No Distance: ' . $body);
        return estimate_distance_fallback($origin, $destination);
    }
    
    $distance_meters = $data['rows'][0]['elements'][0]['distance']['value'];
    $distance_km = $distance_meters / 1000;
    
    error_log('Calculated distance: ' . $distance_km . ' km');
    
    return $distance_km;
}

// 備用距離估算函數
function estimate_distance_fallback($origin, $destination) {
    $city_distances = array(
        '台北' => array('新北' => 10, '基隆' => 25, '桃園' => 30, '新竹' => 70, '台中' => 165),
        '桃園' => array('台北' => 30, '新北' => 25, '新竹' => 50, '苗栗' => 80, '台中' => 135),
        '新竹' => array('台北' => 70, '桃園' => 50, '苗栗' => 40, '台中' => 95),
        '台中' => array('台北' => 165, '桃園' => 135, '彰化' => 25, '南投' => 40),
        '高雄' => array('台北' => 350, '台中' => 185, '台南' => 45, '屏東' => 30)
    );
    
    $origin_city = extract_city_from_address($origin);
    $dest_city = extract_city_from_address($destination);
    
    if (isset($city_distances[$origin_city][$dest_city])) {
        return $city_distances[$origin_city][$dest_city];
    }
    if (isset($city_distances[$dest_city][$origin_city])) {
        return $city_distances[$dest_city][$origin_city];
    }
    
    if ($origin_city === $dest_city) {
        return 8;
    }
    
    return 15;
}

// 從地址提取城市名
function extract_city_from_address($address) {
    $cities = array('台北', '新北', '基隆', '桃園', '新竹', '苗栗', '台中', 
                   '彰化', '南投', '雲林', '嘉義', '台南', '高雄', '屏東', 
                   '宜蘭', '花蓮', '台東');
    
    foreach ($cities as $city) {
        if (strpos($address, $city) !== false) {
            return $city;
        }
    }
    
    if (strpos($address, '桃園國際機場') !== false) {
        return '桃園';
    }
    if (strpos($address, '松山機場') !== false) {
        return '台北';
    }
    
    return '台北';
}

// 偏遠地區加價檢查 - 完整版
function check_wdap_remote_area_surcharge($address) {
    if (empty($address)) {
        return 0;
    }
    
    error_log('偏遠地區檢查: ' . $address);
    
    $coords = get_address_coordinates($address);
    if (!$coords) {
        error_log('無法轉換地址為座標');
        return 0;
    }
    
    error_log('地址座標: lat=' . $coords['lat'] . ', lng=' . $coords['lng']);
    
    // 定義所有偏遠地區
    public static function get_remote_areas() {
        return [
            // +300 區域（3個多邊形）
            [
                'name' => '加價300區域',
                'surcharge' => 300,
                'polygons' => [
                    // 第1個多邊形
                    [
                        ['lat' => 25.226389, 'lng' => 121.436142],
                        ['lat' => 25.224526, 'lng' => 121.452278],
                        ['lat' => 25.211481, 'lng' => 121.460518],
                        ['lat' => 25.171404, 'lng' => 121.463265],
                        ['lat' => 25.145301, 'lng' => 121.466011],
                        ['lat' => 25.126652, 'lng' => 121.466355],
                        ['lat' => 25.121368, 'lng' => 121.460861],
                        ['lat' => 25.113907, 'lng' => 121.459145],
                        ['lat' => 25.116083, 'lng' => 121.453308],
                        ['lat' => 25.123855, 'lng' => 121.445412],
                        ['lat' => 25.135977, 'lng' => 121.436485],
                        ['lat' => 25.146233, 'lng' => 121.429276],
                        ['lat' => 25.150584, 'lng' => 121.426529],
                        ['lat' => 25.133801, 'lng' => 121.411423],
                        ['lat' => 25.130382, 'lng' => 121.400437],
                        ['lat' => 25.115462, 'lng' => 121.387047],
                        ['lat' => 25.124476, 'lng' => 121.38224],
                        ['lat' => 25.143436, 'lng' => 121.373314],
                        ['lat' => 25.161461, 'lng' => 121.368851],
                        ['lat' => 25.168919, 'lng' => 121.38224],
                    ],
                    // 第2個多邊形
                    [
                        ['lat' => 25.158247, 'lng' => 121.526098],
                        ['lat' => 25.149934, 'lng' => 121.537084],
                        ['lat' => 25.138978, 'lng' => 121.535797],
                        ['lat' => 25.133461, 'lng' => 121.535883],
                        ['lat' => 25.130198, 'lng' => 121.538543],
                        ['lat' => 25.127012, 'lng' => 121.544122],
                        ['lat' => 25.12064, 'lng' => 121.5485],
                        ['lat' => 25.117158, 'lng' => 121.55013],
                        ['lat' => 25.114516, 'lng' => 121.557169],
                        ['lat' => 25.116614, 'lng' => 121.564808],
                        ['lat' => 25.110552, 'lng' => 121.563348],
                        ['lat' => 25.109853, 'lng' => 121.567039],
                        ['lat' => 25.110941, 'lng' => 121.572446],
                        ['lat' => 25.114049, 'lng' => 121.577167],
                        ['lat' => 25.116847, 'lng' => 121.579485],
                        ['lat' => 25.121743, 'lng' => 121.58103],
                        ['lat' => 25.127338, 'lng' => 121.581802],
                        ['lat' => 25.136419, 'lng' => 121.576432],
                        ['lat' => 25.142635, 'lng' => 121.571797],
                        ['lat' => 25.144344, 'lng' => 121.565703],
                        ['lat' => 25.146675, 'lng' => 121.557034],
                        ['lat' => 25.152876, 'lng' => 121.559008],
                        ['lat' => 25.154585, 'lng' => 121.552485],
                        ['lat' => 25.160878, 'lng' => 121.548451],
                        ['lat' => 25.162354, 'lng' => 121.545447],
                        ['lat' => 25.163597, 'lng' => 121.534975],
                        ['lat' => 25.162121, 'lng' => 121.527594],
                    ],
                    // 第3個多邊形
                    [
                        ['lat' => 25.007374, 'lng' => 121.60264],
                        ['lat' => 24.996173, 'lng' => 121.602125],
                        ['lat' => 24.993994, 'lng' => 121.615858],
                        ['lat' => 24.995861, 'lng' => 121.630621],
                        ['lat' => 24.999751, 'lng' => 121.631136],
                        ['lat' => 25.001462, 'lng' => 121.633024],
                        ['lat' => 25.006441, 'lng' => 121.628561],
                        ['lat' => 25.007996, 'lng' => 121.623583],
                        ['lat' => 25.007996, 'lng' => 121.613627]
                    ]
                ]
            ],
            
            // +400 區域（1個多邊形）
            [
                'name' => '加價400區域',
                'surcharge' => 400,
                'polygons' => [
                    [
                        ['lat' => 24.728356, 'lng' => 121.111081],
                        ['lat' => 24.711827, 'lng' => 121.107991],
                        ['lat' => 24.703406, 'lng' => 121.137174],
                        ['lat' => 24.709956, 'lng' => 121.14507],
                        ['lat' => 24.720248, 'lng' => 121.147473],
                        ['lat' => 24.727109, 'lng' => 121.145757],
                        ['lat' => 24.733033, 'lng' => 121.132024],
                    ]
                ]
            ],
            
            // +500 區域（9個多邊形）
            [
                'name' => '加價500區域',
                'surcharge' => 500,
                'polygons' => [
                    // 第1個多邊形
                    [
                        ['lat' => 24.618931, 'lng' => 120.997692],
                        ['lat' => 24.615341, 'lng' => 121.028334],
                        ['lat' => 24.606289, 'lng' => 121.041208],
                        ['lat' => 24.599745, 'lng' => 121.051545],
                        ['lat' => 24.596935, 'lng' => 121.048455],
                        ['lat' => 24.593657, 'lng' => 121.03807],
                        ['lat' => 24.591316, 'lng' => 121.019015],
                        ['lat' => 24.59194, 'lng' => 121.004167],
                        ['lat' => 24.585384, 'lng' => 120.998502],
                        ['lat' => 24.58195, 'lng' => 120.9973],
                        ['lat' => 24.583979, 'lng' => 120.988374],
                        ['lat' => 24.594984, 'lng' => 120.988717],
                        ['lat' => 24.606378, 'lng' => 120.990605],
                    ],
                    // 第2個多邊形
                    [
                        ['lat' => 24.433513, 'lng' => 120.864621],
                        ['lat' => 24.434138, 'lng' => 120.881444],
                        ['lat' => 24.40913, 'lng' => 120.87904],
                        ['lat' => 24.405065, 'lng' => 120.858784],
                        ['lat' => 24.429137, 'lng' => 120.855694],
                    ],
                    // 第3-5個多邊形（邊界區重複3次）
                    [
                        ['lat' => 24.392872, 'lng' => 120.858098],
                        ['lat' => 24.376924, 'lng' => 120.863248],
                        ['lat' => 24.379582, 'lng' => 120.873719],
                        ['lat' => 24.393497, 'lng' => 120.875607],
                        ['lat' => 24.401939, 'lng' => 120.875607],
                        ['lat' => 24.404127, 'lng' => 120.870972],
                        ['lat' => 24.404127, 'lng' => 120.864449],
                        ['lat' => 24.398656, 'lng' => 120.858098],
                    ],
                    [
                        ['lat' => 24.392872, 'lng' => 120.858098],
                        ['lat' => 24.376924, 'lng' => 120.863248],
                        ['lat' => 24.379582, 'lng' => 120.873719],
                        ['lat' => 24.393497, 'lng' => 120.875607],
                        ['lat' => 24.401939, 'lng' => 120.875607],
                        ['lat' => 24.404127, 'lng' => 120.870972],
                        ['lat' => 24.404127, 'lng' => 120.864449],
                        ['lat' => 24.398656, 'lng' => 120.858098],
                    ],
                    [
                        ['lat' => 24.392872, 'lng' => 120.858098],
                        ['lat' => 24.376924, 'lng' => 120.863248],
                        ['lat' => 24.379582, 'lng' => 120.873719],
                        ['lat' => 24.393497, 'lng' => 120.875607],
                        ['lat' => 24.401939, 'lng' => 120.875607],
                        ['lat' => 24.404127, 'lng' => 120.870972],
                        ['lat' => 24.404127, 'lng' => 120.864449],
                        ['lat' => 24.398656, 'lng' => 120.858098],
                    ],
                    // 第6個多邊形
                    [
                        ['lat' => 23.196503, 'lng' => 120.485014],
                        ['lat' => 23.190191, 'lng' => 120.509733],
                        ['lat' => 23.16147, 'lng' => 120.507673],
                        ['lat' => 23.149475, 'lng' => 120.499433],
                        ['lat' => 23.137794, 'lng' => 120.484327],
                        ['lat' => 23.145371, 'lng' => 120.465444],
                        ['lat' => 23.162101, 'lng' => 120.459265],
                    ],
                    // 第7個多邊形
                    [
                        ['lat' => 23.125797, 'lng' => 120.440039],
                        ['lat' => 23.101799, 'lng' => 120.443128],
                        ['lat' => 23.097377, 'lng' => 120.449308],
                        ['lat' => 23.087903, 'lng' => 120.467848],
                        ['lat' => 23.11443, 'lng' => 120.477117],
                        ['lat' => 23.129901, 'lng' => 120.480551],
                        ['lat' => 23.13811, 'lng' => 120.476431],
                        ['lat' => 23.139057, 'lng' => 120.456518],
                        ['lat' => 23.146633, 'lng' => 120.441412],
                        ['lat' => 23.146002, 'lng' => 120.437979],
                        ['lat' => 23.13148, 'lng' => 120.426992],
                    ],
                    // 第8個多邊形
                    [
                        ['lat' => 23.066134, 'lng' => 120.384624],
                        ['lat' => 23.064002, 'lng' => 120.407112],
                        ['lat' => 23.061633, 'lng' => 120.419471],
                        ['lat' => 23.05492, 'lng' => 120.420501],
                        ['lat' => 23.051208, 'lng' => 120.422475],
                        ['lat' => 23.048681, 'lng' => 120.415008],
                        ['lat' => 23.052788, 'lng' => 120.384624],
                    ],
                    // 第9個多邊形
                    [
                        ['lat' => 23.045231, 'lng' => 120.363124],
                        ['lat' => 23.043099, 'lng' => 120.359949],
                        ['lat' => 23.04065, 'lng' => 120.363553],
                        ['lat' => 23.039781, 'lng' => 120.366557],
                        ['lat' => 23.041598, 'lng' => 120.369218],
                        ['lat' => 23.046021, 'lng' => 120.368617],
                    ]
                ]
            ],
            
            // +600 區域（6個多邊形）
            [
                'name' => '加價600區域',
                'surcharge' => 600,
                'polygons' => [
                    // 第1個多邊形
                    [
                        ['lat' => 25.092493, 'lng' => 121.760248],
                        ['lat' => 25.092804, 'lng' => 121.865649],
                        ['lat' => 25.127312, 'lng' => 121.863589],
                        ['lat' => 25.133217, 'lng' => 121.837153],
                        ['lat' => 25.122027, 'lng' => 121.81518],
                        ['lat' => 25.113634, 'lng' => 121.791834],
                        ['lat' => 25.112702, 'lng' => 121.774325],
                        ['lat' => 25.109282, 'lng' => 121.756472],
                    ],
                    // 第2個多邊形
                    [
                        ['lat' => 25.011355, 'lng' => 121.627965],
                        ['lat' => 25.0168, 'lng' => 121.648564],
                        ['lat' => 24.989419, 'lng' => 121.67603],
                        ['lat' => 24.983195, 'lng' => 121.657834],
                        ['lat' => 24.989886, 'lng' => 121.647878],
                    ],
                    // 第3個多邊形
                    [
                        ['lat' => 24.934063, 'lng' => 121.535569],
                        ['lat' => 24.939666, 'lng' => 121.560288],
                        ['lat' => 24.902926, 'lng' => 121.560803],
                        ['lat' => 24.88474, 'lng' => 121.557885],
                        ['lat' => 24.889879, 'lng' => 121.526471],
                        ['lat' => 24.907941, 'lng' => 121.533852],
                        ['lat' => 24.912456, 'lng' => 121.535569],
                        ['lat' => 24.925844, 'lng' => 121.533337],
                        ['lat' => 24.920472, 'lng' => 121.511708],
                        ['lat' => 24.922651, 'lng' => 121.494027],
                        ['lat' => 24.932926, 'lng' => 121.492138],
                        ['lat' => 24.937907, 'lng' => 121.505356],
                        ['lat' => 24.943666, 'lng' => 121.518403],
                        ['lat' => 24.946934, 'lng' => 121.523724],
                        ['lat' => 24.946623, 'lng' => 121.534195],
                        ['lat' => 24.93993, 'lng' => 121.537972],
                    ],
                    // 第4個多邊形
                    [
                        ['lat' => 24.549571, 'lng' => 120.91161],
                        ['lat' => 24.54879, 'lng' => 120.952465],
                        ['lat' => 24.527553, 'lng' => 120.943882],
                        ['lat' => 24.526928, 'lng' => 120.919335],
                        ['lat' => 24.526303, 'lng' => 120.90543],
                    ],
                    // 第5個多邊形
                    [
                        ['lat' => 24.327745, 'lng' => 120.808059],
                        ['lat' => 24.330717, 'lng' => 120.819474],
                        ['lat' => 24.326572, 'lng' => 120.833207],
                        ['lat' => 24.305062, 'lng' => 120.848829],
                        ['lat' => 24.302324, 'lng' => 120.856639],
                        ['lat' => 24.297865, 'lng' => 120.864106],
                        ['lat' => 24.295049, 'lng' => 120.862476],
                        ['lat' => 24.293719, 'lng' => 120.853292],
                        ['lat' => 24.295127, 'lng' => 120.835696],
                        ['lat' => 24.306079, 'lng' => 120.818187],
                        ['lat' => 24.314136, 'lng' => 120.815784],
                    ]
                ]
            ],
            
            // +800 區域（8個多邊形）
            [
                'name' => '加價800區域',
                'surcharge' => 800,
                'polygons' => [
                    // 第1個多邊形
                    [
                        ['lat' => 25.029035, 'lng' => 121.722159],
                        ['lat' => 25.008502, 'lng' => 121.72542],
                        ['lat' => 25.011457, 'lng' => 121.769194],
                        ['lat' => 25.022969, 'lng' => 121.797003],
                        ['lat' => 25.051275, 'lng' => 121.814856],
                        ['lat' => 25.056407, 'lng' => 121.787562],
                        ['lat' => 25.052364, 'lng' => 121.765246],
                    ],
                    // 第2個多邊形
                    [
                        ['lat' => 25.195757, 'lng' => 121.583155],
                        ['lat' => 25.180067, 'lng' => 121.590793],
                        ['lat' => 25.181621, 'lng' => 121.598776],
                        ['lat' => 25.197155, 'lng' => 121.612165],
                        ['lat' => 25.207872, 'lng' => 121.607015],
                        ['lat' => 25.21191, 'lng' => 121.612509],
                        ['lat' => 25.213308, 'lng' => 121.623495],
                        ['lat' => 25.196067, 'lng' => 121.658686],
                        ['lat' => 25.176028, 'lng' => 121.675852],
                        ['lat' => 25.168571, 'lng' => 121.686838],
                        ['lat' => 25.184107, 'lng' => 121.702631],
                        ['lat' => 25.192805, 'lng' => 121.704862],
                        ['lat' => 25.215529, 'lng' => 121.713102],
                        ['lat' => 25.230282, 'lng' => 121.686323],
                        ['lat' => 25.234008, 'lng' => 121.662119],
                        ['lat' => 25.24907, 'lng' => 121.645639],
                        ['lat' => 25.278255, 'lng' => 121.625555],
                        ['lat' => 25.281825, 'lng' => 121.613539],
                        ['lat' => 25.270028, 'lng' => 121.603926],
                        ['lat' => 25.249535, 'lng' => 121.600321],
                        ['lat' => 25.231679, 'lng' => 121.601866],
                    ],
                    // 第3個多邊形
                    [
                        ['lat' => 25.289033, 'lng' => 121.504261],
                        ['lat' => 25.299277, 'lng' => 121.525203],
                        ['lat' => 25.318209, 'lng' => 121.608287],
                        ['lat' => 25.284377, 'lng' => 121.61584],
                        ['lat' => 25.277237, 'lng' => 121.607257],
                        ['lat' => 25.286239, 'lng' => 121.584598],
                        ['lat' => 25.289654, 'lng' => 121.581165],
                        ['lat' => 25.291827, 'lng' => 121.575328],
                        ['lat' => 25.287171, 'lng' => 121.564685],
                        ['lat' => 25.284687, 'lng' => 121.539623],
                        ['lat' => 25.280651, 'lng' => 121.52692],
                        ['lat' => 25.273511, 'lng' => 121.51559],
                        ['lat' => 25.288102, 'lng' => 121.498081],
                    ],
                    // 第4個多邊形
                    [
                        ['lat' => 25.250719, 'lng' => 121.457527],
                        ['lat' => 25.23954, 'lng' => 121.475723],
                        ['lat' => 25.242645, 'lng' => 121.496323],
                        ['lat' => 25.250719, 'lng' => 121.519669],
                        ['lat' => 25.256308, 'lng' => 121.519669],
                        ['lat' => 25.265312, 'lng' => 121.515892],
                        ['lat' => 25.281767, 'lng' => 121.506622],
                        ['lat' => 25.267796, 'lng' => 121.47538],
                    ],
                    // 第5個多邊形
                    [
                        ['lat' => 24.67213, 'lng' => 120.981457],
                        ['lat' => 24.673923, 'lng' => 121.004888],
                        ['lat' => 24.696384, 'lng' => 120.998022],
                        ['lat' => 24.700049, 'lng' => 121.002657],
                        ['lat' => 24.703168, 'lng' => 120.995447],
                        ['lat' => 24.70387, 'lng' => 120.985576],
                        ['lat' => 24.701764, 'lng' => 120.974933],
                        ['lat' => 24.69654, 'lng' => 120.973388],
                        ['lat' => 24.687806, 'lng' => 120.973903],
                        ['lat' => 24.680241, 'lng' => 120.978367],
                    ],
                    // 第6個多邊形
                    [
                        ['lat' => 22.935514, 'lng' => 120.509501],
                        ['lat' => 22.932668, 'lng' => 120.552073],
                        ['lat' => 23.002211, 'lng' => 120.584002],
                        ['lat' => 23.007584, 'lng' => 120.555506],
                        ['lat' => 23.002527, 'lng' => 120.527354],
                        ['lat' => 22.979456, 'lng' => 120.504351],
                    ],
                    // 第7個多邊形
                    [
                        ['lat' => 22.96776, 'lng' => 120.473452],
                        ['lat' => 22.94658, 'lng' => 120.440493],
                        ['lat' => 22.901994, 'lng' => 120.425043],
                        ['lat' => 22.913695, 'lng' => 120.468645],
                        ['lat' => 22.938992, 'lng' => 120.483065],
                    ],
                    // 第8個多邊形
                    [
                        ['lat' => 22.405741, 'lng' => 120.552621],
                        ['lat' => 22.428592, 'lng' => 120.575623],
                        ['lat' => 22.425419, 'lng' => 120.602746],
                        ['lat' => 22.375584, 'lng' => 120.624718],
                        ['lat' => 22.343516, 'lng' => 120.632958],
                        ['lat' => 22.338435, 'lng' => 120.580773],
                        ['lat' => 22.388283, 'lng' => 120.546097],
                    ]
                ]
            ],
            
            // +1000 區域（9個多邊形）
            [
                'name' => '加價1000區域',
                'surcharge' => 1000,
                'polygons' => [
                    // 第1個多邊形
                    [
                        ['lat' => 25.026383, 'lng' => 121.791186],
                        ['lat' => 25.012695, 'lng' => 121.785693],
                        ['lat' => 24.980956, 'lng' => 122.001986],
                        ['lat' => 25.024517, 'lng' => 122.047304],
                        ['lat' => 25.115944, 'lng' => 121.958041],
                        ['lat' => 25.117188, 'lng' => 121.931261],
                        ['lat' => 25.115323, 'lng' => 121.902422],
                    ],
                    // 第2個多邊形
                    [
                        ['lat' => 24.170405, 'lng' => 120.85516],
                        ['lat' => 24.150357, 'lng' => 120.845547],
                        ['lat' => 24.159755, 'lng' => 120.905971],
                        ['lat' => 24.157249, 'lng' => 120.974636],
                        ['lat' => 24.194834, 'lng' => 121.05154],
                        ['lat' => 24.220511, 'lng' => 121.164837],
                        ['lat' => 24.236165, 'lng' => 121.202602],
                        ['lat' => 24.238669, 'lng' => 121.258221],
                        ['lat' => 24.350694, 'lng' => 121.382503],
                        ['lat' => 24.373838, 'lng' => 121.37701],
                        ['lat' => 24.468555, 'lng' => 121.46387],
                        ['lat' => 24.602143, 'lng' => 121.587762],
                        ['lat' => 24.632106, 'lng' => 121.544503],
                        ['lat' => 24.584661, 'lng' => 121.463479],
                        ['lat' => 24.398505, 'lng' => 121.326456],
                        ['lat' => 24.369111, 'lng' => 121.298304],
                        ['lat' => 24.273387, 'lng' => 121.185018],
                        ['lat' => 24.240208, 'lng' => 120.985891],
                    ],
                    // 第3個多邊形
                    [
                        ['lat' => 23.495156, 'lng' => 120.724433],
                        ['lat' => 23.456423, 'lng' => 120.720313],
                        ['lat' => 23.452013, 'lng' => 120.758765],
                        ['lat' => 23.468705, 'lng' => 120.800307],
                        ['lat' => 23.482876, 'lng' => 120.82537],
                        ['lat' => 23.501138, 'lng' => 120.831206],
                        ['lat' => 23.520342, 'lng' => 120.796188],
                        ['lat' => 23.508694, 'lng' => 120.762542],
                    ],
                    // 第4個多邊形
                    [
                        ['lat' => 23.306222, 'lng' => 120.586573],
                        ['lat' => 23.30701, 'lng' => 120.61318],
                        ['lat' => 23.28336, 'lng' => 120.607859],
                        ['lat' => 23.286987, 'lng' => 120.571467],
                    ],
                    // 第5個多邊形
                    [
                        ['lat' => 23.08312, 'lng' => 120.48964],
                        ['lat' => 23.073645, 'lng' => 120.487923],
                        ['lat' => 23.065748, 'lng' => 120.495304],
                        ['lat' => 23.061484, 'lng' => 120.523285],
                        ['lat' => 23.085173, 'lng' => 120.529293],
                        ['lat' => 23.087858, 'lng' => 120.524658],
                        ['lat' => 23.084699, 'lng' => 120.504402],
                    ],
                    // 第6個多邊形
                    [
                        ['lat' => 23.082527, 'lng' => 120.527222],
                        ['lat' => 23.068156, 'lng' => 120.581467],
                        ['lat' => 23.052204, 'lng' => 120.558465],
                        ['lat' => 23.074947, 'lng' => 120.521729],
                    ],
                    // 第7個多邊形
                    [
                        ['lat' => 23.046478, 'lng' => 120.553841],
                        ['lat' => 23.04553, 'lng' => 120.578217],
                        ['lat' => 23.067327, 'lng' => 120.603108],
                        ['lat' => 23.086436, 'lng' => 120.600361],
                        ['lat' => 23.091647, 'lng' => 120.58989],
                        ['lat' => 23.089279, 'lng' => 120.583195],
                        ['lat' => 23.054415, 'lng' => 120.554001],
                    ],
                    // 第8-9個多邊形（邊界區重複2次）
                    [
                        ['lat' => 23.005085, 'lng' => 120.630018],
                        ['lat' => 23.007297, 'lng' => 120.655767],
                        ['lat' => 23.001608, 'lng' => 120.65817],
                        ['lat' => 22.895065, 'lng' => 120.6592],
                        ['lat' => 22.887475, 'lng' => 120.651304],
                        ['lat' => 22.883679, 'lng' => 120.636884],
                        ['lat' => 22.884628, 'lng' => 120.621091],
                        ['lat' => 22.892851, 'lng' => 120.609762],
                    ],
                    [
                        ['lat' => 23.005085, 'lng' => 120.630018],
                        ['lat' => 23.007297, 'lng' => 120.655767],
                        ['lat' => 23.001608, 'lng' => 120.65817],
                        ['lat' => 22.895065, 'lng' => 120.6592],
                        ['lat' => 22.887475, 'lng' => 120.651304],
                        ['lat' => 22.883679, 'lng' => 120.636884],
                        ['lat' => 22.884628, 'lng' => 120.621091],
                        ['lat' => 22.892851, 'lng' => 120.609762],
                    ]
                ]
            ],
            
            // +1500 區域（7個多邊形）
            [
                'name' => '加價1500區域',
                'surcharge' => 1500,
                'polygons' => [
                    // 第1個多邊形
                    [
                        ['lat' => 24.016345, 'lng' => 121.086324],
                        ['lat' => 23.985295, 'lng' => 121.139196],
                        ['lat' => 24.008192, 'lng' => 121.200994],
                        ['lat' => 24.032652, 'lng' => 121.21919],
                        ['lat' => 24.042685, 'lng' => 121.21404],
                        ['lat' => 24.072781, 'lng' => 121.172155],
                        ['lat' => 24.074662, 'lng' => 121.158079],
                    ],
                    // 第2個多邊形
                    [
                        ['lat' => 23.591081, 'lng' => 120.865697],
                        ['lat' => 23.590452, 'lng' => 120.916509],
                        ['lat' => 23.733217, 'lng' => 120.895223],
                        ['lat' => 23.758357, 'lng' => 120.878744],
                        ['lat' => 23.755215, 'lng' => 120.840291],
                        ['lat' => 23.702413, 'lng' => 120.822439],
                    ],
                    // 第3個多邊形
                    [
                        ['lat' => 23.591303, 'lng' => 120.860006],
                        ['lat' => 23.592719, 'lng' => 120.946867],
                        ['lat' => 23.54347, 'lng' => 120.941202],
                        ['lat' => 23.550237, 'lng' => 120.900003],
                        ['lat' => 23.542998, 'lng' => 120.860349],
                    ],
                    // 第4個多邊形
                    [
                        ['lat' => 23.296704, 'lng' => 120.699897],
                        ['lat' => 23.267691, 'lng' => 120.748649],
                        ['lat' => 23.197022, 'lng' => 120.708137],
                        ['lat' => 23.205227, 'lng' => 120.660758],
                        ['lat' => 23.225421, 'lng' => 120.655265],
                    ],
                    // 第5-6個多邊形（邊界區重複2次）
                    [
                        ['lat' => 23.207433, 'lng' => 120.656636],
                        ['lat' => 23.195758, 'lng' => 120.687535],
                        ['lat' => 23.131129, 'lng' => 120.634835],
                        ['lat' => 23.15457, 'lng' => 120.617755],
                    ],
                    [
                        ['lat' => 23.207433, 'lng' => 120.656636],
                        ['lat' => 23.195758, 'lng' => 120.687535],
                        ['lat' => 23.131129, 'lng' => 120.634835],
                        ['lat' => 23.15457, 'lng' => 120.617755],
                    ],
                    // 第7個多邊形
                    [
                        ['lat' => 22.890409, 'lng' => 120.657219],
                        ['lat' => 22.878706, 'lng' => 120.661339],
                        ['lat' => 22.89294, 'lng' => 120.713524],
                        ['lat' => 22.914445, 'lng' => 120.734123],
                        ['lat' => 22.920769, 'lng' => 120.715241],
                        ['lat' => 22.921086, 'lng' => 120.701164],
                        ['lat' => 22.917607, 'lng' => 120.679535],
                    ]
                ]
            ]
        ];
    }
    
    foreach ($remote_areas as $area) {
        foreach ($area['polygons'] as $polygon) {
            if (!empty($polygon) && is_point_in_polygon($coords, $polygon)) {
                error_log('找到匹配區域: ' . $area['name'] . ', 加價: NT$' . $area['surcharge']);
                return $area['surcharge'];
            }
        }
    }
    
    error_log('地址不在任何偏遠地區內');
    return 0;
}

// 地址轉座標
function get_address_coordinates($address) {
    $api_key = GOOGLE_MAPS_API_KEY;
    $address_encoded = urlencode($address . ', 台灣');
    
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address_encoded}&key={$api_key}&language=zh-TW&region=TW";
    
    $response = wp_remote_get($url, array('timeout' => 10));
    
    if (is_wp_error($response)) {
        error_log('Geocoding API錯誤: ' . $response->get_error_message());
        return false;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ($data['status'] === 'OK' && isset($data['results'][0]['geometry']['location'])) {
        return array(
            'lat' => $data['results'][0]['geometry']['location']['lat'],
            'lng' => $data['results'][0]['geometry']['location']['lng']
        );
    }
    
    return false;
}

// 檢查點是否在多邊形內
function is_point_in_polygon($point, $polygon) {
    if (!is_array($polygon) || count($polygon) < 3) {
        return false;
    }
    
    $x = $point['lng'];
    $y = $point['lat'];
    $inside = false;
    
    $n = count($polygon);
    for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
        $xi = $polygon[$i]['lng'];
        $yi = $polygon[$i]['lat'];
        $xj = $polygon[$j]['lng'];
        $yj = $polygon[$j]['lat'];
        
        $intersect = (($yi > $y) != ($yj > $y))
            && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi);
        
        if ($intersect) {
            $inside = !$inside;
        }
    }
    
    return $inside;
}

// AJAX 價格計算處理器 - 主要函數（加入舉牌服務）
add_action('wp_ajax_calculate_airport_price', 'handle_airport_price_calc');
add_action('wp_ajax_nopriv_calculate_airport_price', 'handle_airport_price_calc');

function handle_airport_price_calc() {
    try {
        // 基本價格表
        $price_table = array(
            'TPE' => array(
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
            ),
            'TSA' => array(
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
            )
        );
        
        $city_mapping = get_city_mapping();
        
        $price_key_mapping = array(
            'hsinchu-area' => '新竹地區',
            'chiayi-area' => '嘉義地區'
        );
        
        $airport_addresses = array(
            'TPE' => '桃園國際機場',
            'TSA' => '台北松山機場'
        );
        
        // 收集資料
        $airport = isset($_POST['airport']) ? strtoupper($_POST['airport']) : 'TPE';
        $destination = isset($_POST['destination']) ? $_POST['destination'] : 'taipei-city';
        $service_type = isset($_POST['service_type']) ? $_POST['service_type'] : 'pickup';
        $trip_type = isset($_POST['trip_type']) ? $_POST['trip_type'] : 'oneway';
        $time = isset($_POST['time']) ? $_POST['time'] : '';
        $child_seats = isset($_POST['child_seats']) ? intval($_POST['child_seats']) : 0;
        $booster_seats = isset($_POST['booster_seats']) ? intval($_POST['booster_seats']) : 0;
        $name_board = isset($_POST['name_board']) ? $_POST['name_board'] : 'no'; // 新增舉牌服務
        
        $pickup_address = isset($_POST['pickup_address']) ? $_POST['pickup_address'] : '';
        $dropoff_address = isset($_POST['dropoff_address']) ? $_POST['dropoff_address'] : '';
        
        // 處理停靠點
        $stopovers = array();
        if (!empty($_POST['stopovers'])) {
            $decoded = json_decode(stripslashes($_POST['stopovers']), true);
            if (is_array($decoded)) {
                $stopovers = $decoded;
            }
        }
        
        // 轉換城市名稱
        if (isset($price_key_mapping[$destination])) {
            $city = $price_key_mapping[$destination];
        } else {
            $city = isset($city_mapping[$destination]) ? $city_mapping[$destination] : '台北市';
        }
        $airport_address = $airport_addresses[$airport];
        
        // 計算價格
        $breakdown = array();
        $subtotal = 0;
        
        // 1. 基本費用
        $base_price = isset($price_table[$airport][$city]) ? $price_table[$airport][$city] : 3000;
        $breakdown['base_price'] = $base_price;
        $subtotal += $base_price;
        
        // 2. 夜間加價
        $night_surcharge = 0;
        if (!empty($time)) {
            $hour = intval(date('H', strtotime($time)));
            if ($hour >= 22 || $hour < 8) {
                $night_surcharge = 200;
            }
        }
        $breakdown['night_surcharge'] = $night_surcharge;
        $subtotal += $night_surcharge;
        
        // 3. 偏遠地區加價
        $remote_surcharge = 0;
        $main_address = '';
        
        if ($service_type === 'pickup' && !empty($dropoff_address)) {
            $main_address = $dropoff_address;
            error_log('接機服務 - 檢查下車地址: ' . $main_address);
        } elseif ($service_type === 'dropoff' && !empty($pickup_address)) {
            $main_address = $pickup_address;
            error_log('送機服務 - 檢查上車地址: ' . $main_address);
        }
        
        if (!empty($main_address)) {
            $remote_surcharge = check_wdap_remote_area_surcharge($main_address);
            if ($remote_surcharge > 0) {
                error_log('偏遠地區加價: ' . $main_address . ' => +NT$' . $remote_surcharge);
            }
        }
        
        $breakdown['remote_surcharge'] = $remote_surcharge;
        $subtotal += $remote_surcharge;
        
        // 4. 停靠點費用計算
        $stopover_charge = 0;
        $stopover_details = array();
        
        if (count($stopovers) > 0) {
            $all_points = array();
            
            if ($service_type === 'pickup') {
                $all_points[] = $airport_address;
                foreach ($stopovers as $stop) {
                    if (!empty($stop['address'])) {
                        $all_points[] = $stop['address'];
                    }
                }
                if (!empty($dropoff_address)) {
                    $all_points[] = $dropoff_address;
                }
            } else {
                if (!empty($pickup_address)) {
                    $all_points[] = $pickup_address;
                }
                foreach ($stopovers as $stop) {
                    if (!empty($stop['address'])) {
                        $all_points[] = $stop['address'];
                    }
                }
                $all_points[] = $airport_address;
            }
            
            for ($i = 0; $i < count($all_points) - 1; $i++) {
                $from = $all_points[$i];
                $to = $all_points[$i + 1];
                
                $should_charge = true;
                if ($service_type === 'pickup' && $i === 0) {
                    $should_charge = false;
                } elseif ($service_type === 'dropoff' && $i === count($all_points) - 2) {
                    $should_charge = false;
                }
                
                $distance = calculate_distance_google($from, $to);
                
                $fee = $should_charge ? calculate_distance_fee($distance) : 0;
                $stopover_charge += $fee;
                
                $from_short = mb_strlen($from) > 15 ? mb_substr($from, 0, 15) . '...' : $from;
                $to_short = mb_strlen($to) > 15 ? mb_substr($to, 0, 15) . '...' : $to;
                
                $segment_name = sprintf("路段 %d (%s → %s)", 
                    $i + 1, 
                    $from_short, 
                    $to_short
                );
                
                $stopover_details[] = array(
                    'segment' => $segment_name,
                    'distance' => round($distance, 1),
                    'fee' => $fee,
                    'charged' => $should_charge
                );
            }
        }
        
        $breakdown['stopover_charge'] = $stopover_charge;
        $breakdown['stopover_details'] = $stopover_details;
        $subtotal += $stopover_charge;
        
        // 5. 加購項目（包含舉牌服務）
        $addon_charge = ($child_seats * 100) + ($booster_seats * 100);
        
        // 新增：舉牌服務費用
        $name_board_charge = 0;
        if ($name_board === 'yes') {
            $name_board_charge = 200;
            $addon_charge += $name_board_charge;
        }
        
        $breakdown['addon_charge'] = $addon_charge;
        $breakdown['name_board_charge'] = $name_board_charge; // 分別記錄舉牌費用
        $subtotal += $addon_charge;
        
        // 6. 來回程處理
        $total = $subtotal;
        if ($trip_type === 'roundtrip') {
            $return_stopovers = array();
            if (!empty($_POST['return_stopovers'])) {
                $decoded = json_decode(stripslashes($_POST['return_stopovers']), true);
                if (is_array($decoded)) {
                    $return_stopovers = $decoded;
                }
            }
            
            // 回程偏遠地區加價
            $return_remote_surcharge = 0;
            $return_pickup_address = isset($_POST['return_pickup_address']) ? $_POST['return_pickup_address'] : '';
            $return_dropoff_address = isset($_POST['return_dropoff_address']) ? $_POST['return_dropoff_address'] : '';
            
            $return_main_address = '';
            
            if ($service_type === 'pickup') {
                if (!empty($return_pickup_address)) {
                    $return_main_address = $return_pickup_address;
                    error_log('回程送機 - 檢查上車地址: ' . $return_main_address);
                }
            } else {
                if (!empty($return_dropoff_address)) {
                    $return_main_address = $return_dropoff_address;
                    error_log('回程接機 - 檢查下車地址: ' . $return_main_address);
                }
            }
            
            if (!empty($return_main_address)) {
                $return_remote_surcharge = check_wdap_remote_area_surcharge($return_main_address);
                if ($return_remote_surcharge > 0) {
                    error_log('回程偏遠地區加價: ' . $return_main_address . ' => +NT$' . $return_remote_surcharge);
                }
            }
            
            $breakdown['return_remote_surcharge'] = $return_remote_surcharge;
            
            // 計算回程停靠點費用
            $return_stopover_charge = 0;
            $return_stopover_details = array();
            
            if (count($return_stopovers) > 0) {
                $return_points = array();
                
                if ($service_type === 'pickup') {
                    if (!empty($return_pickup_address)) {
                        $return_points[] = $return_pickup_address;
                    }
                    foreach ($return_stopovers as $stop) {
                        if (!empty($stop['address'])) {
                            $return_points[] = $stop['address'];
                        }
                    }
                    $return_points[] = $airport_address;
                } else {
                    $return_points[] = $airport_address;
                    foreach ($return_stopovers as $stop) {
                        if (!empty($stop['address'])) {
                            $return_points[] = $stop['address'];
                        }
                    }
                    if (!empty($return_dropoff_address)) {
                        $return_points[] = $return_dropoff_address;
                    }
                }
                
                for ($i = 0; $i < count($return_points) - 1; $i++) {
                    $from = $return_points[$i];
                    $to = $return_points[$i + 1];
                    
                    $should_charge = true;
                    if ($service_type === 'pickup' && $i === count($return_points) - 2) {
                        $should_charge = false;
                    } elseif ($service_type === 'dropoff' && $i === 0) {
                        $should_charge = false;
                    }
                    
                    $distance = calculate_distance_google($from, $to);
                    
                    $fee = $should_charge ? calculate_distance_fee($distance) : 0;
                    $return_stopover_charge += $fee;
                    
                    $from_short = mb_strlen($from) > 15 ? mb_substr($from, 0, 15) . '...' : $from;
                    $to_short = mb_strlen($to) > 15 ? mb_substr($to, 0, 15) . '...' : $to;
                    
                    $segment_name = sprintf("回程路段 %d (%s → %s)", 
                        $i + 1, 
                        $from_short, 
                        $to_short
                    );
                    
                    $return_stopover_details[] = array(
                        'segment' => $segment_name,
                        'distance' => round($distance, 1),
                        'fee' => $fee,
                        'charged' => $should_charge
                    );
                }
            }
            
            // 回程加購項目
            $return_child_seats = isset($_POST['return_child_seats']) ? intval($_POST['return_child_seats']) : $child_seats;
            $return_booster_seats = isset($_POST['return_booster_seats']) ? intval($_POST['return_booster_seats']) : $booster_seats;
            $return_name_board = isset($_POST['return_name_board']) ? $_POST['return_name_board'] : $name_board; // 新增回程舉牌
            
            $return_addon = ($return_child_seats * 100) + ($return_booster_seats * 100);
            if ($return_name_board === 'yes') {
                $return_addon += 200;
            }
            
            // 回程小計
            $return_subtotal = $base_price + $night_surcharge + $return_remote_surcharge + $return_stopover_charge + $return_addon;
            
            // 計算總價（含9折）
            $original_total = $subtotal + $return_subtotal;
            $total = round($original_total * 0.9);
            $discount = $original_total - $total;
            
            $breakdown['return_stopover_charge'] = $return_stopover_charge;
            $breakdown['return_stopover_details'] = $return_stopover_details;
            $breakdown['return_addon'] = $return_addon;
            $breakdown['return_subtotal'] = $return_subtotal;
            $breakdown['discount'] = -$discount;
            $breakdown['original_total'] = $original_total;
        }
        
        // 返回結果
        wp_send_json_success(array(
            'subtotal' => $subtotal,
            'total' => $total,
            'breakdown' => $breakdown
        ));
        
    } catch (Exception $e) {
        error_log('Airport Price Calc Error: ' . $e->getMessage());
        wp_send_json_error(array('message' => '計算錯誤：' . $e->getMessage()));
    }
    
    wp_die();
}

// AJAX 預約提交處理器 - 含地址驗證
add_action('wp_ajax_submit_airport_booking', 'handle_airport_booking_submit');
add_action('wp_ajax_nopriv_submit_airport_booking', 'handle_airport_booking_submit');

function handle_airport_booking_submit() {
    try {
        // 資料收集
        $airport = isset($_POST['airport']) ? sanitize_text_field($_POST['airport']) : '';
        $destination = isset($_POST['destination']) ? sanitize_text_field($_POST['destination']) : '';
        $service_type = isset($_POST['service_type']) ? sanitize_text_field($_POST['service_type']) : '';
        $trip_type = isset($_POST['trip_type']) ? sanitize_text_field($_POST['trip_type']) : '';
        
        // 去程資料
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $time = isset($_POST['time']) ? sanitize_text_field($_POST['time']) : '';
        $flight = isset($_POST['flight']) ? sanitize_text_field($_POST['flight']) : '';
        $passengers = isset($_POST['passengers']) ? intval($_POST['passengers']) : 1;
        $child_seats = isset($_POST['child_seats']) ? intval($_POST['child_seats']) : 0;
        $booster_seats = isset($_POST['booster_seats']) ? intval($_POST['booster_seats']) : 0;
        $name_board = isset($_POST['name_board']) ? sanitize_text_field($_POST['name_board']) : 'no'; // 新增
        
        // 地址資料
        $pickup_address = isset($_POST['pickup_address']) ? sanitize_text_field($_POST['pickup_address']) : '';
        $dropoff_address = isset($_POST['dropoff_address']) ? sanitize_text_field($_POST['dropoff_address']) : '';
        
        // 客戶資料
        $customer_name = isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '';
        
        // 處理國際電話格式
        $customer_phone = isset($_POST['customer_phone']) ? sanitize_text_field($_POST['customer_phone']) : '';
        $customer_phone = preg_replace('/[^0-9+]/', '', $customer_phone);
        
        // Email 改為選填
        $customer_email = isset($_POST['customer_email']) ? sanitize_email($_POST['customer_email']) : '';
        
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        $total_price = isset($_POST['total_price']) ? floatval($_POST['total_price']) : 0;
        
        // 必填欄位驗證
        $errors = array();
        
        if (empty($customer_name)) {
            $errors[] = '請填寫姓名';
        }
        
        // 驗證電話（支援國際格式）
        if (empty($customer_phone)) {
            $errors[] = '請填寫電話';
        } else {
            if (!preg_match('/^(\+[0-9]{1,4})?[0-9]{9,15}$/', $customer_phone)) {
                $errors[] = '電話格式不正確';
            }
        }
        
        // Email 不再強制驗證，但如果有填寫要驗證格式
        if (!empty($customer_email) && !filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email 格式不正確';
        }
        
        if (empty($date)) {
            $errors[] = '請選擇日期';
        }
        
        if (empty($time)) {
            $errors[] = '請選擇時間';
        }
        
        // 地址驗證 - 檢查是否與選擇的縣市相符
        $main_address = '';
        if ($service_type === 'pickup' && !empty($dropoff_address)) {
            $main_address = $dropoff_address;
            $validation_result = validate_address_city($dropoff_address, $destination);
            if (!$validation_result['valid']) {
                $errors[] = '下車地址錯誤：' . $validation_result['message'];
            }
        } elseif ($service_type === 'dropoff' && !empty($pickup_address)) {
            $main_address = $pickup_address;
            $validation_result = validate_address_city($pickup_address, $destination);
            if (!$validation_result['valid']) {
                $errors[] = '上車地址錯誤：' . $validation_result['message'];
            }
        }
        
        // 處理停靠點
        $stopovers = array();
        if (!empty($_POST['stopovers'])) {
            $decoded = json_decode(stripslashes($_POST['stopovers']), true);
            if (is_array($decoded)) {
                $stopovers = $decoded;
            }
        }
        
        // 處理回程資料（如果是來回程）
        $return_data = null;
        if ($trip_type === 'roundtrip') {
            $return_date = isset($_POST['return_date']) ? sanitize_text_field($_POST['return_date']) : '';
            $return_time = isset($_POST['return_time']) ? sanitize_text_field($_POST['return_time']) : '';
            $return_flight = isset($_POST['return_flight']) ? sanitize_text_field($_POST['return_flight']) : '';
            $return_passengers = isset($_POST['return_passengers']) ? intval($_POST['return_passengers']) : $passengers;
            $return_child_seats = isset($_POST['return_child_seats']) ? intval($_POST['return_child_seats']) : 0;
            $return_booster_seats = isset($_POST['return_booster_seats']) ? intval($_POST['return_booster_seats']) : 0;
            $return_name_board = isset($_POST['return_name_board']) ? sanitize_text_field($_POST['return_name_board']) : 'no'; // 新增
            
            $return_pickup_address = isset($_POST['return_pickup_address']) ? sanitize_text_field($_POST['return_pickup_address']) : '';
            $return_dropoff_address = isset($_POST['return_dropoff_address']) ? sanitize_text_field($_POST['return_dropoff_address']) : '';
            
            // 回程停靠點
            $return_stopovers = array();
            if (!empty($_POST['return_stopovers'])) {
                $decoded = json_decode(stripslashes($_POST['return_stopovers']), true);
                if (is_array($decoded)) {
                    $return_stopovers = $decoded;
                }
            }
            
            // 驗證回程必填
            if (empty($return_date)) {
                $errors[] = '請選擇回程日期';
            }
            if (empty($return_time)) {
                $errors[] = '請選擇回程時間';
            }
            
            // 回程地址驗證（回程服務類型相反）
            if ($service_type === 'pickup') {
                if (empty($return_pickup_address)) {
                    $errors[] = '請填寫回程上車地址';
                } else {
                    $validation_result = validate_address_city($return_pickup_address, $destination);
                    if (!$validation_result['valid']) {
                        $errors[] = '回程上車地址錯誤：' . $validation_result['message'];
                    }
                }
            } elseif ($service_type === 'dropoff') {
                if (empty($return_dropoff_address)) {
                    $errors[] = '請填寫回程下車地址';
                } else {
                    $validation_result = validate_address_city($return_dropoff_address, $destination);
                    if (!$validation_result['valid']) {
                        $errors[] = '回程下車地址錯誤：' . $validation_result['message'];
                    }
                }
            }
            
            $return_data = array(
                'date' => $return_date,
                'time' => $return_time,
                'flight' => $return_flight,
                'passengers' => $return_passengers,
                'child_seats' => $return_child_seats,
                'booster_seats' => $return_booster_seats,
                'name_board' => $return_name_board, // 新增
                'pickup_address' => $return_pickup_address,
                'dropoff_address' => $return_dropoff_address,
                'stopovers' => $return_stopovers
            );
        }
        
        // 如果有錯誤，返回錯誤訊息
        if (!empty($errors)) {
            wp_send_json_error(array(
                'message' => implode('、', $errors)
            ));
            wp_die();
        }
        
        // 格式化電話號碼（統一格式儲存）
        if (strpos($customer_phone, '+') !== 0 && strlen($customer_phone) == 10) {
            $customer_phone = '+886' . ltrim($customer_phone, '0');
        }
        
        // 組裝預約資料
        $booking_data = array(
            'booking_type' => 'airport_transfer',
            'airport' => strtoupper($airport),
            'destination' => $destination,
            'service_type' => $service_type,
            'trip_type' => $trip_type,
            'date' => $date,
            'time' => $time,
            'flight' => $flight,
            'passengers' => $passengers,
            'child_seats' => $child_seats,
            'booster_seats' => $booster_seats,
            'name_board' => $name_board, // 新增
            'pickup_address' => $pickup_address,
            'dropoff_address' => $dropoff_address,
            'stopovers' => $stopovers,
            'customer_name' => $customer_name,
            'customer_phone' => $customer_phone,
            'customer_email' => $customer_email,
            'notes' => $notes,
            'total_price' => $total_price,
            'return_data' => $return_data,
            'booking_time' => current_time('mysql'),
            'booking_status' => 'pending',
            'ip_address' => $_SERVER['REMOTE_ADDR']
        );
        
        // 儲存為自訂 Post Type
        $post_data = array(
            'post_title' => sprintf('[%s] %s - %s %s', 
                strtoupper($airport), 
                $customer_name, 
                $date,
                $service_type === 'pickup' ? '接機' : '送機'
            ),
            'post_content' => wp_json_encode($booking_data, JSON_UNESCAPED_UNICODE),
            'post_status' => 'private',
            'post_type' => 'airport_booking',
            'meta_input' => array(
                '_booking_data' => $booking_data,
                '_customer_name' => $customer_name,
                '_customer_phone' => $customer_phone,
                '_customer_email' => $customer_email,
                '_booking_date' => $date,
                '_total_price' => $total_price,
                '_trip_type' => $trip_type
            )
        );
        
        $booking_id = wp_insert_post($post_data);
        
        if (is_wp_error($booking_id)) {
            throw new Exception('預約儲存失敗');
        }
        
        // 發送 Email 通知（只在有 email 時發送）
        if (!empty($customer_email)) {
            $email_sent = send_booking_confirmation_email($booking_id, $booking_data);
            error_log('確認信發送狀態: ' . ($email_sent ? '成功' : '失敗'));
        } else {
            error_log('客戶未提供 Email，跳過發送確認信');
        }
        
        // 發送管理員通知
        send_admin_notification($booking_id, $booking_data);
        
        // 返回成功訊息
        wp_send_json_success(array(
            'message' => '預約成功！我們將在24小時內與您聯繫確認。',
            'booking_id' => $booking_id,
            'booking_number' => 'APT' . str_pad($booking_id, 6, '0', STR_PAD_LEFT),
        ));
        
    } catch (Exception $e) {
        error_log('Airport Booking Error: ' . $e->getMessage());
        wp_send_json_error(array(
            'message' => '預約失敗：' . $e->getMessage()
        ));
    }
    
    wp_die();
}

// Email 發送函數（加入舉牌服務資訊）
function send_booking_confirmation_email($booking_id, $booking_data) {
    if (empty($booking_data['customer_email'])) {
        return false;
    }
    
    $to = $booking_data['customer_email'];
    $subject = '機場接送預約確認 - 訂單編號 APT' . str_pad($booking_id, 6, '0', STR_PAD_LEFT);
    
    // 組裝郵件內容
    $message = "親愛的 {$booking_data['customer_name']} 您好，\n\n";
    $message .= "您的機場接送預約已成功提交，以下是您的預約詳情：\n\n";
    $message .= "【預約編號】APT" . str_pad($booking_id, 6, '0', STR_PAD_LEFT) . "\n";
    $message .= "【服務類型】" . ($booking_data['service_type'] === 'pickup' ? '接機' : '送機') . "\n";
    $message .= "【機場】" . ($booking_data['airport'] === 'TPE' ? '桃園國際機場' : '台北松山機場') . "\n";
    $message .= "【日期時間】{$booking_data['date']} {$booking_data['time']}\n";
    
    if (!empty($booking_data['flight'])) {
        $message .= "【航班號碼】{$booking_data['flight']}\n";
    }
    
    // 地址資訊
    if ($booking_data['service_type'] === 'pickup') {
        $message .= "【下車地址】{$booking_data['dropoff_address']}\n";
    } else {
        $message .= "【上車地址】{$booking_data['pickup_address']}\n";
    }
    
    // 停靠點
    if (!empty($booking_data['stopovers'])) {
        $message .= "【停靠點】\n";
        foreach ($booking_data['stopovers'] as $i => $stop) {
            $message .= "  " . ($i + 1) . ". {$stop['address']}\n";
        }
    }
    
    $message .= "【乘客人數】{$booking_data['passengers']} 人\n";
    
    // 加購項目
    if ($booking_data['child_seats'] > 0) {
        $message .= "【嬰兒座椅】{$booking_data['child_seats']} 張\n";
    }
    if ($booking_data['booster_seats'] > 0) {
        $message .= "【兒童增高墊】{$booking_data['booster_seats']} 張\n";
    }
    if ($booking_data['name_board'] === 'yes') {
        $message .= "【舉牌服務】是\n";
    }
    
    // 回程資訊
    if ($booking_data['trip_type'] === 'roundtrip' && !empty($booking_data['return_data'])) {
        $return = $booking_data['return_data'];
        $message .= "\n【回程資訊】\n";
        $message .= "日期時間：{$return['date']} {$return['time']}\n";
        if (!empty($return['flight'])) {
            $message .= "航班號碼：{$return['flight']}\n";
        }
        if ($booking_data['service_type'] === 'pickup') {
            $message .= "上車地址：{$return['pickup_address']}\n";
        } else {
            $message .= "下車地址：{$return['dropoff_address']}\n";
        }
        if ($return['name_board'] === 'yes') {
            $message .= "回程舉牌服務：是\n";
        }
    }
    
    $message .= "\n【總金額】NT$ " . number_format($booking_data['total_price']) . "\n";
    
    if (!empty($booking_data['notes'])) {
        $message .= "\n【備註】{$booking_data['notes']}\n";
    }
    
    $message .= "\n我們將在24小時內與您電話確認預約詳情。\n";
    $message .= "如有任何問題，請聯繫我們的客服專線。\n\n";
    $message .= "感謝您的預約！\n";
    $message .= "9o Van Strip 機場接送服務";
    
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'From: 9o Van Strip <noreply@' . $_SERVER['SERVER_NAME'] . '>'
    );
    
    return wp_mail($to, $subject, $message, $headers);
}

// 管理員通知函數
function send_admin_notification($booking_id, $booking_data) {
    $admin_email = get_option('admin_email');
    
    $subject = '新預約通知 - ' . $booking_data['customer_name'] . ' - APT' . str_pad($booking_id, 6, '0', STR_PAD_LEFT);
    
    $message = "有新的機場接送預約：\n\n";
    $message .= "【預約編號】APT" . str_pad($booking_id, 6, '0', STR_PAD_LEFT) . "\n";
    $message .= "【客戶姓名】{$booking_data['customer_name']}\n";
    $message .= "【客戶電話】{$booking_data['customer_phone']}\n";
    
    if (!empty($booking_data['customer_email'])) {
        $message .= "【客戶Email】{$booking_data['customer_email']}\n";
    } else {
        $message .= "【客戶Email】未提供\n";
    }
    
    $message .= "【服務類型】" . ($booking_data['service_type'] === 'pickup' ? '接機' : '送機');
    $message .= " / " . ($booking_data['trip_type'] === 'roundtrip' ? '來回' : '單程') . "\n";
    $message .= "【日期時間】{$booking_data['date']} {$booking_data['time']}\n";
    
    // 新增：舉牌服務狀態
    if ($booking_data['name_board'] === 'yes') {
        $message .= "【舉牌服務】需要舉牌\n";
    }
    
    $message .= "【總金額】NT$ " . number_format($booking_data['total_price']) . "\n";
    
    $edit_link = admin_url('post.php?post=' . $booking_id . '&action=edit');
    $message .= "\n【管理連結】{$edit_link}\n";
    
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8'
    );
    
    wp_mail($admin_email, $subject, $message, $headers);
}

// 註冊自訂 Post Type
add_action('init', 'register_airport_booking_post_type');

function register_airport_booking_post_type() {
    $args = array(
        'labels' => array(
            'name' => '機場接送預約',
            'singular_name' => '預約',
            'menu_name' => '機場接送',
            'add_new' => '新增預約',
            'add_new_item' => '新增預約',
            'edit_item' => '編輯預約',
            'view_item' => '查看預約',
            'search_items' => '搜尋預約',
            'not_found' => '沒有找到預約',
            'not_found_in_trash' => '回收桶中沒有預約'
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 25,
        'menu_icon' => 'dashicons-car',
        'supports' => array('title'),
        'has_archive' => false,
        'rewrite' => false,
        'capability_type' => 'post',
        'capabilities' => array(
            'create_posts' => 'do_not_allow'
        ),
        'map_meta_cap' => true
    );
    
    register_post_type('airport_booking', $args);
}

// 測試端點
add_action('wp_ajax_test_airport_ajax', 'test_airport_ajax');
add_action('wp_ajax_nopriv_test_airport_ajax', 'test_airport_ajax');

function test_airport_ajax() {
    wp_send_json_success(array(
        'message' => 'AJAX 系統正常運作',
        'timestamp' => current_time('mysql'),
        'api_key_set' => defined('GOOGLE_MAPS_API_KEY'),
        'php_version' => phpversion(),
        'wp_version' => get_bloginfo('version')
    ));
    wp_die();
}
