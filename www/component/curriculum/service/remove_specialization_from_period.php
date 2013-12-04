<?php 
class service_remove_specialization_from_period extends Service {
	
	public function get_required_rights() { return array("manage_batches"); }
	
	public function documentation() { echo "Remove a specialization from an academic period"; }
	public function input_documentation() { echo "specialization, period"; }
	public function output_documentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		// remove all linked classes
		$classes = SQLQuery::create()->select("AcademicClass")->where("period", $input["period"])->where("specialization", $input["specialization"])->execute();
		foreach ($classes as $cl)
			SQLQuery::create()->remove_key("AcademicClass", $cl["id"]);
		// remove the specialization
		SQLQuery::create()->remove_key("AcademicPeriodSpecialization", array("period"=>$input["period"], "specialization"=>$input["specialization"]));
		echo "true";
	}
	
}
?>