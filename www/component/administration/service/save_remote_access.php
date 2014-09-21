<?php 
class service_save_remote_access extends Service {
	
	public function getRequiredRights() { return array("manage_application"); }
	
	public function documentation() { echo "Save url and password to connect remotely to another domain."; }
	public function inputDocumentation() { echo "domain, url, password"; }
	public function outputDocumentation() { echo "version"; }
	
	public function execute(&$component, $input) {
		$domain = $input["domain"];
		$url = $input["url"];
		$password = $input["password"];
		
		if ($url == "" && $password == "") {
			// remove
			if (file_exists("data/domains_synch/$domain")) {
				require_once("component/application/Backup.inc");
				Backup::removeDirectory("data/domains_synch/$domain");
			}
			if (unlink("conf/$domain.remote"))
				echo "true";
			return;
		}
		
		if (substr($url,strlen($url)-1) <> "/") $url .= "/";
		
		// Step 1: get the version
		
		$c = curl_init($url."dynamic/application/service/get_backup");
		if (file_exists("conf/proxy")) include("conf/proxy");
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_HEADER, TRUE);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_POSTFIELDS, array("request"=>"get_list"));
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($c, CURLOPT_TIMEOUT, 60);
		$result = curl_exec($c);
		if ($result === false) {
			PNApplication::error(curl_error($c));
			curl_close($c);
			return;
		}
		curl_close($c);
		
		$i = strpos($result, "pn_version_changed:");
		if ($i === false) {
			PNApplication::error("We can connect to the URL, but this is not Students Management Software");
			return;
		}
		$result = substr($result,$i+19);
		$i = strpos($result,"\n");
		$version = trim(substr($result,0,$i));
		
		// Step 2: connect using version cookie

		$c = curl_init($url."dynamic/application/service/get_backup");
		if (file_exists("conf/proxy")) include("conf/proxy");
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_HEADER, TRUE);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_POSTFIELDS, array("request"=>"get_list","password"=>$password));
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($c, CURLOPT_TIMEOUT, 60);
		curl_setopt($c, CURLOPT_HTTPHEADER, array("Cookie: pnversion=$version"));
		$result = curl_exec($c);
		if ($result === false) {
			PNApplication::error(curl_error($c));
			curl_close($c);
			return;
		}
		curl_close($c);
		
		$i = strpos($result, "HTTP/1.0 403 Access Denied");
		if ($i !== false) {
			$error = substr($result, $i+26);
			$i = strpos($error, "\n");
			$error = trim(substr($error,0,$i));
			PNApplication::error("We successfully connect, but the request was rejected ".$error);
			return;
		}
		
		if (file_exists("data/domains_synch/$domain/last_check"))
			@unlink("data/domains_synch/$domain/last_check");
		
		$f = fopen("conf/$domain.remote","w");
		fwrite($f, "<?php return array('url'=>'$url','password'=>\"".str_replace("\\","\\\\", str_replace('"','\\"', $password))."\"); ?>");
		fclose($f);
		echo "{version:".json_encode($version)."}";
	}
	
}
?>