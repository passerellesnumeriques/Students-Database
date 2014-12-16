<?php 
require_once("SelectionPage.inc");
class page_unlock extends SelectionPage {
	public function getRequiredRights() { return array("manage_selection_campaign"); }
	public function executeSelectionPage(){
		$reason = $this->component->getFrozenReason();
		if ($reason == "Campaign Finished") {
?>
<div style='background-color:white;padding:10px'>
	The campaign is currently locked because it is already finshed.<br/>
	Are you sure you want to unlock it ?
	<button class='action green' onclick="unlock();">Yes</button>
	<button class='action' onclick="closePopup();">No</button>
</div>
<script type='text/javascript'>
function unlock() {
	var popup = window.parent.get_popup_window_from_frame(window);
	popup.freeze(null, "Unlocking selection campaign...");
	service.json("selection","unlock_campaign",{},function(res) {
		if (!res) { popup.unfreeze(); return; }
		var win = window.top.frames["pn_application_frame"];
		win.sectionClicked(win.getSection('selection'));
		popup.close();
	});
}
function closePopup() {
	window.parent.get_popup_window_from_frame(window).close();
}
</script>
<?php 						
		} else if ($reason == "Selection Team Travelling") {
			// TODO
?>
<div style='background-color:white;padding:10px'>
</div>
<script type='text/javascript'>
</script>
<?php 						
		}
	}
}