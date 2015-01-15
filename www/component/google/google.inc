<?php 
class google extends Component {
	
	public function initRequest() {
		Component::allowServiceWithoutLogin("google", "get_user");
		Component::allowPageWithoutLogin("google", "auth");
	}
	
	public function getPluginImplementations() {
		require_once("GoogleAdministrationPlugin.inc");
		require_once("CronPlugin.inc");
		return array(
			new GoogleAdministrationPlugin(),
			new GoogleCronPlugin()
		);
	}
	
	public function isInstalled() {
		return file_exists("conf/google.inc");
	}

	private $api_keys_quotas_reached = array();
	
	public static $PLACES_API = "Places";
	
	public function getAPIKey($api_name) {
		$secrets = include("conf/google.inc");
		if (!isset($this->api_keys_quotas_reached[$api_name])) {
			$this->api_keys_quotas_reached[$api_name] = array();
			foreach ($secrets["server_keys"] as $key)
				array_push($this->api_keys_quotas_reached[$api_name], 0);
		}
		for ($i = 0; $i < count($secrets["server_keys"]); ++$i)
			if ($this->api_keys_quotas_reached[$api_name][$i] == 0)
				return $secrets["server_keys"][$i];
		
		// no more key, let's see if we reached the quota at least one hour ago
		for ($i = 0; $i < count($secrets["server_keys"]); ++$i)
			if ($this->api_keys_quotas_reached[$api_name][$i] < time()-60*60) {
				$this->api_keys_quotas_reached[$api_name][$i] = 0;
				return $secrets["server_keys"][$i];
			}
		
		return null;
	}
	
	public function signalAPIQuotaReached($api_name, $api_key) {
		$secrets = include("conf/google.inc");
		for ($key_index = 0; $key_index < count($secrets["server_keys"]); $key_index++)
			if ($secrets["server_keys"][$key_index] == $api_key) break;
		if ($key_index >= count($secrets["server_keys"])) return;
		$this->api_keys_quotas_reached[$api_name][$key_index] = time();
	}
	
	public function getConnectedAccount() {
		return SQLQuery::create()->bypassSecurity()->select("GoogleUser")->whereValue("GoogleUser","user",PNApplication::$instance->user_management->user_id)->executeSingleRow();
	}
	
}
?>