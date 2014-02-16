<?php

$curl = curl_init('http://axe-level-1.herokuapp.com/');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$content = curl_exec($curl);

$doc = new DOMDocument;
$doc->loadHTML($content);

$answer = array();
foreach ($doc->getElementsByTagName('tr') as $tr_dom) {
    $person = new StdClass;
    $td_doms = $tr_dom->getElementsByTagName('td');
    $name = $td_doms->item(0)->nodeValue;
    if ('姓名' == $name) {
        continue;
    }
    $person->name = $name;
    $person->grades = new StdClass;
    $person->grades->{'國語'} = intval($td_doms->item(1)->nodeValue);
    $person->grades->{'數學'} = intval($td_doms->item(2)->nodeValue);
    $person->grades->{'自然'} = intval($td_doms->item(3)->nodeValue);
    $person->grades->{'社會'} = intval($td_doms->item(4)->nodeValue);
    $person->grades->{'健康教育'} = intval($td_doms->item(5)->nodeValue);
    $answer[] = $person;
}
echo json_encode($answer, JSON_UNESCAPED_UNICODE);
