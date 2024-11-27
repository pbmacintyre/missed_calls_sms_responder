<?php

/**
 * Copyright (C) 2019-2024 Paladin Business Solutions
 *
 */

// runs when a RingCentral admin event is triggered...

require_once('includes/ringcentral-functions.inc');
require_once('includes/ringcentral-php-functions.inc');
require_once('includes/ringcentral-curl-functions.inc');
require_once('includes/ringcentral-db-functions.inc');

require('includes/vendor/autoload.php');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/includes")->load();

//show_errors();

$client_id = $_ENV['RC_APP_CLIENT_ID'];
$client_secret = $_ENV['RC_APP_CLIENT_SECRET'];

$hvt = isset($_SERVER['HTTP_VALIDATION_TOKEN']) ? $_SERVER['HTTP_VALIDATION_TOKEN'] : '';
if (strlen($hvt) > 0) {
    header("Validation-Token: {$hvt}");
}

$incoming = file_get_contents("php://input");

// use following to send incoming event data to a file for visual review
file_put_contents("received_EVENT_payload.log", $incoming);

if (empty($incoming)) {
    http_response_code(200);
    echo json_encode(array('responseType' => 'error', 'responseDescription' => 'No data provided Check SMS payload.'));
    exit();
}

$incoming_data = json_decode($incoming);

if (!$incoming_data) {
    http_response_code(200);
    echo json_encode(array('responseType' => 'error', 'responseDescription' => 'Media type not supported.  Please use JSON.'));
    exit();
}

//echo_spaces("incoming payload account #", $incoming_data->body->contacts['0']->account->id);

$accountId = $incoming_data->body->contacts['0']->account->id;
$timeStamp = $incoming_data->timestamp;

// with the account id
// [1] get records from the DB related to the triggering account in the event payload
// [2] get the audit trail information
// [3] send events from last 15 minutes to returned DB records
// [4] post the same events to a TM group as designated by DB record

/* === [1] get records from the DB related to the triggering account in the event payload */
$table = "clients";
$columns_data = array("*");
$where_info = array("account", $accountId);
$db_result = db_record_select($table, $columns_data, $where_info);
// all records connected to the triggering account

//echo_spaces("DB result", $db_result);
$destination_array = array();

foreach ($db_result as $key => $row) {
    // build destination array
    $destination_array[$key] = [
        "access" => $row['access'],
        "extension" => $row['extension_id'],
        "from_number" => $row['from_number'],
        "to_number" => $row['to_number'],
        "tm_chat_id" => $row['team_chat_id'],
    ];

    /* === [2] get the audit trail information from 5 minutes either side of the event date stamp  === */
    $audit_data = get_audit_data($row['access'], $timeStamp);
}

//echo_spaces("destination array", $destination_array);
//echo_spaces("audit array", $audit_data);

foreach ($destination_array as $value) {
    if ($value['from_number'] !== "" && $value['to_number'] !== "") {
        // [3] send event data to admins via SMS
        send_admin_sms($destination_array, $audit_data);
    }
    if ($value['tm_chat_id'] !== "" ) {
        // [4] post the event to a TM group
        send_team_message($destination_array, $audit_data);
    }
}

