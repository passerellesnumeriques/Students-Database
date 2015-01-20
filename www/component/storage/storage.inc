<?php
/**
 * Component storing files
 */ 
class storage extends Component {
	
	public function getPluginImplementations($interface_name) {
		switch ($interface_name) {
			case "CronPlugin":
				require_once("CronPlugin.inc");
				return array(
					new StorageCronPlugin()
				);
		}
		return array();
	}
	
	/**
	 * Get the error message for the given upload
	 * @param array $file uploaded file
	 * @return string error message
	 */
	public function getUploadError($file) {
		$error = @$file['error'];
		$msg = $error.": ";
		switch ($error) {
			case 1: 
				$msg .= "The file is too big (maximum is ".ini_get("upload_max_filesize").")";
				$msg .= ")";
				if (isset($file['size']) && $file['size'] > 0) {
					$msg .= ": ";
					if ($file['size'] >= 1024*1024) $msg .= intval($file['size']/(1024*1204))."MB";
					else if ($file['size'] >= 1024) $msg .= intval($file['size']/1024)."KB";
					else $msg .= $file['size']." bytes";
				}
				break;
			case 2: 
				$msg .= "The file is too big"; 
				if (isset($file['size']) && $file['size'] > 0) {
					$msg .= ": ";
					if ($file['size'] >= 1024*1024) $msg .= intval($file['size']/(1024*1204))."MB";
					else if ($file['size'] >= 1024) $msg .= intval($file['size']/1024)."KB";
					else $msg .= $file['size']." bytes";
				}
				break;
			case 3: $msg .= "The transfer was interrupted and only a part of the file was received. Please try again."; break;
			case 4: $msg .= "No file received"; break;
			default: $msg .= "Unknown error"; break;
		}
		return $msg;
	}
	
	/**
	 * Remove all stored files on the file system
	 * @param string $domain domain to reset
	 */
	public static function resetStorage($domain) {
		$path = self::getDomainStoragePath($domain);
		if (file_exists($path))
			self::removeDirectory($path);
		if (!@mkdir($path,null,true)) {
			sleep(1);
			if (!@mkdir($path,null,true)) {
				sleep(1);
				if (!@mkdir($path,null,true)) {
					sleep(1);
					if (!file_exists($path) && !mkdir($path,null,true)) {
						PNApplication::error("Unable to create storage directory ".$path);
						return;
					}
				}
			}
		}
	}
	/**
	 * Remove the given directory with its content
	 * @param string $path directory to remove
	 */
	private static function removeDirectory($path) {
		$dir = opendir($path);
		while (($filename = readdir($dir)) <> null) {
			if ($filename == ".") continue;
			if ($filename == "..") continue;
			if (is_dir($path."/".$filename))
				self::removeDirectory($path."/".$filename);
			else
				@unlink($path."/".$filename);
		}
		closedir($dir);
		@rmdir($path);
	}
	
	/**
	 * Return the location in the file system where files are stored for the given domain
	 * @param string $domain the domain
	 * @return string the path
	 */
	public static function getDomainStoragePath($domain) {
		// In production, this is integrated in PNApplication.inc, so directory is different
#DEV
		$path = realpath(dirname(__FILE__)."/../../data");
#END
#PROD
#		$path = realpath(dirname(__FILE__)."/../data");
#END
		return $path."/$domain/storage";
	}
	
	/**
	 * Return the path for the current domain
	 * @return string the path
	 */
	public static function storagePath() {
		return self::getDomainStoragePath(PNApplication::$instance->current_domain);
	}
	
	/**
	 * Remove temporary files which already expired
	 */
	public function removeExpired() {
		$rows = SQLQuery::create()->bypassSecurity()->select("Storage")->where("`expire` IS NOT NULL AND `expire` < ".time())->execute();
		SQLQuery::create()->bypassSecurity()->removeRows("Storage", $rows);
		foreach ($rows as $row)
			@unlink($this->get_data_path($row["id"]));
	}
	
