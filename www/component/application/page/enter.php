<?php 
class page_enter extends Page {
	
	public function getRequiredRights() { return array(); }
	
	protected function execute() {
		if (isset($_GET["page"]) && $_GET["page"] == "/dynamic/test/page/ui") {
			header("Location: /dynamic/test/page/ui");
			return;
		}
		if (isset($_GET["testing"]) && $_GET["testing"] == "true") {
			PNApplication::$instance->local_domain = "Test";
			PNApplication::$instance->current_domain = "Test";
		}
		
		global $need_app_loading;
		global $pn_app_version;
		$need_app_loading = !isset($_COOKIE["pnapp_loaded"]) || $_COOKIE["pnapp_loaded"] <> $pn_app_version;
		
		if (PNApplication::$instance->user_management->username == null)
			include "login.inc";
		else
			include "application.inc";
	}
	
}
?>