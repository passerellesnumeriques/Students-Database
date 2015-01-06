<?php 
class service_remove extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Remove a stored file"; }
	public function inputDocumentation() { echo "id"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$id = $input["id"];
		$file = SQLQuery::create()->bypassSecurity()->select("Storage")->whereValue("Storage", "id", $id)->executeSingleRow();
		if ($file == null) {
			PNApplication::error("Invalid storage id");
			return;
		}
		if (!$component->canWriteFile($file)) {
			PNApplication::error("Access Denied.");
			return;
		}
		$component->remove_data($id);
		echo "true";
	}
	
}
?>