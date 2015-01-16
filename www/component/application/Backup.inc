<?php
/**
 * Class to manipulate the backups (create, recover, list..)
 */ 
class Backup {

	/** string[] list of files which can exist in a backup */
	public static $possible_files = array(
		"db",
		"storage",
		"custom_tables",
		"storage.list"
	);
	
	/**
	 * Remove the olds backups
	 * @param number $max_nb maximum number of backups to keep
	 */
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
	
	/**
	 * Get the list of existing backups
	 * @return array list of backups, each being represented by an array with 2 keys: version and time
	 */
	public static function listBackups() {
		$list = array();
		if (!file_exists("data/backups")) return $list;
		$dir = opendir("data/backups");
		while (($filename = readdir($dir)) <> null) {
			if ($filename == "." || $filename == "..") continue;
			if (!is_dir("data/backups/$filename")) continue;
			self::listBackupsRecurs($filename, $list);
		}
		closedir($dir);
		return $list;
	}
	/** Retrieve list of backups for a given version
	 * @param string $version version
	 * @param array $list list to populate
	 */
	private static function listBackupsRecurs($version, &$list) {
		$dir = opendir("data/backups/$version");
		while (($filename = readdir($dir)) <> null) {
			if ($filename == "." || $filename == "..") continue;
			if (!is_dir("data/backups/$version/$filename")) continue;
			array_push($list,array("version"=>$version,"time"=>intval($filename)));
		}
		closedir($dir);
	}
	
	/** Create a new backup of the database
	 * @return string the timestamp given to the backup
	 */
	public static function createBackup() {
		global $pn_app_version;
		@mkdir("data/backups");
		@mkdir("data/backups/$pn_app_version");
		$time = "".time();
		$path = "data/backups/$pn_app_version/$time";
		mkdir($path);
		self::createBackupIn($path);
		return $time;
	}

	/** Create a new backup of the database
	 * @param string $path directory where to save the backup
	 * @param array|null $filters_sub_models only backup some sub model instances: associative array with the sub model table as the key, and an array of sub model instance to backup as value.
	 * @param array|null $filter_storage_types only backup the given type of storage
	 * @param array|null $filter_storage_sub_models backup storage only for the specified sub models
	 * @return string the timestamp given to the backup
	 */
	public static function createBackupIn($path, $filters_sub_models = null, $filter_storage_types = false, $filter_storage_sub_models = false) {
		global $local_domain;
		require_once("component/PNApplication.inc");
		if (PNApplication::$instance == null) {
			PNApplication::$instance = new PNApplication();
			PNApplication::$instance->local_domain = $local_domain;
			PNApplication::$instance->current_domain = $local_domain;
			PNApplication::$instance->init();
		}
		$storage_ids = array();
		mkdir($path."/db");
		require_once("component/data_model/Model.inc");
		require_once("SQLQuery.inc");
		foreach (DataModel::get()->internalGetTables(false) as $table) {
			if ($filters_sub_models <> null && isset($filters_sub_models[$table->getName()])) $filter = $filters_sub_models[$table->getName()];
			else $filter = null;
			$ids = self::backupTable($table, $table->getName(), null, $path."/db", $filter);
			if ($filter_storage_types !== false || $filter_storage_sub_models !== false) $storage_ids = array_merge($storage_ids, $ids);
		}
		foreach (DataModel::get()->getSubModels() as $sm) {
			if ($filters_sub_models <> null && array_key_exists($sm->getParentTable(), $filters_sub_models)) $filter = $filters_sub_models[$sm->getParentTable()];
			else $filter = null;
			foreach ($sm->getExistingInstances() as $sub_model) {
				if ($filter <> null && !in_array($sub_model, $filter)) continue;
				foreach ($sm->internalGetTables() as $table) {
					$ids = self::backupTable($table, $table->getSQLNameFor($sub_model), $sub_model, $path."/db");
					if ($filter_storage_sub_models === false || !array_key_exists($sm->getParentTable(), $filter_storage_sub_models) || in_array($sub_model, $filter_storage_sub_models[$sm->getParentTable()])) {
						if ($filter_storage_types !== false) $storage_ids = array_merge($storage_ids, $ids);
					}
				}
			}
		}
		self::zipDirectory(realpath($path)."/db", realpath($path)."/db.zip");
		self::removeDirectory($path."/db");
		set_time_limit(60*60);
		if ($filter_storage_types === false && $filter_storage_sub_models === false) {
			self::zipDirectory(realpath("data/$local_domain/storage"), realpath($path)."/storage.zip", true);
			$f = fopen(realpath($path)."/storage.list","w");
			$db = SQLQuery::getDataBaseAccessWithoutSecurity();
			$res = $db->execute("SELECT `id`,`revision` FROM `Storage` WHERE `expire` IS NULL ORDER BY `id`");
			while (($row = $db->nextRow($res)) <> null) {
				$filesize = filesize(PNApplication::$instance->storage->get_data_path($row["id"]));
				fwrite($f, $row["id"].",".$row["revision"].",$filesize\n");
			}
			fclose($f);
			self::zipFile(realpath($path)."/storage.list",realpath($path)."/storage.list.zip",true);
		} else {
			if ($filter_storage_types !== true && $filter_storage_types !== false) {
				if (count($storage_ids) > 0)
					$storage_ids = SQLQuery::create()->bypassSecurity()->select("Storage")->whereIn("Storage","id",$storage_ids)->whereIn("Storage","type",$filter_storage_types)->field("id")->executeSingleField();
			}
			mkdir($path."/storage");
			set_time_limit(30+count($storage_ids)*2);
			foreach ($storage_ids as $id) {
				$p = storage::get_data_path_in_storage($id);
				$i = strrpos($p, "/");
				mkdir($path."/storage/".substr($p,0,$i), null, true);
				copy(storage::storagePath()."/".$p, $path."/storage/".$p);
			}
			self::zipDirectory(realpath($path."/storage"), realpath($path)."/storage.zip", true);
		}
		if (file_exists("data/$local_domain/custom_tables"))
			self::zipDirectory(realpath("data/$local_domain/custom_tables"), realpath($path)."/custom_tables.zip", true);
		touch($path."/ready");
	}
	
