<?php 
class service_check_libraries_updates extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		$component->current_request()->no_process_time_warning = true;
		switch ($input["library"]) {
			case "phpexcel": $this->checkPHPExcel(); break;
			case "mpdf": $this->checkMPDF(); break;
			case "tinymce": $this->checkTinyMCE(); break;
			case "google_calendar": $this->checkGoogleCalendar(); break;
			case "google_oauth2": $this->checkGoogleOAuth2(); break;
			case "google_plus": $this->checkGooglePlus(); break;
			case "google_php_api": $this->checkGoogleAPI(); break;
			default: echo "{error:".json_encode("Unknown library ".$input["library"])."}";
		}
	}
		
	private function checkPHPExcel() {
		$this->checkGitHubRepo("PHPOffice/PHPExcel", "component/lib_php_excel/version");
	}
	
	private function checkMPDF() {
		$this->checkGitHubRepo("mpdf/mpdf", "component/lib_mpdf/version");
	}
	
	private function checkTinyMCE() {
		$c = curl_init("https://www.tinymce.com/download/");
		if (file_exists("conf/proxy")) include("conf/proxy");
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($c, CURLOPT_TIMEOUT, 200);
		set_time_limit(240);
		$result = curl_exec($c);
		if ($result === false) { echo "{error:".json_encode(curl_error($c))."}"; curl_close($c); return; }
		curl_close($c);
		$i = strpos($result, "class=\"badge-samosa");
		if ($i === false) return null;
		$j = strpos($result, ">", $i);
		if ($j === false) return null;
		$k = strpos($result, "</span>", $j);
		if ($k === false) return null;
		$v = trim(substr($result,$j+1,$k-$j-1));
		$c = file_get_contents("component/lib_tinymce/version");
		echo "{latest:".json_encode($v).",current:".json_encode($c)."}";
	}

	private function checkGoogleCalendar() {
		$c = curl_init("https://www.googleapis.com/discovery/v1/apis?name=calendar&preferred=true");
		if (file_exists("conf/proxy")) include("conf/proxy");
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($c, CURLOPT_TIMEOUT, 200);
		set_time_limit(240);
		$result = curl_exec($c);
		if ($result === false) { echo "{error:".json_encode(curl_error($c))."}"; curl_close($c); return; }
		curl_close($c);
		$json = json_decode($result, true);
		if ($json === null) return null;
		$v = @$json["items"][0]["version"];
		if ($v == null) return null;
		$c = file_get_contents("component/google/calendar.version");
		echo "{latest:".json_encode($v).",current:".json_encode($c)."}";
	}

	private function checkGoogleOAuth2() {
		$c = curl_init("https://www.googleapis.com/discovery/v1/apis?name=oauth2&preferred=true");
		if (file_exists("conf/proxy")) include("conf/proxy");
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($c, CURLOPT_TIMEOUT, 200);
		set_time_limit(240);
		$result = curl_exec($c);
		if ($result === false) { echo "{error:".json_encode(curl_error($c))."}"; curl_close($c); return; }
		curl_close($c);
		$json = json_decode($result, true);
		if ($json === null) return null;
		$v = @$json["items"][0]["version"];
		if ($v == null) return null;
		$c = file_get_contents("component/google/oauth2.version");
		echo "{latest:".json_encode($v).",current:".json_encode($c)."}";
	}

	private function checkGooglePlus() {
		$c = curl_init("https://www.googleapis.com/discovery/v1/apis?name=plus&preferred=true");
		if (file_exists("conf/proxy")) include("conf/proxy");
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($c, CURLOPT_TIMEOUT, 200);
		set_time_limit(240);
		$result = curl_exec($c);
		if ($result === false) { echo "{error:".json_encode(curl_error($c))."}"; curl_close($c); return; }
		curl_close($c);
		$json = json_decode($result, true);
		if ($json === null) return null;
		$v = @$json["items"][0]["version"];
		if ($v == null) return null;
		$c = file_get_contents("component/google/plus.version");
		echo "{latest:".json_encode($v).",current:".json_encode($c)."}";
	}

	private function checkGoogleAPI() {
		$this->checkGitHubRepo("google/google-api-php-client", "component/google/lib_api/version");
	}
	
	private function checkGitHubRepo($repo, $current_file) {
		$c = curl_init("https://api.github.com/repos/$repo/releases/latest");
		if (file_exists("conf/proxy")) include("conf/proxy");
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
		global $pn_app_version;
		curl_setopt($c, CURLOPT_HTTPHEADER, array(
				"User-Agent: Students-Management-Software/".$pn_app_version.".dev"
		));
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($c, CURLOPT_TIMEOUT, 200);
		set_time_limit(240);
		$result = curl_exec($c);
		if ($result === false) { echo "{error:".json_encode(curl_error($c))."}"; curl_close($c); return; }
		curl_close($c);
		$json = json_decode($result, true);
		if ($json === null) { echo "{error:'Invalid response returned from GitHub',response:".json_encode($result)."}"; return; }
		$v = @$json["tag_name"];
		if ($v == null) { echo "{error:'No release found in GitHub'}"; return; }
		$c = file_get_contents($current_file);
		echo "{latest:".json_encode($v).",current:".json_encode($c)."}";
	}
	
}
?>