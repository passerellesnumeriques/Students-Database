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
	
}

class DevRequest {
	
	public $url;
	public $sql_queries = array();
	
}
?>