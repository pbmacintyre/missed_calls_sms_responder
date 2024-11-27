<?php

require_once('includes/ringcentral-functions.inc');
require_once('includes/ringcentral-php-functions.inc');
require_once('includes/ringcentral-db-functions.inc');

show_errors();

$controller = ringcentral_sdk();
$platform = $controller['platform'];
$sdk = $controller['SDK'];

$endpoint = "/team-messaging/v1/chats";

$params = array(
//        'type' => array('Team'),
        'type' => array( 'Everyone', 'Group', 'Personal', 'Direct', 'Team' ),
        //'recordCount' => 2,
    );

try {
    $resp = $platform->get($endpoint, $params);
//    echo_spaces("Response Object", $resp->json()->records);
} catch (\RingCentral\SDK\Http\ApiException $e) {
    echo_spaces("Error message", $e->getMessage());
}

foreach ($resp->json()->records as $value) {
    if ($value->name) {
        echo_spaces("Chat Id", $value->id);
        echo_spaces("Name", $value->name);
        echo_spaces("type", $value->type);
        echo_spaces("description", $value->description, 1);
    }
}


