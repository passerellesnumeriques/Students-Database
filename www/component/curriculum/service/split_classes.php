<?php 
class service_split_classes extends Service {
	
	public function getRequiredRights() { return array("edit_curriculum"); }
	
	public function documentation() { echo "Split classes previously merged"; }
	public function inputDocumentation() {
		echo "<ul>";
		echo "<li><code>subject</code> ID of the CurriculumSubject</li>";
		echo "<li><code>classes</code> IDs of the AcademicClass which were merged and need to be split</li>";
		echo "</ul>";
	}
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$subject_id = $input["subject"];
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
		echo "true";
	}
	
}
?>