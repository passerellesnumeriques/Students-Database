<?php 
require_once("component/storage/StoragePlugin.inc");
class PeoplePictureStoragePlugin implements StoragePlugin {
	
	public function getType() { return "people_picture"; }
	
	public function canRead($id) {
		/*
		$people = SQLQuery::create()->bypassSecurity()->select("People")->whereValue("People","picture",$id)->field("types")->field("id")->executeSingleRow();
		if ($people == null) return false;
		if ($people["id"] == PNApplication::$instance->user_management->user_id) return true;
		$types = PNApplication::$instance->people->parseTypes($people["types"]);
		return PNApplication::$instance->people->canReadTypes($types);
		*/
		return true;
	}
	public function canWrite($id) {
		$types = SQLQuery::create()->bypassSecurity()->select("People")->whereValue("People","picture",$id)->field("types")->executeSingleValue();
		if ($types == null) return false;
		$types = PNApplication::$instance->people->parseTypes($types);
		return PNApplication::$instance->people->canModifyTypes($types);
	}
	
}
?>