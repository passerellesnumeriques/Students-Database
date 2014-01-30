<?php 
class service_services_scenario_step_finalize extends Service {
	
	public function get_required_rights() { return array(); }
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}
	
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
		$error = $step->finalizationStep($data);
		echo "{";
		echo "error:".json_encode($error);
		echo ",data:".json_encode($data);
		echo "}";
	}
		
}
?>