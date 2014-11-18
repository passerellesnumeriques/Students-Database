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

	private $api_keys_quotas_reached = array();
	
	public static $PLACES_API = "Places";
	
	public function getAPIKey($api_name) {
		$secrets = include("conf/secrets.inc");
		if (!isset($this->api_keys_quotas_reached[$api_name])) {
			$this->api_keys_quotas_reached[$api_name] = array();
			foreach ($secrets["Google"]["server_api_keys"] as $key)
				array_push($this->api_keys_quotas_reached[$api_name], 0);
		}
		for ($i = 0; $i < count($secrets["Google"]["server_api_keys"]); ++$i)
			if ($this->api_keys_quotas_reached[$api_name][$i] == 0)
				return $secrets["Google"]["server_api_keys"][$i];
		
		// no more key, let's see if we reached the quota at least one hour ago
		for ($i = 0; $i < count($secrets["Google"]["server_api_keys"]); ++$i)
			if ($this->api_keys_quotas_reached[$api_name][$i] < time()-60*60) {
				$this->api_keys_quotas_reached[$api_name][$i] = 0;
				return $secrets["Google"]["server_api_keys"][$i];
			}
		
		return null;
	}
	
	public function signalAPIQuotaReached($api_name, $api_key) {
		$secrets = include("conf/secrets.inc");
		for ($key_index = 0; $key_index < count($secrets["Google"]["server_api_keys"]); $key_index++)
			if ($secrets["Google"]["server_api_keys"][$key_index] == $api_key) break;
		if ($key_index >= count($secrets["Google"]["server_api_keys"])) return;
		$this->api_keys_quotas_reached[$api_name][$key_index] = time();
	}
	
	public function getConnectedAccount() {
		return SQLQuery::create()->bypassSecurity()->select("GoogleUser")->whereValue("GoogleUser","user",PNApplication::$instance->user_management->user_id)->executeSingleRow();
	}
	
}
?>