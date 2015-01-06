<?php 
class service_menu extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Provides the selection menu"; }
	public function inputDocumentation() { echo "No"; }
	public function outputDocumentation() { echo "The HTML to put in the menu"; }
	public function getOutputFormat($input) { return "text/html"; }
		
	public function execute(&$component, $input) {
		$campaigns = SQLQuery::create()->select("SelectionCampaign")->execute();
		$id = $component->getCampaignId();
		$campaign = null;
		if ($id <> null) foreach ($campaigns as $c) if ($c["id"] == $id) { $campaign = $c; break; }
		$can_manage = PNApplication::$instance->user_management->hasRight("manage_selection_campaign",true);
?>
<div style="padding-left:5px;text-align:center;margin-bottom:5px;">
Selection Campaign:<br/>
<select onchange="changeCampaign(this.value);">
<option value='0'></option>
<?php 
foreach ($campaigns as $c) {
	echo "<option value='".$c["id"]."'";
	if ($c["id"] == $id) echo " selected='selected'";
	echo ">".toHTML($c["name"])."</option>";
}
?></select>
<?php 
#SELECTION_TRAVEL
#if (false) {
#END
if ($campaign <> null) {
	if ($can_manage) {
		if ($campaign["frozen"] == 1) {
			echo "<button class='flat' style='margin:0px' onclick='unlockCampaign();' title='This campaign is locked (Reason: ".$campaign['frozen_reason']."). Click to unlock it'><img src='".theme::$icons_16["lock_white"]."'/></button>";
		} else {
			echo "<button class='flat' style='margin:0px' onclick='lockCampaign();' title='This campaign is not locked. Click to lock it'><img src='".theme::$icons_16["unlock_white"]."'/></button>";
		}
	} else {
		echo "<img src='";
		if ($campaign["frozen"] == 1) echo theme::$icons_16["lock_white"];
		else echo theme::$icons_16["unlock_white"];
		echo "' title='This selection campaign is ";
		if ($campaign["frozen"] == 1)
			echo "locked. Reason: ".$campaign['frozen_reason'];
		else
			echo "not locked";
		echo "' style='vertical-align:bottom'/>";
	}
}
#SELECTION_TRAVEL
#}
#END
?>
<br/>
<?php 
#SELECTION_TRAVEL
#if (false) {
#END
if ($can_manage) { ?>
	<?php if ($id <> null && $id > 0) {?>
	<button class='flat' style='margin:0px' onclick='renameCampaign();' title='Rename this campaign'><img src='<?php echo theme::$icons_16["edit_white"];?>'/></button>
	<button class='flat' style='margin:0px' onclick='removeCampaign();' title='Remove this campaign'><img src='<?php echo theme::$icons_16["remove_white"];?>'/></button>
	<?php } ?>
<button class='flat' style='margin:0px' onclick='createCampaign();' title='Create a new Selection Campaign'><img src='<?php echo theme::$icons_16["add_white"];?>'/></button>
<?php 
}
#SELECTION_TRAVEL
#}
#END
?>
</div>

<div class="application_left_menu_separator"></div>

<a class='application_left_menu_item' href='/dynamic/selection/page/selection_main_page'>
	<img src='/static/selection/dashboard_white.png'/>
	Dashboard
</a>
<?php if ($can_manage) {?>
<a class='application_left_menu_item' href='/dynamic/selection/page/config/manage'>
	<img src='<?php echo theme::$icons_16["config_white"];?>'/>
	Configure Process
</a>
<?php } ?>
<?php
#SELECTION_TRAVEL
#if (false) {
#END 
if (PNApplication::$instance->user_management->hasRight("edit_applicants")) {
?>
<a class='application_left_menu_item' href='/dynamic/data_model/page/edit_customizable_table?table=ApplicantMoreInfo'>
	<img src='/static/people/profile_white.png'/>
	Edit Application Form
</a>
<?php 
}
#SELECTION_TRAVEL
#}
#END
?>
<a class='application_left_menu_item' href='/dynamic/selection/page/is/main_page'>
	<img src='/static/selection/is/is_white.png'/>
	Information Sessions
</a>
<a class='application_left_menu_item'>
	<img src='/static/selection/exam/exam_white.png'/>
	Written Exams
</a>
	<a class='application_left_menu_item' style='padding-left:20px' href='/dynamic/selection/page/exam/subjects'>
		<img src='/static/selection/exam/subject_white.png'/>
		Subjects
	</a>
	<a class='application_left_menu_item' style='padding-left:20px' href='/dynamic/selection/page/exam/eligibility_rules'>
		<img src='/static/selection/exam/rules_16_white.png'/>
		Eligibility rules
	</a>
	<a class='application_left_menu_item' style='padding-left:20px' href='/dynamic/selection/page/exam/center_main_page'>
		<img src='/static/selection/exam/center_white.png'/>
		Centers
	</a>
	<a class='application_left_menu_item' style='padding-left:20px' href='/dynamic/selection/page/exam/results'>
		<img src='/static/selection/results_white.png'/>
		Results
	</a>
	<a class='application_left_menu_item' style='padding-left:20px' href='/dynamic/selection/page/applicant/list?type=exam_passers'>
		<img src='<?php echo theme::$icons_16["like_white"];?>'/>
		Passers
	</a>
<a class='application_left_menu_item'>
	<img src='/static/selection/interview/interview_white.png'/>
	Interviews
</a>
	<a class='application_left_menu_item' style='padding-left:20px' href='/dynamic/selection/page/interview/criteria'>
		<img src='/static/selection/exam/subject_white.png'/>
		Criteria and rules
	</a>
	<a class='application_left_menu_item' style='padding-left:20px' href='/dynamic/selection/page/interview/centers'>
		<img src='/static/selection/exam/center_white.png'/>
		Centers
	</a>
	<a class='application_left_menu_item' style='padding-left:20px' href='/dynamic/selection/page/interview/results'>
		<img src='/static/selection/results_white.png'/>
		Results
	</a>
	<a class='application_left_menu_item' style='padding-left:20px' href='/dynamic/selection/page/applicant/list?type=interview_passers'>
		<img src='<?php echo theme::$icons_16["like_white"];?>'/>
		Passers
	</a>
<a class='application_left_menu_item' href='/dynamic/selection/page/applicant/list?type=si'>
	<img src='/static/selection/si/si_white.png'/>
	Social Investigations
</a>
<a class='application_left_menu_item' href='/dynamic/selection/page/applicant/list?type=final'>
	<img src='<?php echo theme::$icons_16["like_white"];?>'/>
	Final List
</a>

<div class="application_left_menu_separator"></div>

<a class='application_left_menu_item' href='/dynamic/selection/page/staff/status'>
	<img src='/static/application/pn_letters_white_16.png'/>
	Staff Status
</a>
<!-- 
<a class='application_left_menu_item' href='/dynamic/selection/page/trip/list'>
	<img src='/static/selection/trip/bus_white_16.png'/>
	Trips
</a>
 -->
<a class='application_left_menu_item' href='/dynamic/selection/page/applicant/list'>
	<img src='/static/selection/applicant/applicants_white.png'/>
	Applicants List
</a>
<a class='application_left_menu_item' href='/dynamic/contact/page/organizations?creator=Selection'>
	<img src='/static/selection/organizations_white.png'/>
	Organizations
</a>
<script type='text/javascript'>
var campaign_names = [<?php 
$first = true;
foreach ($campaigns as $c) {
	if ($first) $first = false; else echo ",";
	echo json_encode($c["name"]);
}
?>];
var current_campaign_name = <?php 
$found = false;
foreach ($campaigns as $c) if ($c["id"] == $id) { $found = true; echo json_encode($c["name"]); break;}
if (!$found) echo "''";
?>;
function refreshCampaigns() {
	// refresh menu
	getIFrameWindow(findFrame('pn_application_frame')).reloadMenu();
	// refresh page
	getIFrameWindow(findFrame('application_frame')).location.reload();
}
function createCampaign() {
	inputDialog(theme.icons_16.question,
		"Create a selection campaign",
		"Enter the name of the new selection campaign",
		'',
		50,
		function(text){
			if(!text.checkVisible()) return "You must enter at least one visible character";
			for (var i = 0; i < campaign_names.length; ++i)
				if (campaign_names[i].toLowerCase() == text.trim().toLowerCase())
					return "A campaign already exists with this name";
		},
		function(text){
			if(!text) return;
			var div_locker = window.top.lockScreen(null,"Creation of the new selection campaign...");
			service.json("selection","create_campaign",{name:text.trim()},function(res){
				unlockScreen(div_locker);
				if(!res) return;
				refreshCampaigns();
			});
		}
	);
}
function changeCampaign(id) {
	service.json("selection","set_campaign_id",{campaign_id:id},function(res){
		if(!res) return;
		refreshCampaigns();
	});
}
<?php if ($id <> null && $id > 0) {?>
function renameCampaign() {
	inputDialog(theme.icons_16.question,
		"Rename the current selection campaign",
		"Enter the new name of the selection campaign",
		current_campaign_name,
		50,
		function(text){
			if(!text.checkVisible()) return "You must enter at least one visible caracter";
			text = text.trim().toLowerCase();
			if (text == current_campaign_name.trim().toLowerCase()) return null;
			for (var i = 0; i < campaign_names.length; ++i) {
				if (text == campaign_names[i].trim().toLowerCase()) return "A campaign already exists with this name";
			}
			return null;
		},
		function(text){
			if (!text) return;
			if (text.trim().toLowerCase() == current_campaign_name.trim().toLowerCase()) return;
			var div_locker = lockScreen();
			service.json("selection","set_campaign_name",{id:<?php echo $id;?>, name:text.trim()},function(res){
				unlockScreen(div_locker);
				if(!res) return;
				refreshCampaigns();
			});
		}
	);
}
function removeCampaign() {
	confirmDialog("Are you sure you want to remove this campaign?<br/><i><b>All the related data will be removed</i></b>",function(res){
		if(!res) return;
		var div_locker = lockScreen(null, "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Removing Selection Campaign and every applicant of it...");
		service.json("selection","remove_campaign",{id:<?php echo $id;?>},function(res){
			unlockScreen(div_locker);
			if(!res) return;
			refreshCampaigns();
		});
	});
}
function lockCampaign() {
	popupFrame(theme.icons_16.lock, "Lock Selection Campaign", "/dynamic/selection/page/lock");
}
function unlockCampaign() {
	popupFrame(theme.icons_16.unlock, "Unlock Selection Campaign", "/dynamic/selection/page/unlock");
}
<?php } ?>
</script>
<?php 
	}
	
}
?>