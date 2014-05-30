<?php 
class service_eligibility_rules_remove_topic extends Service {
	
	public function getRequiredRights() { return array("manage_exam_subject"); }
	public function documentation() {echo "Remove an exam topic for eligibility rules from the database";}
	public function inputDocumentation() {
		echo "<code>id</code> the id of the topic to remove";
	}
	public function outputDocumentation() {
		?>
		<ul>
		<li><code>true</code> if well performed</li>
		<li><code>false</code> if any error occured</li>
		</ul>
		<?php
	}
	
	/**
	 * @param $component selection
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		if(isset($input["id"])){
			$res = PNApplication::$instance->selection->removeTopic($input["id"]);
// 			var_dump($res);
			if($res)
				echo "true";
			else 
				echo "false";
		} else
			echo "false";
	}
	
}