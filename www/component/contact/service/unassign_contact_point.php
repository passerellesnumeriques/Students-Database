<?php 
class service_unassign_contact_point extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {
		echo "Remove a contact point from an organization";
	}
	public function inputDocumentation() {
		echo "<ul>";
			echo "<li><code>organization</code>: the organization ID</li>";
			echo "<li><code>people</code>: the people ID of the contact to remove</li>";
		echo "</ul>";
	}
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		if(isset($input["organization"]) && isset($input['people'])){
			$res = $component->removeContactPoint($input["organization"],$input['people']);
			if($res)
				echo 'true';
			else
				echo 'false';
		}
	}
}
?>