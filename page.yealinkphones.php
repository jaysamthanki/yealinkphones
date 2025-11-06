<?php
/* $Id */
/*
 * Yealink Phones Module for FreePBX
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

if (isset($_GET['yealinkphones_form']))
{
	$yealinkphonesForm = $_GET['yealinkphones_form'];
}
else
{
	$yealinkphonesForm = null;
}

echo '<div class="container-fluid">
<div class="row">
<div class="col-sm-10">';

switch($yealinkphonesForm)
{
	// =========================================================================
	// PHONE MANAGEMENT
	// =========================================================================

	case 'phones_list':
		if(isset($_GET['delete']))
		{
			yealinkphones_delete_phones_list($_GET['delete']);
			redirect_standard('yealinkphones_form');
		}

		if(isset($_GET['notify']))
		{
			yealinkphones_notify_checkconfig(!empty($_GET['notify']) ? $_GET['notify'] : null);
			redirect_standard('yealinkphones_form');
		}

		$devices = yealinkphones_get_phones_list();
		require 'modules/yealinkphones/views/yealinkphones_phones.php';
		break;

	case 'phones_edit':
		if(isset($_POST['action']) && $_POST['action'] == 'edit')
		{
			$prevdeviceid = yealinkphones_lookup_deviceid($_GET['edit']);

			$device['name'] = $_POST['name'];
			$device['mac'] = strtoupper($_POST['mac']);

			$device['lines'] = array();

			if(isset($_POST['line']))
			{
				foreach($_POST['line'] as $key=>$value)
				{
					$key++;
					$device['lines'][$key]['deviceid'] = null;

					if(!empty($value))
						$device['lines'][$key]['deviceid'] = $value;
				}

				// Line settings
				$fields = array('label');

				foreach ($fields as $field)
				{
					if(isset($_POST[$field]))
					{
						foreach($_POST[$field] as $key=>$value)
						{
							$key++;
							if($device['lines'][$key])
								$device['lines'][$key]['settings'][$field] = $value;
						}
					}
				}
			}

			// Line keys
			$device['linekeys'] = array();
			if(isset($_POST['linekey_type']))
			{
				foreach($_POST['linekey_type'] as $key=>$value)
				{
					$key++;

					$device['linekeys'][$key]['type'] = $value;
					$device['linekeys'][$key]['line'] = isset($_POST['linekey_line'][$key-1]) ? $_POST['linekey_line'][$key-1] : '1';
					$device['linekeys'][$key]['value'] = isset($_POST['linekey_value'][$key-1]) ? $_POST['linekey_value'][$key-1] : '';
					$device['linekeys'][$key]['label'] = isset($_POST['linekey_label'][$key-1]) ? $_POST['linekey_label'][$key-1] : '';
					$device['linekeys'][$key]['extension'] = isset($_POST['linekey_extension'][$key-1]) ? $_POST['linekey_extension'][$key-1] : '';
					$device['linekeys'][$key]['pickup_value'] = isset($_POST['linekey_pickup'][$key-1]) ? $_POST['linekey_pickup'][$key-1] : '';
				}
			}

			// Device settings
			$device['settings'] = array();

			yealinkphones_save_phones_edit($_GET['edit'], $device);

			// Trigger SIP NOTIFY to reload config
			yealinkphones_notify_checkconfig_deviceid($prevdeviceid);

			redirect('config.php?type=setup&display=yealinkphones&yealinkphones_form=phones_list');
		}

		$device = yealinkphones_get_phones_edit($_GET['edit']);

		foreach ($device['lines'] as $key => $line)
		{
			if ($line['deviceid'] != null)
			{
				$device['lines'][$key]['line'] = $line['deviceid'];
			}
		}

		require 'modules/yealinkphones/views/yealinkphones_phones_edit.php';
		break;

	// =========================================================================
	// NETWORK MANAGEMENT
	// =========================================================================

	case 'networks_list':
		if(isset($_GET['delete']))
		{
			yealinkphones_delete_networks_list($_GET['delete']);
			redirect_standard('yealinkphones_form');
		}

		$networks = yealinkphones_get_networks_list();
		require 'modules/yealinkphones/views/yealinkphones_networks.php';
		break;

	case 'networks_edit':
		if(isset($_POST['action']) && $_POST['action'] == 'edit')
		{
			$network['name'] = $_POST['name'];
			$network['cidr'] = $_POST['cidr'];

			$fields = array(
				'prov_protocol',
				'prov_username',
				'prov_password',
				'sip_server_address',
				'sip_server_port',
				'sip_server_transport',
				'sip_server_expires',
				'nat_keepalive_interval',
				'ntp_server1',
				'ntp_server2',
				'time_zone',
				'time_zone_name',
				'codec_pcmu_enable',
				'codec_pcmu_priority',
				'codec_pcma_enable',
				'codec_pcma_priority',
				'codec_g722_enable',
				'codec_g722_priority',
				'codec_g729_enable',
				'codec_g729_priority',
				'codec_opus_enable',
				'codec_opus_priority',
			);

			foreach ($fields as $field)
			{
				$network['settings'][$field] = $_POST[$field];
			}

			yealinkphones_save_networks_edit($_GET['edit'], $network);
			redirect('config.php?type=setup&display=yealinkphones&yealinkphones_form=networks_list');
		}

		$network = yealinkphones_get_networks_edit($_GET['edit']);

		require 'modules/yealinkphones/views/yealinkphones_networks_edit.php';
		break;

	// =========================================================================
	// GENERAL SETTINGS
	// =========================================================================

	case 'general_edit':
		if(isset($_POST['action']) && $_POST['action'] == 'edit')
		{
			$fields = array(
				'auto_provision_repeat_minutes',
				'device_user_password',
				'device_admin_password',
				'default_backlight_time',
				'default_lang',
				'default_time_zone',
				'default_time_zone_name',
				'default_ntp_server',
				'security_trust_certificates',
			);

			$settings = array();
			foreach ($fields as $field)
				$settings[$field] = $_POST[$field];

			yealinkphones_save_general_edit($settings);
			redirect_standard('yealinkphones_form');
		}

		$general = yealinkphones_get_general_edit();
		require 'modules/yealinkphones/views/yealinkphones_general.php';
		break;

	// =========================================================================
	// MAIN MENU
	// =========================================================================

	default:
		require 'modules/yealinkphones/views/yealinkphones.php';
		break;
}

echo '</div>';
include('views/rnav.php');
echo '</div></div>';

?>
