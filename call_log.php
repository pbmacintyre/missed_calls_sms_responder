<?php
/**
 * Copyright (C) 2019-2024 Paladin Business Solutions
 */
ob_start();
session_start();

require_once('includes/ringcentral-functions.inc');
//require_once('includes/ringcentral-db-functions.inc');
require_once('includes/ringcentral-php-functions.inc');

show_errors();

//page_header(0);  // set back to 1 when recaptchas are set in the .ENV file

$callLogUrl = "https://platform.ringcentral.com/restapi/v1.0/account/~/call-log";

$accessToken = $_GET['access_token'] ;

$startDate = date('Y-m-d\TH:i:s\Z', strtotime('-2 weeks'));
$endDate = date('Y-m-d\TH:i:s\Z', strtotime('now'));

$callLogUrl .= "?dateFrom=$startDate&dateTo=$endDate&recordType=Voice";

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

//echo_spaces("call log", $callLogs);

foreach ($callLogs['records'] as $call) {
//	if ($call['result'] == "Missed") {
		echo_spaces("Call ID" , $call['id']);
		echo_spaces("Call result" , $call['result']);
		echo_spaces("From", $call['from']['phoneNumber']);
		echo_spaces("To",$call['to']['phoneNumber']);
		echo_spaces("Start Time",$call['startTime']);
		echo_spaces("Duration",$call['duration'] . " seconds", 2);
//	}
}

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