	/** Create a SQL file containing INSERT statements for each existing row in the given table
	 * @param \datamodel\Table $table_spec the table to backup
	 * @param string $table_name SQL name of the table
	 * @param number|null $sub_model the sub model instance
	 * @param string $path directory where to save the content of the table
	 * @param integer[]|null $filter_ids if specified, only the given ids will be backup
	 * @return array list of storage id found
	 */
	private static function backupTable($table_spec, $table_name, $sub_model, $path, $filter_ids = null) {
		$f = fopen($path."/$table_name","w");
		$list = self::exportTable($table_spec, $table_name, $sub_model, $f, $filter_ids);
		fclose($f);
		if (filesize($path."/$table_name") == 0) unlink($path."/$table_name");
		return $list;
	}
	/** Write to a file INSERT statements for each existing row in the given table
	 * @param \datamodel\Table $table_spec the table to backup
	 * @param string $table_name SQL name of the table
	 * @param number|null $sub_model the sub model instance
	 * @param resource $f the file to write to
	 * @param integer[]|null $filter_ids if specified, only the given ids will be backup
	 * @return array list of storage id found
	 */
	public static function exportTable($table_spec, $table_name, $sub_model, $f, $filter_ids = null) {
		$storage_ids = array();
		set_time_limit(600);
		$cols = $table_spec->internalGetColumnsFor($sub_model);
		$cols_storage = array();
		$cols_names = "";
		$first = true;
		foreach ($cols as $col) {
			if ($first) $first = false; else $cols_names .= ",";
			$cols_names .= "`".$col->name."`";
			if ($col instanceof datamodel\ForeignKey)
				if ($col->foreign_table == "Storage")
					array_push($cols_storage, $col->name);
		}
		global $db_config;
		$sql = "SELECT $cols_names FROM `".$db_config["prefix"].PNApplication::$instance->local_domain."`.`$table_name`";
		$db = SQLQuery::getDataBaseAccessWithoutSecurity();
		$res = $db->execute($sql);
		$row = $db->nextRow($res);
		if ($row <> null) {
			$insert_sql = "INSERT INTO `".$table_name."` ($cols_names) VALUES\n";
			$first_row = true;
			$row_count = 0;
			do {
				if ($filter_ids == null || in_array($row[$table_spec->getPrimaryKey()->name], $filter_ids)) {
					if ($first_row) {
						$first_row = false;
						fwrite($f, $insert_sql);
					}
					$line = "";
					if ($row_count > 0) $line .= ",";
					$line .= "(";
					$first_col = true;
					foreach ($cols as $col) {
						if ($first_col) $first_col = false; else $line .= ",";
						$val = $row[$col->name];
						if ($val === null) $line .= "NULL";
						else {
							if ($col instanceof datamodel\ColumnBoolean)
								$line .= "b";
							$line .= "'".$db->escapeString($val)."'";
						}
					}
					$line .= ")\n";
					fwrite($f, $line);
					foreach ($cols_storage as $col_name)
						if ($row[$col_name] <> null) array_push($storage_ids, $row[$col_name]);
					$row_count++;
				}
				$row = $db->nextRow($res);
				if ($row <> null && $row_count >= 1000) {
					fwrite($f, ";\n".$insert_sql);
					$row_count = 0;
				}
			} while ($row <> null);
			if (!$first_row)
				fwrite($f, ";");
		}
		return $storage_ids;
	}
	