	/**
	 * Remove files which do not have any reference in the database anymore
	 */
	public function cleanupStorage() {
		$path = self::getDomainStoragePath(PNApplication::$instance->local_domain);
		if (!file_exists($path)) return;
		$this->cleanupDirectory($path, 0, 1, false);
	}
	/**
	 * Recursive function to remove unused files
	 * @param string $path path
	 * @param integer $current_id current id based on the path
	 * @param integer $id_mul id multiplier for the path
	 * @param boolean $remove_if_empty indicates if the directory should be removed if empty
	 */
	private function cleanupDirectory($path, $current_id, $id_mul, $remove_if_empty) {
		$files = array();
		$dir = opendir($path);
		$ids_founds = array();
		$ids_files = array();
		$has_files = false;
		while (($filename = readdir($dir)) <> null) {
			if ($filename == ".") continue;
			if ($filename == "..") continue;
			if (!ctype_digit($filename)) {
				// everything must be numbers
				if (is_dir($path."/".$filename))
					self::removeDirectory($path."/".$filename);
				else
					@unlink($path."/".$filename);
				continue;
			}
			$id = intval($filename);
			$id = $current_id+$id*$id_mul;
			if (is_dir($path."/".$filename)) {
				if ($id_mul >= 1000000) {
					// we should not have a directory here !!
					self::removeDirectory($path."/".$filename);
					continue;
				}
				$this->cleanupDirectory($path."/".$filename, $id, $id_mul*100, true);
				if (file_exists($path."/".$filename)) $has_files = true;
			} else {
				if ($id_mul <> 1000000)
					@unlink($path."/".$filename); // we should not have files here!
				array_push($ids_founds, $id);
				array_push($ids_files, $filename);
			}
		}
		closedir($dir);
		if (count($ids_founds) > 0) {
			$rows = SQLQuery::create()->bypassSecurity()->select("Storage")->whereIn("Storage","id",$ids_founds)->field("id")->executeSingleField();
			if (PNApplication::hasErrors()) return; // do not continue, to make sure we won't remove files which we should not
			foreach ($rows as $row) {
				for ($i = 0; $i < count($ids_founds); $i++) {
					if ($ids_founds[$i] == $row) {
						// it exists in database
						$has_files = true;
						array_splice($ids_founds, $i, 1);
						array_splice($ids_files, $i, 1);
						break;
					}
				}
			}
			if (count($ids_founds) > 0) {
				// files are not anymore in database !
				foreach ($ids_files as $filename)
					@unlink($path."/".$filename);
			}
		}
		if (!$has_files && $remove_if_empty)
			@rmdir($path);
	}
	
	/**
	 * Check the current user has the right to access to the given file
	 * @param array $file file
	 * @return boolean true if access is allowed
	 */
	public function canReadFile($file) {
		if ($file["type"] == "temp") return true;
		require_once("StoragePlugin.inc");
		foreach (PNApplication::$instance->components as $c)
			foreach ($c->getPluginImplementations("StoragePlugin") as $pi) {
				if ($pi->getType() <> $file["type"]) continue;
				return $pi->canRead($file["id"]);
			}
		return false;
	}
	/**
	 * Check the current user has the right to modify the given file
	 * @param array $file file
	 * @return boolean true if access is allowed
	 */
	public function canWriteFile($file) {
		if ($file["type"] == "temp") return true;
		require_once("StoragePlugin.inc");
		foreach (PNApplication::$instance->components as $c)
			foreach ($c->getPluginImplementations("StoragePlugin") as $pi) {
			if ($pi->getType() <> $file["type"]) continue;
			return $pi->canWrite($file["id"]);
		}
		return false;
	}
	
