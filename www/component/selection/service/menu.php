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
<div style="padding-left:5px;text-align:center;margin-bottom:5px">
Selection Campaign:<br/>
<select onchange="changeCampaign(this.value);"><?php 
foreach ($campaigns as $c) {
	echo "<option value='".$c["id"]."'";
	if ($c["id"] == $id) echo " selected='selected'";
	echo ">".htmlentities($c["name"])."</option>";
}
?></select>
<?php if ($can_manage) { ?>
<button class='flat' onclick='createCampaign();' title='Create a new Selection Campaign'><img src='<?php echo theme::$icons_16["add"];?>'/></button>
<?php } ?>
</div>
<a class='application_left_menu_item' href='/dynamic/selection/page/selection_main_page'>
	<img src='/static/selection/dashboard_steps.png'/>
	Dashboard
</a>
<a class='application_left_menu_item' href='/dynamic/selection/page/is/main_page'>
	<img src='/static/selection/is/is_16.png'/>
	Information Sessions
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
</script>
<?php 
	}
	
}
?>