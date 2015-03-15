<?php 
class service_reset_storage extends Service {
	
	public function getRequiredRights() { return array(); }
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function execute(&$component, $input) {
		PNApplication::$instance->development->current_request()->no_process_time_warning = true;
		PNApplication::$instance->development->current_request()->no_database_warning = true;
		
		$domain = $input["domain"];
		storage::resetStorage($domain);
		echo "true";
	}
	
}
?>