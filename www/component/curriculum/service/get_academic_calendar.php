<?php 
class service_get_academic_calendar extends Service {
	
	public function get_required_rights() { return array("consult_curriculum"); }
	
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}
	
	public function execute(&$component, $input) {
		require_once("component/curriculum/CurriculumJSON.inc");
		echo CurriculumJSON::AcademicCalendarJSON();
	}
	
}
?>