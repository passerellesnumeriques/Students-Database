<?php 
class service_check_libraries_updates extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		echo "[";
		$first = true;
		$php_excel = $this->checkPHPExcel();
		if ($php_excel <> null) {
			if ($first) $first = false; else echo ",";
			echo "{name:'PHPExcel',".$php_excel."}";
		}
		$mpdf = $this->checkMPDF();
		if ($mpdf <> null) {
			if ($first) $first = false; else echo ",";
			echo "{name:'mPDF',".$mpdf."}";
		}
		$tinymce = $this->checkTinyMCE();
		if ($tinymce <> null) {
			if ($first) $first = false; else echo ",";
			echo "{name:'tinymce',".$tinymce."}";
		}
		echo "]";
	}
		
	private function checkPHPExcel() {
		$c = curl_init("https://phpexcel.codeplex.com/");
		if (file_exists("conf/proxy")) include("conf/proxy");
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($c, CURLOPT_TIMEOUT, 200);
		set_time_limit(240);
		$result = curl_exec($c);
		curl_close($c);
		if ($result === false) return null;
		$i = strpos($result, "rating_header");
		if ($i === false) return null;
		$j = strpos($result, "<td>", $i);
		if ($j === false) return null;
		$k = strpos($result, "</td>", $j);
		if ($k === false) return null;
		$v = trim(substr($result,$j+4,$k-$j-4));
		$i = strpos($v, " ");
		if ($i === false) return null;
		$v = trim(substr($v, $i+1));
		$c = file_get_contents("component/lib_php_excel/version");
		return $this->compareVersions($v, $c);
	}
	
	private function checkMPDF() {
		$c = curl_init("http://www.mpdf1.com/mpdf/index.php?page=Download");
		if (file_exists("conf/proxy")) include("conf/proxy");
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($c, CURLOPT_TIMEOUT, 200);
		set_time_limit(240);
		$result = curl_exec($c);
		curl_close($c);
		if ($result === false) return null;
		$i = strpos($result, "Download mPDF Version");
		if ($i === false) return null;
		$j = strpos($result, "(", $i);
		if ($j === false) return null;
		$v = trim(substr($result,$i+21,$j-$i-21));
		$c = file_get_contents("component/lib_mpdf/version");
		return $this->compareVersions($v, $c);
	}
	
	private function checkTinyMCE() {
		$c = curl_init("http://www.tinymce.com/download/download.php");
		if (file_exists("conf/proxy")) include("conf/proxy");
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($c, CURLOPT_TIMEOUT, 200);
		set_time_limit(240);
		$result = curl_exec($c);
		curl_close($c);
		if ($result === false) return null;
		$i = strpos($result, "<td><a href=\"/develop/changelog/?type=tinymce\">TinyMCE");
		if ($i === false) return null;
		$j = strpos($result, "</a>", $i);
		if ($j === false) return null;
		$v = trim(substr($result,$i+54,$j-$i-54));
		$c = file_get_contents("component/lib_tinymce/version");
		return $this->compareVersions($v, $c);
	}
	
	private function compareVersions($latest, $current) {
		$l = explode(".",$latest);
		$c = explode(".",$current);
		for ($i = 0; $i < count($l) && $i < count($c); ++$i) {
			$a = intval($l[$i]);
			$b = intval($c[$i]);
			if ($a > $b) return "new_version:".json_encode($latest).",current_version:".json_encode($current);
			if ($a < $b) return null;
		}
		if (count($l) > count($c)) return "new_version:".json_encode($latest).",current_version:".json_encode($current);
		return null;
	}
	
}
?>