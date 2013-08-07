<?php
class DataBaseLock {

	public static function clean() {
		$db = SQLQuery::get_db_system_without_security();
		$db->execute("LOCK TABLES DataLocks WRITE, DataLocksFields WRITE");
		$db->execute("DELETE FROM DataLocks WHERE timestamp <= ".(time()-5*60));
		$db->execute("DELETE FROM DataLocksFields WHERE timestamp <= ".(time()-5*60));
		$db->execute("UNLOCK TABLES");
	}

	public static function lock($table, $fields, &$locked_by, $generate_script = true) {
		DataBaseLock::clean();
		$db = SQLQuery::get_db_system_without_security();
		$db->execute("LOCK TABLES DataLocks WRITE, DataLocksFields WRITE");
		$id = DataBaseLock::_lock($table, $fields, $locked_by, $generate_script);
		$db->execute("UNLOCK TABLES");
		return $id;
	}
	private static function _lock($table, $fields, &$locked_by, $generate_script) {
		$db = SQLQuery::get_db_system_without_security();
		$res = $db->execute("SELECT * FROM DataLocks WHERE locked_table='".SQLQuery::escape($table)."'");
		if ($res <> null) {
			$where = "";
			if ($fields <> null)
				foreach ($fields as $name=>$value) {
					if ($where <> "") $where .= " OR ";
					$where .= "(`field`='".SQLQuery::escape($name)."' AND `value`='".SQLQuery::escape($value).")";
				}
			while (($row = $db->next_row($res)) <> null) {
				if ($row["locker_domain"] <> PNApplication::$instance->user_management->domain ||
					$row["locker_username"] <> PNApplication::$instance->user_management->username) {
					if ($fields == null) {
						// we want to lock the full table -> not possible
						$locked_by = $row["locker_domain"]."\\".$row["locker_username"];
						return null;
					}
					if ($row["full_table"] == 1) {
						$locked_by = $row["locker_domain"]."\\".$row["locker_username"];
						return null;
					}
					$lock_id = $row["id"];
					$res2 = $db->execute("SELECT * FROM DataLocksFields WHERE lock_id=".$lock_id." AND ".$where);
					if ($res2 <> null && $db->next_row($res2) <> null) {
						$locked_by = $row["locker_domain"]."\\".$row["locker_username"];
						return null;
					}
				}
			}
		}
		$ts = time();
		$db->execute(
			"INSERT INTO DataLocks (`timestamp`,`locker_domain`,`locker_username`,`locked_table`,`full_table`)".
			" VALUE (".
			$ts.
			",'".SQLQuery::escape(PNApplication::$instance->user_management->domain)."'".
			",'".SQLQuery::escape(PNApplication::$instance->user_management->username)."'".
			",'".SQLQuery::escape($table)."'".
			",".($fields == null ? 1 : 0).
			")"
		);
		$id = $db->get_insert_id();
		if ($fields <> null) {
			$sql = "INSERT INTO DataLocksFields (`lock_id`,`timestamp`,`field`,`value`) VALUES ";
			$first = true;
			foreach ($fields as $name=>$value) {
				if ($first) $first = false; else $sql .= ",";
				$sql .= "(";
				$sql .= $id.",".$ts;
				$sql .= ",'".SQLQuery::escape($name)."'";
				$sql .= ",'".SQLQuery::escape($value)."'";
				$sql .= ")";
			}
			$db->execute($sql);
		}
		if ($generate_script)
			DataBaseLock::generate_script($id);
		return $id;
	}

	public static function unlock($id) {
		$result = null;
		$db = SQLQuery::get_db_system_without_security();
		$db->execute("LOCK TABLES DataLocks WRITE, DataLocksFields WRITE");
		$r = $db->execute("SELECT locker_domain,locker_username FROM DataLocks WHERE id=".$id);
		if ($r) {
			$row = $db->next_row($r);
			if ($row) {
				if ($row["locker_domain"] == PNApplication::$instance->user_management->domain &&
					$row["locker_username"] == PNApplication::$instance->user_management->username) {
					$db->execute("DELETE FROM DataLocks WHERE id=".$id);
					$db->execute("DELETE FROM DataLocksFields WHERE lock_id=".$id);
				} else
					$result = "You are not the owner of this lock";
			}
		}
		$db->execute("UNLOCK TABLES");
		return $result;
	}

	public static function update($id) {
		$db = SQLQuery::get_db_system_without_security();
		$result = null;
		$ts = time();
		$db->execute("LOCK TABLES DataLocks WRITE, DataLocksFields WRITE");
		$r = $db->execute("SELECT locker_domain,locker_username FROM DataLocks WHERE id=".$id);
		if ($r) {
			$row = $db->next_row($r);
			if ($row) {
				if ($row["locker_domain"] == PNApplication::$instance->user_management->domain &&
					$row["locker_username"] == PNApplication::$instance->user_management->username) {
					$db->execute("UPDATE DataLocks SET timestamp=".$ts." WHERE id=".$id);
					$db->execute("UPDATE DataLocksFields SET timestamp=".$ts." WHERE lock_id=".$id);
				} else
					$result = "You are not the owner of this lock";
			} else
				$result = "This lock does not exist";
		} else
			$result = "This lock does not exist";
		$db->execute("UNLOCK TABLES");
		DataBaseLock::clean();
		return $result;
	}