	/**
	 * Allocate a new ID for a new file, and store information in the database
	 * @param string $type type of file (Plugin)
	 * @param string $mime mime type of the file
	 * @param integer|null $expire expiration timestamp, or null
	 * @return NULL|integer new ID, or null in case of error
	 */
	private function allocateID($type, $mime = null, $expire = null) {
#DEV
		$this->removeExpired();
#END
		$q = "INSERT INTO `Storage` (`type`,`mime`,`expire`,`revision`) VALUE (";
		$q .= "'".SQLQuery::escape($type)."',";
		$q .= ($mime <> null ? "'".SQLQuery::escape($mime)."'" : "NULL").",";
		if ($expire <> null)
			$q .= $expire;
		else
			$q .= "NULL";
		$q .= ",1";
		$q .= ")";
		SQLQuery::getDataBaseAccessWithoutSecurity()->execute($q);
		$id = SQLQuery::getDataBaseAccessWithoutSecurity()->getInsertID();
		$dir1 = $id%100;
		$dir2 = ($id/100)%100;
		$dir3 = ($id/10000)%100;
		$filename = intval($id/1000000);
		$path = storage::storagePath();
		if (!file_exists($path."/".$dir1)) {
			if (!mkdir($path."/".$dir1)) {
				PNApplication::error("Unable to create directory ".$path."/".$dir1);
				SQLQuery::getDataBaseAccessWithoutSecurity()->execute("DELETE FROM `Storage` WHERE `id`=".$id);
				return null;
			}
		}
		$path = $path."/".$dir1;
		if (!file_exists($path."/".$dir2)) {
			if (!mkdir($path."/".$dir2)) {
				PNApplication::error("Unable to create directory ".$path."/".$dir2);
				SQLQuery::getDataBaseAccessWithoutSecurity()->execute("DELETE FROM `Storage` WHERE `id`=".$id);
				return null;
			}
		}
		$path = $path."/".$dir2;
		if (!file_exists($path."/".$dir3)) {
			if (!mkdir($path."/".$dir3)) {
				PNApplication::error("Unable to create directory ".$path."/".$dir3);
				SQLQuery::getDataBaseAccessWithoutSecurity()->execute("DELETE FROM `Storage` WHERE `id`=".$id);
				return null;
			}
		}
		$path = $path."/".$dir3."/".$filename;
		if (file_exists($path)) {
			if (!unlink($path)) {
				PNApplication::error("File ".$path." already exists, but we cannot remove it");
				SQLQuery::getDataBaseAccessWithoutSecurity()->execute("DELETE FROM `Storage` WHERE `id`=".$id);
				return null;
			}
		}
		return $id;
	}
	public function store_data($type, $data, $mime = null, $expire = null) {
		$id = $this->allocateID($type, $mime, $expire);
		$path = $this->get_data_path($id);
		$f = @fopen($path, "w");
		if ($f == null) {
			PNApplication::error("Unable to create file ".$path);
			SQLQuery::getDataBaseAccessWithoutSecurity()->execute("DELETE FROM `Storage` WHERE `id`=".$id);
			return null;
		}
		fwrite($f, $data);
		fclose($f);
		return $id;
	}
	
	public static function get_data_path_in_storage($id) {
		$dir1 = $id%100;
		$dir2 = ($id/100)%100;
		$dir3 = ($id/10000)%100;
		$filename = intval($id/1000000);
		return $dir1."/".$dir2."/".$dir3."/".$filename;
	}
	
	function get_data_path($id) {
		return self::storagePath()."/".self::get_data_path_in_storage($id);
	}
	
	function new_revision($id) {
		SQLQuery::getDataBaseAccessWithoutSecurity()->execute("UPDATE `Storage` SET `revision`=`revision`+1 WHERE `id`=".$id);
	}
	function update_file($id, $data) {
		$path = $this->get_data_path($id);
		$f = fopen($path, "w");
		fwrite($f, $data);
		fclose($f);
		$this->new_revision($id);
	}
	
	function set_expire($id, $new_expire) {
		SQLQuery::getDataBaseAccessWithoutSecurity()->execute("UPDATE `Storage` SET `expire`=".($new_expire == null ? "NULL" : $new_expire)." WHERE `id`=".$id);
	}
	function set_expires($ids, $new_expire) {
		if (count($ids) == 0) return;
		$q = "UPDATE `Storage` SET `expire`=".($new_expire == null ? "NULL" : $new_expire)." WHERE (";
		for ($i = 0; $i < count($ids); $i++) {
			if ($i > 0) $q .= " OR ";
			$q .= "`id`=".$ids[$i];
		}
		$q .= ")";
		SQLQuery::getDataBaseAccessWithoutSecurity()->execute($q);
	}
	
