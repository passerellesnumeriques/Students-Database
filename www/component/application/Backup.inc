<?php 
class Backup {

	public static $possible_files = array(
		"db",
		"storage",
		"custom_tables"
	);
	
	public static function removeOlds($max_nb = 30) {
		$list = self::listBackups();
		if (count($list) <= $max_nb) return;
		usort($list, function($b1,$b2) {
			if ($b1["time"] < $b2["time"]) return -1;
			if ($b1["time"] > $b2["time"]) return 1;
			return 0;
		});
		while (count($list) > $max_nb) {
			self::removeDirectory("data/backups/".$list[0]["version"]."/".$list[0]["time"]);
			array_splice($list, 0, 1);
		}
	}
	
	public static function listBackups() {
		$list = array();
		if (!file_exists("data/backups")) return $list;
		$dir = opendir("data/backups");
		while (($filename = readdir($dir)) <> null) {
			if ($filename == "." || $filename == "..") continue;
			if (!is_dir("data/backups/$filename")) continue;
			self::_listBackups($filename, $list);
		}
		closedir($dir);
		return $list;
	}
	private static function _listBackups($version, &$list) {
		$dir = opendir("data/backups/$version");
		while (($filename = readdir($dir)) <> null) {
			if ($filename == "." || $filename == "..") continue;
			if (!is_dir("data/backups/$version/$filename")) continue;
			array_push($list,array("version"=>$version,"time"=>intval($filename)));
		}
		closedir($dir);
	}
	
	public static function createBackup() {
		global $local_domain;
		require_once("component/PNApplication.inc");
		if (PNApplication::$instance == null) {
			PNApplication::$instance = new PNApplication();
			PNApplication::$instance->local_domain = $local_domain;
			PNApplication::$instance->current_domain = $local_domain;
			PNApplication::$instance->init();
		}
		global $pn_app_version;
		@mkdir("data/backups");
		@mkdir("data/backups/$pn_app_version");
		$time = "".time();
		$path = "data/backups/$pn_app_version/$time";
		mkdir($path);
		mkdir($path."/db");
		require_once("component/data_model/Model.inc");
		require_once("SQLQuery.inc");
		foreach (DataModel::get()->internalGetTables(false) as $table)
			self::backupTable($table, $table->getName(), null, $path."/db");
		foreach (DataModel::get()->getSubModels() as $sm)
			foreach ($sm->getExistingInstances() as $sub_model)
				foreach ($sm->internalGetTables() as $table)
					self::backupTable($table, $table->getSQLNameFor($sub_model), $sub_model, $path."/db");
		self::zipDirectory(realpath($path)."/db", realpath($path)."/db.zip");
		self::removeDirectory($path."/db");
		set_time_limit(60*60);
		self::zipDirectory(realpath("data/$local_domain/storage"), realpath($path)."/storage.zip", true);
		if (file_exists("data/$local_domain/custom_tables"))
			self::zipDirectory(realpath("data/$local_domain/custom_tables"), realpath($path)."/custom_tables.zip", true);
		touch($path."/ready");
		return $time;
	}
	
