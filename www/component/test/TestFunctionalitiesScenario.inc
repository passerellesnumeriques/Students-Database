<?php 
require_once("TestScenario.inc");

/**
 * A TestFunctionalitiesScenario is a TestScenario for the back-end (PHP + Database).
 * It provides the list of functions covered by the scenario.
 * It proviees the list of TestFunctionalitiesStep.
 */
abstract class TestFunctionalitiesScenario extends TestScenario {

	public abstract function getCoveredFunctions();

	/**
	 * @return TestFunctionalitiesStep[]
	*/
	public abstract function getSteps();

	public function run_step($step_index, &$scenario_data) {
		$db = SQLQuery::getDataBaseAccessWithoutSecurity();
		$db->execute("USE students_test");
		// run
		$steps = $this->getSteps();
		$step = $steps[$step_index];
		return $step->run($scenario_data);
	}

}

abstract class TestFunctionalitiesStep extends TestStep {

	public abstract function run(&$scenario_data);

}

?>