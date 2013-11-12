<?php
class DataBaseLock {

	/** lock a table */
	public static function lock_table($table, &$locked_by, $bypass_security = false) {
		return DataBaseLock::lock($table, null, null, $locked_by, $bypass_security);
	}
	
	/** lock a row in a table, based on its primary key */
	public static function lock_row($table, $row_key, &$locked_by, $bypass_security = false) {
		return DataBaseLock::lock($table, $row_key, null, $locked_by, $bypass_security);
	}
	
	/** lock a column in a table, based on its name */
	public static function lock_column($table, $column, &$locked_by, $bypass_security = false) {
		return DataBaseLock::lock($table, null, $column, $locked_by, $bypass_security);
	}
	
	/** lock a specific cell in a table, based on a primary key and a column */
	public static function lock_cell($table, $row_key, $column, &$locked_by, $bypass_security = false) {
		return DataBaseLock::lock($table, $row_key, $column, $locked_by, $bypass_security);
	}
	
	private static function lock($table, $row_key, $column, &$locked_by, $bypass_security) {
		for ($i = 0; $i < 10; $i++) {
			$locked_by = null;
			$id = DataBaseLock::try_lock($table, $row_key, $column, $locked_by, $bypass_security);
			if ($id <> null) return $id;
			if ($i < 9) usleep(100000);
		}
		return null;
	}
	private static function try_lock($table, $row_key, $column, &$locked_by, $bypass_security) {
		if (!$bypass_security && !DataBaseLock::is_locked_by_user($table, $row_key, $column)) {
			require_once("component/data_model/Model.inc");
			$t = DataModel::get()->getTableFromSQLName($table);
			if (!($t->mayInsert() || $t->mayModify() || $t->mayRemove()))
				throw new Exception("Access denied: you cannot lock table '".$t->getName()."'.");
			if ($row_key == null) {
				if (!($t->canModifyAny() || $t->canRemoveAny()))
					throw new Exception("Access denied: you cannot lock table '".$t->getName()."' because you have only partial access to it.");
			} else if (!($t->canModifyAny() || $t->canRemoveAny())) {
				$ok = false;
				if ($t->mayModify()) {
					$q = SQLQuery::create();
					$table_alias = $q->table_id();
					$q->select(array($table=>$table_alias));
					if ($t->getPrimaryKey() <> null)
						$q->where_value($table_alias, $t->getPrimaryKey()->name, $row_key);
					else {
						$pk = $t->getKey();
						foreach ($pk as $pk_name)
							$q->where_value($table_alias, $pk_name, $row_key[$pk_name]);
					}
					$t->prepareSelectToDetermineWriteAccess($q, $table_alias);
					$rows = $q->execute();
					$rows = $t->filterWriteAccess($rows);
					if (count($rows) > 0) $ok = true;
				}
				if (!$ok && $t->mayRemove()) {
					$q = SQLQuery::create();
					$table_alias = $q->table_id();
					$q->select(array($table=>$table_alias));
					if ($t->getPrimaryKey() <> null)
						$q->where_value($table_alias, $t->getPrimaryKey()->name, $row_key);
					else {
						$pk = $t->getKey();
						foreach ($pk as $pk_name)
							$q->where_value($table_alias, $pk_name, $row_key[$pk_name]);
					}
					$locks = array();
					$t->prepareSelectToDetermineRemoveAccess($q, $table_alias, $locks);
					$rows = $q->execute();
					$rows = $t->filterRemoveAccess($rows);
					if (count($rows) > 0) $ok = true;
					foreach ($locks as $lock_id) DataBaseLock::unlock($lock_id);
				}
				if (!$ok) throw new Exception("Access denied: you cannot lock the row id ".$row_key." in table ".$table);
			}
		}
		
		DataBaseLock::clean();
		$db = SQLQuery::get_db_system_without_security();
		$db->execute("LOCK TABLES DataLocks WRITE");
		$lock_user = DataBaseLock::is_locked($table, $row_key, $column, true);
		if ($lock_user <> null) {
			$db->execute("UNLOCK TABLES");
			$locked_by = $lock_user;
			return null;
		}
		$db->execute(
			"INSERT INTO DataLocks".
			" (`timestamp`,`locker_domain`,`locker_username`,`table`,`row_key`,`column`)".
			" VALUE (".
				time().
				",'".$db->escape_string(PNApplication::$instance->user_management->domain)."'".
				",'".$db->escape_string(PNApplication::$instance->user_management->username)."'".
				",'".$db->escape_string($table)."'".
				",".($row_key <> null ? "'".$db->escape_string(json_encode($row_key))."'" : "NULL").
				",".($column <> null ? "'".$db->escape_string($column)."'" : "NULL").
			")"
		);
		$id = $db->get_insert_id();
		$db->execute("UNLOCK TABLES");
		return $id;
	} 
	
	/** remove expired locks */
	public static function clean() {
		$db = SQLQuery::get_db_system_without_security();
		$db->execute("LOCK TABLES DataLocks WRITE");
		$db->execute("DELETE FROM DataLocks WHERE timestamp <= ".(time()-5*60));
		$db->execute("UNLOCK TABLES");
	}
	
