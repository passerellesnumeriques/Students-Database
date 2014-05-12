<?php 
class service_remove_specialization_from_period extends Service {
	
	public function getRequiredRights() { return array("manage_batches"); }
	
	public function documentation() { echo "Remove a specialization from an academic period"; }
	public function inputDocumentation() { echo "specialization, period"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		
		// remove all linked classes
		$classes = SQLQuery::create()->select("AcademicClass")->where("period", $input["period"])->where("specialization", $input["specialization"])->execute();
		foreach ($classes as $cl)
			SQLQuery::create()->removeKey("AcademicClass", $cl["id"]);
		// remove the specialization
		SQLQuery::create()->removeKey("AcademicPeriodSpecialization", array("period"=>$input["period"], "specialization"=>$input["specialization"]));
		// remove all subjects related
		$subjects = SQLQuery::create()->select("CurriculumSubject")->whereValue("CurriculumSubject","period", $input["period"])->whereValue("CurriculumSubject", "specialization", $input["specialization"])->execute();
		SQLQuery::create()->removeRows("CurriculumSubject", $subjects);
		
		SQLQuery::commitTransaction();
		echo "true";
	}
	
}
?>