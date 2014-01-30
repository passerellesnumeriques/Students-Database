<?php 
class service_get_user extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Get user information from Facebook ID"; }
	public function input_documentation() { echo "auth_token: the authentication token from Facebook"; }
	public function output_documentation() { echo "TODO"; }
	
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
			$res = SQLQuery::create()
				->bypassSecurity()
				->select("FacebookUser")
				->whereValue("FacebookUser","facebook_id",$facebook_id)
				->join("FacebookUser","Users",array("user"=>"id"))
				->execute();
			if (count($res) <> 1) { echo "false"; return; }
			echo "{profile:".$data.",user:{id:".json_encode($res[0]["user"]).",domain:".json_encode($res[0]["domain"]).",username:".json_encode($res[0]["username"])."}}";
		}
	}
	
}
?>