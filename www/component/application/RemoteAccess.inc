<?php 
class RemoteAccess {
	
	public static function getDomainVersion($domain, $url) {
		$c = curl_init($url."dynamic/application/service/get_backup");
		if (file_exists("conf/proxy")) include("conf/proxy");
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_HEADER, TRUE);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_POSTFIELDS, array("request"=>"get_list"));
		curl_setopt($c, CURLOPT_HTTPHEADER, array("User-Agent: Students Management Software - Domain Synchronization from ".PNApplication::$instance->local_domain));
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($c, CURLOPT_TIMEOUT, 30);
		$result = curl_exec($c);
		if ($result === false) {
			PNApplication::error("Connection to domain $domain: ".curl_error($c));
			curl_close($c);
			return null;
		}
		curl_close($c);
		
		$i = strpos($result, "pn_version_changed:");
		if ($i === false) {
			PNApplication::error("We can connect to the URL of domain $domain, but this is not Students Management Software");
			return null;
		}
		$result = substr($result,$i+19);
		$i = strpos($result,"\n");
		$version = trim(substr($result,0,$i));
		return $version;
	}
	
	public static function getBackupList($domain, $domain_version, $url, $password) {
		$c = curl_init($url."dynamic/application/service/get_backup");
		if (file_exists("conf/proxy")) include("conf/proxy");
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_HEADER, FALSE);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_POSTFIELDS, array("request"=>"get_list","password"=>$password));
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($c, CURLOPT_TIMEOUT, 30);
		curl_setopt($c, CURLOPT_HTTPHEADER, array("Cookie: pnversion=$domain_version","User-Agent: Students Management Software - Domain Synchronization from ".PNApplication::$instance->local_domain));
		$result = curl_exec($c);
		if ($result === false) {
			PNApplication::error("Connection to domain $domain: ".curl_error($c));
			curl_close($c);
			return null;
		}
		curl_close($c);
		
		$backups = json_decode($result, true);
		if ($backups == null) {
			PNApplication::error("Invalid response of domain $domain");
			return null;
		}
		if ($backups["result"] == null) {
			PNApplication::error("Access denied for domain $domain");
			return null;
		}
		if (!is_array($backups["result"])) {
			PNApplication::error("Invalid response of domain $domain");
			return null;
		}
		return $backups["result"];
	}

	public static function getBackupFileSize($domain, $domain_version, $url, $password, $backup_version, $backup_time, $backup_file) {
		$c = curl_init($url."dynamic/application/service/get_backup?get_size=true");
		if (file_exists("conf/proxy")) include("conf/proxy");
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_HEADER, TRUE);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_POSTFIELDS, array("request"=>"get_backup","password"=>$password,"version"=>$backup_version,"time"=>$backup_time,"file"=>$backup_file));
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($c, CURLOPT_TIMEOUT, 30);
		curl_setopt($c, CURLOPT_HTTPHEADER, array("Cookie: pnversion=$domain_version","User-Agent: Students Management Software - Domain Synchronization from ".PNApplication::$instance->local_domain));
		$result = curl_exec($c);
		if ($result === false) {
			PNApplication::error("Connection to domain $domain: ".curl_error($c));
			curl_close($c);
			return null;
		}
		curl_close($c);
		if (strpos($result, "HTTP/1.0 404") !== false)
			return -1;
		$i = strpos($result, "File-Size:");
		if ($i === false) {
			PNApplication::error("Invalid response of domain $domain");
			return null;
		}
		$result = substr($result, $i+10);
		$i = strpos($result, "\n");
		if ($i === false) {
			PNApplication::error("Invalid response of domain $domain");
			return null;
		}
		$result = trim(substr($result,0,$i));
		if (!is_numeric($result)) {
			PNApplication::error("Invalid response of domain $domain");
			return null;
		}
		return intval($result);
	}
	
	public static function downloadBackupFileRange($domain, $domain_version, $url, $password, $backup_version, $backup_time, $backup_file, $from, $to, $target, $max_time) {
		$c = curl_init($url."dynamic/application/service/get_backup");
		if (file_exists("conf/proxy")) include("conf/proxy");
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_HEADER, FALSE);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_POSTFIELDS, array("request"=>"get_backup","password"=>$password,"version"=>$backup_version,"time"=>$backup_time,"file"=>$backup_file));
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($c, CURLOPT_TIMEOUT, $max_time);
		curl_setopt($c, CURLOPT_HTTPHEADER, array("Cookie: pnversion=$domain_version","Range: bytes=".$from."-".$to,"User-Agent: Students Management Software - Domain Synchronization from ".PNApplication::$instance->local_domain));
		$result = curl_exec($c);
		if ($result === false) {
			PNApplication::error("Download $file from domain $domain: ".curl_error($c));
			curl_close($c);
			return 0;
		}
		curl_close($c);
		$f = fopen($target, "a");
		fwrite($f, $result);
		fclose($f);
		return strlen($result);
	}
	
	public static function downloadBackupFile($domain, $domain_version, $url, $password, $backup_version, $backup_time, $backup_file, $filesize, $target) {
		$f = fopen($target, "w");
		fclose($f);
		$from = 0;
		while ($from < $filesize) {
			$to = $from + 2*1024*1024;
			if ($to >= $filesize) $to = $filesize-1;
			set_time_limit(300);
			$downloaded = self::downloadBackupFileRange($domain, $domain_version, $url, $password, $backup_version, $backup_time, $backup_file, $from, $to, $target, 275);
			if ($downloaded == 0) break;
			$from += $downloaded;
		}
	}
	
	
}
?>