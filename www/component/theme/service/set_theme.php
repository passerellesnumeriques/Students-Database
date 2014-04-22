<?php 
class service_set_theme extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}
	
	public function execute(&$component, $input) {
		$name = $input["theme"];
		if (!file_exists("component/theme/static/".$name)) {
			echo "false";
			return;
		}
		setcookie("theme",$name,time()+365*24*60*60,"/");
		echo "true";
	}
	
}
?>