<?php
/**
 * Copyright (C) 2019-2024 Paladin Business Solutions
 *
 */

require_once('includes/ringcentral-functions.inc');
require_once('includes/ringcentral-db-functions.inc');
require_once('includes/ringcentral-curl-functions.inc');
require_once('includes/ringcentral-php-functions.inc');

$endpoint = "https://platform.ringcentral.com/restapi/v1.0/subscription";

/* get the access & refresh tokens for all records */
$table = "clients";
$columns_data = array("access", "refresh");
$db_result = db_record_select($table, $columns_data);
if (!$db_result) {
    echo_spaces("No DB records to get webhooks with");
} else {
    foreach ($db_result as $row) {
        // need to get all subscriptions for all account records
        $accessToken = $row['access'];
        $refreshToken = $row['refresh'];

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

        if ($subscriptions['errorCode'] == "TokenInvalid") {
            echo_spaces("New access token needed");
            $accessToken = get_new_access_token($refreshToken);

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
        }

//        echo_spaces("Full Subscription", $subscriptions['records']);
        if (empty($subscriptions['records'])) {
            echo_spaces("No subscriptions found");
        }
        foreach ($subscriptions['records'] as $subscription) {
            $subscription_id = $subscription['id'];
            echo_spaces("Subscription ID", $subscription_id);

            $dateTime = new DateTime($subscription['creationTime'], new DateTimeZone('UTC'));
            $dateTime->setTimezone(new DateTimeZone("America/Halifax")); // AST is UTC-4

            echo_spaces("Creation Time => " . $dateTime->format('M j, Y => g:i a'));
            // do a for each next line if needed.
            foreach ($subscription['eventFilters'] as $key => $filter) {
                echo_spaces("Event Filter URI $key", $subscription['eventFilters'][$key]);
            }
            echo_spaces("Webhook URI", $subscription['deliveryMode']['address']);
            echo_spaces("Webhook transport type", $subscription['deliveryMode']['transportType'], 2);

            if ($subscription_id == "f55baaa8-42f7-469a-a406-cc2529580b65") {
                $endpoint_del_url = "https://platform.ringcentral.com/restapi/v1.0/subscription/$subscription_id";
                $subscription_del_ch = curl_init();

                // Set cURL options
                curl_setopt_array($subscription_del_ch, [
                    CURLOPT_URL => $endpoint_del_url,
                    CURLOPT_CUSTOMREQUEST => "DELETE",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => [
                        "Authorization: Bearer $accessToken",
                        "Accept: application/json"
                    ],
                ]);

                $subscription_del_response = curl_exec($subscription_del_ch);
                curl_close($subscription_del_ch);
                echo_spaces("Subscription ID Deleted", $subscription_id, 2);
             } // comment here for deleting all
        }

    }
}

