<?php 
require_once("component/data_import/DataImportTemplatePlugin.inc");
class ApplicantImportTemplate implements DataImportTemplatePlugin {
	
	public function getId() { return "selection_applicant"; }
	public function canRead() {
		return PNApplication::$instance->user_management->hasRight("edit_applicants");
	}
	public function canWrite() {
		return PNApplication::$instance->user_management->hasRight("edit_applicants");
	}
	
}
?>