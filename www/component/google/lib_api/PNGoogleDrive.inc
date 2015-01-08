<?php 
require_once("PNGoogle.inc");
class PNGoogleDrive extends PNGoogle_ProjectSpecific {
	
	/** @var Google_Service_Drive */
	protected $service;
	
	public function __construct() {
		parent::__construct(array('https://www.googleapis.com/auth/drive'));
		// connect to Google Drive service
		require_once("Google/Service/Drive.php");
		$this->service = new Google_Service_Drive($this->client);
	}
	
	/**
	 * 
	 * @return Google_Service_Drive_FileList
	 */
	public function getFiles() {
		return $this->service->files->listFiles(array());
	}
}
?>