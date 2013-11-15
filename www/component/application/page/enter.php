<?php 
class page_enter extends Page {
	
	public function get_required_rights() { return array(); }
	
	protected function execute() {
		if (isset($_GET["page"]) && $_GET["page"] == "/dynamic/test/page/ui") {
			header("Location: /dynamic/test/page/ui");
			return;
		}
		if (isset($_GET["testing"]) && $_GET["testing"] == "true") {
			PNApplication::$instance->local_domain = "Test";
			PNApplication::$instance->current_domain = "Test";
		}
		if (PNApplication::$instance->user_management->username == null)
			include "login.inc";
		else
			include "application.inc";
	}
	
}
?>