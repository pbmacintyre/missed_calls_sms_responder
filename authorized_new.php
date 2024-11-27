<?php
/**
 * Copyright (C) 2019-2024 Paladin Business Solutions
 */
ob_start();
session_start();

require_once('includes/ringcentral-functions.inc');
require_once('includes/ringcentral-db-functions.inc');
require_once('includes/ringcentral-php-functions.inc');
require_once('includes/ringcentral-curl-functions.inc');

//show_errors();

page_header();
?>
    <script>
        window.onload = function () {
            showHideSMS();
            showHideTM();
        };

        function showHideSMS() {
            const sms_checkbox = document.getElementById("SMSEnable");
            const sms_rows = document.querySelectorAll('.SMSToggle');

            if (sms_checkbox.checked) {
                sms_rows.forEach(sms_row => {
                    sms_row.style.display = 'table-row';
                });
            } else {
                sms_rows.forEach(sms_row => {
                    sms_row.style.display = 'none';
                });
            }
        }

        function showHideTM() {
            const tm_checkbox = document.getElementById("TMEnable");
            const tm_rows = document.querySelectorAll('.TMToggle');

            if (tm_checkbox.checked) {
                tm_rows.forEach(tm_row => {
                    tm_row.style.display = 'table-row';
                });
            } else {
                tm_rows.forEach(tm_row => {
                    tm_row.style.display = 'none';
                });
            }
        }
    </script>

