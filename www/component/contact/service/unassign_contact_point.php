<?php 
class service_unassign_contact_point extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {

	}
	public function outputDocumentation() {}
	
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