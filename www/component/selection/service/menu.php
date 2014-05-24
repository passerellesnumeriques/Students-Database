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
		$can_manage = PNApplication::$instance->user_management->has_right("manage_selection_campaign",true);
?>
<div style="padding-left:5px;text-align:center;margin-bottom:5px;">
Selection Campaign:<br/>
<select onchange="changeCampaign(this.value);">
<option value='0'></option>
<?php 
foreach ($campaigns as $c) {
	echo "<option value='".$c["id"]."'";
	if ($c["id"] == $id) echo " selected='selected'";
	echo ">".htmlentities($c["name"])."</option>";
}
?></select>
<br/>
<?php if ($can_manage) { ?>
	<?php if ($id <> null && $id > 0) {?>
	<button class='flat' style='margin:0px' onclick='renameCampaign();' title='Rename this campaign'><img src='<?php echo theme::$icons_16["edit_white"];?>'/></button>
	<button class='flat' style='margin:0px' onclick='removeCampaign();' title='Remove this campaign'><img src='<?php echo theme::$icons_16["remove_white"];?>'/></button>
	<?php } ?>
<button class='flat' style='margin:0px' onclick='createCampaign();' title='Create a new Selection Campaign'><img src='<?php echo theme::$icons_16["add_white"];?>'/></button>
<?php } ?>
</div>

<div class="application_left_menu_separator"></div>

<a class='application_left_menu_item' href='/dynamic/selection/page/selection_main_page'>
	<img src='/static/selection/dashboard_white.png'/>
	Dashboard
</a>
<a class='application_left_menu_item' href='/dynamic/selection/page/config/manage'>
	<img src='<?php echo theme::$icons_16["config_white"];?>'/>
	Configure Process
</a>
<a class='application_left_menu_item' href='/dynamic/selection/page/is/main_page'>
	<img src='/static/selection/is/is_white.png'/>
	Information Sessions
</a>
<a class='application_left_menu_item'>
	<img src='/static/selection/exam/exam_white.png'/>
	Written Exams
</a>
	<a class='application_left_menu_item' style='padding-left:20px' href='/dynamic/selection/page/exam/main_page'>
		<img src='/static/selection/exam/subject_white.png'/>
		Subjects and rules
	</a>
	<a class='application_left_menu_item' style='padding-left:20px' href='/dynamic/selection/page/exam/center_main_page'>
		<img src='/static/selection/exam/center_white.png'/>
		Centers
	</a>
	<a class='application_left_menu_item' style='padding-left:20px' href='/dynamic/selection/page/exam/results'>
		<img src='/static/selection/results_white.png'/>
		Results
	</a>
<a class='application_left_menu_item'>
	<img src='/static/selection/interview_white.png'/>
	Interviews
</a>
	<a class='application_left_menu_item' style='padding-left:20px' href='/dynamic/selection/page/exam/main_page'>
		<img src='/static/selection/exam/subject_white.png'/>
		Questions and rules
	</a>
	<a class='application_left_menu_item' style='padding-left:20px' href='/dynamic/selection/page/exam/center_main_page'>
		<img src='/static/selection/exam/center_white.png'/>
		Centers
	</a>
	<a class='application_left_menu_item' style='padding-left:20px' href='/dynamic/selection/page/exam/results'>
		<img src='/static/selection/results_white.png'/>
		Results
	</a>
<a class='application_left_menu_item'>
	<img src='/static/selection/si/si_white.png'/>
	Social Investigations
</a>
	
<div class="application_left_menu_separator"></div>

<a class='application_left_menu_item' href='/dynamic/selection/page/applicant/list'>
	<img src='/static/selection/applicant/applicants_white.png'/>
	Applicants List
</a>
<a class='application_left_menu_item' href='/dynamic/contact/page/organizations?creator=Selection'>
	<img src='/static/selection/organizations_white.png'/>
	Partners List
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
function createCampaign() {
	input_dialog(theme.icons_16.question,
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
			var div_locker = window.top.lock_screen(null,"Creation of the new selection campaign...");
			service.json("selection","create_campaign",{name:text.trim().uniformFirstLetterCapitalized()},function(res){
				unlock_screen(div_locker);
				if(!res) return;
				location.href = "?section=selection";
			});
		}
	);
}
function changeCampaign(id) {
	service.json("selection","set_campaign_id",{campaign_id:id},function(res){
		if(!res) return;
		location.href = "?section=selection";
	});
}
<?php if ($id <> null && $id > 0) {?>
function renameCampaign() {
	input_dialog(theme.icons_16.question,
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
			var div_locker = lock_screen();
			service.json("selection","set_campaign_name",{id:<?php echo $id;?>, name:text.trim().uniformFirstLetterCapitalized()},function(res){
				unlock_screen(div_locker);
				if(!res) return;
				location.href = "?section=selection";
			});
		}
	);
}
function removeCampaign() {
	confirm_dialog("Are you sure you want to remove this campaign?<br/><i><b>All the related data will be removed</i></b>",function(res){
		if(!res) return;
		var div_locker = lock_screen();
		service.json("selection","remove_campaign",{id:<?php echo $id;?>},function(res){
			unlock_screen(div_locker);
			if(!res) return;
			location.href = "?section=selection";
		});
	});
}
<?php } ?>
</script>
<?php 
	}
	
}
?>