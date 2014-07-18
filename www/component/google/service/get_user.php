<?php 
class service_get_user extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Get user information from Google ID"; }
	public function inputDocumentation() { echo "auth_token: the authentication token from Google"; }
	public function outputDocumentation() { echo "TODO"; }
	
	public function execute(&$component, $input) {
		set_time_limit(60);
		$ch = curl_init("https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token=".$input["auth_token"]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_TIMEOUT,10);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,5);
		$data = curl_exec($ch);
		curl_close($ch);
		$res = json_decode($data, true);
		if ($res <> null && isset($res["id"])) {
			$google_id = $res["id"];
			$q = SQLQuery::create()
				->bypassSecurity()
				->select("GoogleUser")
				->whereValue("GoogleUser","google_id",$google_id)
				;
			PNApplication::$instance->user_management->joinUser($q, "GoogleUser", "user");
			$res = $q->execute();
			if (count($res) <> 1) { echo "{status:'no_user'}"; return; }
			echo "{profile:".$data.",user:{id:".json_encode($res[0]["user"]).",domain:".json_encode($res[0]["domain"]).",username:".json_encode($res[0]["username"])."}}";
		}
	}
	
}
?>