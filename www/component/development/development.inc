<?php 
class development extends Component {
	
	public $requests = array();
	
	public function init() {
		while (count($this->requests) > 50)
			array_splice($this->requests, 0, 1);
	}
	
	public function &current_request() {
		return $this->requests[count($this->requests)-1];
	}
	
	public function init_request() {
		Component::allow_page_without_login("development", "tools");
		Component::allow_page_without_login("development", "reset_db");
		Component::allow_service_without_login("development", "create_db");
		Component::allow_service_without_login("development", "test_data");
		Component::allow_service_without_login("development", "get_debug_info");
	}
	
}

class DevRequest {
	
	public $url;
	public $sql_queries = array();
	
}
?>