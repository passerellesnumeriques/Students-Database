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
			if (isset($_POST["getmirrors"])) {
				try {
					$list = getMirrorsList(json_decode($_POST["provider_info"],true));
					if ($list == null) {
						header("HTTP/1.0 200 Error");
						die("Unable to get mirrors list");
					}
					echo json_encode($list);
					die();
				} catch (Exception $e) {
					header("HTTP/1.0 200 Error");
					die($e->getMessage());
				}
			}
			$url = $_POST["url"];
			if (isset($_POST["getsize"])) {
				try {
					$mirror_id = @$_POST["mirror_id"];
					$mirrors_provider = @$_POST["mirrors_provider"];
					$res = getURLFileSize($url, "application/octet-stream", $mirror_id, $mirrors_provider);
					$info = json_decode($res, true);
					if ($info == null || $info["size"] <= 0) {
						header("HTTP/1.0 200 Error");
						die("Unable to find the file on SourceForge");
					}
					die($res);
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
		switch (@$input["step"]) {
			case "check_if_done":
				if (!file_exists("data/updates/Students_Management_Software_".$input["version"].".zip") ||
					!file_exists("data/updates/Students_Management_Software_".$input["version"].".zip.md5")) {
						echo "not_downloaded";
						return;
					}
				$md5 = md5_file("data/updates/Students_Management_Software_".$input["version"].".zip", false);
				$md5_ = file_get_contents("data/updates/Students_Management_Software_".$input["version"].".zip.md5");
				if ($md5 <> $md5_) {
					echo "invalid_download";
					return;
				}
				echo "OK";
				return;
		}
	}
	
}
?>