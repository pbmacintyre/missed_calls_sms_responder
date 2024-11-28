<?php

ob_start();
session_start();

require_once('includes/ringcentral-php-functions.inc');

if (isset($_GET['code'])) {

	require('includes/vendor/autoload.php');
	$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/includes")->load();

	$client_id = $_ENV['RC_APP_CLIENT_ID'];
	$client_secret = $_ENV['RC_APP_CLIENT_SECRET'];

	$auth_code = htmlentities(strip_tags($_GET['code']));
	$redirect_uri = $_ENV['RC_REDIRECT_URL'];
	$endpoint = 'https://platform.ringcentral.com/restapi/oauth/token';

	$params = [
		'grant_type' => 'authorization_code',
		'code' => $auth_code,
		'redirect_uri' => $redirect_uri,
	];

	$headers = [
		'Authorization: Basic ' . base64_encode($client_id . ':' . $client_secret),
		'Content-Type: application/x-www-form-urlencoded'
	];

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $endpoint);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	$response = curl_exec($ch);
	curl_close($ch);

	$data = json_decode($response, true);

	//echo_spaces("authorization data", $data);

	$accessToken = $data['access_token'];
	$refreshToken = $data['refresh_token'];

//	echo_spaces("access token", $accessToken);

	header("Location: call_log.php?access_token=$accessToken");

} else {
	header("Location: index.php?auth=X");
}

ob_end_flush();
