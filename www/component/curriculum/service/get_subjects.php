<?php 
class service_get_subjects extends Service {
	
	public function get_required_rights() { return array("consult_curriculum"); }
	
	public function documentation() { echo "Retrieve list of existing subjects"; }
	public function input_documentation() {
		echo "<code>category</code>: category id to filter subjects only in this category<br/>";
		echo "<code>specialization</code>: specialization id to filter subjects only for this specialization, or null for subject not related to a specialization<br/>";
		echo "<code>period_to_exclude</code>: period id, subjects already attached to this period will not be returned<br/>";
	}
	public function output_documentation() {
		echo "List of subjects: {id,code,name,hours,hours_type}";
	}
	
	public function execute(&$component, $input) {
		$q = SQLQuery::create()
			->select("CurriculumSubject")
			->whereValue("CurriculumSubject", "category", $input["category"])
			->where("`CurriculumSubject`.`period` != '".SQLQuery::escape($input["period_to_exclude"])."'");
		if (isset($input["specialization"]) && $input["specialization"] <> null)
			$q->whereValue("CurriculumSubject", "specialization", $input["specialization"]);
		else
			$q->whereNull("CurriculumSubject", "specialization");
		$list = $q->execute();
		echo "[";
		$first = true;
		foreach ($list as $subject) {
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "id:".$subject["id"];
			echo ",code:".json_encode($subject["code"]);
			echo ",name:".json_encode($subject["name"]);
			echo ",hours:".json_encode($subject["hours"]);
			echo ",hours_type:".json_encode($subject["hours_type"]);
			echo ",coefficient:".json_encode($subject["coefficient"]);
			echo "}";
		}
		echo "]";
	}
	
}
?>