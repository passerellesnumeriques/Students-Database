<?php
/**
 * Provides functionalities to connect to the Facebook account of the user.
 */ 
class facebook extends Component {
	
	public function init_request() {
		Component::allow_service_without_login("facebook", "get_user");
		Component::allow_page_without_login("facebook", "auth");
	}
	
}
?>