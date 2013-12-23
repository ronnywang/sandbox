<?php
// php "convert-pollution-to-csv.php" {JSON file} > {csv file}
//
$fp = fopen($_SERVER['argv'][1], 'r');
$output = fopen('php://output', 'w');
$columns = array(
    "case_exnum",
    "accuse_Mon",
    "To_Org_Nam",
    "LON",
    "LAT",
    "pollutio_2",
    "pollutio_1",
    "Detail_id",
    "case_date",
    "pollution_",
    "pollution1",
    "From_Org_N",
);
fputcsv($output, $columns);
while (FALSE !== ($line = fgets($fp))) {
    if (!preg_match('#^{"geometry#', $line)){
        continue;
    }
    $json = trim(trim($line), ',');
    $json = json_decode($json);
    $rows = array();
    foreach ($columns as $col) {
        $rows[] = $json->properties->{$col};
    }
    fputcsv($output, $rows);
}
