<?php
/**
 * Copyright (C) 2019-2024 Paladin Business Solutions
 *
 */

require_once('includes/ringcentral-functions.inc');
require_once('includes/ringcentral-php-functions.inc');
require_once('includes/ringcentral-curl-functions.inc');
require_once('includes/ringcentral-db-functions.inc');

show_errors();

/* get the access token */
$table = "clients";
$columns_data = array("access", "refresh");
$where_info = array("account", "3058829020");
$db_result = db_record_select($table, $columns_data, $where_info);
$accessToken = $db_result[0]['access'];
$refreshToken = $db_result[0]['refresh'];
//echo_spaces("access token", $accessToken);
//echo_spaces("refresh token", $refreshToken);

$endpoint_url = "https://platform.ringcentral.com/restapi/v1.0/subscription";

$subscription_ch = curl_init();

// Set cURL options
curl_setopt_array($subscription_ch, [
    CURLOPT_URL => $endpoint_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $accessToken",
        "Accept: application/json"
    ],
]);

$subscription_response = curl_exec($subscription_ch);
curl_close($subscription_ch);
$subscriptions = json_decode($subscription_response, true);

//echo_spaces("Subscription response", $subscriptions);

if ($subscriptions['errorCode'] == "TokenInvalid") {
    echo_spaces("New access token needed");
    $accessToken = get_new_access_token($refreshToken);

    $subscription_ch = curl_init();

// Set cURL options
    curl_setopt_array($subscription_ch, [
        CURLOPT_URL => $endpoint_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $accessToken",
            "Accept: application/json"
        ],
    ]);

    $subscription_response = curl_exec($subscription_ch);
    curl_close($subscription_ch);
    $subscriptions = json_decode($subscription_response, true);
}

//echo_spaces("Listing subscriptions", $subscriptions);

foreach ($subscriptions['records'] as $subscription) {
    // echo_spaces("Individual Subscription array", $subscription);
    echo_spaces("Subscription ID", $subscription['id']);
    echo_spaces("Creation Time", $subscription['creationTime']);
    // do a for each next line if needed.
    foreach ($subscription['eventFilters'] as $key => $filter) {
        echo_spaces("Event Filter URI $key", $subscription['eventFilters'][$key]);
    }
    echo_spaces("Webhook URI", $subscription['deliveryMode']['address']);
    echo_spaces("Webhook transport type", $subscription['deliveryMode']['transportType'], 2);

}


//$table = "ringcentral_control";
//$columns_data = "*";
//$where_info = array("ringcentral_control_id", 1);
//$db_result = db_record_select($table, $columns_data, $where_info);
//
//echo_spaces("testing output", $db_result);
//
//echo_spaces("from #", $db_result[0]['from_number']) ;




