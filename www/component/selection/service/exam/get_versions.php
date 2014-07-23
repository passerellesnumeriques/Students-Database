<?php 
class service_exam_get_versions extends Service {
	
	public function getRequiredRights() { return array("see_exam_subject"); }
	public function documentation() { return "Retrieve a list of subjects version ordered by exam subject id's"; }
	public function inputDocumentation() {
		echo "All fields are optionals, to select subjects:<ul>";
		echo "<li><code>use_vn</code>: use subject version numbering </li>";
		echo "<li><code>id</code>: exam subject version id</li>";
		echo "<li><code>exam_subject</code>: exam subject id</li>";
		echo "</ul>";
	}
	public function outputDocumentation() { echo "A list of ExamSubjectVersion JSON object"; }
	
	public function execute(&$component, $input) {
		
		$q = SQLQuery::create()->select("ExamSubjectVersion");
		if (isset($input["id"])) $q->whereValue("ExamSubjectVersion", "id", $input["id"]);
		if (isset($input["exam_subject"])) $q->whereValue("ExamSubjectVersion", "exam_subject", $input["exam_subject"]);
		require_once("component/selection/SelectionJSON.inc");
		SelectionJSON::ExamSubjectVersionSQL($q);
		
		$rows = $q->execute();
		
		if($rows == null){
			echo "false";
			return;
		}
		
		echo SelectionJSON::ExamSubjectVersionJSON($rows);
					
	}	
}

?>