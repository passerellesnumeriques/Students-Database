<?php 
require_once("/../selection_page.inc");
require_once("exam_subject.inc");
class page_exam_sessions extends selection_page {
	public function get_required_rights() { return array(); }
	public function execute_selection_page(&$page){
		echo "Not yet implemented";
	}
}