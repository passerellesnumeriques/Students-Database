<?php 
abstract class PNGoogle {

	/** @var Google_Client */
	protected $client;
	
	public function __construct($scopes) {
		set_include_path(get_include_path() . PATH_SEPARATOR . realpath("component/google/lib_api"));
		// initialize Google client
		require_once("Google/Client.php");
		$this->client = new Google_Client();
		require_once("Google/IO/Curl.php");
		$io = new Google_IO_Curl($this->client);
		// TODO proxy
		$this->client->setIo($io);
		
		// authentication
		$this->client->setApplicationName("Students Management Software");
		$cred = $this->getCredentials($scopes);
		$this->client->setAssertionCredentials($cred);
		if($this->client->getAuth()->isAccessTokenExpired()) {
			$this->client->getAuth()->refreshTokenWithAssertion($cred);
		}
	}
	
	protected abstract function getCredentials($scopes);
	
}

class PNGoogle_ProjectSpecific extends PNGoogle {
	
	public function getCredentials($scopes) {
		require_once("Google/Auth/AssertionCredentials.php");
		$secrets = include("conf/google.inc");
		$key = file_get_contents("conf/".$secrets["service_key"]);
		$cred = new Google_Auth_AssertionCredentials(
			$secrets["service_account"],
			$scopes,
			$key
		);
		return $cred;
	}

}

class PNGoogle_Admin extends PNGoogle {
	
	public function getCredentials($scopes) {
		require_once("Google/Auth/AssertionCredentials.php");
		$secrets = include("conf/google.inc");
		$key = file_get_contents("conf/".$secrets["service_key_admin"]);
		$cred = new Google_Auth_AssertionCredentials(
			$secrets["service_account_admin"],
			$scopes,
			$key,
			'notasecret',
			'http://oauth.net/grant_type/jwt/1.0/bearer',
			'students.management@passerellesnumeriques.org'
		);
		return $cred;
	}

}
?>