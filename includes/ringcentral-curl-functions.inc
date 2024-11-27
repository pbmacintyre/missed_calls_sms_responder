<?php
/** Copyright (C) 2019-2024 Paladin Business Solutions */

/* ================= */
/* Generic functions */
/* ================= */

function get_audit_data($accessToken, $timeStamp) {
	// set date range for 5 minutes on either side of the event timestamp
	$dateTime = new DateTime($timeStamp, new DateTimeZone('AST'));
	$startDateTime = $dateTime->modify('-5 minutes')->format('Y-m-d\TH:i:s.v\Z');
//    $startDateTime = '2024-08-01T00:00:00.000Z';

	$dateTime = new DateTime($timeStamp, new DateTimeZone('AST'));
	$endDateTime = $dateTime->modify('+5 minutes')->format('Y-m-d\TH:i:s.v\Z');
//    $endDateTime = '2024-08-22T00:00:00.000Z';

//    echo_spaces("start date", $startDateTime);
//    echo_spaces("end date", $endDateTime, 1);

	$url = "https://platform.ringcentral.com/restapi/v1.0/account/~/audit-trail/search";

	$params = [
		'eventTimeFrom' => $startDateTime,
		'eventTimeTo' => $endDateTime,
		'page' => 1,
		'perPage' => 100,
		'includeAdmins' => True,
		'includeHidden' => True,
		// only bring back these types of events from the audit trail
		// there could be a list of different actionIds to bring back
		"actionIds" => ['CHANGE_USER_INFO:USER',],
	];

	$headers = [
		"Authorization: Bearer $accessToken",
		"Content-Type: application/json",
	];

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$response = curl_exec($ch);
	curl_close($ch);

	$data = json_decode($response, true);

	//echo_spaces("Audit data object", $data);
	$audit_data = array();

	foreach ($data['records'] as $key => $value)
		// build an array of events that are applicable to sending to Admins
		$audit_data[$key] = [
			"eventType" => $value['eventType'],
			"eventTime" => $value['eventTime'],
			// "actionId" => $value['actionId'],
			"Element Affected" => $value['details']['parameters'][0]['value'],
			"old value" => $value['details']['parameters'][1]['value'],
			"new value" => $value['details']['parameters'][2]['value'],
			"initiator name" => $value['initiator']['name'],
			"initiator extensionId" => $value['initiator']['extensionId'],
			"initiator extensionNumber" => $value['initiator']['extensionNumber'],
			"target name" => $value['target']['name'],
			"target SW account Id" => $value['target']['objectId'],
			"target extensionNumber" => $value['target']['extensionNumber'],
			// "actionId" => $data['records']['accountID'],
		];
	return $audit_data;
}

function get_admins($accessToken) {
	$endpoint = "https://platform.ringcentral.com/restapi/v1.0/account/~/directory/entries";

	$params = array(
		'showFederated' => 'true',
		'type' => 'User',
		'typeGroup' => '',
		'page' => 1,
		'perPage' => 'all',
		'siteId' => '',
	);

	$endpoint .= '?' . http_build_query($params);

	$ch = curl_init();
	curl_setopt_array($ch, [
		CURLOPT_URL => $endpoint,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPHEADER => [
			'Authorization: Bearer ' . $accessToken,
			'Content-Type: application/json'
		]
	]);

	$response = curl_exec($ch);
	curl_close($ch);

	$data = json_decode($response, true);

//    echo_spaces("data object", $data);
	$admin_data = array();
	$counter2 = 1;
	foreach ($data['records'] as $value) {
		if ($value['type'] == 'User' && $value['status'] == 'Enabled' && $value['phoneNumbers'][0]['phoneNumber']) {
			// if they are enabled user types with a phone number
			$params2 = array(
				'extensionNumber' => $value['extensionNumber'],
			);
			// need to sleep for a while to allow for repeated access to the API without timing out.
			sleep(1);
			try {
				$endpoint2 = "https://platform.ringcentral.com/restapi/v1.0/account/~/extension";
				$endpoint2 .= '?' . http_build_query($params2);

				$ch2 = curl_init();
				curl_setopt_array($ch2, [
					CURLOPT_URL => $endpoint2,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_HTTPHEADER => [
						'Authorization: Bearer ' . $accessToken,
						'Content-Type: application/json'
					]
				]);

				$resp2 = curl_exec($ch2);
				curl_close($ch2);
				$data2 = json_decode($resp2, true);
//                echo_spaces("data 2 object", $data2);

				foreach ($data2['records'] as $value2) {
					if ($value2['permissions']['admin']['enabled']) {
						// if they are admin level
						$admin_data[$counter2] = [
							"first_name" => $value['firstName'],
							"last_name" => $value['lastName'],
							"phone_number" => $value['phoneNumbers'][0]['phoneNumber'],
							"Admin Permission" => $value2['permissions']['admin']['enabled'],
						];
						$counter2++;
//                        echo_spaces("ID", $value['id']);
//                        echo_spaces("first Name", $value['firstName']);
//                        echo_spaces("last Name", $value['lastName']);
//                        echo_spaces("Extension #", $value['extensionNumber']);
//                        echo_spaces("email", $value['email']);
//                        echo_spaces("phone #", $value['phoneNumbers'][0]['phoneNumber']);
//                        echo_spaces("Account ID", $value['account']['id']);
//                        echo_spaces("Admin Permission", $value2['permissions']['admin']['enabled'], 1);
					}

				}
			}
			catch (Exception $e) {
				echo_spaces("Error Message resp2", $e->getMessage());
			}
		}
	}
	return $admin_data;
}

