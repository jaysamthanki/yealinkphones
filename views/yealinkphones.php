<?php
/* Main Menu View */
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
?>

<h2><?php echo _("Yealink Phones"); ?></h2>

<div class="well well-sm">
	<p><?php echo _("Configure and manage Yealink SIP phones on your FreePBX system."); ?></p>
</div>

<div class="list-group">
	<a href="?display=yealinkphones&yealinkphones_form=phones_list" class="list-group-item">
		<h4 class="list-group-item-heading"><?php echo _("Phones"); ?></h4>
		<p class="list-group-item-text"><?php echo _("Manage phone inventory, assign lines, and configure BLF/speed dial buttons"); ?></p>
	</a>

	<a href="?display=yealinkphones&yealinkphones_form=networks_list" class="list-group-item">
		<h4 class="list-group-item-heading"><?php echo _("Networks"); ?></h4>
		<p class="list-group-item-text"><?php echo _("Configure network-specific settings by IP range (SIP server, codecs, NTP, provisioning authentication)"); ?></p>
	</a>

	<a href="?display=yealinkphones&yealinkphones_form=general_edit" class="list-group-item">
		<h4 class="list-group-item-heading"><?php echo _("General Settings"); ?></h4>
		<p class="list-group-item-text"><?php echo _("Global default settings for all phones"); ?></p>
	</a>
</div>

<div class="alert alert-info">
	<strong><?php echo _("Provisioning URL:"); ?></strong> https://<?php echo $_SERVER['SERVER_NAME']; ?>/yealink<br>
	<strong><?php echo _("DHCP Option 66:"); ?></strong> https://<?php echo $_SERVER['SERVER_NAME']; ?>/yealink
</div>
