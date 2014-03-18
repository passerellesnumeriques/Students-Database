
<?php 
class service_applicant_get_assignment_figures extends Service {
	
	public function get_required_rights() {return array("can_access_selection_data");}
	
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {
		
	}
	
	public function execute(&$component, $input) {
		$applicants_total = SQLQuery::create()
			->bypassSecurity()
			->select("Applicant")
			->count()
			->executeSingleValue();
		$applicants_total == null ? 0 : $applicants_total;
		
		$applicants_assigned = SQLQuery::create()
			->bypassSecurity()
			->select("Applicant")
			->count()
			->whereNull('Applicant', "exam_center")
			->executeSingleValue();
		$applicants_not_assigned == null ? 0 : $applicants_assigned;
		
		echo '{not_assigned:'.json_encode($applicants_not_assigned).",total:".json_encode($applicants_total)."}";
	}
	
}
?>