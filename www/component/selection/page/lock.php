<?php 
require_once("SelectionPage.inc");
class page_lock extends SelectionPage {
	public function getRequiredRights() { return array("manage_selection_campaign"); }
	public function executeSelectionPage(){
		$campaign = SQLQuery::create()->select("SelectionCampaign")->whereValue("SelectionCampaign","id",$this->component->getCampaignId())->executeSingleRow();
		if ($campaign["frozen"] == 1) { echo "Already locked!"; return; }
?>
<div style='background-color:white;padding:10px'>
	When you lock a selection campaign, it won't be editable anymore.<br/>
	In other words, we can still consult the data, but we cannot modify anything.<br/>
	<br/>
	Why do you want to lock this campaign ?<br/>
	<input id='travel' type='radio' name='lock_reason' value='travel'/> I'm going to travel, and I want to install this software on my computer<br/>
	<input id='done' type='radio' name='lock_reason' value='done'/> This campaign is finished<br/>
	<br/>
	<button class='action red' onclick="lock();">Lock this campaign</button>
</div>
<script type='text/javascript'>
function lock() {
	if (document.getElementById('travel').checked) lockTravel();
	else if (document.getElementById('done').checked) lockDone();
	else alert("Please select a reason");
}
function lockTravel() {
	location.href = "lock_travel";
}
function lockDone() {
	var popup = window.parent.get_popup_window_from_frame(window);
	popup.freeze(null, "Locking selection campaign...");
	service.json("selection","lock_campaign",{reason:'done'},function(res) {
		if (!res) { popup.unfreeze(); return; }
		var win = window.top.frames["pn_application_frame"];
		win.sectionClicked(win.getSection('selection'));
		popup.close();
	});
}
</script>
<?php 
	}
}