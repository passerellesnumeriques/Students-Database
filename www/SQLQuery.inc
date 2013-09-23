<?php
require_once("DataBaseSystem.inc");
/** 
 * Single way to access to the database: create an SQL query, performs security checks, then execute the request 
 */
class SQLQuery {
	
	// TODO: when remove, bypass security for linked tables

	private static $db_system = null;
	/**
	 * @return DataBaseSystem
	 */
	private static function db() {
		if (SQLQuery::$db_system <> null) return SQLQuery::$db_system;
		$db_conf = include("conf/local_db");
		require_once("DataBaseSystem_".$db_conf["type"].".inc");
		$db_system_class = "DataBaseSystem_".$db_conf["type"];
		SQLQuery::$db_system = new $db_system_class;
		$res = SQLQuery::$db_system->connect($db_conf["server"], $db_conf["user"], $db_conf["password"]);
		if ($res <> DataBaseSystem::ERR_OK) {
			switch ($res) {
				case DataBaseSystem::ERR_CANNOT_CONNECT_TO_SERVER: PNApplication::error("Unable to connect to the database server"); break;
				case DataBaseSystem::ERR_INVALID_CREDENTIALS: PNApplication::error("Invalid credentials to connect to the database server"); break;
				default: PNApplication::error("Unknown result when connecting to the database server"); break;
			}
		}
		// by default, connect to the local domain
		if (!SQLQuery::$db_system->select_db("students_".PNApplication::$instance->local_domain))
			PNApplication::error("Unable to connect to the local domain database");
		return SQLQuery::$db_system;
	}
	
	public static function escape($str) {
		return SQLQuery::db()->escape_string($str);
	}
	
	public static function get_db_system_without_security() { return SQLQuery::db(); }

 	/** utility method to ease chained usage: SQLQuery::create()->select(...)->field(...)->.... */
 	public static function &create() { $q = new SQLQuery(); return $q; }

 	public function __construct($copy = null) {
 		if ($copy) {
 			$this->tables = $copy->tables;
 			$this->fields = $copy->fields;
 			$this->where = $copy->where;
 			$this->joins = $copy->joins;
 			$this->limit_from = $copy->limit_from;
 			$this->limit_number = $copy->limit_number;
 			$this->group_by = $copy->group_by;
 		}
 	}

 	public $database = null;
 	public $sub_models = array();
 	public $tables = array();
 	public $fields = array();
 	public $count = null;
	public $count_one_field = null;
 	public $where = array();
 	public $joins = array();
 	public $limit_from = 0;
 	public $limit_number = -1;
 	public $group_by = array();
 	public $order_by = array();
 	public $bypass_security = false;

 	private $field_id_counter = 0;
 	private $table_id_counter = 0;

	/** Generates a unique field alias name (useful when building a big list) */
	public function field_id() { return "f".($this->field_id_counter++); }
	/** Generates a unique table alias name (useful when building a big list) */
	public function table_id() { return "t".($this->table_id_counter++); }

	public function &database($db_name) { $this->database = $db_name; return $this; }
	
	public function &select_sub_model($key_table, $key) {
		$this->sub_models[$key_table] = $key;
		return $this;
	}
	public function &set_sub_models($sub_models) {
		if ($sub_models <> null) foreach ($sub_models as $key_table=>$key) $this->sub_models[$key_table] = $key;
		return $this;
	}

	/** Prepares a select in the given tables.
	 * Usage:
	 *  - 1 parameter as string: the name of the table in which the select will be performed
	 *  - 1 parameter as array: array containing the list of table_name=>table_alias
	 *  - more than 1 parameter: list of table names (no aliases)
	 * @return SQLQuery
	 */
	public function &select() {
		$nb = func_num_args();
		if ($nb > 1) {
			for ($i = 0; i < $nb; $i++)
				$this->tables[func_get_arg($i)] = null;
		} else {
			$o = func_get_arg(0);
			if (is_string($o))
				$this->tables[$o] = null;
			else foreach ($o as $table_name=>$alias)
				if (is_string($table_name))
					$this->tables[$table_name] = $alias;
				else
					$this->tables[$alias] = null;
		}
		return $this;
	}

