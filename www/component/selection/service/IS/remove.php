<?php 
class service_IS_remove extends Service {
	
	public function get_required_rights() { return array("manage_information_session"); }
	public function documentation() {
		echo "Remove an information session from the database";
	}
	public function input_documentation() {
		?>
		<ul>
			<li><code>id</code> {number} the id of the information session to remove</li>
			<li><code>fake_organization</code> {number} the id of the fake_organization linked to the information session</li>
		</ul>
		<?php
	}
	public function output_documentation() {
		echo "{boolean} false if an error occured, else return true";
	}
	
	/**
	 * @param $component selection
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		if(isset($input["id"]) && isset($input["fake_organization"])){
			$res = $component->removeIS($input["id"], $input["fake_organization"]);
			if($res)
				echo "true";
			else
				echo "false";
		} else echo "false";
	}
	
}
?>