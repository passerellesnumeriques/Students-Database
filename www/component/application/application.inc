<?php
/**
 * This component is the main entry point for the application: login page and home page. It contains also some useful functionalities in javascript like calling a service in JSON. 
 */ 
class application extends Component {

	public function init_request() {
		Component::allow_page_without_login("application", "enter");
		Component::allow_page_without_login("application", "logout");
		Component::allow_service_without_login("application", "get_static_resources");
	}
	
	public function getPluginImplementations() {
		require_once("ApplicationPlugin.inc");
		require_once("GeneralNewsPlugin.inc");
		return array(
			new ApplicationDashboard(),
			new GeneralNewsPlugin()
		);
	}
	
}
?>