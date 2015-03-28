<?php
/**
 * Functions to access to a remote installation of the software
 */ 
class RemoteAccess {
	
	/**
	 * Retrieve the version of the software for the given installation
	 * @param string $domain the domain (only used for error messages)
	 * @param string $url the URL of the software
	 * @return NULL|string the version of the remove Students Management Software
	 */
	public static function getDomainVersion($domain, $url) {
		require_once 'HTTPClient.inc';
		$c = new HTTPClient();
		$req = new HTTPRequest();
		$req->setURL($url."dynamic/application/service/get_backup");
		$req->postForm(array("request"=>"get_list"));
		$req->setHeader("User-Agent", "Students Management Software - Domain Synchronization from ".PNApplication::$instance->local_domain);
		try {
			$responses = $c->send($req);
			$resp = $responses[count($responses)-1];
			if ($resp->getStatus() == 403) {
				$version = $resp->getHeader("pn_version_changed");
				if ($version == null) {
					PNApplication::error("We can connect to the URL of domain $domain, but this is not Students Management Software");
					return null;
				}
				return $version;
			}
			if ($resp->getStatus() < 200 || $resp->getStatus() >= 300)
				throw new Exception("Server response: ".$resp->getStatus()." ".$resp->getStatusMessage());
			PNApplication::error("We can connect to the URL of domain $domain, but this is not Students Management Software");
			return null;
		} catch (Exception $e) {
			PNApplication::error("Connection to domain $domain: ".$e->getMessage());
			return null;
		}
	}
	
