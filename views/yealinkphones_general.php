<?php
/* General Settings View */
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
?>

<h2><?php echo _("General Settings"); ?></h2>

<form method="post" action="?display=yealinkphones&yealinkphones_form=general_edit">
	<input type="hidden" name="action" value="edit">

	<!-- Auto-Provisioning Settings -->
	<h3><?php echo _("Auto-Provisioning"); ?></h3>

	<div class="form-group">
		<label><?php echo _("Auto-Provision Repeat Interval (minutes)"); ?></label>
		<input type="number" name="auto_provision_repeat_minutes" class="form-control"
		       value="<?php echo htmlspecialchars($general['auto_provision_repeat_minutes']); ?>" required>
		<span class="help-block"><?php echo _("How often phones should check for config updates (default: 1440 = 24 hours)"); ?></span>
	</div>

	<!-- Phone Security Settings -->
	<h3><?php echo _("Phone Security"); ?></h3>

	<div class="form-group">
		<label><?php echo _("User Password"); ?></label>
		<input type="text" name="device_user_password" class="form-control"
		       value="<?php echo htmlspecialchars($general['device_user_password']); ?>" required>
		<span class="help-block"><?php echo _("Default password for 'user' access level on phone web interface"); ?></span>
	</div>

	<div class="form-group">
		<label><?php echo _("Admin Password"); ?></label>
		<input type="text" name="device_admin_password" class="form-control"
		       value="<?php echo htmlspecialchars($general['device_admin_password']); ?>" required>
		<span class="help-block"><?php echo _("Default password for 'admin' access level on phone web interface"); ?></span>
	</div>

	<div class="form-group">
		<label><?php echo _("Trust All Certificates"); ?></label>
		<select name="security_trust_certificates" class="form-control">
			<option value="0"<?php echo ($general['security_trust_certificates']=='0'?' selected':''); ?>>No (Validate certificates)</option>
			<option value="1"<?php echo ($general['security_trust_certificates']=='1'?' selected':''); ?>>Yes (Accept all)</option>
		</select>
		<span class="help-block"><?php echo _("For HTTPS provisioning with self-signed certificates"); ?></span>
	</div>

	<!-- Default Phone Settings -->
	<h3><?php echo _("Default Phone Settings"); ?></h3>

	<div class="form-group">
		<label><?php echo _("Default Backlight Timeout (seconds)"); ?></label>
		<input type="number" name="default_backlight_time" class="form-control"
		       value="<?php echo htmlspecialchars($general['default_backlight_time']); ?>">
	</div>

	<div class="form-group">
		<label><?php echo _("Default Language"); ?></label>
		<select name="default_lang" class="form-control">
			<option value="English"<?php echo ($general['default_lang']=='English'?' selected':''); ?>>English</option>
			<option value="Spanish"<?php echo ($general['default_lang']=='Spanish'?' selected':''); ?>>Spanish</option>
			<option value="French"<?php echo ($general['default_lang']=='French'?' selected':''); ?>>French</option>
			<option value="German"<?php echo ($general['default_lang']=='German'?' selected':''); ?>>German</option>
			<option value="Italian"<?php echo ($general['default_lang']=='Italian'?' selected':''); ?>>Italian</option>
			<option value="Portuguese"<?php echo ($general['default_lang']=='Portuguese'?' selected':''); ?>>Portuguese</option>
		</select>
	</div>

	<!-- Default Time Settings -->
	<h3><?php echo _("Default Time Settings"); ?></h3>

	<div class="form-group">
		<label><?php echo _("Default NTP Server"); ?></label>
		<input type="text" name="default_ntp_server" class="form-control"
		       value="<?php echo htmlspecialchars($general['default_ntp_server']); ?>">
	</div>

	<div class="form-group">
		<label><?php echo _("Default Time Zone Offset"); ?></label>
		<input type="number" name="default_time_zone" class="form-control"
		       value="<?php echo htmlspecialchars($general['default_time_zone']); ?>">
		<span class="help-block"><?php echo _("Hours offset from GMT (e.g., -5 for EST)"); ?></span>
	</div>

	<div class="form-group">
		<label><?php echo _("Default Time Zone Name"); ?></label>
		<input type="text" name="default_time_zone_name" class="form-control"
		       value="<?php echo htmlspecialchars($general['default_time_zone_name']); ?>">
	</div>

	<!-- Form Actions -->
	<div class="form-group">
		<button type="submit" class="btn btn-primary">
			<i class="fa fa-save"></i> <?php echo _("Save Settings"); ?>
		</button>
		<a href="?display=yealinkphones" class="btn btn-default">
			<i class="fa fa-times"></i> <?php echo _("Cancel"); ?>
		</a>
	</div>
</form>
