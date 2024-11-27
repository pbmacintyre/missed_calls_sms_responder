<?php
/**
 * Copyright (C) 2019-2024 Paladin Business Solutions
 *
 */

/* ========== */
/*  show_form */
/* ========== */
Function show_form($message, $label = "", $color = "#008EC2") {	
	
	global $print_again, $wpdb;   

 	$response = list_tm_teams() ;
 	
	if ($response == false) {
		$message = "Failed to retrieve Team names";
		$color = "red" ;
	} 
	?>
	
	<form action="" method="post" >
	<table class="GlipTable" >
		<tr class="GlipTable">
			<td colspan="2" align="center">
		<?php	
			if ($print_again == true) {
				echo "<font color='$color'><strong>" . esc_html($message) . "</strong></font>";
			} else {
			    echo "<font color='$color'><strong>" . esc_html($message) . "</strong></font>";	    
			}	
			?></td>
		</tr>	
		<tr class="GlipTable">
			<td class="Glip_left_col">
				<p style='display: inline; <?php if ($label == "chat_id") echo "color:red"; ?>' >Available Team Chats:</p>
			</td>
			<td class="right_col">
			<?php if ($response == false) {
				echo "<font color='red'>No Team Chats are currently available</font>" ;
			} else { ?>
				<select name="chat_id">
			    	<option selected value="-1">Choose Team to Post Chat into</option>
			<?php			
				foreach ($response->json()->records as $record) { ?>               		
			      	<option value="<?php echo $record->id ; ?>"><?php echo $record->name ; ?></option>
			      <?php } ?>
				</select>
			<?php } ?>
			</td>
		</tr>
		<tr class="GlipTable">
			<td class="Glip_left_col">
				<p style='display: inline; <?php if ($label == "team_chat") echo "color:red"; ?>' >Chat Text to Send:</p>
			</td>
			<td class="right_col">
				<textarea name="team_chat"></textarea>
			</td>
		</tr>
		<tr class="GlipTable">
			<td colspan="2" align="center">	
				<?php submit_button("Send Text","primary","submit",""); ?>
			</td>
		</tr>	
	</table>
	</form>
<?php 	
}

/* ========== */
/* check_form */
/* ========== */
Function check_form() {

	global $print_again, $wpdb;
	
	$rcsdk = ringcentral_sdk();
	
	$label = "" ;
	$message = "" ;
	
	if ($_POST['chat_id'] == "-1") {
		$print_again = true;
		$label = "chat_id";
		$message = "Please select a Team Chat to post to.";
	}
	
	if ($_POST['team_chat'] == "") {
		$print_again = true;
		$label = "team_chat";
		$message = "Chat text cannot be blank.";
	}

	if ($print_again == true) {
		$color = "red" ;
		show_form($message, $label, $color);
	} else {
		// POST to selected chat		
		$result = post_tm_chat($rcsdk, $_POST['chat_id'], $_POST['team_chat']) ;
		if ($result) {
			$message = "Chat note sent successfully" ;
			$color = "green" ;			
		} else {
			$message = "Posting chat note failed...sorry" ;
			$color = "red" ;			
		}
		show_form($message, "", $color);
	}
}

/* ============= */
/*  --- MAIN --- */
/* ============= */
if(isset($_POST['submit'])) {
	check_form();
} else {
	$message = "Team Messaging options";
	show_form($message);
}

?>