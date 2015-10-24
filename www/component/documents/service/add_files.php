<?php 
class service_add_files extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Upload new documents"; }
	public function inputDocumentation() {
?>
<ul>
	<li><code>table</code> and <code>sub_model</code>: the table to which the documents are attached</li>
	<li><code>type</code>: defines, together with the table, the DocumentsPlugin used</li>
	<li><code>key</code>: the key in the table defining the row to which the documents are attached</li>
	<li>the uploaded files</li>
</ul>
<?php
	}
	public function outputDocumentation() {
?>The list of uploaded documents:<ul>
	<li><code>id</code>: id of the Document</li>
	<li><code>name</code>: name of the Document, which is the uploaded file name</li>
	<li><code>versions</code>: array containing a single version represented by an object {id,time,type,storage_id,revision}</li>
</ul>
<?php
	}
	
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
			$version_id = SQLQuery::create()->bypassSecurity()->insert("DocumentVersion", array("document"=>$doc_id,"file"=>$ids[$i],"timestamp"=>$now,"people"=>PNApplication::$instance->user_management->people_id));
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