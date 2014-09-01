<?php 
class service_download_document_opener extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function getOutputFormat($input) { return "application/octet-stream"; }
	
	public function execute(&$component, $input) {
		header("Content-Disposition: attachment; filename=\"InstallPNDocumentOpener.exe\"");
		header("Content-Length: ".filesize("component/documents/static/setup.exe"));
		readfile("component/documents/static/setup.exe");
	}
	
}
?>