function send_admin_sms($destination_array, $audit_data) {

	$endpoint = 'https://platform.ringcentral.com/restapi/v1.0/account/~/extension/~/sms';

	foreach ($destination_array as $value) {
		$fromNumber = $value['from_number'];
		$toNumber = $value['to_number'];
		$sms_headers = [
			'Authorization: Bearer ' . $value['access'],
			"Content-Type: application/json"
		];
		foreach ($audit_data as $value2) {
			$settingChanged = $value2['Element Affected'];

			$dateTime = new DateTime($value2['eventTime']);
			$dateTime->setTimezone(new DateTimeZone("America/Halifax")); // AST is UTC-4
			$eventTime = $dateTime->format('M j, Y => g:i a');

			$oldValue = $value2['old value'];
			$newValue = $value2['new value'];
			$initiator = $value2['initiator name'];
			$target_name = $value2['target name'];
			$target_SW_account_id = $value2['target SW account Id'];
			$extension_num = $value2['target extensionNumber'];

			$message = "$eventTime: The setting '$settingChanged' for $target_name (Extension #: $extension_num, SW Account #: $target_SW_account_id) was changed from '$oldValue' to '$newValue' by: $initiator";

			$message .= " To opt out at any time, simply reply with STOP.";

			$sms_data = [
				'from' => array('phoneNumber' => $fromNumber),
				'to' => array(array('phoneNumber' => $toNumber)),
				'text' => $message,
			];

			$sms_ch = curl_init();
			curl_setopt($sms_ch, CURLOPT_URL, $endpoint);
			curl_setopt($sms_ch, CURLOPT_POST, true);
			curl_setopt($sms_ch, CURLOPT_POSTFIELDS, json_encode($sms_data));
			curl_setopt($sms_ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($sms_ch, CURLOPT_HTTPHEADER, $sms_headers);

			$sms_response = curl_exec($sms_ch);
			curl_close($sms_ch);
		}

	}
}

function send_team_message($destination_array, $audit_data) {

	foreach ($destination_array as $value) {
		$chatId = $value['tm_chat_id'];
		$endpoint = "https://platform.ringcentral.com/team-messaging/v1/chats/$chatId/posts";

		foreach ($audit_data as $value2) {
			$settingChanged = $value2['Element Affected'];

			$dateTime = new DateTime($value2['eventTime']);
			$dateTime->setTimezone(new DateTimeZone("America/Halifax")); // AST is UTC-4
			$eventTime = $dateTime->format('M j, Y => g:i a');

			$oldValue = $value2['old value'];
			$newValue = $value2['new value'];
			$initiator = $value2['initiator name'];
			$target_name = $value2['target name'];
			$target_SW_account_id = $value2['target SW account Id'];
			$extension_num = $value2['target extensionNumber'];

			$message = "$eventTime: The setting '$settingChanged' for $target_name (Extension #: $extension_num, SW Account #: $target_SW_account_id) was changed from '$oldValue' to '$newValue' by: $initiator";

			$data = [
				'text' => $message,
			];

			$headers = [
				'Authorization: Bearer ' . $value['access'],
				'Content-Type: application/json',
			];

			$ch = curl_init($endpoint);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$response = curl_exec($ch);
			curl_close($ch);
		}
	}

}

function refresh_tokens($refreshToken, $client_id, $client_secret) {
	$endpoint = 'https://platform.ringcentral.com/restapi/oauth/token';

	$refresh_params = [
		'grant_type' => 'refresh_token',
		'refresh_token' => $refreshToken
	];

	$refresh_headers = [
		'Authorization: Basic ' . base64_encode($client_id . ':' . $client_secret),
		'Content-Type: application/x-www-form-urlencoded'
	];

	$new_ch = curl_init();
	curl_setopt($new_ch, CURLOPT_URL, $endpoint);
	curl_setopt($new_ch, CURLOPT_POST, true);
	curl_setopt($new_ch, CURLOPT_POSTFIELDS, http_build_query($refresh_params));
	curl_setopt($new_ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($new_ch, CURLOPT_HTTPHEADER, $refresh_headers);

	$new_response = curl_exec($new_ch);
	// Display response
    // echo_spaces("Response", $new_response);

	curl_close($new_ch);

	$refresh_data = json_decode($new_response, true);

	$tokens = array();
	$tokens['accessToken'] = $refresh_data['access_token'];
	$tokens['refreshToken'] = $refresh_data['refresh_token'];

	return $tokens;
}

function get_new_access_token($refreshToken) {
	// only used in testing code. New access tokens are generated
	// in the CRON every 30 minutes

	$dotenv = Dotenv\Dotenv::createImmutable(__DIR__)->load();

	$client_id = $_ENV['RC_APP_CLIENT_ID'];
	$client_secret = $_ENV['RC_APP_CLIENT_SECRET'];
	$url = 'https://platform.ringcentral.com/restapi/oauth/token';

	$refresh_params = [
		'grant_type' => 'refresh_token',
		'refresh_token' => $refreshToken,
	];
	$refresh_headers = [
		'Authorization: Basic ' . base64_encode($client_id . ':' . $client_secret),
		'Content-Type: application/x-www-form-urlencoded'
	];
	$new_ch = curl_init();
	curl_setopt($new_ch, CURLOPT_URL, $url);
	curl_setopt($new_ch, CURLOPT_POST, true);
	curl_setopt($new_ch, CURLOPT_POSTFIELDS, http_build_query($refresh_params));
	curl_setopt($new_ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($new_ch, CURLOPT_HTTPHEADER, $refresh_headers);

	$new_response = curl_exec($new_ch);
	curl_close($new_ch);
	$new_data = json_decode($new_response, true);
	$newAccessToken = $new_data['access_token'];
	$newRefreshToken = $new_data['refresh_token'];
	// save back to DB
	$table = "clients";
	$where_info = array("account", "3058829020");
	$fields_data = $fields_data = array(
		"access" => $newAccessToken,
		"refresh" => $newRefreshToken,
	);

	db_record_update($table, $fields_data, $where_info);

//    echo_spaces("new access token", $newAccessToken, 2);

	return $newAccessToken;
}

function is_admin($accessToken, $accountId, $extensionId) {
	$admin = false;
	try {
		$endpoint = "https://platform.ringcentral.com/restapi/v1.0/account/$accountId/extension/$extensionId";

		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => $endpoint,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer ' . $accessToken,
				'Content-Type: application/json'
			]
		]);

		$resp = curl_exec($ch);
		curl_close($ch);
		$data = json_decode($resp, true);

		if ($data['permissions']['admin']['enabled']) {
			$admin = true;
		}

	}
	catch (Exception $e) {
		echo_spaces("Error Message resp", $e->getMessage());
	}

	return $admin;
}

