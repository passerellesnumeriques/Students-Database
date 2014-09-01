<?php 
require_once("component/storage/StoragePlugin.inc");
class DocumentsStorage implements StoragePlugin {
	public function getType() { return "document"; }
	public function canRead($id) {
		$doc = SQLQuery::create()->bypassSecurity()
			->select("DocumentVersion")
			->whereValue("DocumentVersion","file",$id)
			->executeSingleRow();
		if ($doc == null) return false;
		$attached = SQLQuery::create()->bypassSecurity()
			->select("AttachedDocument")
			->whereValue("AttachedDocument","document",$doc["document"])
			->executeSingleRow();
		if ($attached <> null) {
			$pi = PNApplication::$instance->documents->getAttachedDocumentsPlugin($attached["table"], $attached["type"]);
			if ($pi == null) return false;
			return $pi->canRead($attached["key"], $attached["sub_model"]);
		}
		return false;
	}
	public function canWrite($id) {
		$doc = SQLQuery::create()->bypassSecurity()
			->select("DocumentVersion")
			->whereValue("DocumentVersion","file",$id)
			->executeSingleRow();
		if ($doc == null) return false;
		$attached = SQLQuery::create()->bypassSecurity()
			->select("AttachedDocument")
			->whereValue("AttachedDocument","document",$doc["document"])
			->executeSingleRow();
		if ($attached <> null) {
			$pi = PNApplication::$instance->documents->getAttachedDocumentsPlugin($attached["table"], $attached["type"]);
			if ($pi == null) return false;
			return $pi->canWrite($attached["key"], $attached["sub_model"]);
		}
		return false;
	}
}
?>