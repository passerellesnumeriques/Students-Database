<?php 
class service_set_google_account extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Register Google Account to current user"; }
	public function inputDocumentation() {
		echo "This service can be used to (1) set the PN email of the user (parameter <code>pn_email</code>), and/or google_id for authentication purpose (parameter <code>auth_token</code>)";
	}
	public function outputDocumentation() { echo "true"; }
	
	public function execute(&$component, $input) {
		// check if we already have something
		$current = SQLQuery::create()->bypassSecurity()->select("GoogleUser")->whereValue("GoogleUser","user",PNApplication::$instance->user_management->user_id)->executeSingleRow();

		if (isset($input["pn_email"])) {
			if ($current == null) {
				$current = array("user"=>PNApplication::$instance->user_management->user_id,"google_login"=>$input["pn_email"]);
				SQLQuery::create()->bypassSecurity()->insert("GoogleUser", $current);
			} else if ($current["google_login"] <> $input["pn_email"]) {
				SQLQuery::create()->bypassSecurity()->updateByKey("GoogleUser", PNApplication::$instance->user_management->user_id, array("google_id"=>null,"google_login"=>$input["pn_email"]));
				$current["google_id"] = null;
				$current["google_login"] = $input["pn_email"];
			}
		}
		
		if (isset($input["auth_token"])) {
			$c = curl_init("https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token=".$input["auth_token"]);
			if (file_exists("conf/proxy")) include("conf/proxy");
			curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($c, CURLOPT_HEADER, 0);
			curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($c,CURLOPT_TIMEOUT,10);
			curl_setopt($c,CURLOPT_CONNECTTIMEOUT,5);
			$data = curl_exec($c);
			curl_close($c);
			$res = json_decode($data, true);
			if ($res <> null && isset($res["id"])) {
				$google_id = $res["id"];
				if ($current == null)
					SQLQuery::create()->bypassSecurity()->insert("GoogleUser", array("user"=>PNApplication::$instance->user_management->user_id,"google_id"=>$google_id));
				else
					SQLQuery::create()->bypassSecurity()->updateByKey("GoogleUser", PNApplication::$instance->user_management->user_id, array("google_id"=>$google_id));
			}
		}
		
		echo "true";
	}
	
}
?>