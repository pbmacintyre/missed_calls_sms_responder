<?php
/**
 * Copyright (C) 2019-2024 Paladin Business Solutions
 *
 */

/* ========================== */
/* Call HTML wrappers section */
/* ========================== */
function page_header($recap = 0, $errors = 0) {
    if ($errors == 1) { show_errors(); }
    echo "<!DOCTYPE html><html class='SiteWide'><head >";
    if ($recap) { ringcentral_build_recaptcha_v3_top(); }
    echo "<title >Missed Calls SMS App</title>";
    echo "<link rel='stylesheet' href='css/ringcentral-custom.css'></head>";
}
function page_footer() {
    echo "</html>";
}
/* ================== */
/* Turn on Error codes */
/* ================== */
function show_errors() {
//     error_reporting();
     error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    ini_set('display_errors', 1);
}
function echo_spaces($text, $value="", $lines=0) {
    echo "<br /><strong><p style='color: red; display: inline'>$text:</p></strong> " ;
    if (is_string($value) || is_int($value))  { echo $value; }
    if (is_object($value) || is_array($value)) { ringcentral_dump_obj($value); }
    if (is_bool($value)) { if ($value) echo "TRUE / Success"; else echo 'FALSE / Failure'; }
    $i = 1 ;
    while ($i <= $lines) {
        echo "<br />" ;
        $i++;
    }
}
/* ======================== */
/* Dump the provided object */
/* ======================== */
function ringcentral_dump_obj($object) {
    echo "<pre>";
    var_dump($object);
    echo "</pre>";
}
function echo_plain_text($text, $color="black", $font_size="large", $lines=0) {
    echo "<p style='color: $color; display: inline; font-size: $font_size'>$text</p>" ;
    if ($lines > 0 ) {
        $i = 0;
        while ($i <= $lines) {
            echo "<br />";
            $i++;
        }
    }
}
function required_field() {
    echo "<p style='color: red; display: inline'>*</p>" ;
}
function getCiphers() {
    $table = "ringcentral_control";
    $columns_data = array ("cipher_key") ;
    $where_info = array ("ringcentral_control_id", 1);
    $db_result = db_record_select($table, $columns_data, $where_info );

    $ciphers[0] = $db_result[0]['cipher_key'];
    $ciphers[1] = "7uV*nlhApRIsOse@";
    return $ciphers ;
}
function encryptData($data) {
    $ciphers = getCiphers() ;
    return openssl_encrypt($data, 'AES-256-CBC', $ciphers[0], 0, $ciphers[1]);
}
function decryptData($encryptedData) {
    $ciphers = getCiphers() ;
    return openssl_decrypt($encryptedData, 'AES-256-CBC', $ciphers[0], 0, $ciphers[1]);
}
function password_check($password) {
    // Initialize variables
    $pass = false;

    // Check password length
    if (strlen($password) >= 8) {
        $pass = true;
    }
    // Check for mixed case
    if (preg_match("/[a-z]/", $password) && preg_match("/[A-Z]/", $password)) {
        $pass = true;
    }
    // Check for numbers
    if (preg_match("/\d/", $password)) {
        $pass = true;
    }
    // Check for special characters
    if (preg_match("/[^a-zA-Z\d]/", $password)) {
        $pass = true;
    }
    // Return pass / fail
    return $pass;
}
function generate_form_token () {
    $token = sha1(uniqid(rand(), true));
    $_SESSION['form_token'] = $token ;
    return $token ;
}
function dump_log ($filename) {
// Check if the file exists
    if (file_exists($filename)) {
        // Read the contents of the file
        $logContent = file_get_contents($filename);

        // Display the contents in the browser
        echo '<pre>' . htmlspecialchars($logContent) . '</pre>';
    } else {
        echo 'Error: that file does not exist.';
    }

}


