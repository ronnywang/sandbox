<?php

$fp = fopen('2012president.csv', 'r');
$output = fopen('php://output', 'w');

$columns = fgetcsv($fp);
fputcsv($output, array_merge($columns, array('藍綠比例')));

while ($row = fgetcsv($fp)) {
    $green_count = $row[4];
    $blue_count = $row[5];
    fputcsv($output, array_merge($row, array(2 * (floatval($blue_count) / ($blue_count + $green_count)) - 1)));
}
