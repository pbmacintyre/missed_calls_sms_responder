<?php
/**
 * Copyright (C) 2019-2024 Paladin Business Solutions
 */
ob_start();

require_once('includes/ringcentral-functions.inc');
require_once('includes/ringcentral-php-functions.inc');

page_header();  // set back to 1 when recaptchas are set in the DB

function show_form ($message) {  ?>
    <form action="" method="post">
        <table class="CustomTable">
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol">
                    <img src="images/rc-logo.png"/>
                    <h2><?php app_name(); ?></h2>
                    <?php echo_plain_text($message); ?>
                </td>
            </tr>
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol">
                    <hr>
                </td>
            </tr>
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol">
                    <br/>
                    <input type="submit" class="submit_button" value="   Logout   " name="logout">
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
if (isset($_POST['logout'])) {
	header("Location: index.php");
} else {
	$message = "Your account has been fully authorized and any edits have been saved. You will be notified 
	at the provided contact point(s) when admin level editing events occur on your account";
	show_form($message);
}

ob_end_flush();

page_footer();
