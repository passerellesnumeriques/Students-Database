<?php 
class service_get_specializations extends Service {
	
	public function getRequiredRights() { return array("consult_curriculum"); }
	
	public function documentation() { echo "returns the list of existing specializations"; }
	public function inputDocumentation() { echo "none"; }
	public function outputDocumentation() { echo "An array of Specialization objects"; }
	
	public function execute(&$component, $input) {
		require_once 'component/curriculum/CurriculumJSON.inc';
		echo CurriculumJSON::SpecializationsJSON();
	}
	
}
?>