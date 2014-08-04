<?php 
class service_latest_version extends Service {
	
	public function getRequiredRights() { return array("manage_application"); }
	
	public function documentation() { echo "Return the latest available version"; }
	public function inputDocumentation() { echo "none"; }
	public function outputDocumentation() { echo "<code>version</code>"; }
	
	public function execute(&$component, $input) {
		$url = "http://sourceforge.net/projects/studentsdatabase/files/latest.txt/download";
		$c = curl_init($url);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($c, CURLOPT_TIMEOUT, 25);
		set_time_limit(45);
		$result = curl_exec($c);
		if ($result === false) {
			PNApplication::error("Error connecting to SourceForge (".curl_errno($c)."): ".curl_error($c));
			curl_close($c);
			return;
		}
		curl_close($c);
		echo "{version:".json_encode($result)."}";
	}
	
}
?>