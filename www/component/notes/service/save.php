<?php 
class service_save extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Save a note"; }
	public function inputDocumentation() { echo "id,title,text,author,table,key,sub_model,sub_model_instance"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		// check access
		if (!$component->getWriteAccess($input["table"], $input["key"], $input["sub_model"], $input["sub_model_instance"])) {
			PNApplication::error("Access denied");
			return;
		}
		// check the note is really attached to this one
		$q = SQLQuery::create()->bypassSecurity();
		if ($input["sub_model"] <> null) $q->selectSubModel($input["sub_model"], $input["sub_model_instance"]);
		$q->select("Notes".$input["table"]);
		$q->whereValue("Notes".$input["table"], "attach", $input["key"]);
		$q->whereValue("Notes".$input["table"], "note", $input["id"]);
		$note = $q->executeSingleRow();
		if ($note == null) {
			PNApplication::error("The note has been removed already");
			return;
		}
		// save the note
		SQLQuery::create()->bypassSecurity()->updateByKey("Note", $input["id"], array(
			"title"=>$input["title"],
			"text"=>$input["text"],
			"author"=>$input["author"],
			"timestamp"=>time()
		));
		echo "true";
	}
	
}
?>