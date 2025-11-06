<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

global $db;

// =============================================================================
// GLOBAL SETTINGS TABLE
// =============================================================================

$sql[]='CREATE TABLE IF NOT EXISTS `yealink_settings` (
  `keyword` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`keyword`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;';

$sql[]="INSERT IGNORE INTO `yealink_settings` (`keyword`, `value`) VALUES
('auto_provision_repeat_minutes', '1440'),
('device_user_password', 'user'),
('device_admin_password', 'admin'),
('default_backlight_time', '60'),
('default_lang', 'English'),
('default_time_zone', '-5'),
('default_time_zone_name', 'US-Eastern'),
('default_ntp_server', 'pool.ntp.org'),
('security_trust_certificates', '0');";

// =============================================================================
// NETWORK CONFIGURATION TABLES
// =============================================================================

$sql[]='CREATE TABLE IF NOT EXISTS `yealink_networks` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `cidr` varchar(18) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cidr` (`cidr`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;';

$sql[]="INSERT IGNORE INTO `yealink_networks` (`id`, `name`, `cidr`) VALUES
('-1', 'Default Network', '0.0.0.0/0');";

$sql[]='CREATE TABLE IF NOT EXISTS `yealink_network_settings` (
  `id` int(11) NOT NULL,
  `keyword` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`,`keyword`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;';

$sql[]="INSERT IGNORE INTO yealink_network_settings (id, keyword, value) VALUES
('-1', 'prov_protocol', 'HTTPS'),
('-1', 'prov_username', 'yealink'),
('-1', 'prov_password', 'yealink'),
('-1', 'sip_server_address', '" . $db->escapeSimple($_SERVER['SERVER_NAME']) . "'),
('-1', 'sip_server_port', '5060'),
('-1', 'sip_server_transport', '0'),
('-1', 'sip_server_expires', '3600'),
('-1', 'nat_keepalive_interval', '30'),
('-1', 'ntp_server1', 'pool.ntp.org'),
('-1', 'ntp_server2', ''),
('-1', 'time_zone', '-5'),
('-1', 'time_zone_name', 'US-Eastern'),
('-1', 'codec_pcmu_enable', '1'),
('-1', 'codec_pcmu_priority', '1'),
('-1', 'codec_pcma_enable', '1'),
('-1', 'codec_pcma_priority', '2'),
('-1', 'codec_g722_enable', '1'),
('-1', 'codec_g722_priority', '3'),
('-1', 'codec_g729_enable', '0'),
('-1', 'codec_g729_priority', '0'),
('-1', 'codec_opus_enable', '0'),
('-1', 'codec_opus_priority', '0');";

// =============================================================================
// DEVICE (PHONE) TABLES
// =============================================================================

$sql[]='CREATE TABLE IF NOT EXISTS `yealink_devices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `mac` varchar(12) NOT NULL,
  `model` varchar(30) NOT NULL,
  `firmware_version` varchar(30) NOT NULL,
  `lastconfig` datetime NOT NULL,
  `lastip` varchar(15) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mac` (`mac`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;';

$sql[]='CREATE TABLE IF NOT EXISTS `yealink_device_settings` (
  `id` int(11) NOT NULL,
  `keyword` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`,`keyword`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;';

$sql[]='CREATE TABLE IF NOT EXISTS `yealink_device_lines` (
  `id` int(11) NOT NULL,
  `lineid` int(11) NOT NULL,
  `deviceid` int(11) NULL,
  PRIMARY KEY (`id`,`lineid`),
  KEY `deviceid` (`deviceid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;';

$sql[]='CREATE TABLE IF NOT EXISTS `yealink_device_line_settings` (
  `id` int(11) NOT NULL,
  `lineid` int(11) NOT NULL,
  `keyword` varchar(30) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`,`lineid`,`keyword`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;';

$sql[]='CREATE TABLE IF NOT EXISTS `yealink_device_linekeys` (
  `id` int(11) NOT NULL,
  `linekeyid` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `line` int(11) NOT NULL,
  `value` varchar(100) NOT NULL,
  `label` varchar(30) NOT NULL,
  `extension` varchar(20) NOT NULL,
  `pickup_value` varchar(20) NOT NULL,
  PRIMARY KEY (`id`,`linekeyid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;';

// Execute all SQL statements
foreach ($sql as $statement){
	$check = $db->query($statement);
	if (DB::IsError($check)){
		die_freepbx( "Can not execute $statement : " . $check->getMessage() .  "\n");
	}
}

// =============================================================================
// DIRECTORY STRUCTURE & SYMLINKS
// =============================================================================

define("LOCAL_PATH", $amp_conf['AMPWEBROOT'] . '/admin/modules/yealinkphones/');
define("SOFTWARE_PATH", $amp_conf['AMPWEBROOT'] . '/admin/modules/_yealink_software/');
define("PROVISIONING_PATH", $amp_conf['AMPWEBROOT'] . '/yealink');

// Link module assets to FreePBX assets folder
if(!is_link($amp_conf['AMPWEBROOT'] . "/admin/assets/yealinkphones"))
{
	out('Creating symlink to assets');
	if (!symlink(LOCAL_PATH . "assets", $amp_conf['AMPWEBROOT'] . "/admin/assets/yealinkphones")) {
		out("<strong>Your permissions are wrong on " . $amp_conf['AMPWEBROOT'] . ", web assets link not created!</strong>");
	}
}

// Create directory for phone software/configs
foreach(array('', 'logs', 'configs', 'contacts') as $folder)
{
	if(!file_exists(SOFTWARE_PATH.$folder))
	{
		out("Creating Yealink software " . (empty($folder) ? 'root' : $folder) . " directory");
		if(!mkdir(SOFTWARE_PATH.$folder, 0775)) {
			out("<strong>Your permissions are wrong on " . $amp_conf['AMPWEBROOT'] . ", Yealink software directory not created!</strong>");
		}
	}
}

// Remove old link if it exists
if(is_link(PROVISIONING_PATH))
{
	if(readlink(PROVISIONING_PATH) != SOFTWARE_PATH) {
		out("Removing old symlink to web provisioner");
		if(!unlink(PROVISIONING_PATH)) {
			out("<strong>Your permissions are wrong on " . $amp_conf['AMPWEBROOT'] . ", unable to remove previous web provisioning link!</strong>");
		}
	}
}

// Remove all old file links in software folder
foreach(scandir(SOFTWARE_PATH) as $item)
{
	if(is_file(SOFTWARE_PATH . $item) && is_link(SOFTWARE_PATH . $item)) {
		if(!unlink(SOFTWARE_PATH . $item)) {
			out("<strong>Your permissions are wrong on " . $amp_conf['AMPWEBROOT'] . ", unable to remove web provisioning file link!</strong>");
		}
	}
}

// Link provisioning files to software folder
foreach(scandir(LOCAL_PATH . "provisioning/") as $item)
{
	if(is_file(LOCAL_PATH . "provisioning/" . $item) && $item != 'index.html') {
		if (!symlink(LOCAL_PATH . "provisioning/" . $item, SOFTWARE_PATH . $item)) {
			out("<strong>Your permissions are wrong on " . $amp_conf['AMPWEBROOT'] . ", web provisioning file link not created!</strong>");
		}
	}
}

// Link software folder to provisioning path
if(!is_link(PROVISIONING_PATH))
{
	out('Creating symlink to web provisioner');
	if (!symlink(SOFTWARE_PATH, PROVISIONING_PATH)) {
		out("<strong>Your permissions are wrong on " . $amp_conf['AMPWEBROOT'] . ", web provisioning link not created!</strong>");
	}
}

?>
