<?php 
class service_merge_classes extends Service {
	
	public function get_required_rights() { return array("edit_curriculum"); }
	
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}
	
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