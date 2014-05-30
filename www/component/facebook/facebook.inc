<?php
/**
 * Provides functionalities to connect to the Facebook account of the user.
 */ 
class facebook extends Component {
	
	public function initRequest() {
		Component::allowServiceWithoutLogin("facebook", "get_user");
		Component::allowPageWithoutLogin("facebook", "auth");
	}
	
}
?>