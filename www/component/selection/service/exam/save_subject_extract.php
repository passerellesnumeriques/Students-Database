<?php 
class service_exam_save_subject_extract extends Service {
	
	public function getRequiredRights() { return array("manage_exam_subject"); }
	
	public function documentation() { echo "Save an ExamSubjectExtract"; }
	public function inputDocumentation() {
		echo "<ul>";
		echo "<li><code>extract</code>: ExamSubjectExtract ID, or -1 for a new one</li>";
		echo "<li><code>name</code>: the name of the extract</li>";
		echo "<li><code>parts</code>: array of ExamSubjectPart IDs</li>";
		echo "</ul>";
	}
	public function outputDocumentation() {
		echo "on success, return <code>id</code> which is the ExamSubjectExtract ID";
	}
	
	public function execute(&$component, $input) {
		$id = isset($input["extract"]) ? intval($input["extract"]) : -1;
		$name = trim($input["name"]);
		$parts = $input["parts"];
		
		// TODO check we can really modify this (not yet any grade...)
		
		if (strlen($name) == 0) {
			PNApplication::error("Empty name");
			return;
		}
		if (count($parts) == 0) {
			PNApplication::error("No part");
			return;
		}
		
		SQLQuery::startTransaction();
		
		// check unicity of name
		$same_name = SQLQuery::create()->select("ExamSubjectExtract")->where("`name` LIKE '".SQLQuery::escape($name)."'");
		if ($id > 0)
			$same_name->where("`id` != ".$id);
		$same_name = $same_name->executeSingleRow();
		if ($same_name <> null) {
			PNApplication::error("An extract of a subject already exists with the same name");
			return;
		}
		
		// check the given parts are valid
		$check = SQLQuery::create()->select("ExamSubjectPart")->whereIn("ExamSubjectPart", "id", $parts)->execute();
		if (count($check) <> count($parts)) {
			PNApplication::error("Invalid exam parts");
			return;
		}
		
		// everything ok, we can update
		if ($id > 0) {
			SQLQuery::create()->bypassSecurity()->updateByKey("ExamSubjectExtract", $id, array("name"=>$name));
			$rows = SQLQuery::create()->select("ExamSubjectExtractParts")->whereValue("ExamSubjectExtractParts","extract",$id)->execute();
			SQLQuery::create()->bypassSecurity()->removeRows("ExamSubjectExtractParts", $rows);
		} else
			$id = SQLQuery::create()->bypassSecurity()->insert("ExamSubjectExtract", array("name"=>$name));
		$to_insert = array();
		foreach ($parts as $p) array_push($to_insert, array("part"=>$p,"extract"=>$id));
		SQLQuery::create()->bypassSecurity()->insertMultiple("ExamSubjectExtractParts", $to_insert);
		if (PNApplication::hasErrors()) return;
		SQLQuery::commitTransaction();
		echo "{id:".$id."}";
	}
	
}
?>