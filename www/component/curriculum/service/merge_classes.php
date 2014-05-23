<?php 
class service_merge_classes extends Service {
	
	public function getRequiredRights() { return array("edit_curriculum"); }
	
	public function documentation() { echo "Merge classes for a given subject: the classes will be handled in the same room with same teacher"; }
	public function inputDocumentation() {
		echo "<ul>";
		echo "<li><code>subject</code> ID of the CurriculumSubject</li>";
		echo "<li><code>to</code> ID of the AcademicClass with which it will be merged</li>";
		echo "<li><code>classes</code> IDs of the classes to merge</li>";
		echo "</ul>";
	}
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$subject_id = $input["subject"];
		$to_class_id = $input["to"];
		$classes = $input["classes"];
		// remove any existing merge
		$w = "";
		foreach ($classes as $c) {
			if ($w <> "") $w .= " OR ";
			$w .= "(`class1`='".SQLQuery::escape($c)."' OR `class2`='".SQLQuery::escape($c)."')";
		}
		$rows = SQLQuery::create()
			->select("SubjectClassMerge")
			->whereValue("SubjectClassMerge", "subject", $subject_id)
			->where("(".$w.")")
			->execute();
		if (count($rows) > 0) SQLQuery::create()->removeRows("SubjectClassMerge",$rows);
		$rows = array();
		foreach ($classes as $c)
			array_push($rows, array("subject"=>$subject_id,"class1"=>$to_class_id,"class2"=>$c));
		SQLQuery::create()->insertMultiple("SubjectClassMerge", $rows);
		echo "true";
	}
	
}
?>