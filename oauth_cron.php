<?php

////run every 30 minutes
//$minute = (int)date('i');
//if ($minute % 30 !== 0) {
//    exit(); // Exit if it's not the 0th or 30th minute of the hour
//}

require_once(__DIR__ . '/includes/ringcentral-php-functions.inc');
require_once(__DIR__ . '/includes/ringcentral-db-functions.inc');
require_once(__DIR__ . '/includes/ringcentral-functions.inc');
require_once(__DIR__ . '/includes/ringcentral-curl-functions.inc');

show_errors();

require(__DIR__ . '/includes/vendor/autoload.php');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/includes")->load();

$client_id = $_ENV['RC_APP_CLIENT_ID'];
$client_secret = $_ENV['RC_APP_CLIENT_SECRET'];

$table = "clients";
$columns_data = array("*");
$db_result = db_record_select($table, $columns_data);

foreach ($db_result as $row) {
    $tokens = refresh_tokens($row['refresh'], $client_id, $client_secret);

//	echo_spaces("Tokens", $tokens);

	// save newly created client information
    $table = "clients";
    $where_info = array ("account", $row['account']);
    $fields_data = $fields_data = array(
        "access" => $tokens['accessToken'],
        "refresh" => $tokens['refreshToken'],
    );
    db_record_update($table, $fields_data, $where_info);
}

//$message = "CRON runs every 30 minutes";
echo_spaces("CRON code finished running");
//send_basic_sms ($tokens['accessToken'], $message);