	function set_mime($id, $mime_type) {
		SQLQuery::getDataBaseAccessWithoutSecurity()->execute("UPDATE `Storage` SET `mime`='".SQLQuery::escape($mime_type)."' WHERE `id`=".$id);
	}
	
	function remove_data($id) {
		$path = $this->get_data_path($id);
		unlink($path);
		SQLQuery::create()->bypassSecurity()->removeKey("Storage", $id);
	}
	
	function joinRevision(&$q, $join_from, $join_key, $revision_alias = null) {
		$q->join($join_from, "Storage", array($join_key=>"id"));
		if ($revision_alias <> null)
			$q->field("Storage", "revision", $revision_alias);
	}
	function getRevision($id) {
		return SQLQuery::create()->bypassSecurity()->select("Storage")->whereValue("Storage","id",$id)->field("revision")->executeSingleValue();
	}
	function getFileType($id) {
		return SQLQuery::create()->bypassSecurity()->select("Storage")->whereValue("Storage","id",$id)->field("type")->executeSingleValue();
	}
	
	function convertTempFile($id, $type) {
		SQLQuery::create()->bypassSecurity()->updateByKey("Storage", $id, array("type"=>$type,"expire"=>null));
	}
	
	function storeTempFile($path, $expire) {
		$id = $this->allocateID("temp", null, $expire);
		$store_path = $this->get_data_path($id);
		copy($path, $store_path);
		return $id;
	}
	
	/** return true if this is the final call (either form sent, or no file uploaded), or false if this is an asynchronous upload */
	function receive_upload(&$ids, &$names, &$types, &$sizes, $expire_seconds) {
		$headers = getallheaders();
		$fn = (isset($headers['X_FILENAME']) ? $headers['X_FILENAME'] : false);
		if ($fn) {
			// AJAX call
			$data = file_get_contents('php://input');
			$id = $this->store_data("temp",$data, isset($headers['X_FILETYPE']) ? $headers['X_FILETYPE'] : null, time()+$expire_seconds);
			if ($id == null) return true;
			array_push($ids, $id);
			array_push($names, $fn);
			array_push($types, isset($headers['X_FILETYPE']) ? $headers['X_FILETYPE'] : "");
			array_push($sizes, $headers['X_FILESIZE']);
			return false;
		} else {
			// form submit
			if (isset($_FILES["storage_upload"]) && count($_FILES["storage_upload"]) > 0) {
				$files = $_FILES['storage_upload'];
				if (!is_array($files['error'])) {
					$files['error'] = array($files['error']);
					$files['name'] = array($files['name']);
					$files['tmp_name'] = array($files['tmp_name']);
				}
				foreach ($files['error'] as $file_id => $err) {
					if ($err == UPLOAD_ERR_OK) {
						$fn = $files['name'][$file_id];
						$id = $this->allocateID("temp", $files['type'][$file_id], time()+$expire_seconds);
						$path = $this->get_data_path($id);
						if (!move_uploaded_file($files['tmp_name'][$file_id], $path)) {
							PNApplication::error("Unable to create file ".$path);
							SQLQuery::getDataBaseAccessWithoutSecurity()->execute("DELETE FROM `Storage` WHERE `id`=".$id);
						} else {
							array_push($ids, $id);
							array_push($names, $fn);
							array_push($types, $files['type'][$file_id]);
							$size = $files['size'][$file_id];
							if ($size == null) $size = filesize($path);
							array_push($sizes, $size);
						}
					} else
						PNApplication::error("File ".$files['name'][$file_id].": error ".$files['error'][$file_id]);
				}
			}
		}
		return true;
	}

	function custom_upload($type, $expire_seconds) {
		$data = file_get_contents('php://input');
		$id = $this->store_data("temp",$data,$type,$expire_seconds);
		return $id;
	}
	
}
?>