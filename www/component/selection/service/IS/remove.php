<?php 
class service_is_remove extends Service {
	
	public function getRequiredRights() { return array("manage_information_session"); }
	public function documentation() {
		echo "Remove an information session from the database";
	}
	public function inputDocumentation() {
		?>
		<ul>
			<li><code>id</code> {number} the id of the information session to remove</li>
		</ul>
		<?php
	}
	public function outputDocumentation() {
		echo "{boolean} false if an error occured, else return true";
	}
	
	/**
	 * @param $component selection
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		if(isset($input["id"])){
			$res = $component->removeIS($input["id"]);
			if($res)
				echo "true";
			else
				echo "false";
		} else echo "false";
	}
	
}
?>