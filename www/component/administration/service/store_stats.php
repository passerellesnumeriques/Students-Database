<?php 
class service_store_stats extends Service {
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Indicate application usage/version"; }
	public function inputDocumentation() { echo "none"; }
	public function outputDocumentation() { echo "none"; }
	
	public function execute(&$component, $input) {
#DEV
$component->current_request()->no_process_time_warning = true;
#END
		
		global $pn_app_version;
		if (PNApplication::$instance->user_management->user_id > 0)
			$user = PNApplication::$instance->user_management->domain."/".PNApplication::$instance->user_management->username;
		else
			$user = "";
		if (file_exists("data/cron/tasks_time")) {
			$info = stat("data/cron/tasks_time");
			$cron_time = @$info["mtime"];
		} else 
			$cron_time = 0;
		if (file_exists("data/cron/maintenance_tasks_time")) {
			$info = stat("data/cron/maintenance_tasks_time");
			$cron_maintenance_time = @$info["mtime"];
		} else 
			$cron_maintenance_time = 0;
		$c = curl_init("http://stats.lecousin.net/store");
		if (file_exists("conf/proxy")) include("conf/proxy");
		curl_setopt($c, CURLOPT_HEADER, FALSE);
		curl_setopt($c, CURLOPT_POST, TRUE);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_POSTFIELDS, array(
			"product"=>"students_management_software",
#DEV			
			"version"=>$pn_app_version.".dev",
#END
#PROD			
#			"version"=>$pn_app_version,
#END
			"domain"=>PNApplication::$instance->local_domain,
			"user"=>$user,
			"host"=>$_SERVER["HTTP_HOST"],
			"uid"=>@file_get_contents("conf/instance.uid"),
			"google_installed"=>PNApplication::$instance->google->isInstalled() ? 1 : 0,
			"cron"=>$cron_time,
			"cron_maintenance"=>$cron_maintenance_time
		));
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