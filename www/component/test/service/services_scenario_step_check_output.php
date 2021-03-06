<?php 
class service_services_scenario_step_check_output extends Service {
	
	public function getRequiredRights() { return array(); }
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		$db = SQLQuery::getDataBaseAccessWithoutSecurity();
		$db->execute("USE students_test");
		
		$cname = $input["component"];
		$scenario_path = $input["scenario"];
		require_once("component/test/TestScenario.inc");
		require_once("component/test/TestServicesScenario.inc");
		require_once("component/".$cname."/test/services/".$scenario_path.".php");
		$scenario_class = str_replace("/","_",$scenario_path);
		$scenario = new $scenario_class();
		$step = intval($input["step"]);
		$data = $input["data"];
		$steps = $scenario->getSteps();
		$step = $steps[$step];
		echo "{javascript:".json_encode($step->getJavascriptToCheckServiceOutput($data))."}";
	}
		
}
?>