	/**
	 * Indicates a field should be returned by the queyr.
	 * Usage:
	 *  (field_name): in case only one table was indicated in the select() method, this table will be used together with the given field
	 *  (table_name, field_name)
	 *  (table_name, field_name, field_alias)
	 */
	public function &field($table,$name=null,$alias=null) {
		if ($name == null)
			foreach ($this->tables as $t=>$a)
				$this->field($t, $table);
		else {
			foreach ($this->fields as $f)
				if ($f[0] == $table && $f[1] == $name && $f[2] == $alias)
					return $this;
			array_push($this->fields, array($table,$name,$alias));
		}
		return $this;
	}

	public function &reset_fields() {
		$this->fields = array();
		return $this;
	}
	public function &count($alias = null) {
		$this->count = array($alias);
		return $this;
	}
	public function &count_one_field($table, $name, $alias=null){
		$this->count_one_field = array($table,$name,$alias);
		return $this;
	}
	
	public function get_field_alias($table, $field) {
		foreach ($this->fields as $f)
			if ($f[0] == $table && $f[1] == $field) {
				if ($f[2] == null) return $field;
				return $f[2];
			}
		return null;
	}
	public function get_table_alias($table) {
		foreach ($this->tables as $t=>$a)
			if ($t == $table)
				return $a <> null ? $a : $t;
		foreach ($this->joins as $j)
			if ($j[1] == $table)
				return $j[3] <> null ? $j[3] : $j[1];
		return $table;
	}

	public function &bypass_security() { $this->bypass_security = true; return $this; }

	/**
	 * Usages:
	 *  - SQL condition: where("`field1`='value'")
	 *  - map of values: where(array("field1"=>"value1","field2"=>"value2",...))
	 *  - single value:  where("field1","value1")
	 *  - condition:     where("field1", "<=", "value1")
	 */
	public function &where() {
		$nb = func_num_args();
		if ($nb == 1) {
			$o = func_get_arg(0);
			if (is_array($o))
				foreach (func_get_arg(0) as $field=>$value)
					$this->where($field, "=", $value);
			else
				array_push($this->where, $o);
		} else if ($nb == 2)
			$this->where(func_get_arg(0), "=", func_get_arg(1));
		else {
			$field = func_get_arg(0);
			$op = func_get_arg(1);
			$value = func_get_arg(2);
			if ($value === null) {
				if ($op == "=")
					array_push($this->where, "`".$field."` IS NULL");
				else
					array_push($this->where, "`".$field."` IS NOT NULL");
			} else
				array_push($this->where, "`".$field."`='".SQLQuery::escape($value)."'");
		}
		return $this;
	}

	/** if only one table is used, we can use it as ($field,$values), meaning no need to specify the table */
	public function &where_in($table, $field, $values = null) {
		if ($values === null) {
			$values = $field;
			$field = $table;
			foreach ($this->tables as $name=>$alias)
				$table = $name;
		}
		$w = "`".$table."`.`".$field."` IN (";
		$first = true;
		foreach ($values as $value) {
			if ($first) $first = false; else $w .= ",";
			$w .= "'".$value."'";
		}
		$w .= ")";
		array_push($this->where, $w);
		return $this;
	}

	public function &join($table_from, $table_to, $fields_matching, $alias = null) {
		array_push($this->joins, array($table_from, $table_to, $fields_matching, $alias));
		return $this;
	}
	public function get_join_alias($table_from, $table_to, $fields_matching) {
		foreach ($this->joins as $join) {
			if ($join[0] <> $table_from) continue;
			if ($join[1] <> $table_to) continue;
			$remaining = array_merge($fields_matching);
			$ok = true;
			foreach ($join[2] as $f1=>$f2) {
				if (!isset($remaining[$f1])) { $ok = false; break; }
				if ($remaining[$f1] <> $f2) { $ok = false; break; }
				$remaining[$f1] = null;
			}
			if (!$ok) continue;
			foreach ($remaining as $f=>$v) if ($v !== null) { $ok = false; break; }
			if (!$ok) continue;
			return $join[3] <> null ? $join[3] : $table_to;
		}
		return null;
	}

	public function &limit($from, $number) {
		$this->limit_from = $from;
		$this->limit_number = $number;
		return $this;
	}

