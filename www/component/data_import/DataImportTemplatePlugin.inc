<?php 
interface DataImportTemplatePlugin {
	
	public function getId();
	public function canRead();
	public function canWrite();
	
}
?>