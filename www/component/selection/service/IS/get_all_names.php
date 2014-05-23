<?php 
class service_IS_get_all_names extends Service {
	
	public function getRequiredRights() { return array("see_information_session_details"); }
	public function documentation() {
		echo "Get a JSON array containing all the informations sessions names";
	}
	public function inputDocumentation() {
		?>
		Array containing for each information session:
		<ul>
		  <li><code>id</code> number the information session ID</li>
		  <li><code>name</code> string the information session name</li>
		</ul>

		<?php
	}
	public function outputDocumentation() {
		echo "No";
	}
	
	/**
	 * @param $component selection
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		require_once 'component/selection/SelectionJSON.inc';
		echo SelectionJSON::getJSONAllInformationsSessionsNames();
	}
	
}
?>