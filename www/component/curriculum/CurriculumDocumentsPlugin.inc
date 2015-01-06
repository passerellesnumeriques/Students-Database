<?php 
require_once("component/documents/DocumentsPlugin.inc");
/**
 * Add the possibility to attach documents to the curriculum of a batch 
 */
 class CurriculumAttachedDocumentsPlugin implements AttachedDocumentsPlugin {
	
	public function getTable() { return "StudentBatch"; }
	public function getType() { return "curriculum"; }
	public function maxVersions() { return 1; }
	public function canRead($key, $sub_model) {
		if (PNApplication::$instance->user_management->hasRight("consult_curriculum"))
			return true;
		return false;
	}
	public function canWrite($key, $sub_model) {
		if (PNApplication::$instance->user_management->hasRight("edit_curriculum"))
			return true;
		return false;
	}
	public function canAddAndRemove($key, $sub_model) {
		if (PNApplication::$instance->user_management->hasRight("edit_curriculum"))
			return true;
		return false;
	}
	
	
}
?>