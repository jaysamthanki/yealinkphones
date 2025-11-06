<?php
/* Right Navigation */
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
?>

<div class="col-sm-2">
	<div class="list-group">
		<a href="?display=yealinkphones" class="list-group-item"><?php echo _("Main Menu"); ?></a>
		<a href="?display=yealinkphones&yealinkphones_form=phones_list" class="list-group-item"><?php echo _("Phones"); ?></a>
		<a href="?display=yealinkphones&yealinkphones_form=networks_list" class="list-group-item"><?php echo _("Networks"); ?></a>
		<a href="?display=yealinkphones&yealinkphones_form=general_edit" class="list-group-item"><?php echo _("General Settings"); ?></a>
	</div>
</div>
