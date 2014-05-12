<?php 
class service_services_scenario_init_db extends Service {
	
	public function getRequiredRights() { return array(); }
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		$cname = $input["component"];
		$scenario_path = $input["scenario"];
		require_once("component/test/TestScenario.inc");
		require_once("component/test/TestServicesScenario.inc");
		require_once("component/".$cname."/test/services/".$scenario_path.".php");
		$scenario_class = str_replace("/","_",$scenario_path);
		$scenario = new $scenario_class();
		$data = array();
		$error = $scenario->init($data);
		// make sure we are not logged in when starting the scenario
		PNApplication::$instance->user_management->logout();
		echo "{error:".json_encode($error).",data:".json_encode($data)."}";
	}
		
}
?>