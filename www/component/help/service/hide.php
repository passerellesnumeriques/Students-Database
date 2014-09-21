<?php 
class service_hide extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Do not display again the given help"; }
	public function inputDocumentation() { echo "help_id"; }
	public function outputDocumentation() { echo "none"; }
	
	public function execute(&$component, $input) {
		SQLQuery::create()->bypassSecurity()->insert("HelpSystemHidden", array("help_id"=>$input["help_id"],"user"=>PNApplication::$instance->user_management->user_id));
	}
	
}
?>