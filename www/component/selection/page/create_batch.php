<?php 
require_once("SelectionPage.inc");
class page_create_batch extends SelectionPage {

	public function getRequiredRights() { return array("manage_selection_campaign"); }

	public function executeSelectionPage() {
		$program_id = @$_GET["program"];
		// get the list of existing batches
		$future_batches = PNApplication::$instance->curriculum->getFutureBatches(true);
		$current_batches = PNApplication::$instance->curriculum->getCurrentBatches(true, true);
		$old_batches = PNApplication::$instance->curriculum->getAlumniBatches(true);
		$can_create = PNApplication::$instance->user_management->hasRight("edit_curriculum");
?>
<div style='background-color:white;padding:10px'>
<?php if (count($future_batches) > 0) { ?>
If the batch has been already created, please select it:
<select id='future_batch'>
	<option value=''></option>
	<?php foreach ($future_batches as $b) echo "<option value='".$b["id"]."'>".toHTML($b["name"])."</option>"; ?>
</select>
<button class='action' onclick="var batch_id = document.getElementById('future_batch').value; if (batch_id == '') return; updateBatch(batch_id);">Update this batch</button>
<br/><br/>
<?php }?>
<?php if (count($current_batches) > 0) { ?>
If you are working on a old selection process, or the academic year already started,<br/>
you can select one of the current batches:
<select id='current_batch'>
	<option value=''></option>
	<?php foreach ($current_batches as $b) echo "<option value='".$b["id"]."'>".toHTML($b["name"])."</option>"; ?>
</select>
<button class='action' onclick="var batch_id = document.getElementById('current_batch').value; if (batch_id == '') return; updateBatch(batch_id);">Update this batch</button>
<br/><br/>
<?php }?>
<?php if (count($old_batches) > 0) { ?>
If you are working on a very old selection process, you can select<br/>
among the batches of alumni:
<select id='old_batch'>
	<option value=''></option>
	<?php foreach ($old_batches as $b) echo "<option value='".$b["id"]."'>".toHTML($b["name"])."</option>"; ?>
</select>
<button class='action' onclick="var batch_id = document.getElementById('old_batch').value; if (batch_id == '') return; updateBatch(batch_id);">Update this batch</button>
<br/><br/>
<?php }?>
If the batch is not yet created, you can create it:
<?php if (!$can_create) { ?>
<br/>
<i>Unfortunately, you don't have enough privileges to create a batch of student.<br/>
Please ask a staff (i.e. Training manager) to create it.</i>
<?php } else { ?>
<button class='action' onclick='newBatch();'>Create New Batch</button>
<?php } ?>
</div>
<script type='text/javascript'>
function launchUpdate(batch_id) {
	var popup = window.parent.getPopupFromFrame(window);
	popup.freeze("Updating batch of students...");
	service.json("data_model","save_cell",{
		<?php if ($program_id == null) { ?>
		table:'SelectionCampaign',
		column:'batch',
		row_key:<?php echo $this->component->getCampaignId();?>,
		<?php } else { ?>
		table:'SelectionProgram',
		sub_model:<?php echo $this->component->getCampaignId();?>,
		column:'batch',
		row_key:<?php echo $program_id;?>,
		<?php } ?>
		value:batch_id,
		lock:null
	},function(res) {
		if (!res) { popup.unfreeze(); return; }
		service.json("selection","update_batch",{batch:batch_id<?php if ($program_id <> null) echo ",program:".$program_id;?>},function(res) {
			popup.unfreeze();
			if (!res) return;
			<?php if (isset($_GET["ondone"])) echo "window.frameElement.".$_GET["ondone"]."();"; ?>
			popup.close();
		});
	});
}
function updateBatch(batch_id) {
	popupFrame(theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.add), "Update Batch from Selection", "/dynamic/selection/page/update_batch_confirm?batch="+batch_id<?php if ($program_id <> null) echo "+'&program=".$program_id."'";?>, null, null, null, function(frame,popup) {
		frame.confirmed = function() { launchUpdate(batch_id); };
	});
}
window.parent.batchCreated = function(batch_id) {
	launchUpdate(batch_id);
}
function newBatch() {
	popupFrame(theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.add), "New Batch of Students", "/dynamic/curriculum/page/edit_batch?onsave=batchCreated");
}
</script>
<?php 
	}
	
}
?>