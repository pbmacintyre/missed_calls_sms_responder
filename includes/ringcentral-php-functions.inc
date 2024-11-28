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
    echo "<title >Missed Calls SMS App</title>";
    echo "<link rel='stylesheet' href='css/ringcentral-custom.css'></head>";
}
function page_footer() {
    echo "</html>";
}

function app_name() {
	echo "Missed Calls SMS App";
	return "Missed Calls SMS App";
}

function app_version() {
	echo_plain_text("Version 0.1", "grey", "small");
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

function getCiphers() {
    $table = "ringcentral_control";
    $columns_data = array ("cipher_key") ;
    $where_info = array ("ringcentral_control_id", 1);
    $db_result = db_record_select($table, $columns_data, $where_info );

    $ciphers[0] = $db_result[0]['cipher_key'];
    $ciphers[1] = "7uV*nlhApRIsOse@";
    return $ciphers ;
}
