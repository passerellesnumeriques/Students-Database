<?php 
class service_set_google_account extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Register Google Account to current user"; }
	public function inputDocumentation() { echo "auth_token: the authentication token from Google"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$ch = curl_init("https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token=".$input["auth_token"]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch,CURLOPT_TIMEOUT,10);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);
		$data = curl_exec($ch);
		curl_close($ch);
		$res = json_decode($data, true);
		if ($res <> null && isset($res["id"])) {
			$google_id = $res["id"];
			$pn_email = $input["pn_email"];
			$res = SQLQuery::create()->bypassSecurity()->select("GoogleUser")->where("user",PNApplication::$instance->user_management->user_id)->where("google_id",$google_id)->executeSingleRow();
			if (count($res) > 0) {
				if ($pn_email <> $res["google_login"])
					SQLQuery::create()->bypassSecurity()->updateByKey("GoogleUser", PNApplication::$instance->user_management->user_id, array("google_login"=>$pn_email));
				echo "true";
				return;
			}
			SQLQuery::create()->bypassSecurity()->insert("GoogleUser", array("user"=>PNApplication::$instance->user_management->user_id,"google_id"=>$google_id,"google_login"=>$pn_email));
			echo "true";
		}
	}
	
}
?>