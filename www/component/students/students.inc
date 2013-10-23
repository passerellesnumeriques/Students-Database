<?php
require_once("component/people/ProfilePlugin.inc"); 
require_once("component/people/ProfileGeneralInfoPlugin.inc"); 
class students extends Component implements ProfilePlugin, ProfileGeneralInfoPlugin {
	
	public function get_profile_pages(&$people) {
		$r = SQLQuery::create()->select("Student")->where("people",$people)->execute_single_row();
		if ($r == null) return array(); // not a student
		return array(); // TODO
// 		return array(
// 			"profile_batch"=>array("/static/people/profile_16.png", "Batch & Classes", "/dynamic/students/page/profile_batch?people=".$people, 10),
// 		);
	}
	
	public function get_people_profile_general_info_sections($people_id) {
		return array();
	}
	
	public function generate_people_profile_general_info_section(&$page, $people_id, $section_id) {
		
	}
	
	public function get_create_people_pages($people_type) {
		if ($people_type <> "student") return array();
		return array(
			array("Student Batch", "component/students/page/create_people__student.inc",6)
		);
	}
	
	public function create_people($people_id, $people_type, $input, &$create_data) {
		if ($people_type <> "student") return true;
		SQLQuery::create()->insert("Student", array(
			"people"=>$people_id,
			"batch"=>$input["student_batch"]
		));
		return true;
	}
	public function rollback_create_people($people_id, $people_type, $input, &$create_data) {
		// automatically done, when people removed
	}
	
}
?>