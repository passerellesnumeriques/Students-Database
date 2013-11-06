<?php 
interface OrganizationPlugin {
	
	public function getCreator();
	
	public function canRead();
	public function canWrite();
	public function canInsert();
	public function canRemove();
	
}
?>