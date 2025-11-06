<?php
/**
 * Yealink Boot File Generator
 * Returns: y000000000000.boot
 * This file tells the phone which config files to load
 */

$bootstrap_settings['freepbx_auth'] = false;
$bootstrap_settings['skip_astman'] = true;

if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf'))
{
	include_once('/etc/asterisk/freepbx.conf');
}

// Detect phone model from User-Agent
$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
$model = yealinkphones_detect_model($user_agent);

// Get MAC address if provided (convert to uppercase for consistency)
$mac = isset($_GET['mac']) ? strtoupper($_GET['mac']) : '';

// Lookup IP to determine if authentication or SSL is required
$network = yealinkphones_get_networks_ip($_SERVER['REMOTE_ADDR']);
yealinkphones_check_network($network);

// Set content type
header('Content-Type: text/plain');

// Generate boot file
echo "#!version:1.0.0.1\n\n";
echo "# Yealink Boot Configuration\n";
echo "# Generated: " . date('Y-m-d H:i:s') . "\n\n";

// Include common CFG based on model
if($model != '00')
{
	echo "include:config \"y0000000000" . $model . ".cfg\"\n";
}

// Include MAC-specific CFG if MAC provided
if(!empty($mac) && preg_match('/^[0-9A-F]{12}$/', $mac))
{
	echo "include:config \"" . $mac . ".cfg\"\n";
}

?>