	/**
	 * Retrieve the list of available backups
	 * @param string $domain the domain
	 * @param string $domain_version the version currently used, previously retrieved using getDomainVersion
	 * @param string $url the URL of the software
	 * @param string $password the remote access password
	 * @return NULL|array the list of backups, each being an associative array with 2 keys: version, time
	 */
	public static function getBackupList($domain, $domain_version, $url, $password) {
		require_once 'HTTPClient.inc';
		$c = new HTTPClient();
		$req = new HTTPRequest();
		$req->setURL($url."dynamic/application/service/get_backup");
		$req->postForm(array("request"=>"get_list","password"=>$password));
		$req->setHeader("User-Agent", "Students Management Software - Domain Synchronization from ".PNApplication::$instance->local_domain);
		$req->setHeader("Cookie", "pnversion=$domain_version");
		try {
			$responses = $c->send($req);
			$resp = $responses[count($responses)-1];
			if ($resp->getStatus() < 200 || $resp->getStatus() >= 300)
				throw new Exception("Server response: ".$resp->getStatus()." ".$resp->getStatusMessage());
		} catch (Exception $e) {
			PNApplication::error("Connection to domain $domain: ".$e->getMessage());
			return null;
		}
		
		$backups = json_decode($resp->getBody(), true);
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

	/**
	 * Get the size of a file contained in a backup
	 * @param string $domain the domain
	 * @param string $domain_version the version of the software currently used by the domain
	 * @param string $url the URL of the domain
	 * @param string $password the remote access password
	 * @param string $backup_version the version of the backup to retrieve
	 * @param integer $backup_time the timestamp of the backup to retrieve
	 * @param string $backup_file the name of the file of the backup
	 * @return NULL|integer the size of the file
	 */
	public static function getBackupFileSize($domain, $domain_version, $url, $password, $backup_version, $backup_time, $backup_file) {
		require_once 'HTTPClient.inc';
		$c = new HTTPClient();
		$req = new HTTPRequest();
		$req->setURL($url."dynamic/application/service/get_backup?get_size=true");
		$req->postForm(array("request"=>"get_backup","password"=>$password,"version"=>$backup_version,"time"=>$backup_time,"file"=>$backup_file));
		$req->setHeader("User-Agent", "Students Management Software - Domain Synchronization from ".PNApplication::$instance->local_domain);
		$req->setHeader("Cookie", "pnversion=$domain_version");
		try {
			$responses = $c->send($req);
			$resp = $responses[count($responses)-1];
			if ($resp->getStatus() == 404)
				return -1;
			if ($resp->getStatus() < 200 || $resp->getStatus() >= 300)
				throw new Exception("Server response: ".$resp->getStatus()." ".$resp->getStatusMessage());
		} catch (Exception $e) {
			PNApplication::error("Connection to domain $domain: ".$e->getMessage());
			return null;
		}

		$result = $resp->getHeader("File-Size");
		if ($result == null) {
			PNApplication::error("Invalid response of domain $domain");
			return null;
		}
		if (!is_numeric($result)) {
			PNApplication::error("Invalid response of domain $domain");
			return null;
		}
		return intval($result);
	}
	
	/**
	 * Download a portion of a file contained in a backup
	 * @param string $domain the domain
	 * @param string $domain_version the version used by the domain
	 * @param string $url the URL of the domain
	 * @param string $password the remote access password
	 * @param string $backup_version the version of the backup
	 * @param integer $backup_time the timestamp of the backup
	 * @param string $backup_file the file in the backup
	 * @param integer $from the position in the file from which to download
	 * @param integer $to the end position of the file to download
	 * @param string $target the path of the file in which to save the downloaded portion
	 * @param integer $max_time the maximum number of seconds to wait for this portion to be downloaded before to timeout
	 * @return integer the number of bytes downloaded and written in the file
	 */
	public static function downloadBackupFileRange($domain, $domain_version, $url, $password, $backup_version, $backup_time, $backup_file, $from, $to, $target, $max_time) {
		require_once 'HTTPClient.inc';
		$c = new HTTPClient();
		$req = new HTTPRequest();
		$req->setURL($url."dynamic/application/service/get_backup");
		$req->postForm(array("request"=>"get_backup","password"=>$password,"version"=>$backup_version,"time"=>$backup_time,"file"=>$backup_file));
		$req->setHeader("User-Agent", "Students Management Software - Domain Synchronization from ".PNApplication::$instance->local_domain);
		$req->setHeader("Cookie", "pnversion=$domain_version");
		$req->downloadRange($from, $to);
		try {
			$responses = $c->send($req);
			$resp = $responses[count($responses)-1];
			if ($resp->getStatus() < 200 || $resp->getStatus() >= 300)
				throw new Exception("Server response: ".$resp->getStatus()." ".$resp->getStatusMessage());
		} catch (Exception $e) {
			PNApplication::error("Download $file from domain $domain: ".$e->getMessage());
			return null;
		}
		
		$f = fopen($target, "a");
		fwrite($f, $resp->getBody());
		fclose($f);
		return strlen($resp->getBody());
	}
	
	/**
	 * Download a file in a backup
	 * @param string $domain the domain
	 * @param string $domain_version the version used by the domain
	 * @param string $url the URL of the domain
	 * @param string $password the remote access password
	 * @param string $backup_version the version of the backup
	 * @param integer $backup_time the timestamp of the backup
	 * @param string $backup_file the file in the backup
	 * @param integer $filesize the size of the file to download
	 * @param string $target the path of the file in which to save the downloaded portion
	 */
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
	
	public static function downloadStorageFile($domain, $domain_version, $url, $password, $storage_id, $expected_size, $target) {
		// TODO support with HTTPClient ?
		$c = curl_init($url."dynamic/application/service/get_backup");
		if (file_exists("conf/proxy")) include("conf/proxy");
		curl_setopt($c, CURLOPT_RETURNTRANSFER, FALSE);
		curl_setopt($c, CURLOPT_HEADER, FALSE);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_POSTFIELDS, array("request"=>"get_storage","password"=>$password,"id"=>$storage_id));
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 15);
		$max_time = 60+floor($expected_size/32768);
		curl_setopt($c, CURLOPT_TIMEOUT, $max_time);
		curl_setopt($c, CURLOPT_HTTPHEADER, array("Cookie: pnversion=$domain_version","User-Agent: Students Management Software - Domain Synchronization from ".PNApplication::$instance->local_domain));
		curl_setopt(CURLOPT_FILE, $target);
		set_time_limit($max_time+30);
		$result = curl_exec($c);
		curl_close($c);
		return $result;
		
	}
	
}
?>