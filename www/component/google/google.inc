<?php 
class google extends Component {
	
	public function init_request() {
		Component::allow_service_without_login("google", "get_user");
		Component::allow_page_without_login("google", "auth");
	}

}
?>