	/**
	 * @param \datamodel\Table $table_spec
	 * @param string $table_name
	 * @param string $path
	 */
	private static function backupTable($table_spec, $table_name, $sub_model, $path) {
		set_time_limit(600);
		$cols = $table_spec->internalGetColumnsFor($sub_model);
		$cols_names = "";
		$first = true;
		foreach ($cols as $col) {
			if ($first) $first = false; else $cols_names .= ",";
			$cols_names .= "`".$col->name."`";
		}
		global $db_config;
		$sql = "SELECT $cols_names FROM `".$db_config["prefix"].PNApplication::$instance->local_domain."`.`$table_name`";
		$db = SQLQuery::getDataBaseAccessWithoutSecurity();
		$res = $db->execute($sql);
		$row = $db->nextRow($res);
		if ($row <> null) {
			$f = fopen($path."/$table_name","w");
			$insert_sql = "INSERT INTO `".$table_name."` ($cols_names) VALUES\n";
			fwrite($f, $insert_sql);
			$row_count = 0;
			do {
				$line = "";
				if ($row_count > 0) $line .= ",";
				$line .= "(";
				$first_col = true;
				foreach ($cols as $col) {
					if ($first_col) $first_col = false; else $line .= ",";
					$val = $row[$col->name];
					if ($val === null) $line .= "NULL";
					else $line .= "'".$db->escapeString($val)."'";
				}
				$line .= ")\n";
				fwrite($f, $line);
				$row = $db->nextRow($res);
				$row_count++;
				if ($row <> null && $row_count >= 1000) {
					fwrite($f, ";\n".$insert_sql);
					$row_count = 0;
				}
			} while ($row <> null);
			fwrite($f, ";");
			fclose($f);
		}
	}
	
	private static function zipDir($dir_path, $zip_path, &$zip) {
		$dir = opendir($dir_path);
		if (!$dir) throw new Exception("Cannot zip directory ".$dir_path.": directory not found.");
		$empty = true;
		while (($file = readdir($dir)) <> null) {
			if ($file == "." || $file == "..") continue;
			if (is_dir($dir_path."/".$file))
				$empty &= self::zipDir($dir_path."/".$file, $zip_path <> "" ? $zip_path."/".$file : $file, $zip);
			else {
				$zip->addFile($dir_path."/".$file, $zip_path <> "" ? $zip_path."/".$file : $file);
				$empty = false;
			}
		}
		closedir($dir);
		return $empty;
	}
	
	public static function zipDirectory($dir_path, $zip_path, $handle_empty = false) {
		if (class_exists("ZipArchive")) {
			$zip = new ZipArchive();
			if (!$zip->open($zip_path, ZipArchive::CREATE)) throw new Exception("Unable to create zip file: ".$zip_path);
			$empty = self::zipDir($dir_path, "", $zip);
			if ($empty && $handle_empty) $zip->addEmptyDir("empty");
			$zip->close();
		} else {
			$output = array();
			$ret = 0;
			exec("/usr/bin/zip \"".$zip_path."\" \"".$dir_path."\"/*", $output, $ret);
			if ($ret <> 0 && (!$handle_empty || $ret <> 18))
				throw new Exception("Error creating zip file ".$zip_path." from directory ".$dir_path." (".$ret.")");
		}
	}
	
	public static function removeDirectory($path) {
		$dir = opendir($path);
		while (($filename = readdir($dir)) <> null) {
			if ($filename == ".") continue;
			if ($filename == "..") continue;
			if (is_dir($path."/".$filename))
				self::removeDirectory($path."/".$filename);
			else
				unlink($path."/".$filename);
		}
		closedir($dir);
		if (!@rmdir($path))
			rmdir($path);
	}
	
	
	public static function recoverBackup($time, $version = null) {
		global $local_domain;
		require_once("component/PNApplication.inc");
		if (PNApplication::$instance == null) {
			PNApplication::$instance = new PNApplication();
			PNApplication::$instance->local_domain = $local_domain;
			PNApplication::$instance->current_domain = $local_domain;
			PNApplication::$instance->init();
		}
		global $pn_app_version;
		if ($version == null) $version = $pn_app_version;
		if (!is_dir("data/backups/$version/$time")) throw new Exception("Backup does not exist");
		self::synchronizeDatabase("data/backups/$version/$time", $local_domain);
		$domains = include("conf/domains");
		foreach ($domains as $domain=>$descr)
			if ($domain <> $local_domain)
				self::synchronizeDatabase(null, $domain);
	}
	
