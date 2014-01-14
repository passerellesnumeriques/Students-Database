<?php 
class service_remove_IS extends Service {
	
	public function get_required_rights() { return array(); }
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}
	
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