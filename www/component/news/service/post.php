<?php 
class service_post extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		$section = $input["section"];
		$category = $input["category"];
		$tags = $input["tags"];
		$message = $input["message"];
		$component->post($section, $category, $tags, $message);
	}
	
}
?>