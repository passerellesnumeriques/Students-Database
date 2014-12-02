<?php
require_once("Event.inc");
/**
 * This component is the main entry point for the application: login page and home page. It contains also some useful functionalities in javascript like calling a service in JSON. 
 */ 
class application extends Component {

	/** @var Event $domain_changed */
	public $domain_changed;
	
	public function init() {
		$this->domain_changed = new Event();
	}
	
	public function initRequest() {
		Component::allowPageWithoutLogin("application", "enter");
		Component::allowPageWithoutLogin("application", "logout");
		Component::allowServiceWithoutLogin("application", "get_static_resources");
		Component::allowServiceWithoutLogin("application", "loading");
		Component::allowServiceWithoutLogin("application", "ping");
		Component::allowServiceWithoutLogin("application", "get_backup");
	}
	
	public function getPluginImplementations() {
		require_once("GeneralNewsPlugin.inc");
		require_once("ApplicationSectionPlugin.inc");
		require_once("CronPlugin.inc");
		return array(
			new GeneralNewsPlugin(),
			new HomeApplicationSection(),
			new ApplicationCronPlugin()
		);
	}
	
	public function createTemporaryData($value) {
		return SQLQuery::create()->bypassSecurity()->insert("TemporaryData", array("value"=>$value,"expiration"=>time()+15*60));
	}
	public function updateTemporaryData($id, $value) {
		SQLQuery::create()->bypassSecurity()->updateByKey("TemporaryData", $id, array("value"=>$value,"expiration"=>time()+15*60));
	}
	public function getTemporaryData($id) {
		return SQLQuery::create()->bypassSecurity()->select("TemporaryData")->whereValue("TemporaryData","id",$id)->field("value")->executeSingleValue();
	}
	public function removeTemporaryData($id) {
		SQLQuery::create()->bypassSecurity()->removeKey("TemporaryData", $id);
	}
	public function removeExpiredTemporaryData() {
		SQLQuery::getDataBaseAccessWithoutSecurity()->execute("DELETE FROM `TemporaryData` WHERE `expiration` < ".time());
	}
	
}
?>