<?php 
class service_interview_remove_center extends Service {
	
	public function getRequiredRights() { return array("manage_interview_center"); }
	public function documentation() {
		echo "Remove an interview center from its ID";
	}
	public function inputDocumentation() {
		echo "<code>id</code> interview center ID";
	}
	public function outputDocumentation() {
		?>
		<ul>
			<li><code>true</code> if well performed</li>
			<li><code>false</code> if not</li>
		</ul>
		<?php
	}
	
	/**
	 * @param $component selection
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		if(isset($input["id"])){
			$r = PNApplication::$instance->selection->removeInterviewCenter($input["id"]);
			if($r)
				echo "true";
			else
				echo "false";
		} else
				echo "false";
	}
	
}
?>