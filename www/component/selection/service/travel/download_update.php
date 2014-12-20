<?php 
class service_travel_download_update extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function getOutputFormat($input) {
		if (isset($_GET["from"]) || isset($_GET["file"]))
			return "application/octet-stream";
		return parent::getOutputFormat($input);
	}
	
	public function execute(&$component, $input) {
		require_once("component/application/service/deploy_utils.inc");
		require_once("update_urls.inc");
		global $pn_app_version;
		if (!isset($_GET["from"]) && !isset($_GET["file"])) {
			// get the file size
			$version = isset($_GET["version"]) ? $_GET["version"] : $pn_app_version;
			$url = getUpdateURL("Students_Management_Software_".$version."_Selection_Travel.zip");
			try {
				$info = getURLFileSize($url, "application/octet-stream");
				if ($info <> -1) $info = json_decode($info, true);
				if ($info == null || $info == -1 || $info["size"] <= 0) {
					PNApplication::error("Unable to find the file to download");
					return;
				}
			} catch (Exception $e) {
				PNApplication::error($e);
				return;
			}
			echo "{\"size\":".$info["size"].",\"filename\":".json_encode("Students_Management_Software_".$version."_Selection_Travel.zip")."}";
			return;
		}
		if (isset($_GET["from"])) {
			$version = isset($_GET["version"]) ? $_GET["version"] : $pn_app_version;
			$url = getUpdateURL("Students_Management_Software_".$version."_Selection_Travel.zip");
			$from = intval($_GET["from"]);
			$size = intval($_GET["size"]);
			$to = $from + 512*1024 -1;
			if ($to >= $size) $to = $size-1;
			$c = curl_init($url);
			if (file_exists("conf/proxy")) include("conf/proxy");
			curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
			curl_setopt($c, CURLOPT_TIMEOUT, 300);
			curl_setopt($c, CURLOPT_HTTPHEADER, array("Range: bytes=".$from."-".$to));
			set_time_limit(350);
			$result = curl_exec($c);
			curl_close($c);
			if ($result === false) return;
			echo $result;
		} else if (isset($_GET["file"])) {
			$url = getUpdateURL($_GET["file"]);
			$c = curl_init($url);
			if (file_exists("conf/proxy")) include("conf/proxy");
			curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
			curl_setopt($c, CURLOPT_TIMEOUT, 300);
			set_time_limit(350);
			$result = curl_exec($c);
			curl_close($c);
			if ($result === false) return;
			echo $result;
		}
	}
	
}
?>