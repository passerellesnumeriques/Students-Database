<?php
/**
 * Handles curriculum information: students batches, academic periods, specializations, classes, and subjects. However, nothing is linked to a student, this is done by the student component 
 */ 
class curriculum extends Component {
	
	public function getPluginImplementations() {
		require_once("CurriculumStudentsPlugin.inc");
		return array(
			new CurriculumStudentsPlugin()
		);
	}
	
	public function getAcademicClass($class_id) {
		return SQLQuery::create()->select("AcademicClass")->where("id", $class_id)->executeSingleRow();
	}
	
	public function joinAcademicClass(&$q, $from_table, $from_table_foreign_key, $filter_period_id = null) {
		$q->join($from_table, "AcademicClass", array($from_table_foreign_key=>"id"));
		if ($filter_period_id <> null)
			$q->whereValue("AcademicClass", "period", $filter_period_id);
	}
	
}
?>