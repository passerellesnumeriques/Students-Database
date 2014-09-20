<?php
class service_set_domain extends Service {
	
	public function getRequiredRights() { return array(); }
	public function documentation() { echo "Change the database/domain on which the user wants to access."; }
	public function inputDocumentation() { echo "<code>domain</code>: the new domain"; }
	public function outputDocumentation() { echo "return true on success"; }
	public function mayUpdateSession() { return true; }
	public function execute(&$component, $input) {
		$domain = $input["domain"];
		$domains = PNApplication::$instance->getDomains();
		if (!isset($domains[$domain])) PNApplication::error("Unknown domain");
		else {
			if (PNApplication::$instance->current_domain <> $domain) {
				PNApplication::$instance->current_domain = $domain;
				PNApplication::$instance->application->domain_changed->fire();
			}
			echo "true";
		}
	}
	
} 
?>