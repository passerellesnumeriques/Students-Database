<?php 
class storage extends Component {
	
	private static function storage_path() {
		return get_cfg_var("pn.storage");
	}
	
	private function remove_old() {
		SQLQuery::get_db_system_without_security()->execute("DELETE FROM `Storage` WHERE `expire` IS NOT NULL AND `expire` < ".time());
	}
	
	private function allocate_id($mime = null, $expire = null) {
		$this->remove_old();
		$q = "INSERT INTO `Storage` (`mime`,`expire`,`username`) VALUE (";
		$q .= ($mime <> null ? "'".SQLQuery::escape($mime)."'" : "NULL").",";
		if ($expire <> null)
			$q .= $expire.",'".SQLQuery::escape(PNApplication::$instance->user_management->username)."'";
		else
			$q .= "NULL,NULL";
		$q .= ")";
		SQLQuery::get_db_system_without_security()->execute($q);
		$id = SQLQuery::get_db_system_without_security()->get_insert_id();
		$dir1 = $id%100;
		$dir2 = ($id/100)%100;
		$dir3 = ($id/10000)%100;
		$filename = intval($id/1000000);
		$path = storage::storage_path();
		if (!file_exists($path."/".$dir1)) {
			if (!mkdir($path."/".$dir1)) {
				PNApplication::error("Unable to create directory ".$path."/".$dir1);
				SQLQuery::get_db_system_without_security()->execute("DELETE FROM `Storage` WHERE `id`=".$id);
				return null;
			}
		}
		$path = $path."/".$dir1;
		if (!file_exists($path."/".$dir2)) {
			if (!mkdir($path."/".$dir2)) {
				PNApplication::error("Unable to create directory ".$path."/".$dir2);
				SQLQuery::get_db_system_without_security()->execute("DELETE FROM `Storage` WHERE `id`=".$id);
				return null;
			}
		}
		$path = $path."/".$dir2;
		if (!file_exists($path."/".$dir3)) {
			if (!mkdir($path."/".$dir3)) {
				PNApplication::error("Unable to create directory ".$path."/".$dir3);
				SQLQuery::get_db_system_without_security()->execute("DELETE FROM `Storage` WHERE `id`=".$id);
				return null;
			}
		}
		$path = $path."/".$dir3."/".$filename;
		if (file_exists($path)) {
			if (!unlink($path)) {
				PNApplication::error("File ".$path." already exists, but we cannot remove it");
				SQLQuery::get_db_system_without_security()->execute("DELETE FROM `Storage` WHERE `id`=".$id);
				return null;
			}
		}
		return $id;
	}
	public function store_data($data, $mime = null, $expire = null) {
		$id = $this->allocate_id($mime, $expire);
		$path = $this->get_data_path($id);
		$f = @fopen($path, "w");
		if ($f == null) {
			PNApplication::error("Unable to create file ".$path);
			SQLQuery::get_db_system_without_security()->execute("DELETE FROM `Storage` WHERE `id`=".$id);
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
	
	function get_data($id, $new_expire = null) {
		$path = $this->get_data_path($id);
		if (!file_exists($path)) return null;
		$data = file_get_contents($path);
		if ($data === false) return null;
		if ($new_expire <> null)
			SQLQuery::get_db_system_without_security()->execute("UPDATE `Storage` SET `expire`=".$new_expire." WHERE `id`=".$id);
		return $data;
	}

	function set_expire($id, $new_expire) {
		SQLQuery::get_db_system_without_security()->execute("UPDATE `Storage` SET `expire`=".($new_expire == null ? "NULL" : $new_expire)." WHERE `id`=".$id);
	}
	function set_expires($ids, $new_expire) {
		if (count($ids) == 0) return;
		$q = "UPDATE `Storage` SET `expire`=".($new_expire == null ? "NULL" : $new_expire)." WHERE (";
		for ($i = 0; $i < count($ids); $i++) {
			if ($i > 0) $q .= " OR ";
			$q .= "`id`=".$ids[$i];
		}
		$q .= ")";
		SQLQuery::get_db_system_without_security()->execute($q);
	}
	
	function remove_data($id) {
		$path = $this->get_data_path($id);
		unlink($path);
		SQLQuery::get_db_system_without_security()->execute("DELETE FROM `Storage` WHERE `id`=".$id);
	}
	
	/** return true if this is the final call (either form sent, or no file uploaded), or false if this is an asynchronous upload */
	function receive_upload(&$ids, &$names, &$types, &$sizes, $expire_seconds) {
		$fn = (isset($_SERVER['HTTP_X_FILENAME']) ? $_SERVER['HTTP_X_FILENAME'] : false);
		if ($fn) {
			// AJAX call
			$data = file_get_contents('php://input');
			$id = $this->store_data($data, null, time()+$expire_seconds);
			if ($id == null) return true;
			array_push($ids, $id);
			array_push($names, $fn);
			array_push($types, $_SERVER['HTTP_X_FILETYPE']);
			array_push($sizes, $_SERVER['HTTP_X_FILESIZE']);
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
						$id = $this->allocate_id(null, time()+$expire_seconds);
						$path = $this->get_data_path($id);
						if (!move_uploaded_file($files['tmp_name'][$file_id], $path)) {
							PNApplication::error("Unable to create file ".$path);
							SQLQuery::get_db_system_without_security()->execute("DELETE FROM `Storage` WHERE `id`=".$id);
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
}
?>