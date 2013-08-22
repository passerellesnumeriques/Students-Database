<?php
class DataBaseLock {

	public static function lock_table($table, &$locked_by) {
		return DataBaseLock::lock($table, null, null, $locked_by);
	}
	
	public static function lock_row($table, $row_key, &$locked_by) {
		return DataBaseLock::lock($table, $row_key, null, $locked_by);
	}
	
	public static function lock_column($table, $column, &$locked_by) {
		return DataBaseLock::lock($table, null, $column, $locked_by);
	}
	
	public static function lock_cell($table, $row_key, $column, &$locked_by) {
		return DataBaseLock::lock($table, $row_key, $column, $locked_by);
	}
	
	private static function lock($table, $row_key, $column, &$locked_by) {
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
				",".($row_key <> null ? "'".$db->escape_string($row_key)."'" : "NULL").
				",".($column <> null ? "'".$db->escape_string($column)."'" : "NULL").
			")"
		);
		$id = $db->get_insert_id();
		$db->execute("UNLOCK TABLES");
		return $id;
	} 
	
	public static function clean() {
		$db = SQLQuery::get_db_system_without_security();
		$db->execute("LOCK TABLES DataLocks WRITE");
		$db->execute("DELETE FROM DataLocks WHERE timestamp <= ".(time()-5*60));
		$db->execute("UNLOCK TABLES");
	}
	
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
						if ($row["row_key"] <> $row_key) {
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
						if ($row["row_key"] == $row_key) {
							if (!$db_locked)
								$db->execute("UNLOCK TABLES");
							return null; // same cell locked by same user
						}
						continue; // different cell
					} else {
						if ($row["row_key"] <> null) {
							// a row is locked
							if ($row["row_key"] <> $row_key)
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