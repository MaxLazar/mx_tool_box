<?php //success
echo form_open(ee('CP/URL')->make('cp/addons/settings/mx_tool_box/ee1_to_ee3'));
?>		
<p>EE1 Weblog ID:
<input name="weblog_id" /></p>

<p>EE2 Channel ID:
<input name="channel_id" /></p>

<p class="centerSubmit">
	<input name="edit_field_group_name" value="<?= lang('save'); ?>" class="submit" type="submit">&nbsp;&nbsp;					
</p>
