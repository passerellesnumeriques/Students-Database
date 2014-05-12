<?php 
class google extends Component {
	
	public function initRequest() {
		Component::allowServiceWithoutLogin("google", "get_user");
		Component::allowPageWithoutLogin("google", "auth");
	}

}
?>