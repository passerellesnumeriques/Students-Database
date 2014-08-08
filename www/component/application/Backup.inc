<?php 
class Backup {
	
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
		self::zipDirectory($path."/db", $path."/db.zip");
		self::removeDirectory($path."/db");
		set_time_limit(60*60);
		self::zipDirectory(realpath("data/$local_domain/storage"), $path."/storage.zip");
		if (file_exists("data/$local_domain/custom_tables"))
			self::zipDirectory(realpath("data/$local_domain/custom_tables"), $path."/custom_tables.zip");
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
		$sql = "SELECT $cols_names FROM `students_".PNApplication::$instance->local_domain."`.`$table_name`";
		$db = SQLQuery::getDataBaseAccessWithoutSecurity();
		$res = $db->execute($sql);
		$row = $db->nextRow($res);
		if ($row <> null) {
			$f = fopen($path."/$table_name","w");
			$insert_sql = "INSERT INTO `students_".PNApplication::$instance->local_domain."`.`".$table_name."` ($cols_names) VALUES\n";
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
	
	public static function zipDirectory($dir_path, $zip_path) {
		if (class_exists("ZipArchive")) {
			$zip = new ZipArchive();
			$zip->open($zip_path, ZipArchive::CREATE);
			$dir = opendir($dir_path);
			if (!$dir) throw new Exception("Cannot zip directory ".$dir_path.": directory not found.");
			$empty = true;
			while (($file = readdir($dir)) <> null) {
				if ($file == "." || $file == "..") continue;
				$zip->addFile($dir_path."/".$file, $file);
				$empty = false;
			}
			closedir($dir);
			if ($empty) $zip->addEmptyDir("empty");
			$zip->close();
		} else {
			$output = array();
			$ret = 0;
			exec("/usr/bin/unzip \"".$zip_path."\" -d \"".$target_dir."\"", $output, $ret);
			if ($ret <> 0)
				throw new Exception("Error unzipping file ".$zip_path." (".$ret.")");
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
	
	
	public static function recoverBackup($time) {
		global $local_domain;
		require_once("component/PNApplication.inc");
		if (PNApplication::$instance == null) {
			PNApplication::$instance = new PNApplication();
			PNApplication::$instance->local_domain = $local_domain;
			PNApplication::$instance->current_domain = $local_domain;
			PNApplication::$instance->init();
		}
		global $pn_app_version;
		if (!is_dir("data/backups/$pn_app_version/$time")) throw new Exception("Backup does not exist");
		if (file_exists("data/recover")) self::removeDirectory("data/recover");
		mkdir("data/recover");
		// recover database
		mkdir("data/recover/db");
		require_once("component/application/service/deploy_utils.inc");
		unzipFile(realpath("data/backups/$pn_app_version/$time/db.zip"), realpath("data/recover/db"));
		require_once("component/data_model/Model.inc");
		require_once("SQLQuery.inc");
		$model = DataModel::get();
		$db = SQLQuery::getDataBaseAccessWithoutSecurity();
		$res = $db->execute("CREATE DATABASE IF NOT EXISTS students_".$local_domain." DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci",false);
		$res = $db->execute("SELECT concat('DROP TABLE IF EXISTS ', table_name, ';') FROM information_schema.tables WHERE table_schema = 'students_$local_domain'");
		$db->execute("USE students_".$local_domain);
		set_time_limit(600);
		while (($sql = $db->nextRowArray($res)) <> null)
			$db->execute($sql[0]);
		require_once("component/data_model/DataBaseUtilities.inc");
		$ref = new ReflectionClass("DataModel");
		$p = $ref->getProperty("tables");
		$p->setAccessible(true);
		$tables = $p->getValue($model);
		foreach ($tables as $table) {
			if ($table->getModel() instanceof SubDataModel) continue;
			set_time_limit(600);
			DataBaseUtilities::createTable($db, $table);
			if (file_exists("data/recover/db/".$table->getName())) {
				set_time_limit(30*60);
				DataBaseUtilities::importSQL($db, "data/recover/db/".$table->getName());
			}
		}
		foreach ($tables as $table) {
			if (!($table->getModel() instanceof SubDataModel)) continue;
			foreach ($table->getModel()->getExistingInstances() as $sub_model) {
				set_time_limit(600);
				DataBaseUtilities::createTable($db, $table, "_".$sub_model);
				if (file_exists("data/recover/db/".$table->getName()."_".$sub_model)) {
					set_time_limit(30*60);
					DataBaseUtilities::importSQL($db, "data/recover/db/".$table->getName()."_".$sub_model);
				}
			}
		}
		
		// recover custom tables
		if (file_exists("data/$local_domain/custom_tables")) self::removeDirectory("data/$local_domain/custom_tables");
		if (file_exists("data/backups/$pn_app_version/$time/custom_tables.zip")) {
			mkdir("data/$local_domain/custom_tables");
			unzipFile(realpath("data/backups/$pn_app_version/$time/custom_tables.zip"), realpath("data/$local_domain/custom_tables"));
		}
		// recover storage		
		if (file_exists("data/$local_domain/storage")) self::removeDirectory("data/$local_domain/storage");
		mkdir("data/$local_domain/storage");
		set_time_limit(60*60);
		unzipFile(realpath("data/backups/$pn_app_version/$time/storage.zip"), realpath("data/$local_domain/storage"));
		if (file_exists("data/$local_domain/storage/empty")) rmdir("data/$local_domain/storage/empty");
	}
	
}
?>