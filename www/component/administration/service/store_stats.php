<?php 
class service_store_stats extends Service {
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Indicate application usage/version"; }
	public function inputDocumentation() { echo "none"; }
	public function outputDocumentation() { echo "none"; }
	
	public function execute(&$component, $input) {
		global $pn_app_version;
		if (PNApplication::$instance->user_management->user_id > 0)
			$user = PNApplication::$instance->user_management->domain."/".PNApplication::$instance->user_management->username;
		else
			$user = "";
		$c = curl_init("http://stats.lecousin.net/store");
		if (file_exists("conf/proxy")) include("conf/proxy");
		curl_setopt($c, CURLOPT_POST, TRUE);
		curl_setopt($c, CURLOPT_POSTFIELDS, array(
			"product"=>"students_management_software",
			"version"=>$pn_app_version,
			"domain"=>PNApplication::$instance->local_domain,
			"user"=>$user
		));
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($c, CURLOPT_TIMEOUT, 25);
		set_time_limit(45);
		$result = curl_exec($c);
		if ($result === false)
			echo "{connection_error:".json_encode(curl_error($c))."}";
		else
			echo "{stats_response:".json_encode($result)."}";
		curl_close($c);
	}
}
?>