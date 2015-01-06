<?php 
class service_create extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Create a note"; }
	public function inputDocumentation() { echo "title,text,author,table,key,sub_model,sub_model_instance"; }
	public function outputDocumentation() { echo "id on success"; }
	
	public function execute(&$component, $input) {
		// check access
		if (!$component->getWriteAccess($input["table"], $input["key"], $input["sub_model"], $input["sub_model_instance"])) {
			PNApplication::error("Access denied");
			return;
		}
		// create the note
		SQLQuery::startTransaction();
		$id = SQLQuery::create()->bypassSecurity()->insert("Note", array(
			"title"=>$input["title"],
			"text"=>$input["text"],
			"author"=>$input["author"],
			"timestamp"=>time()
		));
		SQLQuery::create()->bypassSecurity()->insert("Notes".$input["table"], array("note"=>$id,"attach"=>$input["key"]));
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "{id:$id}";
		}
	}
	
}
?>