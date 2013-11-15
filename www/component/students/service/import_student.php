<?php 
class service_import_student extends Service {
	
	public function get_required_rights() { return array("manage_batches"); }
	
	public function documentation() { echo "Import a student"; }
	public function input_documentation() { echo "Data from custom_import.js"; }
	public function output_documentation() { echo "On success, return <code>id</code>: the people id of the new student"; }
	
	public function execute(&$component, $input) {
		require_once("component/people/service/import_people.inc");
		$batch_id = getDataDisplayFromInput("Student", "Batch", $input);
		if ($batch_id == null) {
			PNApplication::error("Invalid data to import a student: no batch");
			return;
		}
		$people_id = import_people($input);
		if ($people_id == null) return;
		try {
			SQLQuery::create()->insert("Student", array("people"=>$people_id,"batch"=>$batch_id));
		} catch (Exception $e) {
			PNApplication::error($e);
			// rollback
			SQLQuery::create()->bypass_security()->remove_key("People", $people_id);
			return;
		}
		echo "{id:".$people_id."}";
	}
	
}
?>