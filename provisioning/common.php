<?php
/**
 * Yealink Common Configuration Generator
 * URL: /yealink/common.php?model={XX}
 * Returns: y0000000000{XX}.cfg
 * This file contains model-specific defaults shared by all phones of the same model
 */

$bootstrap_settings['freepbx_auth'] = false;
$bootstrap_settings['skip_astman'] = true;

if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf'))
{
	include_once('/etc/asterisk/freepbx.conf');
}

// Get model number from URL parameter
$model = isset($_GET['model']) ? $_GET['model'] : '00';

// Ensure model is numeric and safe
if (!preg_match('/^[0-9]{2,3}$/', $model))
{
	$model = '00';
}

// Lookup IP to determine if authentication or SSL is required
$network = yealinkphones_get_networks_ip($_SERVER['REMOTE_ADDR']);
yealinkphones_check_network($network);

// Set content type
header('Content-Type: text/plain');

// =============================================================================
// GENERATE COMMON CFG FILE
// =============================================================================

$cfg = array();

// Header
$cfg[] = "#!version:1.0.0.1";
$cfg[] = "";
$cfg[] = "# Yealink Common Configuration";
$cfg[] = "# Model: T" . $model;
$cfg[] = "# Generated: " . date('Y-m-d H:i:s');
$cfg[] = "";

// =============================================================================
// GLOBAL FEATURES
// =============================================================================

$cfg[] = "## Features";
$cfg[] = "features.enhanced_dss_keys.enable = 1";
$cfg[] = "features.n_way_conference.enable = 1";
$cfg[] = "features.url_dialing.enable = 0";
$cfg[] = "";

// =============================================================================
// VOICE SETTINGS
// =============================================================================

$cfg[] = "## Voice";
$cfg[] = "voice.vad.enable = 1";
$cfg[] = "voice.cng.enable = 1";
$cfg[] = "voice.echo_cancellation = 1";
$cfg[] = "voice.jitter_buffer.adaptive = 1";
$cfg[] = "voice.jitter_buffer.min = 50";
$cfg[] = "voice.jitter_buffer.max = 300";
$cfg[] = "";

// =============================================================================
// CALL FEATURES
// =============================================================================

$cfg[] = "## Call Features";
$cfg[] = "call.enable_on_not_registered = 0";
$cfg[] = "";

// =============================================================================
// OUTPUT CFG FILE
// =============================================================================

foreach($cfg as $line)
{
	echo $line . "\n";
}

?>
