<?php 
class service_post extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Post a news"; }
	public function inputDocumentation() { echo "section, category, tags, messages, type"; }
	public function outputDocumentation() { echo "none"; }
	
	public function execute(&$component, $input) {
		$section = $input["section"];
		$category = $input["category"];
		$tags = $input["tags"];
		$message = $input["message"];
		$type = $input["type"];
		$component->post($section, $category, $tags, $type, $message);
	}
	
}
?>