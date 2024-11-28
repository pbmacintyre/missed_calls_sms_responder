<?php
/**
 * Copyright (C) 2019-2024 Paladin Business Solutions
 */
ob_start();
session_start();

require_once('includes/ringcentral-php-functions.inc');

$callLogUrl = "https://platform.ringcentral.com/restapi/v1.0/account/~/call-log";

$accessToken = $_GET['access_token'] ;

//$startDate = date('Y-m-d\TH:i:s\Z', strtotime('-8 weeks'));
//$endDate = date('Y-m-d\TH:i:s\Z', strtotime('now'));

$callLogUrl .= "?recordType=Voice";
//$callLogUrl .= "?dateFrom=$startDate&dateTo=$endDate&recordType=Voice";

$headers = [
	"Authorization: Bearer $accessToken"
];
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $callLogUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
if (curl_errno($ch)) {
	echo 'Error:' . curl_error($ch);
}
curl_close($ch);
$callLogs = json_decode($response, true);

// show all items in the call log for provided date range, if any
//echo_spaces("call log", $callLogs);

$i = 0 ;
foreach ($callLogs['records'] as $call) {
	if ($call['result'] == "Missed" && $call['direction'] == "Inbound" ) {
		$i++;
		echo_spaces("Call Count" , $i);
		echo_spaces("Call ID" , $call['id']);
		echo_spaces("Call result" , $call['result']);
		echo_spaces("From #", $call['from']['phoneNumber']);
		echo_spaces("From Name", $call['from']['name']);
		echo_spaces("From Location", $call['from']['location']);
		echo_spaces("To (RingCentral #)",$call['to']['phoneNumber']);
		echo_spaces("Start Time",$call['startTime']);
		echo_spaces("Duration",$call['duration'] . " seconds", 2);
		send_missed_call_sms($accessToken, $call['from']['phoneNumber'], $call['to']['phoneNumber'], $call['startTime']);
	}
}

function send_missed_call_sms($accessToken, $fromNumber, $toNumber, $callTime) {

	$endpoint = 'https://platform.ringcentral.com/restapi/v1.0/account/~/extension/~/sms';

	$callTime_formatted = date("F j, Y, g:i a", strtotime($callTime)) ;

	$message="You have a new missed call event from $fromNumber at $callTime_formatted" ;

	$sms_data = [
		'from' => array('phoneNumber' => $toNumber),
		'to' => array(array('phoneNumber' => $toNumber)),
		'text' => $message,
	];

	$sms_headers = [
		'Authorization: Bearer ' . $accessToken,
		"Content-Type: application/json"
	];

	$sms_ch = curl_init();
	curl_setopt($sms_ch, CURLOPT_URL, $endpoint);
	curl_setopt($sms_ch, CURLOPT_POST, true);
	curl_setopt($sms_ch, CURLOPT_POSTFIELDS, json_encode($sms_data));
	curl_setopt($sms_ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($sms_ch, CURLOPT_HTTPHEADER, $sms_headers);

	curl_exec($sms_ch);
	if (curl_errno($sms_ch)) {
		echo 'Error:' . curl_error($sms_ch);
	}
	curl_close($sms_ch);

	// ==========================================
	// now send to the caller
	// ==========================================

	$message_2="Thank you for calling us on $callTime_formatted, sorry we missed you. We have your contact information and will call you back when we can." ;

	$sms_data_2 = [
		'from' => array('phoneNumber' => $toNumber),
		'to' => array(array('phoneNumber' => $fromNumber)),
		'text' => $message_2,
	];

	$sms_ch_2 = curl_init();
	curl_setopt($sms_ch_2, CURLOPT_URL, $endpoint);
	curl_setopt($sms_ch_2, CURLOPT_POST, true);
	curl_setopt($sms_ch_2, CURLOPT_POSTFIELDS, json_encode($sms_data_2));
	curl_setopt($sms_ch_2, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($sms_ch_2, CURLOPT_HTTPHEADER, $sms_headers);

	curl_exec($sms_ch_2);
	curl_close($sms_ch_2);
}

ob_end_flush();
page_footer();