	/** If only one parameter, it means that only one table is selected, and we will use this table */
	public function &group_by($table, $field=null) {
		if ($field == null) {
			$field = $table;
			foreach ($this->tables as $t=>$a) $table = $t;
		}
		array_push($this->group_by, array($table, $field));
		return $this;
	}

	/** Usage: (table,field,asc) or (alias,asc) or (alias) */
	public function &order_by($table, $field=null, $asc=true) {
		if ($field === null) {
			$field = $table;
			$table = null;
		} else if (is_bool($field)) {
			$asc = $field;
			$field = $table;
			$table = null;
		}
		array_push($this->order_by, array($table, $field, $asc));
		return $this;
	}

	public function generate() {
		require_once("component/data_model/Model.inc");

		// validate the query
		$final_fields = array();
		foreach ($this->tables as $t=>$alias) {
			if ($this->bypass_security)
				$table = DataModel::get()->internalGetTable($t);
			else
				$table = DataModel::get()->getTable($t);
			if ($alias == null) $alias = $table->getSQLName($this->sub_models);
			if (count($this->fields) == 0 && $this->count === null) {
				if ($this->bypass_security)
					$columns = $table->internalGetColumns();
				else
					$columns = $table->getColumns();
				foreach ($columns as $col)
					array_push($final_fields, array($alias, $col->name, null));
				unset($columns);
			} else foreach ($this->fields as $f) {
				if ($f[0] == $t || $f[0] == $alias) {
					// check the field is accessible
					if ($this->bypass_security)
						$col = $table->internalGetColumn($f[1]);
					else
						$col = $table->getColumn($f[1]);
					array_push($final_fields, array($f[0], $col->name, $f[2]));
					unset($col);
				}
			}
			if (!$this->bypass_security && !$table->canReadAll())
				foreach ($table->table_specific_read_access as $specific) {
					$this->where($alias."`.`".$specific[0], $specific[1]());
				}
				unset($table);
		}
		foreach ($this->joins as $join) {
			if ($this->bypass_security)
				$table = DataModel::get()->internalGetTable($join[1]);
			else
				$table = DataModel::get()->getTable($join[1]);
			$alias = $join[3];
			if ($alias == null) $alias = $table->getSQLName($this->sub_models);
			if (count($this->fields) == 0 && $this->count_one_field === null && $this->count === null) {
				if ($this->bypass_security)
					$columns = $table->internalGetColumns();
				else
					$columns = $table->getColumns();
				foreach ($columns as $col)
					array_push($final_fields, array($alias, $col->name, null));
				unset($columns);
			} else foreach ($this->fields as $f) {
				if ($f[0] == $join[1] || $f[0] == $alias) {
					// check the field is accessible
					if ($this->bypass_security)
						$col = $table->internalGetColumn($f[1]);
					else
						$col = $table->getColumn($f[1]);
					array_push($final_fields, array($alias, $col->name, $f[2]));
					unset($col);
				}
			}
			if (!$this->bypass_security && !$table->canReadAll())
				foreach ($table->table_specific_read_access as $specific) {
					$this->where($alias."`.`".$specific[0], $specific[1]());
				}
			unset($table);
		}
		$this->fields = $final_fields;
		
		$sql = "SELECT ";
		if (count($this->fields) == 0 && $this->count_one_field === null && $this->count_one_field === null && $this->count === null && $this->bypass_security)
			$sql .= "*";
		else {
			$first = true;
			foreach ($this->fields as $f) {
				if ($first) $first = false; else $sql .= ",";
				$sql .= "`".$f[0]."`.`".$f[1]."`";
				if ($f[2] <> null) $sql .= " AS `".$f[2]."`";
			}
			if ($this->count !== null) {
				if ($first) $first = false; else $sql .= ",";
				$sql .= "COUNT(*)";
				if ($this->count[0] <> null)
					$sql .= " AS `".$this->count[0]."`";
			}
			if ($this->count_one_field !== null){
				if ($first) $first = false; else $sql .= ",";
				$sql .= " COUNT(`".$this->count_one_field[0]."`.`".$this->count_one_field[1]."`)";
				if ($this->count_one_field[2] <> null)
					$sql .= " AS `".$this->count_one_field[2]."`";
			}
		}
		$sql .= " FROM ";
		$first = true;
		foreach ($this->tables as $name=>$alias) {
			if ($first) $first = false; else $sql .= ",";
			if ($this->database) $sql .= "`".$this->database."`.";
			$table = DataModel::get()->internalGetTable($name);
			$sql .= "`".$table->getSQLName($this->sub_models)."`";
			if ($alias <> null) $sql .= " AS `".$alias."`";
			unset($table);
		}
		foreach ($this->joins as $join) {
			$sql .= " LEFT JOIN ";
			if ($this->database) $sql .= "`".$this->database."`.";
			$table = DataModel::get()->internalGetTable($join[1]);
			$sql .= "`".$table->getSQLName($this->sub_models)."`";
			if ($join[3] <> null) $sql .= " AS `".$join[3]."`";
			$sql .= " ON ";
			$first = true;
			foreach ($join[2] as $field_from=>$field_to) {
				if ($first) $first = false; else $sql .= " AND ";
				$sql .= "`".$join[0]."`.`".$field_from."`=";
				if (is_string($field_to))
					$sql .= "`".($join[3] <> null ? $join[3] : $table->getSQLName($this->sub_models))."`.`".$field_to."`";
				else
					$sql .= "'".SQLQuery::escape($field_to[0])."'";
			}
			unset($table);
		}
		if (count($this->where) > 0) {
			$sql .= " WHERE ";
			$first = true;
			foreach ($this->where as $w) {
				if ($first) $first = false; else $sql .= " AND ";
				$sql .= $w;
			}
		}
		if (count($this->group_by) > 0) {
			$sql .= " GROUP BY ";
			$first = true;
			foreach ($this->group_by as $g) {
				if ($first) $first = false; else $sql .= ",";
				$sql .= "`".$g[0]."`.`".$g[1]."`";
			}
		}
		if (count($this->order_by) > 0) {
			$sql .= " ORDER BY ";
			$first = true;
			foreach ($this->order_by as $o) {
				if ($first) $first = false; else $sql .= ",";
				if ($o[0] !== null)
					$sql .= "`".$o[0]."`.";
				$sql .= "`".$o[1]."` ".($o[2] ? "ASC" : "DESC");
			}
		}
		if ($this->limit_number > 0) {
			$sql .= " LIMIT ".$this->limit_from.",".$this->limit_number;
		}
		return $sql;
	}

