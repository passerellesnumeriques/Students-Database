<?php 
class service_unassign_contact_point extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() {}
	public function input_documentation() {

	}
	public function output_documentation() {}
	
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