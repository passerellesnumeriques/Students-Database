<?php
/**
 * This component is the main entry point for the application: login page and home page. It contains also some useful functionalities in javascript like calling a service in JSON. 
 */ 
class application extends Component {

	public function initRequest() {
		Component::allowPageWithoutLogin("application", "enter");
		Component::allowPageWithoutLogin("application", "logout");
		Component::allowServiceWithoutLogin("application", "get_static_resources");
		Component::allowServiceWithoutLogin("application", "loading");
	}
	
	public function getPluginImplementations() {
		require_once("GeneralNewsPlugin.inc");
		return array(
			new GeneralNewsPlugin()
		);
	}
	
}
?>