<?php 
require_once("/../selection_page.inc");
require_once("profile.inc");
class page_IS_profile extends selection_page {
	public function get_required_rights() { return array(); }
	public function execute_selection_page(&$page){
		
	$name = $page->generateID();
	$page->add_javascript("/static/widgets/vertical_layout.js");
	$page->onload("new vertical_layout('IS_profile_container');");
	if(!isset($_GET["id"]))
		$id = -1;
	else if($_GET["id"] == "-1")
		$id = -1;
	else
		$id = $_GET["id"];
	?>
		<div id = "IS_profile_container" style = "width:100%; height:100%">
			<div id = "page_header">
				<div class = "button" onclick = "location.assign('/dynamic/selection/page/IS/main_page');"><img src = '<?php echo theme::$icons_16['back'];?>'/> Back to list</div>
				<div class = "button" id = "save_IS_button"><img src = '<?php echo theme::$icons_16["save"];?>' /> <b>Save</b></div>
				<div class = "button" id = "remove_IS_button"><img src = '<?php echo theme::$icons_16["remove"];?>' /> Remove Information Session</div>
			</div>
			<div id='IS_profile_<?php echo $name; ?>' style = "overflow:auto" layout = "fill"></div>
		</div>
		
	<?php
		// IS_profile($page,"IS_profile_".$name,$id);
		//temp:
		IS_profile($page,"IS_profile_".$name,$id,"save_IS_button","remove_IS_button");
	}
	
}