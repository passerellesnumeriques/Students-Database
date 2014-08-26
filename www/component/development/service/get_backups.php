<?php 
class service_get_backups extends Service {
	public function getRequiredRights() { return array(); }
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function execute(&$component, $input) {
		$backups = array();
		if (file_exists("data/backups")) {
			$versions = array();
			$dir = opendir("data/backups");
			while (($file = readdir($dir)) <> null) {
				if ($file == "." || $file == "..") continue;
				if (!is_dir("data/backups/$file")) continue;
				array_push($versions, $file);
			}
			closedir($dir);
			foreach ($versions as $version) {
				$dir = opendir("data/backups/$version");
				while (($file = readdir($dir)) <> null) {
					if ($file == "." || $file == "..") continue;
					if (!is_dir("data/backups/$version/$file")) continue;
					array_push($backups, array("version"=>$version,"time"=>$file));
				}
				closedir($dir);
			}
		}
		echo json_encode($backups);
	}
}
?>