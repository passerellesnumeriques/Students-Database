<?php 
require_once("component/documents/DocumentsPlugin.inc");
class PeopleDocuments implements AttachedDocumentsPlugin {
	
	public function getTable() { return "People"; }
	public function getType() { return "people"; }
	public function maxVersions() { return 1; }
	public function canRead($key, $sub_model) {
		return PNApplication::$instance->people->canRead($key);
	}
	public function canWrite($key, $sub_model) {
		return PNApplication::$instance->people->canModify($key);
	}
	public function canAddAndRemove($key, $sub_model) {
		return PNApplication::$instance->people->canModify($key);
	}
	
}
?>