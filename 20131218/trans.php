<?php

// 資料來源: http://data.gov.tw/opendata/Details?sno=301000000A-00023
// 臺灣地區地名資料

if (!$_SERVER['argv'][1]) {
    die("php trans.php {proj4php.php position}");
}
include($_SERVER['argv'][1]);

$proj4 = new Proj4php();
$projSrc = new Proj4phpProj('EPSG:TM2', $proj4);
$projDst = new Proj4phpProj('WGS84', $proj4);

$twd67to97 = function($point){
    $A = 0.00001549;
    $B = 0.000006521;
    $X67 = $point[0];
    $Y67 = $point[1];
    $X97 = $X67 + 807.8 + $A * $X67 + $B * $Y67;
    $Y97 = $Y67 - 248.6 + $A * $Y67 + $B * $X67;
    return array($X97, $Y97);
};

$fp = fopen('road.csv', 'r');
$output = fopen('php://output', 'w');
$columns = fgetcsv($fp);
fputcsv($output, $columns);
while ($rows = fgetcsv($fp)) {
    if (($rows[13] == 99 and $rows[14] == 99) or (!$rows[13])) {
        $rows[13] = $rows[14] = '';
    } else {
        // 有些資料 X, Y 寫反，所以加上 min, max 判斷
        $point = $twd67to97(array(min($rows[13], $rows[14]), max($rows[13], $rows[14])));
        $pointSrc = new proj4phpPoint($point[0], $point[1]);
        $pointDst = $proj4->transform($projSrc, $projDst, $pointSrc);
        $rows[13] = $pointDst->x;
        $rows[14] = $pointDst->y;
        fputcsv($output, $rows);
    }
}
