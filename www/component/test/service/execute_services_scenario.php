<?php 
class service_execute_services_scenario extends Service {
	
	public function get_required_rights() { return array(); }
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}
	
	public function execute(&$component, $input) {
		$cname = $input["component"];
		$scenario_path = $input["scenario"];
		require_once("component/test/TestScenario.inc");
		require_once("component/".$cname."/test/services".$scenario_path);
		$i = strrpos($scenario_path, "/");
		$scenario_class = substr($scenario_path, $i+1);
		$scenario_class = substr($scenario_class, 0, strlen($scenario_class)-4);
		$scenario = new $scenario_class();
		$step = intval($input["step"]);
		$data = @$input["data"];
		if ($data == null) $data = array();
		if ($step == -1)
			$error = $scenario->init($data);
		else 
			$error = $scenario->run_step($step, $data);
		echo "{error:".json_encode($error).",data:".json_encode($data)."}";
	}
		
}
?>