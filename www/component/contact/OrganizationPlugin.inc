<?php 
interface OrganizationPlugin {
	
	public function getOrganizationCreator();
	
	public function canReadOrganization();
	public function canWriteOrganization();
	public function canInsertOrganization();
	public function canRemoveOrganization();
	
}
?>