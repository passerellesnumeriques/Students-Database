<?php 
class page_auth extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$ch = curl_init("https://graph.facebook.com/debug_token?input_token=".$_GET["auth_token"]."&access_token=316910509803|843ab6b200f732996a72c87557a81843");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$data = curl_exec($ch);
		curl_close($ch);
		$res = json_decode($data, true);
		$error = null;
		if ($res <> null && isset($res["data"]) && isset($res["data"]["user_id"])) {
			$facebook_id = $res["data"]["user_id"];
			$res = SQLQuery::create()
			->bypass_security()
			->select("FacebookUser")
			->where_value("FacebookUser","facebook_id",$facebook_id)
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
			} else 
				$error = "No PN account registered with your Facebook account";
		}
		if ($error == null) $error = "Authentication on Facebook failed.";
		echo "<form name='facebook_error' method='POST' action='/dynamic/application/page/enter'><input type='hidden' name='message' value=".json_encode($error)."/></form>";
		echo "<script type='text/javascript'>document.forms['facebook_error'].submit();</script>";
	}
	
}
?>