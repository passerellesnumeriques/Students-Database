<?php 
class development extends Component {
	
	public $requests = array();
	
	public function &current_request() {
		return $this->requests[count($this->requests)-1];
	}
	
	public function initRequest() {
		//Component::allowPageWithoutLogin("development", "tools");
		Component::allowPageWithoutLogin("development", "reset_db");
		Component::allowPageWithoutLogin("development", "clean_storage");
		Component::allowPageWithoutLogin("development", "export_storage");
		//Component::allowPageWithoutLogin("development", "export_data");
		Component::allowPageWithoutLogin("development", "check_code");
		Component::allowPageWithoutLogin("development", "server_variables");
		Component::allowServiceWithoutLogin("development", "create_db");
		Component::allowServiceWithoutLogin("development", "reset_storage");
		Component::allowServiceWithoutLogin("development", "init_data");
		Component::allowServiceWithoutLogin("development", "test_data");
		Component::allowServiceWithoutLogin("development", "get_debug_info");
		Component::allowServiceWithoutLogin("development", "check_php");
		Component::allowServiceWithoutLogin("development", "check_todo");
		Component::allowServiceWithoutLogin("development", "check_datamodel");
		Component::allowServiceWithoutLogin("development", "get_datamodel");
		Component::allowServiceWithoutLogin("development", "check_libraries_updates");
		Component::allowServiceWithoutLogin("development", "backup");
		Component::allowServiceWithoutLogin("development", "get_backups");
		Component::allowServiceWithoutLogin("development", "recover");
		
		for ($i = 0; $i < 15; $i++) {
			if ($i >= count($this->requests)) break;
			//if ($i > 5 && count($this->requests[$i]->sql_queries) > 25) {
			//	array_splice($this->requests[$i]->sql_queries, 25, count($this->requests[$i]->sql_queries)-25);
			//	array_push($this->requests[$i]->sql_queries, array("TOO MUCH SQL QUERIES",0,null,0));
			//}
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