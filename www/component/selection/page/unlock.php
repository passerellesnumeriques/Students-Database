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
			$user_id = SQLQuery::create()->bypassSecurity()->select("TravelVersion")->field("user")->executeSingleValue();
?>
<div style='background-color:white;padding:10px'>
<?php 
$people_id = PNApplication::$instance->user_management->get_people_from_user($user_id);
$people = PNApplication::$instance->people->getPeople($people_id, true);
$name = toHTML($people["first_name"]." ".$people["last_name"]);
if ($user_id <> PNApplication::$instance->user_management->user_id) {
	$he = ($people["sex"] == "M" ? "he" : "she");
	$him = ($people["sex"] == "M" ? "him" : "her");
	$his = ($people["sex"] == "M" ? "his" : "her");
	echo "This campaign is currently locked by $name because $he is travelling with the software.<br/>";
	echo "Only $him can unlock this campaign so $he can update the database with $his modifications.<br/><br/>";
	echo "Only in case of emergency (like the travelling version has been lost), you should unlock this campaign.<br/>";
	echo "But if you do so, $name won't be able to synchronize with the server,<br/>";
	echo "meaning all the modifications $he did on $his computer will be lost.<br/><br/>";
	echo "<button class='action red' onclick='unlock();'>Unlock anyway</button>";
} else {
	echo "Welcome back $name !<br/>";
	echo "You probably want to unlock this campaign because you are back, so we need to get the version of the database<br/>";
	echo "from your computer and put it on the server.<br/>";
	echo "<br/>";
	echo "<div id='progress'>Connecting to your computer</div>";
} 
?>
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
function connectToComputer() {
	// TODO
}
<?php if ($user_id == PNApplication::$instance->user_management->user_id) echo "connectToComputer();"; ?>
</script>
<?php 						
		}
	}
}