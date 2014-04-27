<?php 
class service_menu extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Provides the selection menu"; }
	public function input_documentation() { echo "No"; }
	public function output_documentation() { echo "The HTML to put in the menu"; }
	public function get_output_format($input) { return "text/html"; }
		
	public function execute(&$component, $input) {
		$campaigns = SQLQuery::create()->select("SelectionCampaign")->execute();
		$id = $component->getCampaignId();
		$can_manage = PNApplication::$instance->user_management->has_right("manage_selection_campaign",true);
?>
<div style="padding-left:5px;text-align:center;margin-bottom:5px;">
Selection Campaign:<br/>
<select onchange="changeCampaign(this.value);"><?php 
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
<a class='application_left_menu_item' href='/dynamic/selection/page/organizations_for_selection'>
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
			service.json("selection","create_campaign",{name:text.trim()},function(res){
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
function renameCampaign() {
	alert("TODO");
}
function removeCampaign() {
	alert("TODO");
}
</script>
<?php 
	}
	
}
?>