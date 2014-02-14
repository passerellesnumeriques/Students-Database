<?php 
class service_eligibility_rules_remove_topic extends Service {
	
	public function get_required_rights() { return array(); }//TODO
	public function documentation() {}//TODO
	public function input_documentation() {
	//TODO
	}
	public function output_documentation() {
		//TODO
	}
	
	/**
	 * @param $component selection
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		if(isset($input["id"])){
			$res = PNApplication::$instance->selection->removeTopic($input["id"]);
			if($res)
				echo "true";
			else 
				echo "false";
		} else
			echo "false";
	}
	
}