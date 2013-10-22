<?php 
interface ProfileGeneralInfoPlugin {
	
	/** returns an array of (section_id,section_title,priority) */
	public function get_people_profile_general_info_sections($people_id);
	
	public function generate_people_profile_general_info_section(&$page, $people_id, $section_id);
	
	/** returns an array of (title,page_path,priority) */
	public function get_create_people_pages($people_type);
	
	public function create_people($people_id, $people_type, $input, &$create_data);
	public function rollback_create_people($people_id, $people_type, $input, &$create_data);
	
}
?>