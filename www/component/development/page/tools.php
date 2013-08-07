<?php 
class page_tools extends Page {
	
	public function get_required_rights() {
		return array();
	}
	
	protected function execute() {
?>
<a href="reset_db">Reset DataBase, and populate with test data</a><br/>
<a href="/">Back to application</a>
<?php
	}
	
}
?>