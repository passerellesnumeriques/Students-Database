<?php 
class page_auth extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$c = curl_init("https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token=".$_GET["auth_token"]);
		if (file_exists("conf/proxy")) include("conf/proxy");
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_HEADER, 0);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		$data = curl_exec($c);
		curl_close($c);
		$res = json_decode($data, true);
		$error = null;
		if ($res <> null && isset($res["id"])) {
			$google_id = $res["id"];
			$res = SQLQuery::create()
			->bypassSecurity()
			->select("GoogleUser")
			->whereValue("GoogleUser","google_id",$google_id)
			->execute();
			if (count($res) == 1) {
				$error = PNApplication::$instance->user_management->external_login($res[0]["user"]);
				if ($error == null) {
					echo "<script type='text/javascript'>";
					echo "window.top.set_loading_message('Starting application...');";
					echo "location.href = '/dynamic/application/page/enter';";
					echo "</script>";
					return;
				} 
			}
		}
		if ($error == null) $error = "Authentication on Google failed.";
		echo "<form name='google_error' method='POST' action='/dynamic/application/page/enter'><input type='hidden' name='message' value=".json_encode($error)."/></form>";
		echo "<script type='text/javascript'>document.forms['google_error'].submit();</script>";
	}
	
}
?>