	public function execute() {
		$sql = $this->generate();
		$res = SQLQuery::db()->execute($sql);
		if ($res === FALSE) return null;
		$a = array();
		while (($r = SQLQuery::db()->next_row($res)) !== FALSE)
			array_push($a, $r);
		return $a;
	}

	public function execute_single_field() {
		$a = $this->execute();
		if ($a === null) return null;
		$a2 = array();
		foreach ($a as $r) array_push($a2, $r[0]);
		return $a2;
	}

	public function execute_single_value() {
		$a = $this->execute_single_field();
		if ($a === null) return null;
		if (count($a) == 0) return null;
		return $a[0];
	}

	public function execute_single_row() {
		$sql = $this->generate();
		$res = SQLQuery::db()->execute($sql);
		if ($res === FALSE) return null;
		$r = SQLQuery::db()->next_row($res);
		if ($r === FALSE) return null;
		return $r;
	}

	public function update_by_key($table, $key, $fields_values, $sub_models = null, $lock_id = null) {
		require_once("component/data_model/Model.inc");
		/* @var $t datamodel\Table */
		if ($this->bypass_security)
			$t = DataModel::get()->internalGetTable($table);
		else
			$t = DataModel::get()->getTable($table);
		
		// check we are allowed
		if (!$this->bypass_security)
			foreach ($fields_values as $field=>$value)
				if (!$t->canModifyField($field)) throw new Exception("Access Denied: modify ".$field." on table ".$t->getName());

		// lock table
		require_once("component/data_model/DataBaseLock.inc");
		if ($lock_id <> null) {
			$column = null;
			if (count($fields_values) == 1) { $column = array_keys($fields_values); $column = $column[0]; }
			if (!DataBaseLock::check_lock($lock_id, $t->getName(), $key, $column))
				throw new Exception("Access denied: invalid lock");
		} else {
			$locker = null;
			$lock_id = DataBaseLock::lock_row($t->getName(), $key, $locker);
			if ($lock_id == null)
				throw new Exception("This data is already locked by ".$locker);
		}
		
		// validate values, and check unicity
		foreach ($fields_values as $field=>$value) {
			$col = $t->internalGetColumn($field);
			try { $fields_values[$field] = $col->validate($value); }
			catch (Exception $e) {
				DataBaseLock::unlock($lock_id);
				$dname = $t->getDisplayableDataName($field);
				if ($dname == null) $dname = $field;
				throw new Exception($dname.": ".$e->getMessage());
			}
			if ($col->unique) {
				$r = SQLQuery::create()->bypass_security()->select($t->getName())->set_sub_models($sub_models)->where($col->name, $fields_values[$field])->execute_single_row();
				if ($r <> null) {
					DataBaseLock::unlock($lock_id);
					$dname = $t->getDisplayableDataName($field);
					if ($dname == null) $dname = $field;
					throw new Exception("The element already exists: ".$dname);
				}
			}
		}
		// everything is valid and allowed, we can perform the update
		$sql = "UPDATE `".$t->getSQLName($sub_models)."` SET ";
		$first = true;
		foreach ($fields_values as $field=>$value) {
			if ($first) $first = false; else $sql .= ",";
			$sql .= "`".$field."`=";
			if ($value === null)
				$sql .= "NULL";
			else
				$sql .= "'".SQLQuery::escape($value)."'";
		}
		$sql .= " WHERE `".$t->getPrimaryKey()->name."`='".$key."'";
		SQLQuery::db()->execute($sql);
		$error = DataBaseLock::unlock($lock_id);
		if ($error <> null) throw new Exception($error);
	}
	
