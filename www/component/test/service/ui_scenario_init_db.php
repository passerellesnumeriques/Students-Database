<?php 
class service_ui_scenario_init_db extends Service {
	
	public function get_required_rights() { return array(); }
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}
	
	public function execute(&$component, $input) {
		$cname = $input["component"];
		$scenario_path = $input["scenario"];
		require_once("component/test/TestScenario.inc");
		require_once("component/test/TestUIScenario.inc");
		require_once("component/".$cname."/test/ui/".$scenario_path.".php");
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