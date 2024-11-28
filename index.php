<?php
/**
 * Copyright (C) 2019-2024 Paladin Business Solutions
 */
ob_start();
session_start();

require_once('includes/ringcentral-php-functions.inc');

page_header(0);  // set back to 1 when recaptchas are set in the .ENV file

function show_form($message, $label = "", $print_again = false) { ?>

    <form action="" method="post">
        <table class="CustomTable">
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol">
                    <img src="images/rc-logo.png"/>
                    <h2><?php app_name(); ?></h2>
					<?php
					if ($print_again == true) {
//                        echo "<p class='msg_bad'>" . $message . "</strong></font>";
						echo_plain_text($message, "red", "large");
					} else {
//                        echo "<p class='msg_good'>" . $message . "</p>";
						echo_plain_text($message, "#008EC2", "large");
					} ?>
                </td>
            </tr>
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol">
                    <input type="submit" class="submit_button" value="   Check log   " name="checkLog">
                </td>
            </tr>
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol">
                    <hr>
                </td>
            </tr>
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol">
					<?php app_version(); ?>
                </td>
            </tr>
        </table>
    </form>
	<?php
}

/* ============= */
/*  --- MAIN --- */
/* ============= */
if (isset($_POST['checkLog'])) {
	require(__DIR__ . '/includes/vendor/autoload.php');
	$dotenv = Dotenv\Dotenv::createMutable(__DIR__ . '/includes')->load();

	$client_id = $_ENV['RC_APP_CLIENT_ID'];
	$redirect_url = $_ENV['RC_REDIRECT_URL'];

    // authorize the access and get an access key
	$authorization_url = "https://platform.ringcentral.com/restapi/oauth/authorize?response_type=code&client_id={$client_id}&redirect_uri={$redirect_url}";

    header("Location: $authorization_url");
} else {
	$message = "Click the Check log button to see if there are any missed calls in the log <br/>";
	show_form($message);
}

ob_end_flush();
page_footer();
