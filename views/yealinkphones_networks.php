<?php
/* Network List View */
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
?>

<h2><?php echo _("Networks"); ?></h2>

<p>
	<a href="?display=yealinkphones&yealinkphones_form=networks_edit&edit=" class="btn btn-primary">
		<i class="fa fa-plus"></i> <?php echo _("Add Network"); ?>
	</a>
</p>

<p class="help-block">
	<?php echo _("Configure network-specific settings by IP range (CIDR notation). Settings are applied based on the phone's IP address during provisioning."); ?>
</p>

<?php if(count($networks) == 0): ?>
	<div class="alert alert-info">
		<?php echo _("No networks configured."); ?>
	</div>
<?php else: ?>
	<table class="table table-striped table-bordered">
		<thead>
			<tr>
				<th><?php echo _("Name"); ?></th>
				<th><?php echo _("CIDR Range"); ?></th>
				<th><?php echo _("Actions"); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach($networks as $network): ?>
				<tr>
					<td><?php echo htmlspecialchars($network['name']); ?></td>
					<td><code><?php echo htmlspecialchars($network['cidr']); ?></code></td>
					<td>
						<a href="?display=yealinkphones&yealinkphones_form=networks_edit&edit=<?php echo $network['id']; ?>" class="btn btn-sm btn-default">
							<i class="fa fa-edit"></i> <?php echo _("Edit"); ?>
						</a>
						<?php if($network['id'] != '-1'): ?>
							<a href="?display=yealinkphones&yealinkphones_form=networks_list&delete=<?php echo $network['id']; ?>" class="btn btn-sm btn-danger"
							   onclick="return confirm('<?php echo _("Are you sure you want to delete this network?"); ?>');">
								<i class="fa fa-trash"></i> <?php echo _("Delete"); ?>
							</a>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
