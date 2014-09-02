<?php 
class storage extends Component {
	
	public function get_upload_error($file) {
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
	
	public static function reset_storage($domain) {
		$path = realpath(dirname($_SERVER["SCRIPT_FILENAME"]))."/data/".$domain;
		if (file_exists($path))
			self::remove_directory($path);
		if (!mkdir($path))
			PNApplication::error("Unable to create storage directory "+$path);
		if (!mkdir($path."/storage"))
			PNApplication::error("Unable to create storage directory "+$path);
	}
	private static function remove_directory($path) {
		$dir = opendir($path);
		while (($filename = readdir($dir)) <> null) {
			if ($filename == ".") continue;
			if ($filename == "..") continue;
			if (is_dir($path."/".$filename))
				self::remove_directory($path."/".$filename);
			else
				unlink($path."/".$filename);
		}
		closedir($dir);
		rmdir($path);
	}
	
	private static function storage_path() {
		return realpath(dirname($_SERVER["SCRIPT_FILENAME"])."/data/".PNApplication::$instance->current_domain."/storage");
	}
	
	private function remove_old() {
		SQLQuery::getDataBaseAccessWithoutSecurity()->execute("DELETE FROM `Storage` WHERE `expire` IS NOT NULL AND `expire` < ".time());
	}
	
	public function canReadFile($file) {
		if ($file["type"] == "temp") return true;
		require_once("StoragePlugin.inc");
		foreach (PNApplication::$instance->components as $c)
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof StoragePlugin)) continue;
				if ($pi->getType() <> $file["type"]) continue;
				return $pi->canRead($file["id"]);
			}
		return false;
	}
	public function canWriteFile($file) {
		if ($file["type"] == "temp") return true;
		require_once("StoragePlugin.inc");
		foreach (PNApplication::$instance->components as $c)
			foreach ($c->getPluginImplementations() as $pi) {
			if (!($pi instanceof StoragePlugin)) continue;
			if ($pi->getType() <> $file["type"]) continue;
			return $pi->canWrite($file["id"]);
		}
		return false;
	}
	
	private function allocate_id($type, $mime = null, $expire = null) {
		$this->remove_old();
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
		$path = storage::storage_path();
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
		$id = $this->allocate_id($type, $mime, $expire);
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
	
	function get_data_path($id) {
		$dir1 = $id%100;
		$dir2 = ($id/100)%100;
		$dir3 = ($id/10000)%100;
		$filename = intval($id/1000000);
		$path = storage::storage_path()."/".$dir1."/".$dir2."/".$dir3."/".$filename;
		return $path;
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
		SQLQuery::getDataBaseAccessWithoutSecurity()->execute("DELETE FROM `Storage` WHERE `id`=".$id);
	}
	
	function joinRevision(&$q, $join_from, $join_key, $revision_alias = null) {
		$q->join($join_from, "Storage", array($join_key=>"id"));
		if ($revision_alias <> null)
			$q->field("Storage", "revision", $revision_alias);
	}
	function getFileType($id) {
		return SQLQuery::create()->bypassSecurity()->select("Storage")->whereValue("Storage","id",$id)->field("type")->executeSingleField();
	}
	
	function convertTempFile($id, $type) {
		SQLQuery::create()->bypassSecurity()->updateByKey("Storage", $id, array("type"=>$type,"expire"=>null));
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
						$id = $this->allocate_id("temp", $files['type'][$file_id], time()+$expire_seconds);
						$path = $this->get_data_path($id);
						if (!move_uploaded_file($files['tmp_name'][$file_id], $path)) {
							PNApplication::error("Unable to create file ".$path);
							SQLQuery::getDataBaseAccessWithoutSecurity()->execute("DELETE FROM `Storage` WHERE `id`=".$id);
						} else {
							array_push($ids, $id);
							array_push($names, $fn);
							array_push($types, $files['type'][$file_id]);
							array_push($sizes, $files['size'][$file_id]);
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