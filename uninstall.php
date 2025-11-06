<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

global $db;

// Drop all tables
$sql[] = "DROP TABLE IF EXISTS `yealink_settings`;";
$sql[] = "DROP TABLE IF EXISTS `yealink_networks`;";
$sql[] = "DROP TABLE IF EXISTS `yealink_network_settings`;";
$sql[] = "DROP TABLE IF EXISTS `yealink_devices`;";
$sql[] = "DROP TABLE IF EXISTS `yealink_device_settings`;";
$sql[] = "DROP TABLE IF EXISTS `yealink_device_lines`;";
$sql[] = "DROP TABLE IF EXISTS `yealink_device_line_settings`;";
$sql[] = "DROP TABLE IF EXISTS `yealink_device_linekeys`;";

foreach ($sql as $statement){
	$check = $db->query($statement);
	if (DB::IsError($check)){
		out("Error executing: $statement - " . $check->getMessage());
	}
}

out("Yealink Phones module uninstalled");

?>