	/** check if the table/row/column/cell is locked by someone else. If it is the case, return the username of the locker, else return null (if not locked, or locked by the current user) */
	public static function is_locked($table, $row_key, $column, $db_locked = false) {
		$db = SQLQuery::get_db_system_without_security();
		if (!$db_locked)
			$db->execute("LOCK TABLES DataLocks WRITE");
		$res = $db->execute("SELECT * FROM DataLocks WHERE `table`='".$db->escape_string($table)."'");
		while (($row = $db->next_row($res)) <> null) {
			if ($row["table"] <> $table) continue; // not the same table
			if ($row_key == null) {
				if ($column == null) {
					// full table is requested
					if ($row["locker_domain"] <> PNApplication::$instance->user_management->domain ||
						$row["locker_username"] <> PNApplication::$instance->user_management->username) {
						if (!$db_locked)
							$db->execute("UNLOCK TABLES");
						return $row["locker_username"]; // not the current user
					}
					// same user
					if ($row["row_key"] == null && $row["column"] == $column) {
						if (!$db_locked)
							$db->execute("UNLOCK TABLES");
						return null; // same user locked the full table
					}
					continue; // same user, but not full table
				}
				// one column is requested
				if ($row["column"] == $column) {
					// same column is locked
					if ($row["locker_domain"] <> PNApplication::$instance->user_management->domain ||
						$row["locker_username"] <> PNApplication::$instance->user_management->username) {
						if (!$db_locked)
							$db->execute("UNLOCK TABLES");
						return $row["locker_username"]; // not the current user
					}
					if (!$db_locked)
						$db->execute("UNLOCK TABLES");
					return null; // same user: ok
				}
				if ($row["row_key"] <> null) {
					// a row is locked: there will be a conflict if we lock a column
					if ($row["locker_domain"] <> PNApplication::$instance->user_management->domain ||
						$row["locker_username"] <> PNApplication::$instance->user_management->username) {
						if (!$db_locked)
							$db->execute("UNLOCK TABLES");
						return $row["locker_username"]; // not the current user
					}
					continue; // same user: ok
				}
				if ($row["column"] <> null) {
					// a column is locked, and this is not the same
					continue;
				}
				// the full table is locked
				if ($row["locker_domain"] <> PNApplication::$instance->user_management->domain ||
					$row["locker_username"] <> PNApplication::$instance->user_management->username) {
					if (!$db_locked)
						$db->execute("UNLOCK TABLES");
					return $row["locker_username"]; // not the current user
				}
				if (!$db_locked)
					$db->execute("UNLOCK TABLES");
				return null; // same user
			} else {
				if ($column == null) {
					// a row is requested
					if ($row["column"] <> null) {
						// there is a column locked
						if ($row["locker_domain"] <> PNApplication::$instance->user_management->domain ||
							$row["locker_username"] <> PNApplication::$instance->user_management->username) {
							if (!$db_locked)
								$db->execute("UNLOCK TABLES");
							return $row["locker_username"]; // not the current user
						}
						continue; // same user
					}
					if ($row["row_key"] <> null) {
						if ($row["row_key"] <> json_encode($row_key)) {
							// not the same row
							continue;
						}
						if ($row["locker_domain"] <> PNApplication::$instance->user_management->domain ||
							$row["locker_username"] <> PNApplication::$instance->user_management->username) {
							if (!$db_locked)
								$db->execute("UNLOCK TABLES");
							return $row["locker_username"]; // not the current user
						}
						continue; // same user
					}
					// the table is locked
					if ($row["locker_domain"] <> PNApplication::$instance->user_management->domain ||
						$row["locker_username"] <> PNApplication::$instance->user_management->username) {
						if (!$db_locked)
							$db->execute("UNLOCK TABLES");
						return $row["locker_username"]; // not the current user
					}
					if (!$db_locked)
						$db->execute("UNLOCK TABLES");
					return null; // same user
				} else {
					// a cell is requested
					if ($row["column"] <> null) {
						if ($row["column"] <> $column)
							continue; // not on the same column
						// same column is locked
						if ($row["locker_domain"] <> PNApplication::$instance->user_management->domain ||
							$row["locker_username"] <> PNApplication::$instance->user_management->username) {
							if (!$db_locked)
								$db->execute("UNLOCK TABLES");
							return $row["locker_username"]; // not the current user
						}
						if ($row["row_key"] == null) {
							if (!$db_locked)
								$db->execute("UNLOCK TABLES");
							return null; // full column already locked by same user
						}
						if ($row["row_key"] == json_encode($row_key)) {
							if (!$db_locked)
								$db->execute("UNLOCK TABLES");
							return null; // same cell locked by same user
						}
						continue; // different cell
					} else {
						if ($row["row_key"] <> null) {
							// a row is locked
							if ($row["row_key"] <> json_encode($row_key))
								continue; // not on the same row
							if ($row["locker_domain"] <> PNApplication::$instance->user_management->domain ||
								$row["locker_username"] <> PNApplication::$instance->user_management->username) {
								if (!$db_locked)
									$db->execute("UNLOCK TABLES");
								return $row["locker_username"]; // not the current user
							}
							if (!$db_locked)
								$db->execute("UNLOCK TABLES");
							return null; // same user
						} else {
							// full table is locked
							if ($row["locker_domain"] <> PNApplication::$instance->user_management->domain ||
								$row["locker_username"] <> PNApplication::$instance->user_management->username) {
								if (!$db_locked)
									$db->execute("UNLOCK TABLES");
								return $row["locker_username"]; // not the current user
							}
							if (!$db_locked)
								$db->execute("UNLOCK TABLES");
							return null; // same user
						}
					}
				}
			}
		}
		if (!$db_locked)
			$db->execute("UNLOCK TABLES");
	}

