<?php 
class service_add_period_specialization extends Service {
	
	public function get_required_rights() { return array("edit_curriculum"); }
	
	public function documentation() { echo "Add a specialization to an academic period"; }
	public function input_documentation() { echo "<code>period</code>: period id, <code>specialization</code>: specialization id"; }
	public function output_documentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		// check it does not exist already
		$exists = SQLQuery::create()->select("AcademicPeriodSpecialization")->where_value("AcademicPeriodSpecialization","period",$input["period"])->where_value("AcademicPeriodSpecialization","specialization",$input["specialization"])->execute();
		if (count($exists) == 0) {
			// if there are subjects without specialization, we need to move them
			SQLQuery::get_db_system_without_security()->execute("UPDATE `CurriculumSubject` SET `specialization`='".SQLQuery::escape($input["specialization"])."' WHERE `specialization` IS NULL AND `period`='".SQLQuery::escape($input["period"])."'");
			// if there are classes without specialization, we need to move them
			SQLQuery::get_db_system_without_security()->execute("UPDATE `AcademicClass` SET `specialization`='".SQLQuery::escape($input["specialization"])."' WHERE `specialization` IS NULL AND `period`='".SQLQuery::escape($input["period"])."'");
			// add new specialization
			SQLQuery::create()->insert("AcademicPeriodSpecialization", array("period"=>$input["period"], "specialization"=>$input["specialization"]));
		}
		echo "true";
	}
	
}
?>