<?php 
class service_add_period_specialization extends Service {
	
	public function getRequiredRights() { return array("edit_curriculum"); }
	
	public function documentation() { echo "Add a specialization to an academic period"; }
	public function inputDocumentation() { echo "<code>period</code>: period id, <code>specialization</code>: specialization id"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		// check it does not exist already
		$exists = SQLQuery::create()->select("AcademicPeriodSpecialization")->whereValue("AcademicPeriodSpecialization","period",$input["period"])->whereValue("AcademicPeriodSpecialization","specialization",$input["specialization"])->execute();
		if (count($exists) == 0) {
			// if there are subjects without specialization, we need to move them
			SQLQuery::getDataBaseAccessWithoutSecurity()->execute("UPDATE `CurriculumSubject` SET `specialization`='".SQLQuery::escape($input["specialization"])."' WHERE `specialization` IS NULL AND `period`='".SQLQuery::escape($input["period"])."'");
			// if there are classes without specialization, we need to move them
			SQLQuery::getDataBaseAccessWithoutSecurity()->execute("UPDATE `AcademicClass` SET `specialization`='".SQLQuery::escape($input["specialization"])."' WHERE `specialization` IS NULL AND `period`='".SQLQuery::escape($input["period"])."'");
			// add new specialization
			SQLQuery::create()->insert("AcademicPeriodSpecialization", array("period"=>$input["period"], "specialization"=>$input["specialization"]));
		}
		echo "true";
	}
	
}
?>