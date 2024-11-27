<?php
/** Copyright (C) 2019-2024 Paladin Business Solutions */

/* ================= */
/* Generic functions */
/* ================= */

//use RingCentral\SDK\Http\HttpException;
//use RingCentral\SDK\Http\ApiResponse;
//use RingCentral\SDK\SDK;

function prod_test() {
	return 0;  // 1 for production & 0 for test environment
}

function app_name() {
	echo "Missed Calls SMS App";
	return "Missed Calls SMS App";
}

function app_version() {
	echo_plain_text("Version 0.1", "grey", "small");
}

/* =================== */
/* Build ReCaptcha top */
/* =================== */
function ringcentral_build_recaptcha_v3_top() {

	require(__DIR__ . '/vendor/autoload.php');
	$dotenv = Dotenv\Dotenv::createImmutable('includes')->load();

	$site_key = $_ENV['GRC_SITE_KEY'];

	echo "<script src='https://www.google.com/recaptcha/api.js?render=$site_key'></script>";
}

/* ========================= */
/* Verify ReCaptcha response */
/* ========================= */
function ringcentral_verify_v3_recaptcha($action) {
	require(__DIR__ . '/vendor/autoload.php');
	$dotenv = Dotenv\Dotenv::createImmutable('includes')->load();

	$secret_key = $_ENV['GRC_SECRET_KEY'];

	$token = $_POST['g-recaptcha-response'];
	$ip = $_SERVER['REMOTE_ADDR'];

	$url = 'https://www.google.com/recaptcha/api/siteverify';
	$data = array(
		'secret' => $secret_key,
		'response' => $token,
		'remoteip' => $ip
	);

	$options = array(
		'http' => array(
			'method' => 'POST',
			'content' => http_build_query($data)
		)
	);

	$context = stream_context_create($options);
	$response = file_get_contents($url, false, $context);
	$reCaptchaResult = json_decode($response);

// Check if reCAPTCHA is successful
	if ($reCaptchaResult->success) {
		// reCAPTCHA validation succeeded
		echo "reCAPTCHA verification passed!";
		// Further processing goes here
	} else {
		// reCAPTCHA validation failed
		echo "reCAPTCHA verification failed!";
		// Redirect or display an error message as needed
	}

}

function ringcentral_build_help($field) {
	$image_source = 'images/question_mark.png';

	$table = "ringcentral_help";
	$columns_data = array("ringcentral_help_help");
	$where_info = array("ringcentral_help_field", $field);
	$db_result = db_record_select($table, $columns_data, $where_info);

	$help_text = $db_result[0]['ringcentral_help_help'];

	return "<img src='$image_source' title='" . $help_text . "' />";
}

function ringcentral_create_admin_webhook_subscription($accountId, $accessToken) {
	// check to see if the account passed in already has an admin webhook

	// see if there are any subscriptions for this access token
	$endpoint = 'https://platform.ringcentral.com/restapi/v1.0/subscription';

	$subscription_ch = curl_init();
	// Set cURL options
	curl_setopt_array($subscription_ch, [
		CURLOPT_URL => $endpoint,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPHEADER => [
			"Authorization: Bearer $accessToken",
			"Accept: application/json"
		],
	]);

	$subscription_response = curl_exec($subscription_ch);
	curl_close($subscription_ch);
	$subscriptions = json_decode($subscription_response, true);

//    echo_spaces("Full Subscription", $subscriptions['records']);

	$admin_webhook_exists = false;

	if (empty($subscriptions['records'])) {
		// no subscriptions at all
	} else {
		$needle = "directory/entries";
		// go through each subscription to see if it is for directory/entries event
		foreach ($subscriptions['records'] as $subscription) {
			foreach ($subscription['eventFilters'] as $value) {
				$haystack = substr($value, -17);
				if ($haystack === $needle) {
					$admin_webhook_exists = true;
					$webhook_id = $subscription['id'];
				}
			}
		}
	}

	if ($admin_webhook_exists == false) {
		// no webhook, so create a new admin webhook and pass id back
		require('includes/vendor/autoload.php');
		$dotenv = Dotenv\Dotenv::createImmutable(__DIR__)->load();

		$webhook_url = $_ENV['RC_ADMIN_WEBHOOK_LISTENING_ADDRESS'];

		// Create the data array (example for subscribing to message events)
		$data = [
			"eventFilters" => [
				// listening for some admin edits
				"/restapi/v1.0/account/$accountId/directory/entries",
				// TODO: need to get full lis of events to listen for from here:
				// https://developers.ringcentral.com/guide/notifications/event-filters
				// "/restapi/v1.0/account/~/extension/~/message-store",
			],
			"deliveryMode" => [
				"transportType" => "WebHook",
				"address" => $webhook_url // WEBHOOK_LISTENING_ADDRESS URL
			],
		];
		$ch = curl_init($endpoint);

		// Set the cURL options
		curl_setopt($ch, CURLOPT_POST, true); // Set the request method to POST
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Authorization: Bearer ' . $accessToken, // Set the Authorization header with the access token
			'Content-Type: application/json', // Specify the content type as JSON
			'accept: application/json',
		]);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); // Attach the data as JSON

		// Execute the cURL request and get the response
		$response = curl_exec($ch);
		$responseData = json_decode($response, true);
		$result = $responseData['id'];
	} else {
		$result = $webhook_id;
	}
	return $result;
}