	public static function check($id, $table, $fields) {
		$db = SQLQuery::get_db_system_without_security();
		$ts = time();
		$db->execute("LOCK TABLES DataLocks WRITE, DataLocksFields WRITE");
		$r = $db->execute("SELECT * FROM DataLocks WHERE id=".$id);
		// check if it exist
		if (!$r) { $db->execute("UNLOCK TABLES"); return false; }
		$r = $db->next_row($r);
		if (!$r) { $db->execute("UNLOCK TABLES"); return false; }
		// check the locker is the current user
		if ($r["locker_domain"] <> PNApplication::$instance->user_management->domain ||
			$r["locker_username"] <> PNApplication::$instance->user_management->username) {
			$db->execute("UNLOCK TABLES");
			return false;
		}
		// check the table
		if ($r["locked_table"] <> $table) { $db->execute("UNLOCK TABLES"); return false; }
		// check the fields
		if (!$r["full_table"]) {
			$res = $db->execute("SELECT * FROM DataLocksFields WHERE lock_id=".$id);
			$remaining = array_merge($fields);
			while (($r = $db->next_row($res)) <> null) {
				if (!isset($remaining[$r["field"]])) continue; // strange, but acceptable
				if ($remaining[$r["field"]] <> $r["value"]) {
					$db->execute("UNLOCK TABLES");
					return false;
				}
				unset($remaining[$r["field"]]);
			}
			if (count($remaining) > 0) {
				$db->execute("UNLOCK TABLES");
				return false;
			}
		}
		// ok, let's update the timestamps
		$db->execute("UPDATE DataLocks SET timestamp=".$ts." WHERE id=".$id);
		$db->execute("UPDATE DataLocksFields SET timestamp=".$ts." WHERE lock_id=".$id);
		$db->execute("UNLOCK TABLES");
		return true;
	}

	public static function has_lock($table, $field = null, $value = null) {
		$db = SQLQuery::get_db_system_without_security();
		$db->execute("LOCK TABLES DataLocks WRITE, DataLocksFields WRITE");
		$res = $db->execute(
			"SELECT * FROM DataLocks WHERE ".
			"locker_domain='".SQLQuery::escape(PNApplication::$instance->user_management->domain)."'".
			" AND locker_username='".SQLQuery::escape(PNApplication::$instance->user_management->username)."'".
			" AND locked_table='".SQLQuery::escape($table)."'");
		// check if it exist
		if (!$res) { $db->execute("UNLOCK TABLES"); return false; }
		while (($r =$db->next_row($res)) <> null) {
			if (!$r) { $db->execute("UNLOCK TABLES"); return false; }
			// if all table is locked, this is ok
			if ($r["full_table"] == 1) { $db->execute("UNLOCK TABLES"); return true; }
			// else, we need to check the field
			if ($field == null) break; // we need the full table
			$lock_id = $r["id"];
			$res2 = $db->execute("SELECT `value` FROM DataLocksFields WHERE lock_id=".$lock_id." AND field='".SQLQuery::escape($field)."'");
			if (!$res2) continue;
			$res2 = $db->next_row($res2);
			if ($res2 === null) continue;
			// field found
			if ($value !== null && $value <> $res2['value']) continue; // value do not match
			$db->execute("UNLOCK TABLES");
			return true;
		}
		$db->execute("UNLOCK TABLES");
		return true;
	}

	public static function is_locked_by_someone_else($table, &$locker) {
		$db = SQLQuery::get_db_system_without_security();
		$db->execute("LOCK TABLES DataLocks WRITE, DataLocksFields WRITE");
		$res = $db->execute(
			"SELECT * FROM DataLocks WHERE ".
			" AND locked_table='".$db->escape_string($table)."'");
		// check if it exist
		if (!$res) { $db->execute("UNLOCK TABLES"); return false; }
		while (($r = $db->next_row($res)) <> null) {
			if ($r["locker_domain"] <> PNApplication::$instance->user_management->domain ||
				$r["locker_username"] <> PNApplication::$instance->user_management->username) {
			$locker = $r["locker_domain"]."\\".$r["locker_username"];
			$db->execute("UNLOCK TABLES");
			return true;
		}
		}
		$db->execute("UNLOCK TABLES");
		return false;
	}

	private static function generate_script($id) {
?>
<script type='text/javascript'>
if (window.database_locks)
	window.database_locks.add_lock(<?php echo $id?>);
else {
	var f = function() {
		if (window.database_locks)
			window.database_locks.add_lock(<?php echo $id?>);
		else
			setTimeout(f,100);
	};
	setTimeout(f,10);
}
</script>
<?php
	}

}
?>