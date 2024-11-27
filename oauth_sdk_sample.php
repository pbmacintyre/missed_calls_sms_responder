<?php
ob_start();
session_start();

$USE_PKCE = True;

require_once('includes/ringcentral-functions.inc');
require_once('includes/ringcentral-php-functions.inc');

show_errors();
$platform = ringcentral_oAuth_sdk();

// process an oauth redirect
if (isset($_GET['code'])) {
    echo_spaces("GET Code string value", $_GET['code']);
    echo_spaces("SESSION value", $_SESSION );
    $qs = $platform->parseAuthRedirectUrl($_SERVER['QUERY_STRING']);
    $qs["redirectUri"] = $_ENV['RC_REDIRECT_URL'];
    if ($USE_PKCE) {
        $qs["codeVerifier"] = $_SESSION['sessionCodeVerifier'];
    }
    $platform->login($qs);
    $_SESSION['sessionAccessToken'] = $platform->auth()->data();
}

// seed the SDK with auth context if present
if (isset($_SESSION['sessionAccessToken'])) {
    $platform->auth()->setData((array)$_SESSION['sessionAccessToken']);
}

// process a logout request
if (isset($_REQUEST['logout'])) {
    unset($_SESSION['sessionAccessToken']);
    $platform->logout();

// process a request to call the API as a demo
} elseif (isset($_REQUEST['api'])) {
    switch ($_REQUEST['api']) {
        case "audit-trail":
            $endpoint = "/restapi/v1.0/account/~/audit-trail/search";
            $dateTime = new DateTime('now', new DateTimeZone('AST'));
            $startDateTime = $dateTime->modify('-15 minutes')->format('Y-m-d\TH:i:s.v\Z');

            $dateTime = new DateTime('now', new DateTimeZone('AST'));
            $endDateTime = $dateTime->format('Y-m-d\TH:i:s.v\Z');

            $params = array(
//                'eventTimeFrom' => $startDateTime,
                'eventTimeFrom' => '2024-08-01T00:00:00.000Z',
//                'eventTimeTo' => $endDateTime,
                'eventTimeTo' => '2024-08-25T00:00:00.000Z',
                'includeAdmins' => True,
                'includeHidden' => True,
                );

            callPostRequest($platform, $endpoint, $params);
            break;
        case "user-roll":
            $endpoint = "/restapi/v1.0/dictionary/user-role";
            callUserRollRequest($endpoint, null);
            break;
        case "account-info":
//            $endpoint = "/restapi/v1.0/account/~";
            $endpoint = "/restapi/v1.0/account/~/extension/~/assigned-role";
            callGetRequest($endpoint, null);
            break;
        case "extension-call-log":
            $endpoint = "/restapi/v1.0/account/~/extension/~/call-log";
            $params = array('fromDate' => '2018-12-01T00:00:00.000Z');
            callGetRequest($endpoint, $params);
            break;
        case "account-call-log":
            $endpoint = "/restapi/v1.0/account/~/call-log";
            $params = array('fromDate' => '2018-12-01T00:00:00.000Z');
            callGetRequest($endpoint, $params);
            break;
    }
    // no more output
    exit();
}

if ($platform->loggedIn()) {
    ?>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>RingCentral OAuth Example (logged in)</title>
    </head>
    <body>
    <?php

    // echo_spaces("GET Code string", $_GET['code']);
    echo_spaces("Auth Data", $platform->auth()->data());

    ?>
    <h2>Test out these API functions:</h2>
    <ul>
        <li><a href="?api=audit-trail">Get Audit Trail Info</a></li>
        <li><a href="?api=user-roll">Show assigned role Info</a></li>
        <li><a href="?api=account-info">Show account Info</a></li>
        <li><a href="?api=extension-call-log">Read Extension Call Log</a></li>
        <li><a href="?api=account-call-log">Read Account Call Log</a></li>
        <li><a href="?logout">Logout</a></li>
    </ul>
    </body>
    </html>
    <?php
} else {

    $options = array(
        'redirectUri' => $_ENV['RC_REDIRECT_URL'],
        'state' => 'initialState'
    );
    if ($USE_PKCE) {
        $random = bin2hex(openssl_random_pseudo_bytes(32));
        $verifier = base64url_encode(pack('H*', $random));
        $options['code_challenge'] = base64url_encode(pack('H*', hash('sha256', $verifier)));
        $options['code_challenge_method'] = 'S256';
        // store the verifier in the session, it will be used when processing the redirect
        $_SESSION['sessionCodeVerifier'] = $verifier;
    }
    $url = $platform->authUrl($options);
    ?>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>RingCentral OAuth Example (logged out)</title>
    </head>
    <body>
    <h2>RingCentral OAuth auth code flow demonstration</h2>
    <p><a href="<?php echo $url; ?>">Log into your RingCentral account</a></p>
    </body>
    </html>
    <?php
}

ob_end_flush();
