<?php
class service_timezone extends Service {
	
	public function get_required_rights() { return array(); }
	public function documentation() { echo "Get User client Timezone"; }
	public function input_documentation() { echo "nothing"; }
	public function output_documentation() { echo "nothing"; }
	
	public function execute(&$component, $input) {
		if ($input<>null){
			PNApplication::$instance->timezone=$input;
		}
	}
} 
?>