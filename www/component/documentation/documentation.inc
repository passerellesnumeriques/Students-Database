<?php 
class documentation extends Component {
	
	public function init_request() {
		Component::allow_page_without_login("documentation", "index");
		Component::allow_page_without_login("documentation", "navigation");
		Component::allow_page_without_login("documentation", "home");
		Component::allow_page_without_login("documentation", "component");
		Component::allow_page_without_login("documentation", "php");
		Component::allow_page_without_login("documentation", "php_search");
		
		Component::allow_service_without_login("documentation", "datamodel");
		Component::allow_service_without_login("documentation", "component_dependencies");
		Component::allow_service_without_login("documentation", "get_js");
	}
	
}
?>