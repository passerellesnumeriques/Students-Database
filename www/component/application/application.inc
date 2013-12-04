<?php 
class application extends Component {

	public function init_request() {
		Component::allow_page_without_login("application", "enter");
		Component::allow_page_without_login("application", "logout");
		Component::allow_service_without_login("application", "get_static_resources");
	}
	
}
?>