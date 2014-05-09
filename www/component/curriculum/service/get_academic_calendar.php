<?php 
class service_get_academic_calendar extends Service {
	
	public function get_required_rights() { return array("consult_curriculum"); }
	
	public function documentation() { echo "Returns the list of academic years and periods"; }
	public function input_documentation() { echo "none"; }
	public function output_documentation() { echo "A list of AcademicYear"; }
	
	public function execute(&$component, $input) {
		require_once("component/curriculum/CurriculumJSON.inc");
		echo CurriculumJSON::AcademicCalendarJSON();
	}
	
}
?>