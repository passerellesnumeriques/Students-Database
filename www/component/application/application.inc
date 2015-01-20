<?php
require_once("Event.inc");
/**
 * This component is the main entry point for the application: login page and home page. It contains also some useful functionalities in javascript like calling a service in JSON. 
 */ 
class application extends Component {

	/** @var Event $domain_changed event fired when the user change domain */
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
	
	public function getPluginImplementations($interface_name) {
		switch ($interface_name) {
			case "NewsPlugin":
				require_once("GeneralNewsPlugin.inc");
				return array(
					new GeneralNewsPlugin(),
				);
			case "ApplicationSectionPlugin":
				require_once("ApplicationSectionPlugin.inc");
				return array(
					new HomeApplicationSection(),
				);
			case "CronPlugin":
				require_once("CronPlugin.inc");
				return array(
					new ApplicationCronPlugin()
				);
		}
		return array();
	}
	
	/** Store a new temporary data (expiring in 15 minutes)
	 * @param string $value the data to store
	 * @return number the ID of the data
	 */
	public function createTemporaryData($value) {
		return SQLQuery::create()->bypassSecurity()->insert("TemporaryData", array("value"=>$value,"expiration"=>time()+15*60));
	}
	/** Update a temporary data, and extends its expiration to 15 minutes
	 * @param number $id ID of the data
	 * @param string $value new value to store
	 */
	public function updateTemporaryData($id, $value) {
		SQLQuery::create()->bypassSecurity()->updateByKey("TemporaryData", $id, array("value"=>$value,"expiration"=>time()+15*60));
	}
	/** Get a temporary data
	 * @param number $id the ID
	 * @return string the data
	 */
	public function getTemporaryData($id) {
		return SQLQuery::create()->bypassSecurity()->select("TemporaryData")->whereValue("TemporaryData","id",$id)->field("value")->executeSingleValue();
	}
	/** Remove a temporary data
	 * @param number $id the ID
	 */
	public function removeTemporaryData($id) {
		SQLQuery::create()->bypassSecurity()->removeKey("TemporaryData", $id);
	}
	/** Remove temporary data which already expired (this is automatically called by a cron task) */
	public function removeExpiredTemporaryData() {
		SQLQuery::getDataBaseAccessWithoutSecurity()->execute("DELETE FROM `TemporaryData` WHERE `expiration` < ".time());
	}
	
}
?>