	public function update($table, $fields_values, $where, $sub_models = null) {
		require_once("component/data_model/Model.inc");
		/* @var $t datamodel\Table */
		if ($this->bypass_security)
			$t = DataModel::get()->internalGetTable($table);
		else
			$t = DataModel::get()->getTable($table);
		
		$sql = "UPDATE `".$t->getSQLName($sub_models)."` SET ";
		$first = true;
		foreach ($fields_values as $field=>$value) {
			if (!$this->bypass_security && !$t->canModifyField($field)) throw new Exception("Access denied for column '".$field."' on table '".$table."'");
			if ($first) $first = false; else $sql .= ",";
			$sql .= "`".$field."`=";
			if ($value === null) $sql .= "NULL";
			else $sql .= "'".SQLQuery::escape($value)."'";
		}
		if ($where <> null) {
			$sql .= " WHERE ";
			if (is_string($where)) $sql .= $where;
			else {
				$first = true;
				foreach ($where as $field=>$value) {
					if ($first) $first = false; else $sql .= " AND ";
					$sql .= "`".$field."`";
					if ($value === null) $sql .= " IS NULL";
					else $sql .= "='".SQLQuery::escape($value)."'";
				}
			}
		}
		SQLQuery::db()->execute($sql);
		if (SQLQuery::db()->get_last_error_number() == 0)
			return SQLQuery::db()->affected_rows();
		return -1;
	}
	
