<?php
/**
 * Copyright (C) 2019-2024 Paladin Business Solutions
 *
 */

require_once('includes/ringcentral-functions.inc');
require_once('includes/ringcentral-curl-functions.inc');
require_once('includes/ringcentral-php-functions.inc');
require_once('includes/ringcentral-db-functions.inc');

show_errors();

//$webhookId = ringcentral_create_webhook_subscription() ;

echo_spaces("Webhook ID", $webhookId);


