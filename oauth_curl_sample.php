<?php
ob_start();
session_start();

require_once('includes/ringcentral-functions.inc');
require_once('includes/ringcentral-php-functions.inc');
show_errors();

$client_id      = '24pu9Cwlu1fcAtmSh5osBv';
$client_secret  = 'Z3FNye3kt3kc6Ek6cj1FsF7Cpu4EJHRfhdXt0hz571Jg';

echo_spaces("Client ID", $client_id);
echo_spaces("Secret", $client_secret,1);
echo_spaces("Initial Session", $_SESSION,1);

if (!$_SESSION['loop_count']) {
    $_SESSION['loop_count'] = 1 ;
}

if (isset($_GET['code'])) {

    $redirect_uri = 'https://paladin-bs.com/craig_chan_project/oauth_authorizer.php';
    $url = 'https://platform.ringcentral.com/restapi/oauth/token';

    $params = [
        'grant_type' => 'authorization_code',
        'code' => $_GET['code'],
        'redirect_uri' => $redirect_uri,
    ];

    $headers = [
        'Authorization: Basic ' . base64_encode($client_id . ':' . $client_secret),
        'Content-Type: application/x-www-form-urlencoded'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    echo_spaces("access token", $data['access_token'],2);
    echo_spaces("refresh token", $data['refresh_token'],2);

    if ($_SESSION['loop_count'] == 1) {
        // if this is the first time thru the code, save the refresh token to the session
        $_SESSION['refresh_token'] = $data['refresh_token'];
        // and increase the loop counter
        $_SESSION['loop_count'] = 2;
    } else {
        $_SESSION['loop_count'] = 1;
    }

    echo_spaces("data object", $data);

    $accessToken = $data['access_token'];
    $message = "This is a test SMS from the Craig Chan app original access token";

    if (!$accessToken) {
        // second time thru code
        echo_spaces("Session", $_SESSION, 2);
        echo_spaces("Refresh token with no access token", $_SESSION['refresh_token'], 2);
        // refresh the token
        $refresh_params = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $_SESSION['refresh_token']
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
        $refresh_data = json_decode($new_response, true);
        $accessToken = $refresh_data['access_token'];
        echo_spaces("new access token", $accessToken, 2);
        echo_spaces("new data object", $refresh_data, 2);
        $message = "This is a test SMS from the Craig Chan app with refresh token";
    }

    $api_url = 'https://platform.ringcentral.com/restapi/v1.0/account/~/extension/~/sms';

    $sms_headers = [
        'Authorization: Bearer ' . $accessToken,
        "Content-Type: application/json"
    ];

    $sms_data = [
        'from' => array('phoneNumber' => '+16502950182'),  // my account phone #
        'to' => array(array('phoneNumber' => '+19029405827')),
        'text' => $message,
    ];

    $sms_ch = curl_init();
    curl_setopt($sms_ch, CURLOPT_URL, $api_url);
    curl_setopt($sms_ch, CURLOPT_POST, true);
    curl_setopt($sms_ch, CURLOPT_POSTFIELDS, json_encode($sms_data));
    curl_setopt($sms_ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($sms_ch, CURLOPT_HTTPHEADER, $sms_headers);

    $sms_response = curl_exec($sms_ch);
    // Check if there were any errors with the request
    if (curl_errno($sms_ch)) {
        echo 'Error:' . curl_error($sms_ch);
    } else {
        // Print the API response
        echo_spaces("SMS response object", $sms_response, 2);
    }
    curl_close($sms_ch);

}
