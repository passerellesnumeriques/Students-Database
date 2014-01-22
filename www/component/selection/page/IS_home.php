<?php 
require_once("selection_page.inc");
class page_IS_home extends selection_page {
	public function get_required_rights() { return array(); }
	public function execute_selection_page(&$page){
		$this->add_javascript("/static/widgets/frame_header.js");
		$this->onload("new frame_header('IS_home');");
		$rights = array();
		$config = null;
		$rights["read"] = PNApplication::$instance->user_management->has_right("see_information_session_details",true);
		$rights["write"] = PNApplication::$instance->user_management->has_right("edit_information_session",true);
		$rights["add"] = PNApplication::$instance->user_management->has_right("manage_information_session",true);
		$rights["remove"] = PNApplication::$instance->user_management->has_right("manage_information_session",true);
		$config = PNApplication::$instance->selection->get_config();
		$calendar_id = PNApplication::$instance->selection->get_calendar_id();
	?>
		<div id='IS_home'
			icon='/static/selection/IS_32.png' 
			title='Information Sessions'
			page='/dynamic/selection/page/IS_main_page'
		>
		<a class = 'button' href = '/dynamic/selection/page/IS_profile' target = 'selection_page_content'> TEMP: IS Profile </a>
		</div>
		
	<?php
	}
	
}