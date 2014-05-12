<?php 
class documentation extends Component {
	
	public function initRequest() {
		Component::allowPageWithoutLogin("documentation", "index");
		Component::allowPageWithoutLogin("documentation", "navigation");
		Component::allowPageWithoutLogin("documentation", "home");
		Component::allowPageWithoutLogin("documentation", "component");
		Component::allowPageWithoutLogin("documentation", "php");
		Component::allowPageWithoutLogin("documentation", "php_search");
		Component::allowPageWithoutLogin("documentation", "static");
		
		Component::allowServiceWithoutLogin("documentation", "datamodel");
		Component::allowServiceWithoutLogin("documentation", "component_dependencies");
		Component::allowServiceWithoutLogin("documentation", "get_js");
		Component::allowServiceWithoutLogin("documentation", "static");
	}
	
}
?>