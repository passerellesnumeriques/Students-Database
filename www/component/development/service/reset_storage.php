<?php 
class service_reset_storage extends Service {
	
	public function getRequiredRights() { return array(); }
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function execute(&$component, $input) {
		$domain = $input["domain"];
		storage::resetStorage($domain);
		echo "true";
	}
	
}
?>