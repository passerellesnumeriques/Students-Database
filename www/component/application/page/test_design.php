<?php 
class page_test_design extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		include("test_design.inc");
	}
	
}
?>