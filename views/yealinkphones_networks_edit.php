<?php
/* Network Edit View */
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

$isNew = empty($_GET['edit']);
?>

<h2><?php echo $isNew ? _("Add Network") : _("Edit Network"); ?></h2>

<form method="post" action="?display=yealinkphones&yealinkphones_form=networks_edit&edit=<?php echo $_GET['edit']; ?>">
	<input type="hidden" name="action" value="edit">

	<!-- Network Information -->
	<h3><?php echo _("Network Information"); ?></h3>

	<div class="form-group">
		<label><?php echo _("Network Name"); ?></label>
		<input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($network['name']); ?>" required>
	</div>

	<div class="form-group">
		<label><?php echo _("CIDR Range"); ?></label>
		<input type="text" name="cidr" class="form-control" value="<?php echo htmlspecialchars($network['cidr']); ?>"
		       placeholder="192.168.1.0/24" required>
		<span class="help-block"><?php echo _("IP range in CIDR notation (e.g., 192.168.1.0/24)"); ?></span>
	</div>

	<!-- Provisioning Settings -->
	<h3><?php echo _("Provisioning Settings"); ?></h3>

	<div class="form-group">
		<label><?php echo _("Protocol"); ?></label>
		<select name="prov_protocol" class="form-control">
			<?php foreach(yealinkphones_dropdown('protocol') as $key => $value): ?>
				<option value="<?php echo $key; ?>"<?php echo ($network['settings']['prov_protocol']==$key?' selected':''); ?>>
					<?php echo $value; ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>

	<div class="form-group">
		<label><?php echo _("Username"); ?></label>
		<input type="text" name="prov_username" class="form-control" value="<?php echo htmlspecialchars($network['settings']['prov_username']); ?>">
		<span class="help-block"><?php echo _("Leave blank to disable authentication"); ?></span>
	</div>

	<div class="form-group">
		<label><?php echo _("Password"); ?></label>
		<input type="text" name="prov_password" class="form-control" value="<?php echo htmlspecialchars($network['settings']['prov_password']); ?>">
	</div>

	<!-- SIP Server Settings -->
	<h3><?php echo _("SIP Server Settings"); ?></h3>

	<div class="form-group">
		<label><?php echo _("SIP Server Address"); ?></label>
		<input type="text" name="sip_server_address" class="form-control" value="<?php echo htmlspecialchars($network['settings']['sip_server_address']); ?>" required>
	</div>

	<div class="form-group">
		<label><?php echo _("SIP Server Port"); ?></label>
		<input type="number" name="sip_server_port" class="form-control" value="<?php echo htmlspecialchars($network['settings']['sip_server_port']); ?>" required>
	</div>

	<div class="form-group">
		<label><?php echo _("Transport"); ?></label>
		<select name="sip_server_transport" class="form-control">
			<?php foreach(yealinkphones_dropdown('transport') as $key => $value): ?>
				<option value="<?php echo $key; ?>"<?php echo ($network['settings']['sip_server_transport']==$key?' selected':''); ?>>
					<?php echo $value; ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>

	<div class="form-group">
		<label><?php echo _("Registration Expires (seconds)"); ?></label>
		<input type="number" name="sip_server_expires" class="form-control" value="<?php echo htmlspecialchars($network['settings']['sip_server_expires']); ?>">
	</div>

	<div class="form-group">
		<label><?php echo _("NAT Keepalive Interval (seconds)"); ?></label>
		<input type="number" name="nat_keepalive_interval" class="form-control" value="<?php echo htmlspecialchars($network['settings']['nat_keepalive_interval']); ?>">
		<span class="help-block"><?php echo _("0 = disabled"); ?></span>
	</div>

	<!-- Time Settings -->
	<h3><?php echo _("Time Settings"); ?></h3>

	<div class="form-group">
		<label><?php echo _("NTP Server 1"); ?></label>
		<input type="text" name="ntp_server1" class="form-control" value="<?php echo htmlspecialchars($network['settings']['ntp_server1']); ?>">
	</div>

	<div class="form-group">
		<label><?php echo _("NTP Server 2"); ?></label>
		<input type="text" name="ntp_server2" class="form-control" value="<?php echo htmlspecialchars($network['settings']['ntp_server2']); ?>">
	</div>

	<div class="form-group">
		<label><?php echo _("Time Zone Offset"); ?></label>
		<input type="number" name="time_zone" class="form-control" value="<?php echo htmlspecialchars($network['settings']['time_zone']); ?>">
		<span class="help-block"><?php echo _("Hours offset from GMT (e.g., -5 for EST)"); ?></span>
	</div>

	<div class="form-group">
		<label><?php echo _("Time Zone Name"); ?></label>
		<input type="text" name="time_zone_name" class="form-control" value="<?php echo htmlspecialchars($network['settings']['time_zone_name']); ?>">
	</div>

	<!-- Codec Settings -->
	<h3><?php echo _("Codec Settings"); ?></h3>
	<p class="help-block"><?php echo _("Configure codec priority (1-27, or 0 to disable). Lower numbers = higher priority."); ?></p>

	<table class="table table-bordered">
		<thead>
			<tr>
				<th><?php echo _("Codec"); ?></th>
				<th><?php echo _("Enabled"); ?></th>
				<th><?php echo _("Priority"); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>G.711 μ-law (PCMU)</td>
				<td>
					<select name="codec_pcmu_enable" class="form-control">
						<option value="1"<?php echo ($network['settings']['codec_pcmu_enable']=='1'?' selected':''); ?>>Enabled</option>
						<option value="0"<?php echo ($network['settings']['codec_pcmu_enable']=='0'?' selected':''); ?>>Disabled</option>
					</select>
				</td>
				<td>
					<input type="number" name="codec_pcmu_priority" class="form-control" min="0" max="27"
					       value="<?php echo htmlspecialchars($network['settings']['codec_pcmu_priority']); ?>">
				</td>
			</tr>
			<tr>
				<td>G.711 A-law (PCMA)</td>
				<td>
					<select name="codec_pcma_enable" class="form-control">
						<option value="1"<?php echo ($network['settings']['codec_pcma_enable']=='1'?' selected':''); ?>>Enabled</option>
						<option value="0"<?php echo ($network['settings']['codec_pcma_enable']=='0'?' selected':''); ?>>Disabled</option>
					</select>
				</td>
				<td>
					<input type="number" name="codec_pcma_priority" class="form-control" min="0" max="27"
					       value="<?php echo htmlspecialchars($network['settings']['codec_pcma_priority']); ?>">
				</td>
			</tr>
			<tr>
				<td>G.722 (Wideband)</td>
				<td>
					<select name="codec_g722_enable" class="form-control">
						<option value="1"<?php echo ($network['settings']['codec_g722_enable']=='1'?' selected':''); ?>>Enabled</option>
						<option value="0"<?php echo ($network['settings']['codec_g722_enable']=='0'?' selected':''); ?>>Disabled</option>
					</select>
				</td>
				<td>
					<input type="number" name="codec_g722_priority" class="form-control" min="0" max="27"
					       value="<?php echo htmlspecialchars($network['settings']['codec_g722_priority']); ?>">
				</td>
			</tr>
			<tr>
				<td>G.729</td>
				<td>
					<select name="codec_g729_enable" class="form-control">
						<option value="1"<?php echo ($network['settings']['codec_g729_enable']=='1'?' selected':''); ?>>Enabled</option>
						<option value="0"<?php echo ($network['settings']['codec_g729_enable']=='0'?' selected':''); ?>>Disabled</option>
					</select>
				</td>
				<td>
					<input type="number" name="codec_g729_priority" class="form-control" min="0" max="27"
					       value="<?php echo htmlspecialchars($network['settings']['codec_g729_priority']); ?>">
				</td>
			</tr>
			<tr>
				<td>Opus</td>
				<td>
					<select name="codec_opus_enable" class="form-control">
						<option value="1"<?php echo ($network['settings']['codec_opus_enable']=='1'?' selected':''); ?>>Enabled</option>
						<option value="0"<?php echo ($network['settings']['codec_opus_enable']=='0'?' selected':''); ?>>Disabled</option>
					</select>
				</td>
				<td>
					<input type="number" name="codec_opus_priority" class="form-control" min="0" max="27"
					       value="<?php echo htmlspecialchars($network['settings']['codec_opus_priority']); ?>">
				</td>
			</tr>
		</tbody>
	</table>

	<!-- Form Actions -->
	<div class="form-group">
		<button type="submit" class="btn btn-primary">
			<i class="fa fa-save"></i> <?php echo _("Save"); ?>
		</button>
		<a href="?display=yealinkphones&yealinkphones_form=networks_list" class="btn btn-default">
			<i class="fa fa-times"></i> <?php echo _("Cancel"); ?>
		</a>
	</div>
</form>
