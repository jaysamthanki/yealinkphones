<?php
/**
 * Yealink Log Upload Handler
 * Phones can upload diagnostic logs via HTTP POST
 */

$bootstrap_settings['freepbx_auth'] = false;
$bootstrap_settings['skip_astman'] = true;

if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf'))
{
	include_once('/etc/asterisk/freepbx.conf');
}

// Get MAC address from URL or POST
$mac = '';
if(isset($_GET['mac']))
	$mac = strtoupper($_GET['mac']);
elseif(isset($_POST['mac']))
	$mac = strtoupper($_POST['mac']);

if(empty($mac) || !preg_match('/^[0-9A-F]{12}$/', $mac))
{
	header('HTTP/1.0 400 Bad Request');
	die('Invalid MAC address');
}

// Lookup IP to determine if authentication is required
$network = yealinkphones_get_networks_ip($_SERVER['REMOTE_ADDR']);
yealinkphones_check_network($network);

// Check if this is a file upload
if(isset($_FILES['file']))
{
	$upload_file = $_FILES['file'];

	if($upload_file['error'] != UPLOAD_ERR_OK)
	{
		header('HTTP/1.0 500 Internal Server Error');
		die('Upload failed');
	}

	// Save log file
	$log_path = $amp_conf['AMPWEBROOT'] . '/admin/modules/_yealink_software/logs/';
	$log_filename = $mac . '-' . date('Y-m-d-His') . '.log';

	if(move_uploaded_file($upload_file['tmp_name'], $log_path . $log_filename))
	{
		header('HTTP/1.0 200 OK');
		echo 'Log uploaded successfully';
	}
	else
	{
		header('HTTP/1.0 500 Internal Server Error');
		die('Failed to save log');
	}
}
else
{
	// No file uploaded
	header('HTTP/1.0 400 Bad Request');
	die('No file uploaded');
}

?>
