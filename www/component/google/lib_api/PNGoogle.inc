<?php 
class PNGoogle {

	/** @var Google_Client */
	protected $client;
	
	public function __construct() {
		set_include_path(get_include_path() . PATH_SEPARATOR . realpath("component/google/lib_api"));
		// initialize Google client
		require_once("Google/Client.php");
		$this->client = new Google_Client();
		require_once("Google/IO/Curl.php");
		$io = new Google_IO_Curl($this->client);
		// TODO proxy
		$this->client->setIo($io);
		
		// authentication
		require_once("Google/Auth/AssertionCredentials.php");
		$this->client->setApplicationName("Students Management Software");
		$secrets = include("conf/secrets.inc");
		$key = file_get_contents("conf/".$secrets["Google"]["service_key"]);
		$cred = new Google_Auth_AssertionCredentials(
			$secrets["Google"]["service_account"],
			array('https://www.googleapis.com/auth/calendar'),
			$key
		);
		$this->client->setAssertionCredentials($cred);
		if($this->client->getAuth()->isAccessTokenExpired()) {
			$this->client->getAuth()->refreshTokenWithAssertion($cred);
		}
	}
	
}
?>