/* ============================================= */
/* Get list of Team Messaging Group Chats (Teams) */
/* ============================================= */
function list_tm_teams($accessToken) {

	$endpoint = "https://platform.ringcentral.com/team-messaging/v1/chats";

	$params = [
		'type' => 'Team',
//        'type' => array( 'Everyone', 'Group', 'Personal', 'Direct', 'Team' ),
		'recordCount' => 30,
	];

	$endpoint .= '?' . http_build_query($params);

	$headers = [
		'Authorization: Bearer ' . $accessToken,
		"Content-Type: application/json",
	];

	$tm_ch = curl_init($endpoint);
	curl_setopt($tm_ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($tm_ch, CURLOPT_HTTPHEADER, $headers);

	$resp = curl_exec($tm_ch);
	$data = json_decode($resp, true);
	// Close the cURL session
	curl_close($tm_ch);
//	echo_spaces("team chats", $data);

	$groupChats = array();
	if ($data['errors']) {
		$groupChats = "";
	}
	else {
		$groupChats = $data;
	}
	return $groupChats;
}
function getTMChatName($chat_id, $accessToken) {

	$endpoint = "https://platform.ringcentral.com/team-messaging/v1/chats";

	$params = [
		'type' => 'Team',
//        'type' => array( 'Everyone', 'Group', 'Personal', 'Direct', 'Team' ),
		'recordCount' => 30,
	];

	$endpoint .= '?' . http_build_query($params);

	$headers = [
		'Authorization: Bearer ' . $accessToken,
		"Content-Type: application/json",
	];

	$tm_ch = curl_init($endpoint);
	curl_setopt($tm_ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($tm_ch, CURLOPT_HTTPHEADER, $headers);

	$resp = curl_exec($tm_ch);
	$data = json_decode($resp, true);
	// Close the cURL session
	curl_close($tm_ch);

	foreach ($data['records'] as $value) {
		if ($value['id'] == $chat_id) {
			$chat_name = $value['name'];
		}
	}
	return $chat_name;
}

function list_extension_sms_enabled_numbers($accessToken, $accountId, $extensionId) {
	$endpoint = "https://platform.ringcentral.com/restapi/v1.0/account/$accountId/extension/$extensionId/phone-number";

	$headers = [
		'Authorization: Bearer ' . $accessToken,
		"Content-Type: application/json",
	];

	$sms_ch = curl_init($endpoint);
	curl_setopt($sms_ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($sms_ch, CURLOPT_HTTPHEADER, $headers);

	$resp = curl_exec($sms_ch);
	$data = json_decode($resp, true);
	// Close the cURL session
	curl_close($sms_ch);
	$numbers = array();
	foreach ($data['records'] as $key => $record) {
		foreach ($record['features'] as $acctType) {
			if ($acctType == 'SmsSender') {
				$numbers[$key]["phoneNumber"] = $record['phoneNumber'];
			}
		}
	}
	return $numbers;
}

function send_stop_sms($fromNumber, $toNumber) {

	$table = "clients";
	$columns_data = array("client_id", "access");
	$where_info = array("from_number", $fromNumber, "to_number", $toNumber,);
	$condition = "AND";
	$db_result = db_record_select($table, $columns_data, $where_info, $condition);

	$clientId = $db_result[0]['client_id'];
	$accessToken = $db_result[0]['access'];

	$endpoint = 'https://platform.ringcentral.com/restapi/v1.0/account/~/extension/~/sms';

	$sms_headers = [
		'Authorization: Bearer ' . $accessToken,
		"Content-Type: application/json"
	];

	$message = "You have been removed from the SMS distribution list";

	$sms_data = [
		'from' => array('phoneNumber' => $fromNumber),
		'to' => array(array('phoneNumber' => $toNumber)),
		'text' => $message,
	];

	$sms_ch = curl_init();
	curl_setopt($sms_ch, CURLOPT_URL, $endpoint);
	curl_setopt($sms_ch, CURLOPT_POST, true);
	curl_setopt($sms_ch, CURLOPT_POSTFIELDS, json_encode($sms_data));
	curl_setopt($sms_ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($sms_ch, CURLOPT_HTTPHEADER, $sms_headers);

	$sms_response = curl_exec($sms_ch);
	curl_close($sms_ch);

	return $clientId ;
}

function kill_sms_webhook($client_id) {

	$table = "clients";
	$columns_data = array("access", "sms_webhook");
	$where_info = array("client_id", $client_id,);
	$db_result = db_record_select($table, $columns_data, $where_info);

	$accessToken = $db_result[0]['access'];
	$subscription_id = $db_result[0]['sms_webhook'];

	$endpoint = "https://platform.ringcentral.com/restapi/v1.0/subscription/$subscription_id";
	$subscription_del_ch = curl_init();

	// Set cURL options
	curl_setopt_array($subscription_del_ch, [
		CURLOPT_URL => $endpoint,
		CURLOPT_CUSTOMREQUEST => "DELETE",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPHEADER => [
			"Authorization: Bearer $accessToken",
			"Accept: application/json"
		],
	]);

	$subscription_del_response = curl_exec($subscription_del_ch);
	curl_close($subscription_del_ch);

	// remove sms_webhook id from DB
	$fields_data = array(
		"sms_webhook" => "",
	);
	db_record_update($table, $fields_data, $where_info);

}

function send_basic_sms($accessToken = "", $message = "Testing SMS sending") {

	$endpoint = 'https://platform.ringcentral.com/restapi/v1.0/account/~/extension/~/sms';

	$fromNumber = '+19029154774';
	$sms_data = [
		'from' => array('phoneNumber' => $fromNumber),
		'to' => array(array('phoneNumber' => '+19029405827')),
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
	curl_close($sms_ch);
}
function get_call_log () {
	$response = $platform->get('/restapi/v1.0/account/~/extension/~/call-log', [
		'view' => 'Simple',
		'type' => 'Voice',
		'direction' => 'Inbound',
		'perPage' => 100
	]);

	$callLog = $response->json();
	foreach ($callLog['records'] as $call) {
		if ($call['result'] === 'Missed') {
			$missedCalls[] = $call;
		}
	}
//	send the SMS
	foreach ($missedCalls as $call) {
		$callerNumber = $call['from']['phoneNumber'];
		$message = "Sorry we missed your call. We'll get back to you shortly.";

		$platform->post('/restapi/v1.0/account/~/extension/~/sms', [
			'from' => ['phoneNumber' => 'YourRingCentralNumber'],
			'to' => [['phoneNumber' => $callerNumber]],
			'text' => $message
		]);
	}
}