function ringcentral_create_sms_webhook_subscription($accountId, $extensionId, $accessToken) {
	// create watching subscription webhook for audit events
	require('includes/vendor/autoload.php');
	$dotenv = Dotenv\Dotenv::createImmutable(__DIR__)->load();

	$webhook_url = $_ENV['RC_SMS_WEBHOOK_LISTENING_ADDRESS'];

	$endpoint = 'https://platform.ringcentral.com/restapi/v1.0/subscription';

	// Create the data array (example for subscribing to message events)
	$data = [
		"eventFilters" => [
			// listening for SMS opt-outs
			"/restapi/v1.0/account/$accountId/extension/$extensionId/message-store/instant?type=SMS",
		],
		"expiresIn" => "315360000",
		"deliveryMode" => [
			"transportType" => "WebHook",
			"address" => $webhook_url // WEBHOOK_LISTENING_ADDRESS URL
		],
	];
	$ch = curl_init($endpoint);

	// Set the cURL options
	curl_setopt($ch, CURLOPT_POST, true); // Set the request method to POST
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Authorization: Bearer ' . $accessToken, // Set the Authorization header with the access token
		'Content-Type: application/json', // Specify the content type as JSON
		'accept: application/json',
	]);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); // Attach the data as JSON

	// Execute the cURL request and get the response
	$response = curl_exec($ch);
	$responseData = json_decode($response, true);

	return $responseData['id'];
}

function callPostRequest($platform, $endpoint, $params = null) {
//    echo_spaces("SDK object", $sdk);
	echo_spaces("Session", $_SESSION);
	$i = 1;
	try {
		$resp = $platform->post($endpoint, $params);
		foreach ($resp->json()->records as $value) {
			//if ($value->eventType == "UPDATE") {
			echo_spaces("Counter", $i++);
			echo_spaces("Event Time", date("F j, Y, g:i a", strtotime($value->eventTime)));
			echo_spaces("Event Type", $value->eventType);
			echo_spaces("Action", $value->actionId);
			echo_spaces("Account Id", $value->accountId);
			echo_spaces("Initiator Id", $value->initiator->extensionId);
			echo_spaces("Initiator Name", $value->initiator->name);
			echo_spaces("Initiator Role", $value->initiator->role);

			echo_spaces("Element affected", $value->details->parameters[0]->value);
			echo_spaces("Old value", $value->details->parameters[1]->value);
			echo_spaces("New value", $value->details->parameters[2]->value, 2);
//            }
		}
		echo_spaces("Object", $resp->json());
	} catch (\RingCentral\SDK\Http\ApiException $e) {
		print 'Expected HTTP Error: ' . $e->getMessage() . PHP_EOL;
	}
}

// helper function for calling API and printing results
function callGetRequest($endpoint, $params) {
	global $platform;
	try {
		$resp = $platform->get($endpoint, $params);
		header("Content-type: text/plain");
		echo $resp->text();
	} catch (\RingCentral\SDK\Http\ApiException $e) {
		print 'Expected HTTP Error: ' . $e->getMessage() . PHP_EOL;
	}
}

function callUserRollRequest($endpoint, $params) {
	global $platform;
	try {
		$resp = $platform->get($endpoint, $params);

		$i = 1;
		foreach ($resp->json()->records as $value) {
//            echo_spaces("admins", $value->displayName);
//            if ($value->description) { echo_spaces("desc", $value->description); }
			if (stripos($value->displayName, "admin") == true) {
				// build output
				$outstring = $value->displayName;
				if ($value->description) {
					$outstring .= " - " . $value->description;
				}
				echo_spaces($i++ . " Admin User Role", $outstring);
			}
		}
//        echo_spaces("result", $resp->json()->records);

	} catch (\RingCentral\SDK\Http\ApiException $e) {
		print 'Expected HTTP Error: ' . $e->getMessage() . PHP_EOL;
	}
}

function base64url_encode($plainText) {
	$base64 = base64_encode($plainText);
	$base64 = trim($base64, "=");
	$base64url = strtr($base64, '+/', '-_');
	return ($base64url);
}

/* =============================================== */
/* Post text to Team Messaging Group Chats (Teams) */
/* =============================================== */
function post_tm_chat($rcsdk, $chatId, $team_chat) {

	$platform = $rcsdk->platform();

	$body = array(
		'text' => $team_chat,
		// use options below if you are ever going to send attachments to the chat as well.
		// 'attachments' => array(
		//	array(
		//		'id' => '<ENTER VALUE>',
		//		'type' => '<ENTER VALUE>'
		//	)
	);

	try {
		$platform->post("/restapi/v1.0/glip/chats/{$chatId}/posts", $body);
		$result = true;
	} catch (\RingCentral\SDK\Http\ApiException $e) {
		// echo "<br/><br/>Failed to add text to selected Team chat -><br/>" . $e ;
		$result = false;
	}
	return $result;
}
function check_call_log($client_id, $client_secret) {
	$access_token_url = "https://platform.ringcentral.com/restapi/oauth/token";

	$data = [
		'grant_type' => 'password',
		'username' => '15075137054*101',
//		'extension' => '101', // Optional
		'password' => 'bRosW!_u2hl@Uh'
	];
	$headers = [
		'Authorization: Basic ' . base64_encode("$client_id:$client_secret"),
		'Content-Type: application/x-www-form-urlencoded'
	];
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $access_token_url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	if ($httpCode === 200) {
		$tokenData = json_decode($response, true);
		echo_spaces("access token", $tokenData['access_token']);
	} else {
		echo "Error: " . $response . "\n";
	}


	// $url = "https://platform.ringcentral.com/restapi/v1.0/account/~/extension/~/call-log";

}



