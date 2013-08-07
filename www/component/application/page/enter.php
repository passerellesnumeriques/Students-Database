<?php 
class page_enter extends Page {
	
	public function get_required_rights() { return array(); }
	
	protected function execute() {
		if (PNApplication::$instance->user_management->username == null)
			include "login.inc";
		else
			include "application.inc";
	}
	
}
?>