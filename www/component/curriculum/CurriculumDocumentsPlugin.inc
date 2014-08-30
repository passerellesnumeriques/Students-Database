<?php 
require_once("component/documents/DocumentsPlugin.inc");
class CurriculumAttachedDocumentsPlugin implements AttachedDocumentsPlugin {
	
	public function getTable() { return "StudentBatch"; }
	public function getType() { return "curriculum"; }
	public function maxVersions() { return 1; }
	public function canRead($key, $sub_model) {
		if (PNApplication::$instance->user_management->has_right("consult_curriculum"))
			return true;
		return false;
	}
	public function canWrite($key, $sub_model) {
		if (PNApplication::$instance->user_management->has_right("edit_curriculum"))
			return true;
		return false;
	}
	public function canAddAndRemove($key, $sub_model) {
		if (PNApplication::$instance->user_management->has_right("edit_curriculum"))
			return true;
		return false;
	}
	
	
}
?>