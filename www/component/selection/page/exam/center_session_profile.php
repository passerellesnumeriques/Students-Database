<?php
require_once("/../selection_page.inc");
require_once("component/selection/SelectionJSON.inc");
class page_exam_center_session_profile extends selection_page {
	
	public function get_required_rights() {return array("see_exam_center_detail");}
	
	public function execute_selection_page(&$page) {
		
	}
}