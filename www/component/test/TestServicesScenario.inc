<?php 
require_once("TestScenario.inc");
require_once("TestFunctionalitiesScenario.inc");

/**
 * A TestServicesScenario is a TestScenario which aims at testing a specific service.
 */
abstract class TestServicesScenario extends TestScenario {

	public final function getCoveredServices() {
		$steps = $this->getSteps();
		$services = array();
		foreach ($steps as $step) {
			$service = $step->getServiceName();
			if (!in_array($service, $services)) array_push($services, $service);
		}
		return $services;
	}

	/**
	 * @return TestServicesStep[]
	 */
	public abstract function getSteps();

}

/**
 * To test a service, the following steps are performed:
 *  - The first step is performed on the back-end part: to prepare the call to the service (login...)
 *  - Then, the service is called with the provided input
 *  - The output of the service is checked
 *  - The last step is performed on the back-end part, to check the impacts of the service call to the back-end/database
 */
abstract class TestServicesStep extends TestStep {
	
	public abstract function initializationStep(&$scenario_data);
	
	public abstract function getServiceName();
	/**
	 * @return string one of: parse_json,parse_xml,custom
	 */
	public function getServiceType() { return "parse_json"; }
	public abstract function getServiceInput(&$scenario_data);
	/**
	 * Depending on the type, the javascript is called with:
	 *  - parse_json: (errors,result)
	 *  - parse_xml: (errors,result)
	 *  - custom: (raw_output)
	 */
	public abstract function getJavascriptToCheckServiceOutput($scenario_data);
	
	public abstract function finalizationStep(&$scenario_data);
	
}

?>