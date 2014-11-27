<?php 
class service_download_update extends Service {
	
	public function getRequiredRights() { return array("manage_application"); }
	
	public function documentation() { echo "Functionalities to download an update"; }
	public function inputDocumentation() { echo "download, getsize, url, rest, range_from, range_to, target, step";}
	public function outputDocumentation() { echo "depends on the input..."; }
	public function getOutputFormat($input) { return "text/plain"; }
	
	public function execute(&$component, $input) {
		if (isset($_GET["download"])) {
			// deploy utils functionalities
			require_once("component/application/service/deploy_utils.inc");
			$url = $_POST["url"];
			if (isset($_POST["getsize"])) {
				try {
					$size = getURLFileSize($url, "application/octet-stream");
					if ($size <= 0) {
						header("HTTP/1.0 200 Error");
						die("Unable to find the file on SourceForge");
					}
					die("".$size);
				} catch (Exception $e) {
					header("HTTP/1.0 200 Error");
					die($e->getMessage());
				}
			}
			@mkdir("data/updates");
			if (isset($_GET["reset"]) && @$_POST["range_from"] == 0)
				@unlink($_POST["target"]);
			try {
				$from = isset($_POST["range_from"]) ? intval($_POST["range_from"]) : null;
				$to = isset($_POST["range_to"]) ? intval($_POST["range_to"]) : null;
				$result = download($url, @$_POST["target"], $from, $to, true);
				if (!isset($_POST["target"]))
					die($result);
			} catch (Exception $e) {
				header("HTTP/1.0 200 Error");
				die($e->getMessage());
			}
		}
		switch ($input["step"]) {
			case "check_if_done":
				if (!file_exists("data/updates/Students_Management_Software_".$input["version"].".zip") ||
					!file_exists("data/updates/Students_Management_Software_".$input["version"].".zip.checksum")) {
						echo "not_downloaded";
						return;
					}
				if (!$this->checkSum(realpath("data/updates/Students_Management_Software_".$input["version"].".zip"))) {
					echo "invalid_download";
					return;
				}
				echo "OK";
				return;
		}
	}
	
	/** Check a downloaded file using checksum
	 * @param string $path path of the file to check
	 * @return boolean true if it succeed
	 */
	private function checkSum($path) {
		$f = fopen($path,"r");
		$f2 = fopen($path.".checksum","r");
		do {
			set_time_limit(60);
			$s = fread($f, 1024);
			$nb = strlen($s);
			while ($nb < 1024) {
				$s2 = fread($f, 1024-$nb);
				$nb2 = strlen($s2);
				if ($nb2 == 0) break;
				$s .= $s2;
				$nb += $nb2;
			}
			if ($nb == 0) break;
			$bytes = unpack("C*",$s);
			$cs = pack("C", array_sum($bytes)%256);
			$s = fread($f2, 1);
			$bytes = unpack("C",$s);
			if ($bytes[1] <> $cs) { /*echo "invalid at ".$pos." expected is ".$cs." found is ".$bytes[1];*/ return false; }
		} while (true);
		fclose($f);
		fclose($f2);
		return true;
	}
	
}
?>