<?php 
class page_import_students extends Page {
	
	public function get_required_rights() { return array("manage_batches"); }
	
	public function execute() {
		$input = json_decode(file_get_contents('php://input'), true);

		require_once("component/people/page/import_people.inc");
		$data_list = array();
		import_people(
			$this, 
			"/static/application/icon.php?main=/static/students/student_32.png&small=".theme::$icons_16["add"]."&where=right_bottom",
			"Import Students",
			$data_list
		);
	}
	
}
?>