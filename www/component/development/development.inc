<?php 
class development extends Component {
	
	public $requests = array();
	
	public function &current_request() {
		return $this->requests[count($this->requests)-1];
	}
	
	public function init_request() {
		Component::allow_page_without_login("development", "tools");
		Component::allow_page_without_login("development", "reset_db");
		Component::allow_page_without_login("development", "check_code");
		Component::allow_service_without_login("development", "create_db");
		Component::allow_service_without_login("development", "test_data");
		Component::allow_service_without_login("development", "get_debug_info");
		Component::allow_service_without_login("development", "check_php");
		Component::allow_service_without_login("development", "get_js");
		
		for ($i = 0; $i < 15; $i++) {
			if ($i >= count($this->requests)) break;
			if ($i > 5 && count($this->requests[$i]->sql_queries) > 25) {
				array_splice($this->requests[$i]->sql_queries, 25, count($this->requests[$i]->sql_queries)-25);
				array_push($this->requests[$i]->sql_queries, array("TOO MUCH SQL QUERIES",0,null,0));
			}
		}
		while (count($this->requests) > 15)
			array_splice($this->requests, 0, 1);
	}
	
}

class DevRequest {
	
	public $url;
	public $sql_queries = array();
	public $start_time, $end_time = -1;
	
}
?>