	/**
	 * Insert an entry into the given table.
	 * @param string $table
	 * @param array $fields_values associated array with column name => value
	 * @param array|null $sub_models
	 * @return integer the ID of the inserted data of any
	 * @throws Exception in case of error (security or database)
	 */
	public function insert($table, $fields_values, $sub_models = null) {
		require_once("component/data_model/Model.inc");
		/* @var $t datamodel\Table */
		if ($this->bypass_security)
			$t = DataModel::get()->internalGetTable($table);
		else
			$t = DataModel::get()->getTable($table);

		// check we are allowed
		if (!$this->bypass_security && !$t->canAdd()) throw new Exception("Access denied: add data to table ".$t->getName());
		// lock the table
		require_once("component/data_model/DataBaseLock.inc");
		$locker = null;
		$lock = DataBaseLock::lock_table($t->getName(), $locker);
		if ($lock == null)
			throw new Exception("Access denied: the table ".$t->getName()." is locked by ".$locker);
		$columns = $t->internalGetColumns($sub_models);
		$id = null;
		$error = null;
		try {
			// validate values
			foreach ($fields_values as $field=>$value)
				$fields_values[$field] = $t->internalGetColumn($field, $sub_models)->validate($value);
			// check unicity and presence
			foreach ($columns as $col) {
				if ($col->unique) {
					if (!isset($fields_values[$col->name]))
						throw new Exception("Missing column ".$col->name." to insert data in table ".$t->getName());
					$q = SQLQuery::create();
					if ($this->bypass_security) $q->bypass_security();
					$r = $q->select($t->getSQLName($sub_models))->where($col->name,$fields_values[$col->name])->execute_single_row();
					if ($r <> null) {
						$dname = $t->getDisplayableDataName($col->name);
						if ($dname == null) $dname = $col->name;
						throw new Exception("The element already exists (".$dname.")");
					}
				} else if (!$col->can_be_null && !isset($fields_values[$col->name]))
					throw new Exception("Missing column ".$col->name." to insert data in table ".$t->getName());
			}
			// insert
			$sql = "INSERT INTO `".$t->getSQLName($sub_models)."` (";
			$first = true;
			foreach ($fields_values as $name=>$value) {
				if ($first) $first = false; else $sql .= ",";
				$sql .= "`".$name."`";
			}
			$sql .= ") VALUE (";
			$first = true;
			foreach ($fields_values as $name=>$value) {
				if ($first) $first = false; else $sql .= ",";
				if ($value === null) $sql .= "NULL"; else $sql .= "'".SQLQuery::escape($value)."'";
			}
			$sql .= ")";
			SQLQuery::db()->execute($sql);
			$id = SQLQuery::db()->get_insert_id();
			// sub models
			foreach ($t->_key_submodels as &$sm) {
				$sm->create_tables($id);
			}
		} catch (Exception $e) {
			$error = $e;
		}
		// unlock the table
		DataBaseLock::unlock($lock);
		if ($error <> null) throw $error;
		return $id;
	}

	/** Remove the data having the given primary key, and all linked data */
	public function remove_key($table, $key, $sub_models = null) {
		$this->remove_keys($table, array($key), $sub_models);		
	}
	/** Remove entries corresponding to the given rows */
	public function remove_rows($table, $rows) {
		require_once("component/data_model/Model.inc");
		$keys = array();
		/* @var $t datamodel\Table */
		if ($this->bypass_security)
			$t = DataModel::get()->internalGetTable($table);
		else
			$t = DataModel::get()->getTable($table);
		$pk = $t->getPrimaryKey()->name;
		foreach ($rows as $r) array_push($keys, $r[$pk]);
		$this->remove_keys($keys);
	}

