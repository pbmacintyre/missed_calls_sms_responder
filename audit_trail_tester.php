<?php

require_once('includes/ringcentral-functions.inc');
require_once('includes/ringcentral-php-functions.inc');
require_once('includes/ringcentral-db-functions.inc');

//show_errors();

$controller = ringcentral_sdk();
$platform = $controller['platform'];
$sdk = $controller['SDK'];

$endpoint = "/restapi/v1.0/account/~/audit-trail/search";
$dateTime = new DateTime('now', new DateTimeZone('AST'));
$startDateTime = $dateTime->modify('-15 minutes')->format('Y-m-d\TH:i:s.v\Z');

$dateTime = new DateTime('now', new DateTimeZone('AST'));
$endDateTime = $dateTime->format('Y-m-d\TH:i:s.v\Z');

$params = array(
//                'eventTimeFrom' => $startDateTime,
    'eventTimeFrom' => '2024-08-01T00:00:00.000Z',
//                'eventTimeTo' => $endDateTime,
    'eventTimeTo' => '2024-08-22T00:00:00.000Z',
    'includeAdmins' => True,
    'includeHidden' => True,
);

callPostRequest($platform, $endpoint, $params);


//$table = "ringcentral_control";
//$columns_data = "*";
//$where_info = array("ringcentral_control_id", 1);
//$db_result = db_record_select($table, $columns_data, $where_info);
//
//echo_spaces("testing output", $db_result);
//
//echo_spaces("from #", $db_result[0]['from_number']) ;




