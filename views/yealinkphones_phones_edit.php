<?php
/* Phone Edit View */
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

$isNew = empty($_GET['edit']);
?>

<h2><?php echo $isNew ? _("Add Phone") : _("Edit Phone"); ?></h2>

<form method="post" action="?display=yealinkphones&yealinkphones_form=phones_edit&edit=<?php echo $_GET['edit']; ?>">
	<input type="hidden" name="action" value="edit">

	<!-- General Information -->
	<h3><?php echo _("General Information"); ?></h3>
	<div class="form-group">
		<label><?php echo _("Phone Name"); ?></label>
		<input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($device['name']); ?>" required>
		<span class="help-block"><?php echo _("Descriptive name for this phone"); ?></span>
	</div>

	<div class="form-group">
		<label><?php echo _("MAC Address"); ?></label>
		<input type="text" name="mac" class="form-control" value="<?php echo htmlspecialchars($device['mac']); ?>"
		       pattern="[0-9A-Fa-f]{12}" maxlength="12" required
		       placeholder="<?php echo _("12 hex digits, no separators (e.g., 805EC0123456)"); ?>">
		<span class="help-block"><?php echo _("MAC address without colons or dashes"); ?></span>
	</div>

	<?php if(!$isNew): ?>
	<div class="well well-sm">
		<strong><?php echo _("Model:"); ?></strong> <?php echo htmlspecialchars($device['model']); ?><br>
		<strong><?php echo _("Firmware:"); ?></strong> <?php echo htmlspecialchars($device['firmware_version']); ?><br>
		<strong><?php echo _("Last Config:"); ?></strong> <?php echo htmlspecialchars($device['lastconfig']); ?><br>
		<strong><?php echo _("Last IP:"); ?></strong> <?php echo htmlspecialchars($device['lastip']); ?>
	</div>
	<?php endif; ?>

	<!-- Line Configuration -->
	<h3><?php echo _("Line Configuration"); ?></h3>
	<p class="help-block"><?php echo _("Assign FreePBX devices to phone lines (up to 16 lines supported)"); ?></p>

	<table class="table table-bordered">
		<thead>
			<tr>
				<th style="width: 10%;"><?php echo _("Line"); ?></th>
				<th style="width: 50%;"><?php echo _("FreePBX Device"); ?></th>
				<th style="width: 40%;"><?php echo _("Label"); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$dropdown = yealinkphones_dropdown_lines($_GET['edit']);
			for($i=1; $i<=16; $i++):
				$line_value = isset($device['lines'][$i]['line']) ? $device['lines'][$i]['line'] : '';
				$label_value = isset($device['lines'][$i]['settings']['label']) ? $device['lines'][$i]['settings']['label'] : '';
			?>
				<tr>
					<td><?php echo $i; ?></td>
					<td>
						<select name="line[]" class="form-control">
							<?php
							foreach($dropdown as $group => $options)
							{
								if(is_array($options))
								{
									echo '<optgroup label="'.$group.'">';
									foreach($options as $key => $value)
										echo '<option value="'.$key.'"'.($line_value==$key?' selected':'').'>'.$value.'</option>';
									echo '</optgroup>';
								}
								else
									echo '<option value="'.$group.'"'.($line_value==$group?' selected':'').'>'.$options.'</option>';
							}
							?>
						</select>
					</td>
					<td>
						<input type="text" name="label[]" class="form-control" value="<?php echo htmlspecialchars($label_value); ?>"
						       placeholder="<?php echo _("Optional custom label"); ?>">
					</td>
				</tr>
			<?php endfor; ?>
		</tbody>
	</table>

	<!-- Line Keys (BLF/Speed Dial) -->
	<h3><?php echo _("Line Keys (BLF / Speed Dial)"); ?></h3>
	<p class="help-block"><?php echo _("Configure programmable keys for BLF monitoring, speed dial, etc."); ?></p>

	<div id="linekeys-container">
		<table class="table table-bordered">
			<thead>
				<tr>
					<th style="width: 8%;"><?php echo _("Key"); ?></th>
					<th style="width: 20%;"><?php echo _("Type"); ?></th>
					<th style="width: 10%;"><?php echo _("Line"); ?></th>
					<th style="width: 20%;"><?php echo _("Value/Extension"); ?></th>
					<th style="width: 20%;"><?php echo _("Label"); ?></th>
					<th style="width: 12%;"><?php echo _("Extension"); ?></th>
					<th style="width: 10%;"><?php echo _("Pickup"); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$linekey_types = yealinkphones_dropdown_linekey_types();
				for($i=1; $i<=27; $i++):
					$linekey = isset($device['linekeys'][$i]) ? $device['linekeys'][$i] : array('type'=>'0','line'=>'1','value'=>'','label'=>'','extension'=>'','pickup_value'=>'');
				?>
					<tr>
						<td><?php echo $i; ?></td>
						<td>
							<select name="linekey_type[]" class="form-control input-sm">
								<?php foreach($linekey_types as $type_id => $type_name): ?>
									<option value="<?php echo $type_id; ?>"<?php echo ($linekey['type']==$type_id?' selected':''); ?>>
										<?php echo $type_name; ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
						<td>
							<select name="linekey_line[]" class="form-control input-sm">
								<?php for($l=1; $l<=16; $l++): ?>
									<option value="<?php echo $l; ?>"<?php echo ($linekey['line']==$l?' selected':''); ?>><?php echo $l; ?></option>
								<?php endfor; ?>
							</select>
						</td>
						<td>
							<input type="text" name="linekey_value[]" class="form-control input-sm"
							       value="<?php echo htmlspecialchars($linekey['value']); ?>"
							       placeholder="<?php echo _("Extension or number"); ?>">
						</td>
						<td>
							<input type="text" name="linekey_label[]" class="form-control input-sm"
							       value="<?php echo htmlspecialchars($linekey['label']); ?>"
							       placeholder="<?php echo _("Display label"); ?>">
						</td>
						<td>
							<input type="text" name="linekey_extension[]" class="form-control input-sm"
							       value="<?php echo htmlspecialchars($linekey['extension']); ?>"
							       placeholder="<?php echo _("Ext"); ?>">
						</td>
						<td>
							<input type="text" name="linekey_pickup[]" class="form-control input-sm"
							       value="<?php echo htmlspecialchars($linekey['pickup_value']); ?>"
							       placeholder="*8">
						</td>
					</tr>
				<?php endfor; ?>
			</tbody>
		</table>
	</div>

	<!-- Form Actions -->
	<div class="form-group">
		<button type="submit" class="btn btn-primary">
			<i class="fa fa-save"></i> <?php echo _("Save"); ?>
		</button>
		<a href="?display=yealinkphones&yealinkphones_form=phones_list" class="btn btn-default">
			<i class="fa fa-times"></i> <?php echo _("Cancel"); ?>
		</a>
	</div>
</form>