	/** Add the content of a directory into a ZIP file
	 * @param string $dir_path the directory to zip
	 * @param string $zip_path the path inside the zip file
	 * @param ZipArchive $zip the zip file
	 * @throws Exception in case the directory does not exist
	 * @return boolean true if the directory was empty, thus nothing has been zipped
	 */
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
	
	/**
	 * Create a ZIP file from the content of a directory
	 * @param string $dir_path the directory to zip
	 * @param string $zip_path the path of the ZIP file to create
	 * @param bool $handle_empty if true, and there is no file in the directory, we will create an entry in the zip file 'empty' (this is because by default, zip to not contain empty directories, thus the ZIP file may be completely empty, and thus not created)
	 * @throws Exception if an error occurs
	 */
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
			exec("/usr/bin/zip \"".$zip_path."\" \"".$dir_path."\"/"."*", $output, $ret);
			if ($ret <> 0 && (!$handle_empty || $ret <> 18))
				throw new Exception("Error creating zip file ".$zip_path." from directory ".$dir_path." (".$ret.")");
		}
	}
	
	public static function zipFile($file, $zipfile, $remove_after) {
		if (class_exists("ZipArchive")) {
			$zip = new ZipArchive();
			if (!$zip->open($zipfile, ZipArchive::CREATE)) throw new Exception("Unable to create zip file: ".$zipfile);
			$zip->addFile($file,basename($file));
			$zip->close();
		} else {
			$output = array();
			$ret = 0;
			exec("/usr/bin/zip \"".$zipfile."\" \"".$file."\"", $output, $ret);
			if ($ret <> 0 && (!$handle_empty || $ret <> 18))
				throw new Exception("Error creating zip file ".$zipfile." from file ".$file." (".$ret.")");
		}
		if ($remove_after) @unlink($file);
	}
	
	/**
	 * Remove a directory with its content
	 * @param string $path the directory to remove
	 */
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
	
	/**
	 * Recover the database from the given backup
	 * @param number $time the timestamp identifying the backup
	 * @param string $version the version of the backup
	 * @param string $backups_path the directory containing the backups
	 * @throws Exception in case of error
	 */
	public static function recoverBackup($time, $version = null, $backups_path = "data/backups") {
		global $local_domain;
		require_once("component/PNApplication.inc");
		require_once("SQLQuery.inc");
		if (PNApplication::$instance == null) {
			PNApplication::$instance = new PNApplication();
			PNApplication::$instance->local_domain = $local_domain;
			PNApplication::$instance->current_domain = $local_domain;
			PNApplication::$instance->init();
		}
		global $pn_app_version;
		if ($version == null) $version = $pn_app_version;
		if (!is_dir("$backups_path/$version/$time")) throw new Exception("Backup does not exist");
		$db = SQLQuery::getDataBaseAccessWithoutSecurity();
		global $db_config;
		self::synchronizeDatabase("$backups_path/$version/$time", $local_domain, $db, $db_config["prefix"].$local_domain);
		$domains = include("conf/domains");
		foreach ($domains as $domain=>$descr)
			if ($domain <> $local_domain)
				self::synchronizeDatabase(null, $domain, $db, $db_config["prefix"].$domain);
		$db->selectDatabase($db_config["prefix"].PNApplication::$instance->current_domain); // make sure following request won't be on the wrong domain
	}
	
	/**
	 * Synchronize a database with the current DataModel, and optionnaly import a backup
	 * @param string|null $directory the directory containing the backup to import, or null
	 * @param string $domain domain identifying which database to synchronize
	 * @param DataBaseSystem $db the database connection to use
	 * @param string $db_name the database to use
	 */
	public static function synchronizeDatabase($directory, $domain, &$db, $db_name) {
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
		
		@mkdir("data/$domain");
		require_once("component/application/service/deploy_utils.inc");
		
		// recover custom tables
		if (file_exists("data/$domain/custom_tables")) self::removeDirectory("data/$domain/custom_tables");
		if ($directory <> null && file_exists("$directory/custom_tables.zip")) {
			mkdir("data/$domain/custom_tables");
			unzipFile(realpath("$directory/custom_tables.zip"), realpath("data/$domain/custom_tables"));
		}
		
		// recover database
		mkdir("data/recover/db");
		if ($directory <> null)
			unzipFile(realpath("$directory/db.zip"), realpath("data/recover/db"));
		require_once("component/data_model/Model.inc");
		require_once("SQLQuery.inc");
		$model = DataModel::get();
		require_once("component/data_model/DataBaseUtilities.inc");
		$res = $db->execute("CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci",false);
		$res = $db->execute("SELECT concat('DROP TABLE IF EXISTS ', table_name, ';') FROM information_schema.tables WHERE table_schema = '$db_name'");
		$db->execute("USE ".$db_name);
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
			if ($table->isCustomized()) $table->forceReloadCustomization(null);
			DataBaseUtilities::createTable($db, $table);
			if (file_exists("data/recover/db/".$table->getName())) {
				set_time_limit(30*60);
				DataBaseUtilities::importSQL($db, "data/recover/db/".$table->getName());
				foreach ($table->_key_submodels as $sm) {
					$res = $db->execute("SELECT `".$table->getPrimaryKey()->name."` FROM `".$table->getName()."`");
					$keys = array();
					while (($row = $db->nextRowArray($res)) <> null)
						array_push($keys, $row[0]);
					$sub_models_keys[$table->getName()] = $keys;
					foreach ($keys as $key) $sm->createTables($key, $db);
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
						if ($table->isCustomized()) $table->forceReloadCustomization($sub_model);
						DataBaseUtilities::createTable($db, $table, "_".$sub_model);
						set_time_limit(30*60);
						DataBaseUtilities::importSQL($db, "data/recover/db/".$table->getName()."_".$sub_model);
					}
				}
			}
		}
		
		// recover storage
		if (file_exists("$directory/storage.zip")) {
			// single file with everything
			if (file_exists("data/$domain/storage")) self::removeDirectory("data/$domain/storage");
			mkdir("data/$domain/storage");
			set_time_limit(60*60);
			if ($directory <> null)
				unzipFile(realpath("$directory/storage.zip"), realpath("data/$domain/storage"));
			if (file_exists("data/$domain/storage/empty")) rmdir("data/$domain/storage/empty");
		} else if (is_dir("$directory/storage")) {
			// diff
			$removed = @file_get_contents("$directory/storage/removed");
			if ($removed <> "" && $removed <> null) {
				$removed = explode(",", $removed);
				foreach ($removed as $id)
					@unlink(storage::getDomainStoragePath($domain)."/".storage::get_data_path_in_storage($id));
			}
			$dir = opendir("$directory/storage/files");
			while (($filename = readdir($dir)) <> null) {
				if (is_dir("$directory/storage/files/$filename")) continue;
				$filepath = storage::getDomainStoragePath($domain)."/".storage::get_data_path_in_storage(intval($filename));
				@unlink($filepath);
				rename("$directory/storage/files/$filename", $filepath);
			}
			closedir($dir);
		} else {
			// error ?
		}
	}
	
	/**
	 * Import a backup, using a specific DataModel version
	 * @param number $time the timestamp identifying the backup
	 * @param string $version the version of the backup
	 * @param string $dm_version the version of the DataModel
	 * @param string $domain the domain in which to import the backup
	 * @return boolean true on success
	 */
	public static function importBackup($time, $version, $dm_version, $domain) {
		// first, we need the datamodel, so we can create sub model tables
		if (!file_exists("data/datamodels")) mkdir("data/datamodels");
		if (!file_exists("data/datamodels/datamodel_".$dm_version.".json")) {
			if (!file_exists("data/datamodels/Students_Management_Software_".$dm_version."_datamodel.zip")) {
				// we need to download it
				require_once("component/application/service/deploy_utils.inc");
				require_once("update_urls.inc");
				download(getUpdateURL("Students_Management_Software_".$dm_version."_datamodel.zip"), "data/datamodels/Students_Management_Software_".$dm_version."_datamodel.zip");
			}
			// extract the datamodel
			set_time_limit(60);
			try {
				@unlink("data/datamodels/datamodel.json");
				$zip = new ZipArchive();
				$zip->open("data/datamodels/Students_Management_Software_".$dm_version."_datamodel.zip");
				$zip->extractTo("data/datamodels", "datamodel.json");
				$zip->close();
				rename("data/datamodels/datamodel.json", "data/datamodels/datamodel_$dm_version.json");
			} catch (Exception $e) {
				@unlink("data/datamodels/Students_Management_Software_".$dm_version."_datamodel.zip");
				PNApplication::error("Invalid datamodel ZIP file, we removed it, please download it again.", $e);
				return false;
			}
		}
		// connect to Database
		set_time_limit(30);
		global $db_config;
		require_once("DataBaseSystem_".$db_config["type"].".inc");
		$db_system_class = "DataBaseSystem_".$db_config["type"];
		/* @var $db_system DataBaseSystem */
		$db_system = new $db_system_class;
		$res = $db_system->connect($db_config["server"], $db_config["user"], $db_config["password"]);
		if ($res <> DataBaseSystem::ERR_OK) {
			switch ($res) {
				case DataBaseSystem::ERR_CANNOT_CONNECT_TO_SERVER: PNApplication::error("Unable to connect to the database server"); break;
				case DataBaseSystem::ERR_INVALID_CREDENTIALS: PNApplication::error("Invalid credentials to connect to the database server"); break;
				default: PNApplication::error("Unknown result when connecting to the database server"); break;
			}
			return false;
		}
		return self::importBackupFrom(realpath("data/backups/$version/$time"), "data/datamodels/datamodel_$dm_version.json", $db_system, $db_config["prefix"].$domain, $domain, realpath("data"));
	}
	/**
	 * Import a backup
	 * @param string $backup_path the directory containing the files of the backup to import
	 * @param string $datamodel_json_path the path of the file containing the datamodel to use for the import 
	 * @param DataBaseSystem $db_system the database connection to user
	 * @param string $db_name the database in which to import the backup
	 * @param string $domain the domain in which the backup is imported
	 * @param string $target the 'data' directory in which to import the backup 
	 * @return boolean true on success
	 */
	public static function importBackupFrom($backup_path, $datamodel_json_path, $db_system, $db_name, $domain, $target) {
		// get the datamodel specification
		$json = file_get_contents($datamodel_json_path);
		if ($json == null) return false;
		$json = json_decode($json, true);
		if ($json == null) {
			PNApplication::error("Invalid DataModel JSON specification");
			return false;
		}
		$spec = $json["result"];
		$model = $spec["model"];
		$db_system->execute("USE ".$db_name);
		// extract Database backup
		set_time_limit(60);
		if (file_exists("$target/recover")) self::removeDirectory("$target/recover");
		mkdir("$target/recover");
		mkdir("$target/recover/db");
		require_once("component/application/service/deploy_utils.inc");
		unzipFile("$backup_path/db.zip", realpath("$target/recover/db"));
		// import tables on root model
		require_once 'component/data_model/DataBaseUtilities.inc';
		foreach ($model["tables"] as $table) {
			if (file_exists("$target/recover/db/".$table["name"])) {
				DataBaseUtilities::importSQL($db_system, "$target/recover/db/".$table["name"]);
			}
		}
		// import sub models
		foreach ($model["sub_models"] as $sm) {
			$sm_table_name = $sm["parent_table"];
			$sm_table_spec = null;
			foreach ($model["tables"] as $table) if ($table["name"] == $sm_table_name) { $sm_table_spec = $table; break; }
			if ($sm_table_spec == null) {
				PNApplication::error("Invalid sub-model parent table: ".$sm_table_name." does not exist");
				return false;
			}
			$pk = @$sm_table_spec["key"];
			if ($pk == null) {
				PNApplication::error("Invalid sub-model: parent table $sm_table_name does not have a primary key");
				return false;
			}
			$res = $db_system->execute("SELECT `$pk` FROM `$sm_table_name`");
			if ($res)
				while (($row = $db_system->nextRowArray($res)) <> null) {
					$sm_instance = $row[0];
					foreach ($sm["tables"] as $table) {
						if (file_exists("$target/recover/db/".$table["name"]."_".$sm_instance)) {
							DataBaseUtilities::importSQL($db_system, "$target/recover/db/".$table["name"]."_".$sm_instance);
						}
					}
				}
		}
		
		@mkdir("$target/$domain");
		// recover custom tables
		if (file_exists("$target/$domain/custom_tables")) self::removeDirectory("$target/$domain/custom_tables");
		if (file_exists("$backup_path/custom_tables.zip")) {
			mkdir("$target/$domain/custom_tables");
			unzipFile("$backup_path/custom_tables.zip", realpath("$target/$domain/custom_tables"));
		}
		// recover storage
		if (file_exists("$target/$domain/storage")) self::removeDirectory("$target/$domain/storage");
		mkdir("$target/$domain/storage");
		set_time_limit(60*60);
		unzipFile("$backup_path/storage.zip", realpath("$target/$domain/storage"));
		if (file_exists("$target/$domain/storage/empty")) rmdir("$target/$domain/storage/empty");
		return true;
	}
	
}
?>