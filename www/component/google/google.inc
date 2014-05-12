<?php 
class google extends Component {
	
	public function initRequest() {
		Component::allow_service_without_login("google", "get_user");
		Component::allow_page_without_login("google", "auth");
	}

}
?>