	/** Remove data with given primary keys */
	public function remove_keys($table, $keys, $sub_models = null) {
		require_once("component/data_model/Model.inc");
		/* @var $t datamodel\Table */
		if ($this->bypass_security)
			$t = DataModel::get()->internalGetTable($table);
		else
			$t = DataModel::get()->getTable($table);

		// search for all tables that may contain a data to remove
		$tables = array();
		$sub_model_instance = null;
		if ($t->getModel() instanceof SubDataModel) {
			$sub_model_instance = @$sub_models[$t->getModel()->getParentTable()];
			if ($sub_model_instance == null)
				throw new Exception("Try to remove data from a table in a sub data model: no sub data model specified");
		}
		$this->search_tables_for_remove($t, $tables, $sub_model_instance);

		// check we can access all the tables
		if (!$this->bypass_security)
			foreach ($tables as $ta)
				if (!$ta[0]->canRemove())
					throw new \Exception("Access denied: remove data from table ".$ta[0]->getName());
		
		// lock all the tables
		require_once("component/data_model/DataBaseLock.inc");
		$locker = null;
		$locks = array();
		foreach ($tables as $ta) {
			$lock = DataBaseLock::lock_table($ta[0]->getSQLNameFor($ta[1]), $locker);
			if ($lock == null) {
				foreach ($locks as $l)
					DataBaseLock::unlock($l);
				throw new Exception("Access denied: the table ".$ta[0]->getName()." is locked by ".$locker);
			}
		}
		
		// remove data
		foreach ($keys as $key)
			$this->perform_remove_by_key($t, $key, $sub_model_instance);
		
		// clean sub models linked to the table
		foreach ($t->_key_submodels as &$sm) {
			$sm->remove_tables($key);
		}
		
		// unlock tables
		foreach ($locks as $l)
			DataBaseLock::unlock($l);
	}
	/** Remove data with given where clause */
	public function remove($table, $where, $sub_models = null) {
		require_once("component/data_model/Model.inc");
		/* @var $t datamodel\Table */
		if ($this->bypass_security)
			$t = DataModel::get()->internalGetTable($table);
		else
			$t = DataModel::get()->getTable($table);
		
		if ($t->getPrimaryKey() <> null) {
			// the table has primary key, let's remove by key
			require_once("component/data_model/DataBaseLock.inc");
			$locker = null;
			$lock = DataBaseLock::lock_table($t->getSQLName($sub_models), $locker);
			if ($lock == null)
				throw new Exception("Access denied: the table ".$t->getName()." is locked by ".$locker);
			$rows = $this->select($table)->set_sub_models($sub_models)->where($where)->field($t->getPrimaryKey()->name)->execute_single_field();
			$this->remove_keys($table, $rows, $sub_models);
			DataBaseLock::unlock($lock);
			return;
		}

		// TODO if there are links ?
		require_once("component/data_model/DataBaseLock.inc");
		$locker = null;
		$lock = DataBaseLock::lock_table($t->getSQLName($sub_models), $locker);
		if ($lock == null)
			throw new Exception("Access denied: the table ".$t->getName()." is locked by ".$locker);

		// remove data
		$sql = "DELETE FROM `".$t->getSQLName($sub_models)."` WHERE ";
		$first = true;
		foreach ($where as $col=>$value) {
			if ($first) $first = false; else $sql .= " AND ";
			$sql .= "`".$col."`";
			if ($value === null)
				$sql .= " IS NULL";
			else
				$sql .= "='".SQLQuery::escape($value)."'";
		}
		SQLQuery::db()->execute($sql);

		DataBaseLock::unlock($lock);
	}
	private function search_tables_for_remove(&$table, &$tables, $sub_model_instance) {
		// check we didn't reach this table yet
		foreach ($tables as $t)
			if ($t[0] == $table && $t[1] == $sub_model_instance)
				return;
		array_push($tables, array(&$table,$sub_model_instance));
		
		if ($table->getModel() instanceof SubDataModel) {
			// we are in a sub model: everything must be inside this sub model
			foreach ($table->getModel()->internalGetTables() as $t) {
				$cols = $t->internalGetColumns(array($table->getModel()->getParentTable()=>$sub_model_instance));
				foreach ($cols as $col)
					if ($col instanceof ForeignKey && $col->foreign_table == $table->getName())
						$this->search_tables_for_remove($t, $tables, $sub_model_instance);
			}
		} else {
			// we are on the root model, we may have link everywhere
			foreach (DataModel::get()->internalGetTables() as $t) {
				if ($t->getModel() instanceof SubDataModel) {
					foreach ($t->getModel()->getExistingInstances() as $smi) {
						$sm = array($t->getModel()->getParentTable()=>$smi);
						$cols = $t->internalGetColumns($sm);
						foreach ($cols as $col)
							if ($col instanceof ForeignKey && $col->foreign_table == $table->getName())
								$this->search_tables_for_remove($t, $tables, $sm);
					}
				} else {
					$cols = $t->internalGetColumns(null);
					foreach ($cols as $col)
						if ($col instanceof ForeignKey && $col->foreign_table == $table->getName())
							$this->search_tables_for_remove($t, $tables, null);
				}
			}
		}
	}
	// TODO on remove, handle foreign_key->remove_primary_when_foreign_removed
	private function perform_remove_by_key(&$table, $key, $sub_model_instance) {
		// remove data
		SQLQuery::db()->execute("DELETE FROM `".$table->getName().($sub_model_instance !== null ? "_".$sub_model_instance : "")."` WHERE `".$table->getPrimaryKey()->name."`=".$key);
		
		// remove linked data
		foreach (DataModel::get()->internalGetTables() as $t) {
			if ($t->getModel() instanceof SubDataModel) {
				if ($table->getModel() instanceof SubDataModel) {
					// if we are on two different sub-models, we cannot have links
					if ($table->getModel()->getParentTable() <> $t->getModel()->getParentTable()) continue;
					// we need to be on the same sub model instance
					$sm = array($t->getModel()->getParentTable()=>$sub_model_instance);
					$cols = $t->internalGetColumns($sm);
					foreach ($cols as $col) {
						if ($col instanceof ForeignKey && $col->foreign_table == $table->getName()) {
							if ($t->getPrimaryKey() <> null) {
								if ($col->remove_foreign_when_primary_removed) {
									$linked = SQLQuery::create()->bypass_security()->select($t->getSQLName($sm))->field($t->getPrimaryKey()->name)->where($col->name, $key)->execute_single_field();
									if ($linked)
										foreach ($linked as $linked_key)
											$this->perform_remove_by_key($t, $linked_key, $sub_model_instance);
								} else
									SQLQuery::create()->bypass_security()->update($t->getSQLName($sm), array($col->name=>null), array($col->name=>$key));
							} else {
								// no primary key, just remove the entries
								if ($col->remove_foreign_when_primary_removed)
									SQLQuery::db()->execute("DELETE FROM `".$t->getSQLName($sm)."` WHERE `".$col->name."`=".$key);
								else
									SQLQuery::db()->execute("UPDATE FROM `".$t->getSQLName($sm)."` SET `".$col->name."`=NULL WHERE `".$col->name."`=".$key);
							}
						}
					}
				} else {
					// we need to check for each instance of the sub model
					foreach ($t->getModel()->getExistingInstances() as $smi) {
						$sm = array($t->getModel()->getParentTable()=>$smi);
						$cols = $t->internalGetColumns($sm);
						foreach ($cols as $col) {
							if ($col instanceof ForeignKey && $col->foreign_table == $table->getName()) {
								if ($t->getPrimaryKey() <> null) {
									if ($col->remove_foreign_when_primary_removed) {
										$linked = SQLQuery::create()->bypass_security()->select($t->getSQLName($sm))->field($t->getPrimaryKey()->name)->where($col->name, $key)->execute_single_field();
										if ($linked)
											foreach ($linked as $linked_key)
												$this->perform_remove_by_key($t, $linked_key, $sub_model_instance);
									} else
										SQLQuery::create()->bypass_security()->update($t->getSQLName($sm), array($col->name=>null), array($col->name=>$key));
								} else {
									// no primary key, just remove the entries
									if ($col->remove_foreign_when_primary_removed)
										SQLQuery::db()->execute("DELETE FROM `".$t->getSQLName($sm)."` WHERE `".$col->name."`=".$key);
									else
										SQLQuery::db()->execute("UPDATE FROM `".$t->getSQLName($sm)."` SET `".$col->name."`=NULL WHERE `".$col->name."`=".$key);
								}
							}
						}
					}
				}
			} else {
				// t is not in a sub-model
				// if table is in a sub-model, no possible link
				if ($table->getModel() instanceof SubDataModel) continue;
				// both are on root
				foreach ($t->internalGetColumns(null) as $col) {
					if ($col instanceof ForeignKey && $col->foreign_table == $table->getName()) {
						if ($t->getPrimaryKey() <> null) {
							if ($col->remove_foreign_when_primary_removed) {
								$linked = SQLQuery::create()->bypass_security()->select($t->getSQLName(null))->field($t->getPrimaryKey()->name)->where($col->name, $key)->execute_single_field();
								if ($linked)
									foreach ($linked as $linked_key)
										$this->perform_remove_by_key($t, $linked_key, null);
							} else
								SQLQuery::create()->bypass_security()->update($t->getSQLName($sm), array($col->name=>null), array($col->name=>$key));
						} else {
							// no primary key, just remove the entries
							if ($col->remove_foreign_when_primary_removed)
								SQLQuery::db()->execute("DELETE FROM `".$t->getSQLName($sm)."` WHERE `".$col->name."`=".$key);
							else
								SQLQuery::db()->execute("UPDATE FROM `".$t->getSQLName($sm)."` SET `".$col->name."`=NULL WHERE `".$col->name."`=".$key);
						}
					}
				}
			}
		}
	}
}
?>