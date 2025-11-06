<?php
/**
 * Yealink MAC-Specific Configuration Generator
 * URL: /yealink/config.php?mac={MAC}
 * Returns: {MAC}.cfg
 */

$bootstrap_settings['freepbx_auth'] = false;
$bootstrap_settings['skip_astman'] = true;

if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf'))
{
	include_once('/etc/asterisk/freepbx.conf');
}

// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Ensure global $db is available
global $db;

// Get MAC address and convert to uppercase for database lookup
if (!isset($_GET['mac']))
{
	yealinkphones_send_forbidden();
}

$mac = strtoupper($_GET['mac']);

// Validate MAC format (12 hex digits)
if (preg_match('/^([0-9A-F]{12})$/', $mac) != 1)
{
	yealinkphones_send_forbidden();
}

// Lookup IP to determine if authentication or SSL is required
$network = yealinkphones_get_networks_ip($_SERVER['REMOTE_ADDR']);
yealinkphones_check_network($network);

// Look up device by MAC
$device_id = yealinkphones_lookup_mac($mac);

// Auto-register phone if it doesn't exist
if(!$device_id)
{
	global $db;

	// Extract model and firmware from User-Agent
	$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
	$model_number = yealinkphones_detect_model($user_agent);

	// Parse firmware version from User-Agent: "Yealink SIP-T33G 124.86.0.118 24:9a:d8:1e:83:fa"
	$firmware = '';
	if (preg_match('/SIP-T\d+[A-Z]?\s+([\d\.]+)\s/', $user_agent, $matches)) {
		$firmware = $matches[1];
	}

	// Auto-create phone with descriptive name (max 30 chars)
	// Format: "T33-249AD81E83FA" (17 chars max)
	$auto_name = "T" . $model_number . "-" . $mac;

	try {
		sql("INSERT INTO yealink_devices (name, mac, model, firmware_version, lastconfig, lastip)
			VALUES (
				'".$db->escapeSimple($auto_name)."',
				'".$db->escapeSimple($mac)."',
				'T".$db->escapeSimple($model_number)."',
				'".$db->escapeSimple($firmware)."',
				now(),
				'".$db->escapeSimple($_SERVER['REMOTE_ADDR'])."'
			)");

		$device_id = sql("SELECT LAST_INSERT_ID()",'getOne');
	} catch (Exception $e) {
		error_log("Yealink auto-discovery failed: " . $e->getMessage());
		yealinkphones_send_error('500 Internal Server Error', 'Failed to register phone: ' . $e->getMessage());
	}
}
else
{
	global $db;

	// Update existing device info
	$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
	$firmware = '';
	if (preg_match('/SIP-T\d+[A-Z]?\s+([\d\.]+)\s/', $user_agent, $m)) {
		$firmware = $m[1];
	}

	sql("UPDATE yealink_devices SET
		lastconfig = now(),
		lastip = '".$db->escapeSimple($_SERVER['REMOTE_ADDR'])."',
		model = 'T".$db->escapeSimple(yealinkphones_detect_model($user_agent))."',
		firmware_version = '".$db->escapeSimple($firmware)."'
		WHERE id = '".$db->escapeSimple($device_id)."'");
}

// Load device configuration
$device = yealinkphones_get_phones_edit($device_id);
$global = yealinkphones_get_general_edit();

// Debug: Log device info
error_log("Yealink Debug - Device ID: $device_id, Lines: " . count($device['lines']) . ", Linekeys: " . count($device['linekeys']));

// Set content type
header('Content-Type: text/plain');

// =============================================================================
// GENERATE CFG FILE
// =============================================================================

$cfg = array();

// Header
$cfg[] = "#!version:1.0.0.1";
$cfg[] = "";
$cfg[] = "# Yealink Phone Configuration";
$cfg[] = "# Device: " . $device['name'];
$cfg[] = "# MAC: " . $mac;
$cfg[] = "# Generated: " . date('Y-m-d H:i:s');
$cfg[] = "";

// =============================================================================
// AUTO-PROVISIONING SETTINGS
// =============================================================================

$cfg[] = "## Auto-Provisioning";
$prov_url = ($network['settings']['prov_protocol'] == 'HTTPS' ? 'https://' : 'http://') .
	$_SERVER['SERVER_NAME'] . '/yealink';

$cfg[] = "auto_provision.server.url = " . $prov_url;
if(!empty($network['settings']['prov_username']))
{
	$cfg[] = "auto_provision.server.username = " . $network['settings']['prov_username'];
	$cfg[] = "auto_provision.server.password = " . $network['settings']['prov_password'];
}
$cfg[] = "auto_provision.dhcp_option.enable = 1";
$cfg[] = "auto_provision.repeat.enable = 1";
$cfg[] = "auto_provision.repeat.minutes = " . $global['auto_provision_repeat_minutes'];
$cfg[] = "";

// =============================================================================
// SECURITY SETTINGS
// =============================================================================

$cfg[] = "## Security";
$cfg[] = "security.user_password = user:" . $global['device_user_password'];
$cfg[] = "security.user_password = admin:" . $global['device_admin_password'];
$cfg[] = "security.trust_certificates = " . $global['security_trust_certificates'];
$cfg[] = "";

// =============================================================================
// NETWORK SETTINGS
// =============================================================================

$cfg[] = "## Network Settings";
$cfg[] = "network.internet_port.type = 0";
$cfg[] = "network.vlan.internet_port_enable = 0";
$cfg[] = "";

// =============================================================================
// TIME/NTP SETTINGS
// =============================================================================

$cfg[] = "## Time and Date";
$cfg[] = "local_time.ntp_server1 = " . $network['settings']['ntp_server1'];
if(!empty($network['settings']['ntp_server2']))
	$cfg[] = "local_time.ntp_server2 = " . $network['settings']['ntp_server2'];
$cfg[] = "local_time.time_zone = " . $network['settings']['time_zone'];
$cfg[] = "local_time.time_zone_name = " . $network['settings']['time_zone_name'];
$cfg[] = "local_time.summer_time = 2";
$cfg[] = "local_time.dhcp_time = 1";
$cfg[] = "";

// =============================================================================
// PHONE SETTINGS
// =============================================================================

$cfg[] = "## Phone Settings";
$cfg[] = "phone_setting.backlight_time = " . $global['default_backlight_time'];
$cfg[] = "phone_setting.lang = " . $global['default_lang'];
$cfg[] = "";

// =============================================================================
// ACCOUNT/LINE CONFIGURATION
// =============================================================================

// First, disable all accounts
for($i=1; $i<=16; $i++)
{
	$cfg[] = "account." . $i . ".enable = 0";
}
$cfg[] = "";

// Configure assigned lines
error_log("Yealink Debug - Processing lines: " . print_r($device['lines'], true));

foreach($device['lines'] as $lineid => $line)
{
	error_log("Yealink Debug - Line $lineid: deviceid=" . (isset($line['deviceid']) ? $line['deviceid'] : 'NULL') . ", type=" . gettype($line['deviceid']));

	// Skip if deviceid is not set or is NULL (but allow 0 and "0")
	if(!isset($line['deviceid']) || $line['deviceid'] === NULL || $line['deviceid'] === '')
		continue;

	// Look up FreePBX device details
	$freepbx_device = sql("SELECT devices.id, devices.description, devices.dial, users.extension, users.name
		FROM devices
		LEFT OUTER JOIN users ON devices.user = users.extension
		WHERE devices.id = '".$db->escapeSimple($line['deviceid'])."'", 'getRow', DB_FETCHMODE_ASSOC);

	if(!$freepbx_device)
		continue;

	// Get SIP/PJSIP credentials
	// FreePBX stores both chan_sip and PJSIP credentials in the 'sip' table
	$tech = sql("SELECT tech FROM devices WHERE id = '".$db->escapeSimple($line['deviceid'])."'", 'getOne');
	$extension = $freepbx_device['extension'] ? $freepbx_device['extension'] : $freepbx_device['dial'];

	// Initialize defaults
	$auth_name = $extension;
	$password = '';

	// Look up credentials from sip table (works for both chan_sip and PJSIP in FreePBX)
	$secret = sql("SELECT data FROM sip WHERE id = '".$db->escapeSimple($extension)."' AND keyword = 'secret'", 'getOne');

	if($secret)
	{
		$password = $secret;
		error_log("Yealink Debug - Found password for extension $extension in sip table");
	}
	else
	{
		error_log("Yealink Debug - No password found in sip table for extension $extension");
	}

	// Skip this line if no password found
	if(empty($password))
	{
		error_log("Yealink provisioning: No password found for extension $extension");
		continue;
	}

	$cfg[] = "## Account " . $lineid;
	$cfg[] = "account." . $lineid . ".enable = 1";
	$cfg[] = "account." . $lineid . ".label = " . (!empty($freepbx_device['name']) ? $freepbx_device['name'] : "Line " . $lineid);
	$cfg[] = "account." . $lineid . ".display_name = " . (!empty($freepbx_device['name']) ? $freepbx_device['name'] : $extension);
	$cfg[] = "account." . $lineid . ".auth_name = " . $auth_name;
	$cfg[] = "account." . $lineid . ".user_name = " . $extension;
	$cfg[] = "account." . $lineid . ".password = " . $password;
	$cfg[] = "account." . $lineid . ".sip_server.1.address = " . $network['settings']['sip_server_address'];
	$cfg[] = "account." . $lineid . ".sip_server.1.port = " . $network['settings']['sip_server_port'];
	$cfg[] = "account." . $lineid . ".sip_server.1.transport = " . $network['settings']['sip_server_transport'];
	$cfg[] = "account." . $lineid . ".sip_server.1.expires = " . $network['settings']['sip_server_expires'];
	$cfg[] = "account." . $lineid . ".outbound_proxy.1.address = ";
	$cfg[] = "account." . $lineid . ".outbound_proxy.1.port = " . $network['settings']['sip_server_port'];
	$cfg[] = "account." . $lineid . ".subscribe_mwi = 1";
	$cfg[] = "account." . $lineid . ".subscribe_mwi_to_vm = 1";
	$cfg[] = "account." . $lineid . ".dtmf.type = 2";
	$cfg[] = "account." . $lineid . ".dtmf.info_type = 1";
	$cfg[] = "account." . $lineid . ".nat.udp_update_enable = 1";
	$cfg[] = "account." . $lineid . ".nat.udp_update_time = " . $network['settings']['nat_keepalive_interval'];
	$cfg[] = "account." . $lineid . ".nat.rport = 1";
	$cfg[] = "voice_mail.number." . $lineid . " = *97";

	// Codecs
	if($network['settings']['codec_pcmu_enable'] == '1')
	{
		$cfg[] = "account." . $lineid . ".codec.pcmu.enable = 1";
		$cfg[] = "account." . $lineid . ".codec.pcmu.priority = " . $network['settings']['codec_pcmu_priority'];
	}
	if($network['settings']['codec_pcma_enable'] == '1')
	{
		$cfg[] = "account." . $lineid . ".codec.pcma.enable = 1";
		$cfg[] = "account." . $lineid . ".codec.pcma.priority = " . $network['settings']['codec_pcma_priority'];
	}
	if($network['settings']['codec_g722_enable'] == '1')
	{
		$cfg[] = "account." . $lineid . ".codec.g722.enable = 1";
		$cfg[] = "account." . $lineid . ".codec.g722.priority = " . $network['settings']['codec_g722_priority'];
	}
	if($network['settings']['codec_g729_enable'] == '1')
	{
		$cfg[] = "account." . $lineid . ".codec.g729.enable = 1";
		$cfg[] = "account." . $lineid . ".codec.g729.priority = " . $network['settings']['codec_g729_priority'];
	}
	if($network['settings']['codec_opus_enable'] == '1')
	{
		$cfg[] = "account." . $lineid . ".codec.opus.enable = 1";
		$cfg[] = "account." . $lineid . ".codec.opus.priority = " . $network['settings']['codec_opus_priority'];
	}

	$cfg[] = "";
}

// =============================================================================
// LINE KEYS (BLF/SPEED DIAL)
// =============================================================================

$cfg[] = "## Line Keys";

// First disable all linekeys (typical max is 27 for most models)
for($i=1; $i<=50; $i++)
{
	$cfg[] = "linekey." . $i . ".type = 0";
}
$cfg[] = "";

// Configure assigned line keys
foreach($device['linekeys'] as $linekeyid => $linekey)
{
	if($linekey['type'] == '0')
		continue;

	$cfg[] = "linekey." . $linekeyid . ".type = " . $linekey['type'];
	$cfg[] = "linekey." . $linekeyid . ".line = " . $linekey['line'];
	$cfg[] = "linekey." . $linekeyid . ".value = " . $linekey['value'];
	$cfg[] = "linekey." . $linekeyid . ".label = " . $linekey['label'];

	if(!empty($linekey['extension']))
		$cfg[] = "linekey." . $linekeyid . ".extension = " . $linekey['extension'];
	if(!empty($linekey['pickup_value']))
		$cfg[] = "linekey." . $linekeyid . ".pickup_value = " . $linekey['pickup_value'];

	$cfg[] = "";
}

// =============================================================================
// OUTPUT CFG FILE
// =============================================================================

foreach($cfg as $line)
{
	echo $line . "\n";
}

?>
