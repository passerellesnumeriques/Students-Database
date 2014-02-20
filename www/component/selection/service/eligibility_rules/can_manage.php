
<?php 
class service_eligibility_rules_can_manage extends Service {
	
	public function get_required_rights() {return array();}
	public function documentation() {echo "the steps status of the eligibility rules in a JSON object";}
	public function input_documentation() {
		echo "No";
	}
	public function output_documentation() {
		?>
		Return the steps status of the eligibility rules in a JSON object
		<ul>
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
		echo "{topic_exist:".json_encode($steps["define_topic_for_eligibility_rules"]).",can_valid:".json_encode($can[0]).",can_unvalid:".json_encode($can[1]).",error:".json_encode($can[2])."}";
	}
	
}