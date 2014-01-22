<?php 
require_once("/../selection_page.inc");
require_once("profile.inc");
class page_IS_profile extends selection_page {
	public function get_required_rights() { return array(); }
	public function execute_selection_page(&$page){
	$name = $page->generateID();
	if(!isset($_GET["id"]))
		$id = -1;
	else if($_GET["id"] == "-1")
		$id = -1;
	else
		$id = $_GET["id"];
	?>
		<div id = "page_header">
			<div class = "button" onclick = "location.assign('/dynamic/selection/page/IS/main_page');"><img src = '<?php echo theme::$icons_16['back'];?>'/> Back to list</div>
			<div id = "save_IS_button"></div>
			<div id = "remove_IS_button"></div>
		</div>
		<div id='IS_profile_<?php echo $name; ?>'></div>
		
	<?php
		// IS_profile($page,"IS_profile_".$name,$id);
		//temp:
		IS_profile($page,"IS_profile_".$name,$id,"save_IS_button","remove_IS_button");
	}
	
}