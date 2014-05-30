<?php 
class service_remove extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
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