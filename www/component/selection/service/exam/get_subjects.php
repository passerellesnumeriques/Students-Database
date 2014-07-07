<?php 
class service_exam_get_subjects extends Service {
	
	public function getRequiredRights() { return array("see_exam_subject"); }
	
	public function documentation() { return "Retrieve a list of subjects"; }
	public function inputDocumentation() {
		echo "All fields are optionals, to select subjects:<ul>";
		echo "<li><code>id</code>: exam subject id</li>";
		echo "<li><code>name</code>: exam subject name</li>";
		echo "<li><code>max_score</code>: maximal score ID</li>";
		echo "</ul>";
	}
	public function outputDocumentation() { echo "A list of ExamSubject JSON object"; }
	
	public function execute(&$component, $input) {
		$q = SQLQuery::create()->select("ExamSubject");
		if (isset($input["id"])) $q->whereValue("ExamSubject", "id", $input["id"]);
		if (isset($input["name"])) $q->whereValue("ExamSubject", "name", $input["name"]);
		if (isset($input["max_score"])) $q->whereValue("ExamSubject", "max_score", $input["max_score"]);
		require_once("component/selection/SelectionJSON.inc");
		SelectionJSON::ExamSubjectFullSQL($q);
		$rows = $q->execute();
		
		if($rows == null){
			echo "false";
			return;
		}

		echo SelectionJSON::ExamSubjectsJSON($rows);
	}
}
?>