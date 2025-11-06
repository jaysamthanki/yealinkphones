<?php
/* $Id */
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

// =============================================================================
// FREEPBX HOOKS
// =============================================================================

function yealinkphones_configpageinit($pagename)
{
	global $currentcomponent;

	if (isset($_REQUEST['display']) && $_REQUEST['display'] == 'devices' && isset($_REQUEST['extdisplay']))
	{
		$currentcomponent->addguifunc('yealinkphones_configpageload', 8);
	}
}

function yealinkphones_configpageload($pagename)
{
	global $currentcomponent;
	global $db;

	if($_REQUEST['extdisplay'] !== false)
	{
		$phones = sql("SELECT yealink_devices.id, yealink_devices.name, yealink_devices.mac FROM yealink_devices
			INNER JOIN yealink_device_lines ON yealink_devices.id = yealink_device_lines.id
			WHERE yealink_device_lines.deviceid = '".$db->escapeSimple($_REQUEST['extdisplay'])."'",'getAll',DB_FETCHMODE_ASSOC);

		foreach($phones as $phone)
		{
			$editURL = $_SERVER['PHP_SELF'].'?display=yealinkphones&yealinkphones_form=phones_edit&edit='.$phone['id'];
			$tlabel =  sprintf(_("Edit Yealink Phone: %s (%s)"),$phone['name'], $phone['mac']);
			$label = '<span><img width="16" height="16" border="0" title="'.$tlabel.'" alt="" src="images/telephone_edit.png"/>&nbsp;'.$tlabel.'</span>';
			$currentcomponent->addguielem('_top', new gui_link('edit_yealinkphone', $label, $editURL, true, false), 0);
		}
	}
}

function yealinkphones_get_config($engine)
{
	global $db;
	global $core_conf;

	switch ($engine) {
		case "asterisk":
			if (isset($core_conf) && is_a($core_conf, "core_conf") && (method_exists($core_conf, 'addSipNotify'))) {
				$core_conf->addSipNotify('yealink-check-cfg', array('Event' => 'check-sync', 'Content-Length' => '0'));
				$core_conf->addSipNotify('yealink-reboot', array('Event' => 'check-sync;reboot=true', 'Content-Length' => '0'));
			}
			break;
	}
}

// =============================================================================
// PHONE MANAGEMENT FUNCTIONS
// =============================================================================

function yealinkphones_get_phones_list()
{
	global $db;

	$results = sql("SELECT id, name, mac, model, firmware_version, lastconfig, lastip
		FROM yealink_devices
		ORDER BY mac",'getAll',DB_FETCHMODE_ASSOC);

	foreach($results as $key=>$result)
	{
		$results[$key]['lines'] = sql("SELECT yealink_device_lines.lineid, yealink_device_lines.deviceid,
				devices.id, devices.description, users.extension, users.name
			FROM yealink_device_lines
			LEFT OUTER JOIN devices ON devices.id = yealink_device_lines.deviceid
			LEFT OUTER JOIN users ON devices.user = users.extension
			WHERE yealink_device_lines.id = \"{$db->escapeSimple($result['id'])}\"
			ORDER BY yealink_device_lines.lineid",'getAll',DB_FETCHMODE_ASSOC);
	}

	return $results;
}

function yealinkphones_get_phones_edit($id)
{
	global $db;

	$device = sql("SELECT name, mac, model, firmware_version, lastconfig, lastip
		FROM yealink_devices WHERE id = \"{$db->escapeSimple($id)}\"", 'getRow', DB_FETCHMODE_ASSOC);

	$lines = sql("SELECT lineid, deviceid FROM yealink_device_lines
		WHERE id = \"{$db->escapeSimple($id)}\" ORDER BY lineid", 'getAll', DB_FETCHMODE_ASSOC);

	if (!is_array($device))
	{
		$device = array();
		$device["name"] = '';
		$device["mac"] = '';
		$device["model"] = '';
		$device["firmware_version"] = '';
		$device["lastconfig"] = '';
		$device["lastip"] = '';
	}

	$device['settings'] = array();
	$device['lines'] = array();

	if (is_array($lines))
	{
		foreach($lines as $line)
		{
			$device['lines'][$line['lineid']] = $line;
		}
	}

	// Get line settings
	foreach($device['lines'] as $key=>$line)
	{
		$settings = sql("SELECT keyword, value FROM yealink_device_line_settings
			WHERE id = \"{$db->escapeSimple($id)}\" AND lineid = \"{$db->escapeSimple($key)}\"",'getAll',DB_FETCHMODE_ASSOC);

		foreach($settings as $setting)
			$device['lines'][$key]['settings'][$setting['keyword']]=$setting['value'];
	}

	// Get line keys (BLF/speed dial)
	$linekeys = sql("SELECT linekeyid, type, line, value, label, extension, pickup_value
		FROM yealink_device_linekeys
		WHERE id = \"{$db->escapeSimple($id)}\"
		ORDER BY linekeyid",'getAll',DB_FETCHMODE_ASSOC);

	$device['linekeys'] = array();
	foreach($linekeys as $linekey)
	{
		$device['linekeys'][$linekey['linekeyid']] = $linekey;
	}

	// Get device settings
	$settings = sql("SELECT keyword, value FROM yealink_device_settings
		WHERE id = \"{$db->escapeSimple($id)}\"",'getAll',DB_FETCHMODE_ASSOC);

	foreach ($settings as $setting)
	{
		$device['settings'][$setting['keyword']] = $setting['value'];
	}

	return $device;
}

function yealinkphones_save_phones_edit($id, $device)
{
	global $db;

	$create = empty($id);

	if(empty($id))
	{
		sql("INSERT INTO yealink_devices (name, mac, model, firmware_version, lastconfig, lastip)
			VALUES ('".$db->escapeSimple($device['name'])."','".$db->escapeSimple($device['mac'])."','','',now(),'')");
		$id = sql("SELECT LAST_INSERT_ID()",'getOne');
	}
	else
	{
		sql("UPDATE yealink_devices SET name = '".$db->escapeSimple($device['name'])."',
			mac = '".$db->escapeSimple($device['mac'])."' WHERE id = '".$db->escapeSimple($id)."'");
	}

	// Save lines
	sql("DELETE FROM yealink_device_lines WHERE id = '".$db->escapeSimple($id)."'");
	sql("DELETE FROM yealink_device_line_settings WHERE id = '".$db->escapeSimple($id)."'");

	if(isset($device['lines']))
	{
		foreach($device['lines'] as $lineid => $line)
		{
			sql("INSERT INTO yealink_device_lines (id, lineid, deviceid)
				VALUES ('".$db->escapeSimple($id)."','".$db->escapeSimple($lineid)."',".
					($line['deviceid'] != null ? "'".$db->escapeSimple($line['deviceid'])."'" : 'NULL') .")");

			if(isset($line['settings']))
			{
				$entries = array();
				foreach ($line['settings'] as $key => $val)
					$entries[] = '\''.$db->escapeSimple($id).'\',\''.$db->escapeSimple($lineid).'\',\''.
						$db->escapeSimple($key).'\',\''.$db->escapeSimple($val).'\'';

				if(count($entries) > 0)
					sql("INSERT INTO yealink_device_line_settings (id, lineid, keyword, value)
						VALUES (" . implode('),(', $entries) . ")");
			}
		}
	}

	// Save line keys
	sql("DELETE FROM yealink_device_linekeys WHERE id = '".$db->escapeSimple($id)."'");

	if(isset($device['linekeys']))
	{
		foreach($device['linekeys'] as $linekeyid => $linekey)
		{
			sql("INSERT INTO yealink_device_linekeys (id, linekeyid, type, line, value, label, extension, pickup_value)
				VALUES ('".$db->escapeSimple($id)."','".$db->escapeSimple($linekeyid)."','".
					$db->escapeSimple($linekey['type'])."','".$db->escapeSimple($linekey['line'])."','".
					$db->escapeSimple($linekey['value'])."','".$db->escapeSimple($linekey['label'])."','".
					$db->escapeSimple($linekey['extension'])."','".$db->escapeSimple($linekey['pickup_value'])."')");
		}
	}

	// Save device settings
	if(isset($device['settings']))
	{
		$entries = array();
		foreach ($device['settings'] as $key => $val)
			$entries[] = '\''.$db->escapeSimple($id).'\',\''.$db->escapeSimple($key).'\',\''.$db->escapeSimple($val).'\'';

		if(count($entries) > 0)
			sql("REPLACE INTO yealink_device_settings (id, keyword, value)
				VALUES (" . implode('),(', $entries) . ")");
	}
}

function yealinkphones_delete_phones_list($id)
{
	global $db, $amp_conf;

	$mac = sql("SELECT mac FROM yealink_devices WHERE id = '" . $db->escapeSimple($id) . "'",'getOne');

	sql("DELETE FROM yealink_devices WHERE id = '".$db->escapeSimple($id)."'");
	sql("DELETE FROM yealink_device_settings WHERE id = '".$db->escapeSimple($id)."'");
	sql("DELETE FROM yealink_device_lines WHERE id = '".$db->escapeSimple($id)."'");
	sql("DELETE FROM yealink_device_line_settings WHERE id = '".$db->escapeSimple($id)."'");
	sql("DELETE FROM yealink_device_linekeys WHERE id = '".$db->escapeSimple($id)."'");

	if(!empty($mac))
	{
		$path = $amp_conf['AMPWEBROOT'] . '/admin/modules/_yealink_software/';

		foreach(array('logs', 'configs', 'contacts') as $folder)
			foreach (glob($path . $folder . '/' . $mac . "*") as $filename)
				unlink($filename);
	}
}

function yealinkphones_lookup_deviceid($id)
{
	global $db;

	return sql("SELECT MIN(deviceid) AS deviceid FROM `yealink_device_lines`
		WHERE deviceid IS NOT NULL AND id = '".$id."' GROUP BY id",'getOne');
}

// =============================================================================
// NETWORK MANAGEMENT FUNCTIONS
// =============================================================================

function yealinkphones_get_networks_list()
{
	global $db;

	$results = sql("SELECT id, name, cidr FROM yealink_networks ORDER BY cidr",'getAll',DB_FETCHMODE_ASSOC);

	return $results;
}

function yealinkphones_get_networks_edit($id)
{
	global $db;

	$network = sql("SELECT name, cidr FROM yealink_networks
		WHERE id = \"{$db->escapeSimple($id)}\"",'getRow',DB_FETCHMODE_ASSOC);

	$settings = sql("SELECT keyword, value FROM yealink_network_settings
		WHERE id = \"{$db->escapeSimple($id)}\"",'getAll',DB_FETCHMODE_ASSOC);

	if (!is_array($network))
	{
		$network = array();
		$network["name"] = '';
		$network["cidr"] = '';
	}

	// Default network settings
	$network['settings'] = array(
		'prov_protocol' => 'HTTPS',
		'prov_username' => 'yealink',
		'prov_password' => 'yealink',
		'sip_server_address' => '',
		'sip_server_port' => '5060',
		'sip_server_transport' => '0',
		'sip_server_expires' => '3600',
		'nat_keepalive_interval' => '30',
		'ntp_server1' => 'pool.ntp.org',
		'ntp_server2' => '',
		'time_zone' => '-5',
		'time_zone_name' => 'US-Eastern',
		'codec_pcmu_enable' => '1',
		'codec_pcmu_priority' => '1',
		'codec_pcma_enable' => '1',
		'codec_pcma_priority' => '2',
		'codec_g722_enable' => '1',
		'codec_g722_priority' => '3',
		'codec_g729_enable' => '0',
		'codec_g729_priority' => '0',
		'codec_opus_enable' => '0',
		'codec_opus_priority' => '0',
	);

	foreach($settings as $setting)
		$network['settings'][$setting['keyword']]=$setting['value'];

	return $network;
}

function yealinkphones_save_networks_edit($id, $network)
{
	global $db;

	if(empty($id))
	{
		sql("INSERT INTO yealink_networks (name, cidr)
			VALUES ('".$db->escapeSimple($network['name'])."', '".$db->escapeSimple($network['cidr'])."')");
		$results = sql("SELECT LAST_INSERT_ID()",'getAll',DB_FETCHMODE_ASSOC);

		if(count($results) > 0)
			$id = $results[0]['LAST_INSERT_ID()'];
		else
			die_freepbx('Unable to determine SQL insert id');
	}
	else
	{
		sql("UPDATE yealink_networks SET
			name = '".$db->escapeSimple($network['name'])."',
			cidr = '".$db->escapeSimple($network['cidr'])."'
		WHERE id = '".$db->escapeSimple($id)."'");
	}

	$entries = array();
	foreach ($network['settings'] as $key => $val)
		$entries[] = '\''.$db->escapeSimple($id).'\',\''.$db->escapeSimple($key).'\',\''.$db->escapeSimple($val).'\'';

	if(count($entries) > 0)
		sql("REPLACE INTO yealink_network_settings (id, keyword, value)
			VALUES (" . implode('),(', $entries) . ")");
}

function yealinkphones_delete_networks_list($id)
{
	global $db;

	sql("DELETE FROM yealink_networks WHERE id = '".$db->escapeSimple($id)."'");
	sql("DELETE FROM yealink_network_settings WHERE id = '".$db->escapeSimple($id)."'");
}

function yealinkphones_cidr_ip_check ($ip, $cidr)
{
	list ($net, $mask) = explode ("/", $cidr);

	$ip_net = ip2long ($net);
	$ip_mask = ~((1 << (32 - $mask)) - 1);

	$ip_ip = ip2long ($ip);

	$ip_ip_net = $ip_ip & $ip_mask;

	return ($ip_ip_net == $ip_net);
}

function yealinkphones_get_networks_ip($ip)
{
	global $db;

	$results = sql("SELECT id, cidr FROM yealink_networks ORDER BY cidr DESC",'getAll',DB_FETCHMODE_ASSOC);

	foreach($results as $result)
	{
		if (yealinkphones_cidr_ip_check($ip, $result['cidr']))
		{
			return yealinkphones_get_networks_edit($result['id']);
		}
	}

	return null;
}

function yealinkphones_check_network($network)
{
	if(empty($network))
	{
		yealinkphones_send_forbidden();
	}

	// Check if SSL is required
	if ($network['settings']['prov_protocol'] == 'HTTPS' && empty($_SERVER['HTTPS']))
	{
		yealinkphones_send_forbidden();
	}

	// Network has authentication disabled
	if(empty($network['settings']['prov_username']))
	{
		return;
	}

	if (!isset($_SERVER['PHP_AUTH_USER']))
	{
		yealinkphones_send_unauthorized();
	}

	if($_SERVER['PHP_AUTH_USER'] != $network['settings']['prov_username'] ||
	   $_SERVER['PHP_AUTH_PW'] != $network['settings']['prov_password'])
	{
		yealinkphones_send_unauthorized();
	}
}

function yealinkphones_send_unauthorized()
{
	header('WWW-Authenticate: Basic realm="Yealink Provisioning"');
	header('HTTP/1.0 401 Unauthorized');
	yealinkphones_send_error('401 Unauthorized', 'Authentication is required to view this page.');
}

function yealinkphones_send_forbidden()
{
	header('HTTP/1.0 403 Forbidden');
	yealinkphones_send_error('403 Forbidden', 'Access is denied');
}

function yealinkphones_send_error($title, $message)
{
	echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>' . $title . '</title>
</head><body>
<h1>' . $title . '</h1>
<p>' . $message . '</p>
</body></html>';
	exit;
}

// =============================================================================
// GENERAL SETTINGS FUNCTIONS
// =============================================================================

function yealinkphones_get_general_edit()
{
	global $db;

	$results = sql("SELECT keyword, value FROM yealink_settings",'getAll',DB_FETCHMODE_ASSOC);

	$settings = array();
	foreach($results as $result)
		$settings[$result['keyword']]=$result['value'];

	return $settings;
}

function yealinkphones_save_general_edit($settings)
{
	global $db;

	$entries = array();
	foreach ($settings as $key => $val)
		$entries[] = '\''.$db->escapeSimple($key).'\',\''.$db->escapeSimple($val).'\'';

	sql("REPLACE INTO yealink_settings (keyword, value)
		VALUES (" . implode('),(', $entries) . ")");
}

// =============================================================================
// SIP NOTIFY FUNCTIONS
// =============================================================================

function yealinkphones_notify_checkconfig($id = null)
{
	global $db, $astman;

	$id_array = is_array($id) ? $id : array($id);

	if(empty($id))
	{
		$results = sql("SELECT MIN(deviceid) AS deviceid FROM `yealink_device_lines`
			WHERE deviceid IS NOT NULL GROUP BY id",'getAll',DB_FETCHMODE_ASSOC);
	}
	else
	{
		$escaped = array();
		foreach($id_array as $single_id)
			$escaped[] = $db->escapeSimple($single_id);

		$results = sql("SELECT MIN(deviceid) AS deviceid FROM `yealink_device_lines`
			WHERE deviceid IS NOT NULL AND id IN ('".implode("','", $escaped)."')
			GROUP BY id",'getAll',DB_FETCHMODE_ASSOC);
	}

	foreach($results as $result)
	{
		// Try pjsip first, then chan_sip
		$astman->send_request('Command', array('Command' => 'pjsip send notify yealink-check-cfg endpoint '.$result['deviceid']));
		$astman->send_request('Command', array('Command' => 'sip notify yealink-check-cfg '.$result['deviceid']));
	}
}

function yealinkphones_notify_checkconfig_deviceid($deviceid)
{
	global $astman;

	$astman->send_request('Command', array('Command' => 'pjsip send notify yealink-check-cfg endpoint '.$deviceid));
	$astman->send_request('Command', array('Command' => 'sip notify yealink-check-cfg '.$deviceid));
}

// =============================================================================
// DROPDOWN HELPERS
// =============================================================================

function yealinkphones_dropdown_lines($id)
{
	global $db;

	$dropdown = array('' => '');
	$lines = array();

	$results = sql("SELECT devices.id, devices.description, users.extension, users.name FROM devices
		LEFT OUTER JOIN users on devices.user = users.extension
		WHERE tech IN ('sip', 'pjsip') ORDER BY devices.id",'getAll',DB_FETCHMODE_ASSOC);

	foreach($results as $result)
		$lines[$result['id']]=$result['id'] .
			(!empty($result['extension']) ? ': '.$result['name'].' <'.$result['extension'].'>' :
			(!empty($result['description']) ? ': '.$result['description'] : ''));

	if(count($lines) > 0)
		$dropdown['FreePBX Lines'] = $lines;

	return $dropdown;
}

function yealinkphones_dropdown_linekey_types()
{
	return array(
		'0' => 'N/A (Disabled)',
		'15' => 'Line',
		'16' => 'BLF',
		'13' => 'Speed Dial',
		'11' => 'DTMF',
		'14' => 'Intercom',
		'10' => 'Call Park',
		'27' => 'Group Pickup',
	);
}

function yealinkphones_dropdown($id, $default = false, $defaultvalue = 'Default')
{
	$dropdowns['transport'] = array(
		'0' => 'UDP',
		'1' => 'TCP',
		'2' => 'TLS',
	);

	$dropdowns['protocol'] = array(
		'HTTP' => 'HTTP',
		'HTTPS' => 'HTTPS',
	);

	$dropdowns['enabled_disabled'] = array(
		'1' => 'Enabled',
		'0' => 'Disabled',
	);

	return $default ? array(''=>$defaultvalue) + $dropdowns[$id] : $dropdowns[$id];
}

// =============================================================================
// UTILITY FUNCTIONS
// =============================================================================

function yealinkphones_array_escape($values)
{
	global $db;

	if($values == null)
		return array();

	if(!is_array($values))
		$values = array($values);

	$escaped = array();
	foreach($values as $value)
		$escaped[] = $db->escapeSimple($value);

	return $escaped;
}

function yealinkphones_lookup_mac($mac)
{
	global $db;

	return sql("SELECT id FROM yealink_devices WHERE mac = '" . $db->escapeSimple($mac) . "'",'getOne');
}

function yealinkphones_detect_model($user_agent)
{
	// Parse User-Agent: "Yealink SIP-T33G 124.86.0.118 24:9a:d8:1e:83:fa"
	// Extract model number from SIP-T[model][letter]
	if (preg_match('/SIP-T(\d+)[A-Z]?\s/', $user_agent, $matches)) {
		return $matches[1]; // Return "33" for T33G, "46" for T46S, etc.
	}
	return '00'; // Generic fallback
}

?>
