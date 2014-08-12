<?php 
class page_server_variables extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		var_dump($_SERVER);
	}
}
?>