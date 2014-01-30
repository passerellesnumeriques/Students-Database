<?php 
require_once("TestScenario.inc");
require_once("TestFunctionalitiesScenario.inc");
require_once("TestServicesScenario.inc");
require_once("TestUIScenario.inc");
abstract class ComponentTests {
	
	public abstract function getFunctionalitiesTests();
	public abstract function getServicesTests();
	public abstract function getUITests();
	
}
?>