	/** check if the table/row/column/cell is locked by the current user. If it is the case, return true, else return false. */
	public static function is_locked_by_user($table, $row_key, $column, $db_locked = false) {
		$db = SQLQuery::get_db_system_without_security();
		if (!$db_locked)
			$db->execute("LOCK TABLES DataLocks WRITE");
		$res = $db->execute("SELECT * FROM DataLocks WHERE `table`='".$db->escape_string($table)."'");
		while (($row = $db->next_row($res)) <> null) {
			if ($row["table"] <> $table) continue; // not the same table
			if ($row["locker_domain"] <> PNApplication::$instance->user_management->domain ||
				$row["locker_username"] <> PNApplication::$instance->user_management->username) continue;
			if ($row["row_key"] == null) {
				if ($row["column"] == null) {
					if (!$db_locked) $db->execute("UNLOCK TABLES");
					return true; // full table is locked
				} else {
					if ($column == $row["column"]) {
						if (!$db_locked) $db->execute("UNLOCK TABLES");
						return true; // full column is locked
					}
				}
			} else {
				if (json_encode($row_key) == $row["row_key"]) {
					// same row
					if ($row["column"] == null) {
						if (!$db_locked) $db->execute("UNLOCK TABLES");
						return true; // full row is locked
					}
					if ($row["column"] == $column) {
						if (!$db_locked) $db->execute("UNLOCK TABLES");
						return true; // same cell is locked				
					}
				}
			}
		}
		if (!$db_locked) $db->execute("UNLOCK TABLES");
	}
	
	/** remove a lock (it must be owned by the current user) */
	public static function unlock($id) {
		$result = null;
		$db = SQLQuery::get_db_system_without_security();
		$db->execute("LOCK TABLES DataLocks WRITE");
		$r = $db->execute("SELECT locker_domain,locker_username FROM DataLocks WHERE id='".$db->escape_string($id)."'");
		if ($r) {
			$row = $db->next_row($r);
			if ($row) {
				if ($row["locker_domain"] == PNApplication::$instance->user_management->domain &&
					$row["locker_username"] == PNApplication::$instance->user_management->username) {
					$db->execute("DELETE FROM DataLocks WHERE id='".$db->escape_string($id)."'");
				} else
					$result = "You are not the owner of this lock";
			}
		}
		$db->execute("UNLOCK TABLES");
		return $result;
	}

	/** extend expiration time of a lock */
	public static function update($id) {
		$db = SQLQuery::get_db_system_without_security();
		$result = null;
		$ts = time();
		$db->execute("LOCK TABLES DataLocks WRITE");
		$r = $db->execute("SELECT locker_domain,locker_username FROM DataLocks WHERE id='".$db->escape_string($id)."'");
		if ($r) {
			$row = $db->next_row($r);
			if ($row) {
				if ($row["locker_domain"] == PNApplication::$instance->user_management->domain &&
					$row["locker_username"] == PNApplication::$instance->user_management->username) {
					$db->execute("UPDATE DataLocks SET timestamp=".$ts." WHERE id='".$db->escape_string($id)."'");
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
	
	/** check the given lock is locking the given table by the current user */
	public static function check_lock($lock_id, $table, $row_key, $column) {
		$db = SQLQuery::get_db_system_without_security();
		$r = $db->execute("SELECT * FROM DataLocks WHERE id='".$db->escape_string($lock_id)."'");
		if ($r == null) return false;
		$r = $db->next_row($r);
		if ($r == null) return false;
		if ($r["locker_domain"] <> PNApplication::$instance->user_management->domain ||
			$r["locker_username"] <> PNApplication::$instance->user_management->username)
			return false;
		if ($r["table"] <> $table) return false;
		if ($r["row_key"] == null) {
			if ($r["column"] == null) {
				// full table is locked
				return true;
			}
			// only a column is locked
			if ($column == null) return false;
			if ($column == $r["column"]) return true;
			return false;
		}
		// a specific row is locked
		if ($r["column"] <> null) {
			// a specific cell
			if ($row_key == null) return false;
			if ($column == null) return false;
			if (json_encode($row_key) <> $r["row_key"]) return false;
			if ($column <> $r["column"]) return false;
			return true;
		}
		if ($row_key == null) return false;
		if (json_encode($row_key) == $r["row_key"]) return true;
		return false;
	}

	public static function generate_script($id) {
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