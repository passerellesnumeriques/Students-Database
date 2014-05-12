<?php 
class service_get_academic_calendar extends Service {
	
	public function getRequiredRights() { return array("consult_curriculum"); }
	
	public function documentation() { echo "Returns the list of academic years and periods"; }
	public function inputDocumentation() { echo "none"; }
	public function outputDocumentation() { echo "A list of AcademicYear"; }
	
	public function execute(&$component, $input) {
		require_once("component/curriculum/CurriculumJSON.inc");
		echo CurriculumJSON::AcademicCalendarJSON();
	}
	
}
?>