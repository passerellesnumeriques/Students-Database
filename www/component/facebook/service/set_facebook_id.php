<?php 
class service_set_facebook_id extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Register Google ID to current user"; }
	public function input_documentation() { echo "auth_token: the authentication token from Google"; }
	public function output_documentation() { echo "true"; }
	
	public function execute(&$component, $input) {
		$ch = curl_init("https://graph.facebook.com/debug_token?input_token=".$input["auth_token"]."&access_token=316910509803|843ab6b200f732996a72c87557a81843");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch,CURLOPT_TIMEOUT,10);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);
		$data = curl_exec($ch);
		curl_close($ch);
		$res = json_decode($data, true);
		if ($res <> null && isset($res["data"]) && isset($res["data"]["user_id"])) {
			$facebook_id = $res["data"]["user_id"];
			$res = SQLQuery::create()->bypass_security()->select("FacebookUser")->where("user",PNApplication::$instance->user_management->user_id)->where("facebook_id",$facebook_id)->execute_single_row();
			if (count($res) > 0) return;
			SQLQuery::create()->bypass_security()->insert("FacebookUser", array("user"=>PNApplication::$instance->user_management->user_id,"facebook_id"=>$facebook_id));
		}
	}
	
}
?>