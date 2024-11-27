<?php
/**
 * Copyright (C) 2019-2024 Paladin Business Solutions
 */
ob_start();
session_start();

require_once('includes/ringcentral-functions.inc');
require_once('includes/ringcentral-db-functions.inc');
require_once('includes/ringcentral-php-functions.inc');

show_errors();

page_header(1);  // set back to 1 when recaptchas are set in the .ENV file

$callLogUrl = "https://platform.ringcentral.com/restapi/v1.0/account/~/call-log";

$accessToken = $_GET['access_token'] ;

$headers = [
	"Authorization: Bearer $accessToken"
];
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $callLogUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
$callLogs = json_decode($response, true);

echo_spaces("call log", $callLogs);

//foreach ($callLogs['records'] as $call) {
//    //
//}

/*
// Filter unanswered calls
$unansweredCalls = array_filter($callLogs['records'], function ($call) {
	return $call['result'] === 'Missed';
});

foreach ($unansweredCalls as $call) {
	$toNumber = $call['from']['phoneNumber'];
}
*/


ob_end_flush();
page_footer();
