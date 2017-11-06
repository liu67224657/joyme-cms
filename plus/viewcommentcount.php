<?php

require_once(dirname(__FILE__)."/../include/common.inc.php");
$client_id = 'e6601523811f1b63';
$vids = addslashes($vids);
$callback = addslashes($callback);
$url = 'https://openapi.youku.com/v2/videos/show_batch.json?client_id='.$client_id.'&video_ids='.$vids;

$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADER, 0);
$output = curl_exec($ch);
curl_close($ch);
echo $callback.'('.$output.')';
exit();

