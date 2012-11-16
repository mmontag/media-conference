<?php
/*

By matt.montag@gmail.com 
10/5/2011

*/

/* CONFERENCE CONFIGURATION */
$CONFERENCE_TITLE = "Seamus 2013";
$CONFERENCE_WEBSITE = "www.seamusonline.org";
$ADMIN_EMAIL = "matt.montag@gmail.com";
$ADMIN_NAME = "Matt Montag";

$upload_relative_path = "2013/upload";
$upload_absolute_path = "/home/seamu1/www/seamusonline.org/conference/2013/upload";
$wp_table_prefix = "wp_";
$judges_table = "judges";
$ratings_table = "ratings";
$form_ids_to_names = array(4=>"Music", 5=>"ASCAP", 6=>"Paper"); // Associative array mapping cForms form ID to abbreviated form name
$media = array(".mp3",".m4a",".mus",".aiff",".mid",".wav");

/* POST-PROCESSING DATA */
$bypass = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 44, 91, 92, 169, 
193, 263, 327, 343, 479, 495, 496, 497, 500, 503, 504, 505, 522, 595, 597, 587,
613, 614, 615); // a comma-delimited list of invalid submission IDs
$concerts = array( ); // an array of arrays defining submission IDs in each concert

/* DATABASE CONNECTION */
require("_server.include.php");
error_reporting(E_WARNING);
$db = mysql_connect($db_host,$db_user,$db_pass);
mysql_select_db($db_name);
echo mysql_error();

?>