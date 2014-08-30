<?php 
interface AttachedDocumentsPlugin {
	
	public function getTable();
	public function getType();
	public function maxVersions();
	public function canRead($key, $sub_model);
	public function canWrite($key, $sub_model);
	public function canAddAndRemove($key, $sub_model);
	
}
?>