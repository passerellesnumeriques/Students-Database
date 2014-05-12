<?php 
class service_exam_remove_subject extends Service {
	
	public function getRequiredRights() { return array("manage_exam_subject"); }
	public function documentation() {
		echo "Remove an exam subject from the database";
	}
	public function inputDocumentation() {
		echo "<code>id</code> id of the exam to remove";
	}
	public function outputDocumentation() {
		?>
		<ul>
			<li> <code>true</code> if well performed</li>
			<li> <code>false</code> if an error occured</li>
		</ul>
		<?php
	}
	
	/**
	 * @param $component selection
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		if(isset($input["id"])){
			$res = $component->removeSubject($input["id"]);
			if($res)
				echo "true";
			else
				echo "false";
		} else echo "false";
	}
	
}
?>