<?php
function show_form($message, $label = "", $print_again = false, $color = "#008EC2") {
	$accessToken = $_SESSION['access_token'];
	$accountId = $_SESSION['account_id'];
	$extensionId = $_SESSION['extension_id'];
//    echo_spaces("Post Array", $_POST);

	?>
    <form action="" method="post">
        <table class="CustomTable">
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol">
                    <img src="images/rc-logo.png"/>
                    <h2><?php app_name(); ?></h2>
					<?php
					echo_plain_text($message, $color, "large");
					?>
                </td>
            </tr>
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol">
                    <hr>
                </td>
            </tr>
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol_left">
					<?php echo_plain_text("Receive Audit Trail notifications via SMS", "", "medium"); ?>
                </td>
            </tr>
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol_left">
                    <input type="checkbox" name="SMSEnableToggle" id="SMSEnable" onClick="showHideSMS()"
						<?php if ($print_again) {
							if ($_POST['SMSEnableToggle'] == "on") {
								echo " checked";
							}
						} ?>
                    >Enable
                </td>
            </tr>
			<?php $response = list_extension_sms_enabled_numbers($accessToken, $accountId, $extensionId); ?>
            <tr class="CustomTable SMSToggle">
                <td>
                    <!--  blank column for formatting -->
                </td>
                <td class="right_col">
					<?php echo_plain_text("Phone number formats: +19991234567", "", "small"); ?>
                </td>
            </tr>
            <tr class="CustomTable SMSToggle">
                <td class="addform_left_col">
                    <p style='display: inline; <?php if ($label == "from_number") echo "color:red"; ?>'>From Number:</p>
					<?php required_field(); ?>
                </td>
                <td class="addform_right_col">
					<?php
					if (!$response) {
						echo "<span style=\"color: red; \">No SMS enabled phone numbers were found for that account</span>";
					} else { ?>
                        <select name="from_number">
							<?php
							if ($print_again) {
								if ($_POST['from_number'] == "-1") {
									echo "<option selected value='-1'>Choose a From Number</option>";
								} else {
									echo "<option selected value='" . $_POST['from_number'] . "'>" . $_POST['from_number'] . "</option>";
								}
							} else {
								echo "<option selected value='-1'>Choose a From Number</option>";
							}
							foreach ($response as $record) { ?>
                                <option value="<?php echo $record['phoneNumber']; ?>"><?php echo $record['phoneNumber']; ?></option>
							<?php } ?>
                        </select>
					<?php } ?>
                </td>
            </tr>
            <tr class="CustomTable SMSToggle">
                <td class="addform_left_col">
                    <p style='display: inline; <?php if ($label == "to_number") echo "color:red"; ?>'>To Number:</p>
					<?php required_field(); ?>
                </td>
                <td class="addform_right_col">
                    <input type="text" name="to_number" value="<?php
					if ($print_again) {
						echo strip_tags($_POST['to_number']);
					}
					?>">
                </td>
            </tr>
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol_left">
					<?php
					echo_plain_text("AND / OR", "green", "large", 1);
					echo_plain_text("Receive Audit Trail notifications via RingCentral Team Messaging", "", "medium"); ?>
                </td>
            </tr>
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol_left">
                    <input type="checkbox" name="TMEnableToggle" id="TMEnable" onClick="showHideTM()"
						<?php if ($print_again) {
							if ($_POST['TMEnableToggle'] == "on") {
								echo " checked";
							}
						} ?>
                    >Enable
                </td>
            </tr>
			<?php $response = list_tm_teams($accessToken); ?>
            <tr class="CustomTable TMToggle">
                <td class="addform_left_col">
                    <p style='display: inline; <?php if ($label == "chat_id") echo "color:red"; ?>'>Team Chats:</p>
					<?php required_field(); ?>
                </td>
                <td class="addform_right_col">
					<?php
					if (!$response) {
						echo "<span style=\"color: red; \">No Team Chats are currently available</span>";
					} else { ?>
                        <select name="chat_id">
							<?php
							if ($print_again) {
								$parts = explode("/", $_POST['chat_id']);
								$chat_id = $parts[0];
								$group_name = $parts[1];
								if ($chat_id == "-1") {
									echo "<option selected value='-1'>Select a Team Chat in which to post notifications</option>";
								} else {
									echo "<option selected value='" . $chat_id . "'>" . $group_name . "</option>";
								}
							} else {
								echo "<option selected value='-1'>Select a Team Chat in which to post notifications</option>";
							}
							foreach ($response['records'] as $record) { ?>
                                <option value="<?php echo $record['id'] . "/" . $record['name']; ?>"><?php echo $record['name']; ?></option>
							<?php } ?>
                        </select>
					<?php } ?>
                </td>
            </tr>
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol">
                    <br/>
                    <input type="submit" class="submit_button" value="   Save   " name="save">
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

function check_form() {
	$print_again = false;
	$label = "";
	$message = "";

	$from_number = htmlspecialchars(strip_tags($_POST['from_number']));
	$to_number = htmlspecialchars(strip_tags($_POST['to_number']));

	$parts = explode("/", $_POST['chat_id']);
	$chat_id = htmlspecialchars(strip_tags($parts[0]));

	$SMSEnableToggle = $_POST['SMSEnableToggle'] == "on" ? true : false;
	$TMEnableToggle = $_POST['TMEnableToggle'] == "on" ? true : false;

	if ($SMSEnableToggle) {
		if ($from_number == "-1") {
			$print_again = true;
			$label = "from_number";
			$message = "You need to select a phone number from the dropdown list if you enable the SMS option";
		}
		// check the formatting of the mobile # == +19991234567
		$pattern = '/^\+\d{11}$/'; // Assumes 11 digits after the '+'

		if (!preg_match($pattern, $to_number)) {
			$print_again = true;
			$label = "to_number";
			$message = "The mobile TO number is not in the correct format of +19991234567";
		}
	}
	if ($TMEnableToggle) {
		if ($chat_id == "-1") {
			$print_again = true;
			$label = "chat_id";
			$message = "You need to select a Team chat from the dropdown list <br/>if you enable the Team Messaging option";
		}
	}
	if (!$SMSEnableToggle && !$TMEnableToggle) {
		$print_again = true;
		$message = "You need to enable either an SMS or Team Messaging option.";
	}

	// end edit checks
	if ($print_again == true) {
		show_form($message, $label, true, "red");
	} else {
		$accountId = $_SESSION['account_id'];
		$extensionId = $_SESSION['extension_id'];
		$accessToken = $_SESSION['access_token'];
		$refreshToken = $_SESSION['refresh_token'];

//        echo_spaces("session", $_SESSION);
//        echo_spaces("post", $_POST);

		// insert the record into DB with validated information
		$table = "clients";
		$columns_data = array(
			"account" => $accountId,
			"extension_id" => $extensionId,
			"access" => $accessToken,
			"refresh" => $refreshToken,
			"from_number" => $from_number,
			"to_number" => $to_number,
			"team_chat_id" => $chat_id,);
		$new_client_id = db_record_insert($table, $columns_data, "client_id");

		// create admin webhook, there may already be an admin webhook so let the function test that
		ringcentral_create_admin_webhook_subscription($accountId, $accessToken);

		// if from & to number exist create sms webhook,
		if ($from_number && $to_number) {
			$sms_webhook_id = ringcentral_create_sms_webhook_subscription($accountId, $extensionId, $accessToken);
		} else {
			$sms_webhook_id = 0;
		}

		// store new webhook ids
		$where_info = array("client_id", $new_client_id,);
		$fields_data = array(
			"sms_webhook" => $sms_webhook_id,
		);
		db_record_update($table, $fields_data, $where_info);

		header("Location: authorization_complete.php");
	}
}

/* ============= */
/*  --- MAIN --- */
/* ============= */
if (isset($_SESSION['form_token']) && $_GET['token'] == $_SESSION['form_token']) {
	if (isset($_POST['save'])) {
		check_form();
	} else {
		$message = "Your account will be authorized. <br/> Please provide the following additional information";
		show_form($message);
	}
} else {
	$_SESSION['form_token'] = "";
	header("Location: index.php");
}

ob_end_flush();
page_footer();
