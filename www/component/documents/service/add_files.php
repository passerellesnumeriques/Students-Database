<?php 
class service_add_files extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		$table = $_GET["table"];
		$sub_model = @$_GET["sub_model"];
		$type = $_GET["type"];
		$key = $_GET["key"];
		$pi = $component->getAttachedDocumentsPlugin($table,$type);
		if ($pi == null) {
			PNApplication::error("Invalid request");
			return;
		}
		if (!$pi->canAddAndRemove($key, $sub_model)) {
			PNApplication::error("Access denied");
			return;
		}
		SQLQuery::startTransaction();
		$ids = array();
		$names = array();
		$types = array();
		$sizes = array();
		PNApplication::$instance->storage->receive_upload($ids, $names, $types, $sizes, 60);
		echo "[";
		for ($i = 0; $i < count($ids); $i++) {
			$doc_id = SQLQuery::create()->bypassSecurity()->insert("Document", array("name"=>$names[$i]));
			$now = time();
			$version_id = SQLQuery::create()->bypassSecurity()->insert("DocumentVersion", array("document"=>$doc_id,"file"=>$ids[$i],"timestamp"=>$now,"user"=>PNApplication::$instance->user_management->user_id));
			SQLQuery::create()->bypassSecurity()->insert("AttachedDocument", array("document"=>$doc_id,"table"=>$table,"key"=>$key,"sub_model"=>$sub_model,"type"=>$type));
			if ($i > 0) echo ",";
			echo "{";
			echo "id:$doc_id";
			echo ",name:".json_encode($names[$i]);
			echo ",versions:[{id:$version_id,time:$now,type:".json_encode($types[$i]).",storage_id:".$ids[$i].",revision:1}]";
			echo "}";
		}
		echo "]";
		if (!PNApplication::hasErrors()) {
			for ($i = 0; $i < count($ids); $i++)
				PNApplication::$instance->storage->convertTempFile($ids[$i], "document");
			SQLQuery::commitTransaction();
		}
	}
}
?>