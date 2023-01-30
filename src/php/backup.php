<?php
/*
  Module: Backup Client Databases
  Description: Given an array of database details, create text file backups of all the tables in each database and zip them.
    The output is similar to what you'd get from using the mysqldump command and is suitable for importing back into MySQL.
  History:
    10-05-2013 MKM : Initial coding.
    15-11-2020 MKM : Update hosts for all DBs due to changes at hosting service.
    05-12-2021 MKM : Changes to convert to using PHP's MySQLi extension rather than the old MySQL extension.
*/

error_reporting(E_ALL);
header("Content-type: text/plain");
$verbose = !empty($_GET['verbose']) ? TRUE : FALSE;
set_time_limit(0);
ob_implicit_flush(TRUE);
if ($verbose) display_status("*** Starting Backup at " . date("l jS \of F Y h:i:s A") . " ***\r\n\r\n");

if (!class_exists('ZipArchive')) {
  die("The ZipArchive class doesn't exist!");
}

$i = 0;
$cfg[$i]['db_name']  = 'databasename1';
$cfg[$i]['host']     = 'host1';
$cfg[$i]['user']     = 'user1';
$cfg[$i]['password'] = 'password1';

$i++;
$cfg[$i]['db_name']  = 'databasename2';
$cfg[$i]['host']     = 'host2';
$cfg[$i]['user']     = 'user2';
$cfg[$i]['password'] = 'password2';

$i++;
$cfg[$i]['db_name']  = 'databasename3';
$cfg[$i]['host']     = 'host3';
$cfg[$i]['user']     = 'user3';
$cfg[$i]['password'] = 'password3';

// Repeat the above block for each successive database you want backed up

// Process each DB
for($j = 0; $j <= $i; $j++) {
  $dbname = $cfg[$j]['db_name'];
  $dbhost = $cfg[$j]['host'];
  $dbuser = $cfg[$j]['user'];
  $dbpwd = $cfg[$j]['password'];
  if(!$mycon = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname)) die("Database Error: " . mysqli_connect_error());
  backup_mysql();
  mysqli_close($mycon);
  sleep(10);
}

if ($verbose) display_status("*** Backup Finished ***\r\n");

function backup_mysql() {
  global $dbname, $verbose, $mycon;
  $backticks = 1;
  $ticks = ($backticks == 1) ? "`" : "'";
  $set['endit'] = 1;
  $set['usedrop'] = 1;

  // Get a list of all the tables in the DB
  $sql = "SHOW TABLES FROM $dbname";
  $tables = mysqli_query($mycon, $sql) or die("Getting list of tables\r\nSQL: $sql\r\nError: " . mysqli_error($mycon));
  $gettables = mysqli_num_rows($tables);

  $a .= "##--------------------------------------------\r\n";
  $a .= "##--" . $_SERVER['SERVER_NAME'] . " Database: " . $dbname . "\r\n";
  $a .= "##--Total Tables: " . $gettables . " Saved On: " . date("Y-m-d H:i:s", time()) . "\r\n";
  $a .= "##--------------------------------------------\r\n";
  if ($verbose) display_status($a);

  $ender = ($set['endit']) ? ";" : "";

  // Process each table
  while($table1 = mysqli_fetch_array($tables)) {
    $table = $table1[0];
    $a .= "\r\n##------------------ " . $table . " ----------------------\r\n\r\n";

    $drop = "DROP TABLE IF EXISTS " . $ticks . $table . $ticks . $ender . "\r\n";
    $a .= ($set['usedrop'] == 1) ? $drop : "";

    $row1 = mysqli_query($mycon, "SHOW CREATE TABLE " . $table) or die(mysqli_error($mycon));
    $row2 = mysqli_fetch_array($row1);
    $row2 = ($backticks == 1) ? $row2[1] : str_replace("`", "'", $row2[1]);
    $a .= $row2 . $ender . "\r\n";

    $a .= "\r\n" . backup_table($table, $set['endit']);
  }

  $a .= "##------------ END OF FILE ----------------------\r\n\r\n";
  $filename = $dbname . "_" . date("Y-m-d", time()) . ".sql";

  // Zip the file
  if ($verbose) display_status("Zipping file...\r\n");
  $zip = new ZipArchive();
  $zipfilename = $dbname . "_" . date("Y-m-d", time()) . ".zip";
  if ($zip->open($zipfilename, ZIPARCHIVE::CREATE) !== TRUE) {
     die("Cannot open $zipfilename");
  }
  $zip->addFromString("/$filename", $a);
  $zip->close();
  if ($verbose) display_status("Zip finished\r\n");
}

function backup_table($table, $endit = 0) {
  global $dbname, $mycon;
  $a = "";

  $sql = "SELECT * FROM $table";
  $sql = mysqli_query($mycon, $sql) or die("Selecting records from table $table\r\nSQL: $sql\r\nError: " . mysqli_error($mycon));
  $count = mysqli_num_rows($sql);
  $backticks = 1;
  $ticks = ($backticks == 1) ? '`' : "'";
  $countit = mysqli_num_fields($sql);

  // Process each row in the table
  while ($row = mysqli_fetch_array($sql)) {
    $a .= "INSERT INTO $ticks" . $table . "$ticks VALUES(";
    for ($i = 0; $i < $countit; $i++) {
      $a .= "'" . mysqli_real_escape_string($mycon, $row[$i]) . "'";
      if ($i + 1 < $countit) {
        $a .= ",";
      }
    }
    $a .= ")";
    $a .= ($endit == 1 ) ? ";" : "";
    $a .= "\r\n";
  }

  return $a;
}

function display_status($text) {
  echo $text;
  ob_flush();
  flush();
}
?>
