<?php 
class page_phpinfo extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		phpinfo();
	}
	
}
?>