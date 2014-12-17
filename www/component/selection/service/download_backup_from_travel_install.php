<?php 
class service_download_backup_from_travel_install extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		$username = @$_POST["username"];
		$session = @$_POST["session"];
		$token = @$_POST["token"];
		
		$i = strpos($token, "-");
		$rand1 = substr($token, 0, $i);
		$token = substr($token, $i+1);
		$i = strpos($token, "-");
		$ts = substr($token, 0, $i);
		$token = substr($token, $i+1);
		$i = strpos($token, "-");
		$rand2 = substr($token, 0, $i);
		$id = substr($token, $i+1);
		
		$value = PNApplication::$instance->application->getTemporaryData($id);
		if ($value <> $ts."/".$rand1."/".$session."/".$ts."/".$username."/".$rand2)
			die();

		// extend temporary data with token
		PNApplication::$instance->application->updateTemporaryData($id, $value);
		
		if ($_GET["type"] == "get_info") {
			// generate the backup
			require_once 'component/application/Backup.inc';
			$path = realpath("data")."/tmp_backup_for_selection_travel";
			if (file_exists($path)) Backup::removeDirectory($path);
			Backup::createBackupIn($path);
			// generate the datamodel
			require_once 'component/data_model/Model.inc';
			require_once 'component/data_model/DataModelJSON.inc';
			$f = fopen($path."/datamodel.json","w");
			fwrite($f, DataModelJSON::model(DataModel::get()));
			fclose($f);
			// include Google config
			mkdir($path."/conf");
			if (file_exists("conf/google.inc")) {
				$conf = include("conf/google.inc");
				if (isset($conf["service_key"])) copy("conf/".$conf["service_key"], $path."/conf/".$conf["service_key"]);
				$conf["service_key_admin"] = null;
				$f = fopen($path."/conf/google.inc");
				fwrite($f, "<?php return ".var_export($conf,true).";?>");
				fclose($f);
			}
			// create a file using storage
			$store_id = PNApplication::$instance->storage->store_data("temp", "", null, time()+60*60);
			$file = PNApplication::$instance->storage->get_data_path($store_id);
			// create the final zip file
			unlink($file);
			Backup::zipDirectory($path, $file);
			// remove temp files
			Backup::removeDirectory($path);
			// send size and id
			echo "{size:".filesize($file).",id:".$store_id."}";
		} else if ($_GET["type"] == "download") {
			$store_id = $_POST["id"];
			$from = $_POST["from"];
			$to = $_POST["to"];
			$file = PNApplication::$instance->storage->get_data_path($store_id);
			$f = fopen($file,"r");
			fseek($f, $from, SEEK_SET);
			while ($from <= $to) {
				$part = fread($f, $to-$from+1);
				if ($part === false) break;
				echo $part;
				$from += strlen($part);
			}
			fclose($f);
		}
	}
	
}
?>