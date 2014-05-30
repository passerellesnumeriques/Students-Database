
<?php 
class service_eligibility_rules_status_from_steps extends Service {
	
	public function getRequiredRights() {return array();}
	public function documentation() {echo "Get the steps status of the eligibility rules in a JSON object";}
	public function inputDocumentation() {
		echo "No";
	}
	public function outputDocumentation() {
		?>
	<ul>
	<li> {Boolean} <code>rule_exist</code> true if any rule exist into the database</li>
	<li> {Boolean} <code>topic_exist</code> true if any topic exist into the database</li>
  	<li> {Boolean} <code>can_valid</code> </li>
  	<li> {Boolean}<code>can_unvalid</code></li>
  	<li> {String}<code>error</code> the error message to display if ever</li>
	</ul>

		<?php
	}
	
	/**
	 * @param $component selection
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		$steps = PNApplication::$instance->selection->getSteps();
		$can = PNApplication::$instance->selection->canUpdateStep("define_eligibility_rules");
		echo "{rule_exist:".json_encode($steps["define_eligibility_rules"]).",topic_exist:".json_encode($steps["define_topic_for_eligibility_rules"]).",can_valid:".json_encode($can[0]).",can_unvalid:".json_encode($can[1]).",error:".json_encode($can[2])."}";
	}
	
}