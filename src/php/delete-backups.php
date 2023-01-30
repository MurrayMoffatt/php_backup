<?php
/*
  Module: Delete Client Database Backups
  Description: Delete all the .zip database backup files that were created by backup.php.
  History:
    10-05-2013 MKM : Initial coding.
*/

error_reporting(E_ALL);
header("Content-type: text/plain");
set_time_limit(0);
ob_implicit_flush(TRUE);
display_status("*** Starting to Delete Backup Files at " . date("l jS \of F Y h:i:s A") . " ***\r\n");

$mask = "*.zip";
array_map("unlink", glob($mask));

display_status("*** Deletion Finished ***\r\n");

function display_status($text) {
  echo $text;
  ob_flush();
  flush();
}
?>