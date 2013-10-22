<?php
require_once("ProfilePlugin.inc");
require_once("ProfileGeneralInfoPlugin.inc");
class people extends Component implements ProfilePlugin, ProfileGeneralInfoPlugin {

	public function get_profile_pages(&$people) {
		return array(
			"profile_people"=>array("/static/people/profile_16.png", "General Information", "/dynamic/people/page/profile_people?people=".$people, 1),
		);
	}
	
	public function get_people_profile_general_info_sections($people_id) {
		return array(array("personal_info","<img src='/static/people/people_info.png' style='vertical-align:bottom;padding-right:3px'/>Personal Information",1));
	}
	
	public function generate_people_profile_general_info_section(&$page, $people_id, $section_id) {
		if ($section_id == "personal_info") {
			require_once("page/picture_and_info.inc");
			people_picture_and_info($page, $people_id);
		}
	}
	
	public function get_create_people_pages($people_type) {
		return array(
			array("General Information", "component/people/page/create_people__personal_info.inc",1)
		);
	}
	public function create_people($people_id, $people_type, $input, &$create_data) { return true; }
	public function rollback_create_people($people_id, $people_type, $input, &$create_data) {}
	
}
?>