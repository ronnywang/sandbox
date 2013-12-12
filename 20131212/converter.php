<?php

// 將 http://github.com/ronnywang/twgeojson/twivillage2010.2.json 中的面積取出
// 與主計處人口數算出人口面積的 script
$fp = fopen($_SERVER['argv'][1], 'r');
$json = json_decode(file_get_contents('twvillage2010.2.json'));
$area = array();
foreach ($json->features as $feature) {
    $properties = $feature->properties;
    $area[$properties->town_id . '-' . $properties->village_id] = $properties->area;
}
fgetcsv($fp);
fgetcsv($fp);

$output = fopen('php://output', 'w');
fputcsv($output, array('縣市名稱', '鄉鎮市區名稱', '村里名稱', '鄉鎮市區代碼', '村里代碼', '人口數', '面積', '人口密度'));

while ($row = fgetcsv($fp)) {
    list($c_c, $c_n, $t_c, $t_n, $v_c, $v_n, $home, $human, $men, $women) = $row;
    list(, $v_code) = explode('-', $v_c);

    fputcsv($output, array(
        $c_n,
        $t_n,
        $v_n,
        $t_c,
        $v_code,
        $human,
        $area[$v_c] ?: 0,
        $area[$v_c] ? (10000 * $human / $area[$v_c]) : 0,
    ));
}
