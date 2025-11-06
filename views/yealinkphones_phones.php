<?php
/* Phone List View */
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
?>

<h2><?php echo _("Yealink Phones"); ?></h2>

<p>
	<a href="?display=yealinkphones&yealinkphones_form=phones_edit&edit=" class="btn btn-primary">
		<i class="fa fa-plus"></i> <?php echo _("Add Phone"); ?>
	</a>
	<a href="?display=yealinkphones&yealinkphones_form=phones_list&notify=" class="btn btn-default">
		<i class="fa fa-refresh"></i> <?php echo _("Notify All Phones"); ?>
	</a>
</p>

<?php if(count($devices) == 0): ?>
	<div class="alert alert-info">
		<?php echo _("No phones configured. Click 'Add Phone' to get started."); ?>
	</div>
<?php else: ?>
	<table class="table table-striped table-bordered">
		<thead>
			<tr>
				<th><?php echo _("Name"); ?></th>
				<th><?php echo _("MAC Address"); ?></th>
				<th><?php echo _("Model"); ?></th>
				<th><?php echo _("Firmware"); ?></th>
				<th><?php echo _("Lines"); ?></th>
				<th><?php echo _("Last Config"); ?></th>
				<th><?php echo _("Last IP"); ?></th>
				<th><?php echo _("Actions"); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach($devices as $device): ?>
				<tr>
					<td><?php echo htmlspecialchars($device['name']); ?></td>
					<td><?php echo htmlspecialchars($device['mac']); ?></td>
					<td><?php echo htmlspecialchars($device['model']); ?></td>
					<td><?php echo htmlspecialchars($device['firmware_version']); ?></td>
					<td>
						<?php
						$line_labels = array();
						foreach($device['lines'] as $line)
						{
							if(!empty($line['extension']))
								$line_labels[] = $line['extension'] . ' (' . $line['name'] . ')';
							elseif(!empty($line['description']))
								$line_labels[] = $line['id'] . ' (' . $line['description'] . ')';
						}
						echo implode('<br>', $line_labels);
						?>
					</td>
					<td><?php echo htmlspecialchars($device['lastconfig']); ?></td>
					<td><?php echo htmlspecialchars($device['lastip']); ?></td>
					<td>
						<a href="?display=yealinkphones&yealinkphones_form=phones_edit&edit=<?php echo $device['id']; ?>" class="btn btn-sm btn-default">
							<i class="fa fa-edit"></i> <?php echo _("Edit"); ?>
						</a>
						<a href="?display=yealinkphones&yealinkphones_form=phones_list&notify=<?php echo $device['id']; ?>" class="btn btn-sm btn-info">
							<i class="fa fa-refresh"></i> <?php echo _("Notify"); ?>
						</a>
						<a href="?display=yealinkphones&yealinkphones_form=phones_list&delete=<?php echo $device['id']; ?>" class="btn btn-sm btn-danger"
						   onclick="return confirm('<?php echo _("Are you sure you want to delete this phone?"); ?>');">
							<i class="fa fa-trash"></i> <?php echo _("Delete"); ?>
						</a>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