	public static function synchronizeDatabase($directory, $domain) {
		if (file_exists("data/recover")) self::removeDirectory("data/recover");
		if (!@mkdir("data/recover")) {
			sleep(1);
			if (!@mkdir("data/recover")) {
				sleep(1);
				if (!@mkdir("data/recover")) {
					sleep(1);
					if (!mkdir("data/recover")) return;
				}
			}
		}
		// recover database
		mkdir("data/recover/db");
		require_once("component/application/service/deploy_utils.inc");
		if ($directory <> null)
			unzipFile(realpath("$directory/db.zip"), realpath("data/recover/db"));
		require_once("component/data_model/Model.inc");
		require_once("SQLQuery.inc");
		$model = DataModel::get();
		$db = SQLQuery::getDataBaseAccessWithoutSecurity();
		require_once("component/data_model/DataBaseUtilities.inc");
		global $db_config;
		$res = $db->execute("CREATE DATABASE IF NOT EXISTS ".$db_config["prefix"].$domain." DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci",false);
		$res = $db->execute("SELECT concat('DROP TABLE IF EXISTS ', table_name, ';') FROM information_schema.tables WHERE table_schema = '".$db_config["prefix"].$domain."'");
		$db->execute("USE ".$db_config["prefix"].$domain);
		set_time_limit(600);
		while (($sql = $db->nextRowArray($res)) <> null)
			$db->execute($sql[0]);
		$ref = new ReflectionClass("DataModel");
		$p = $ref->getProperty("tables");
		$p->setAccessible(true);
		$tables = $p->getValue($model);
		$sub_models_keys = array();
		foreach ($tables as $table) {
			set_time_limit(600);
			DataBaseUtilities::createTable($db, $table);
			if (file_exists("data/recover/db/".$table->getName())) {
				set_time_limit(30*60);
				DataBaseUtilities::importSQL($db, "data/recover/db/".$table->getName());
				foreach ($table->_key_submodels as $sm) {
					$keys = SQLQuery::create()
					->bypassSecurity()
					#DEV
					->noWarning()
					#END
					->select($table->getName())->field($table->getPrimaryKey()->name)->executeSingleField();
					$sub_models_keys[$table->getName()] = $keys;
					foreach ($keys as $key) $sm->createTables($key);
				}
			}
		}
		$p = $ref->getProperty("sub_models");
		$p->setAccessible(true);
		$sub_models = $p->getValue($model);
		foreach ($sub_models as $sm) {
			if (!isset($sub_models_keys[$sm->getParentTable()])) continue;
			$p = $ref->getProperty("tables");
			$p->setAccessible(true);
			$tables = $p->getValue($sm);
			foreach ($tables as $table) {
				foreach ($sub_models_keys[$sm->getParentTable()] as $sub_model) {
					if (file_exists("data/recover/db/".$table->getName()."_".$sub_model)) {
						set_time_limit(600);
						$db->execute("DROP TABLE IF EXISTS `".($table->getName()."_".$sub_model)."`");
						DataBaseUtilities::createTable($db, $table, "_".$sub_model);
						set_time_limit(30*60);
						DataBaseUtilities::importSQL($db, "data/recover/db/".$table->getName()."_".$sub_model);
					}
				}
			}
		}
		
		@mkdir("data/$domain");
		// recover custom tables
		if (file_exists("data/$domain/custom_tables")) self::removeDirectory("data/$domain/custom_tables");
		if ($directory <> null && file_exists("$directory/custom_tables.zip")) {
			mkdir("data/$domain/custom_tables");
			unzipFile(realpath("$directory/custom_tables.zip"), realpath("data/$domain/custom_tables"));
		}
		// recover storage
		if (file_exists("data/$domain/storage")) self::removeDirectory("data/$domain/storage");
		mkdir("data/$domain/storage");
		set_time_limit(60*60);
		if ($directory <> null)
			unzipFile(realpath("$directory/storage.zip"), realpath("data/$domain/storage"));
		if (file_exists("data/$domain/storage/empty")) rmdir("data/$domain/storage/empty");
	}
	
}
?>