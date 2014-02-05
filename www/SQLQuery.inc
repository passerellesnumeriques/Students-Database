<?php
require_once("DataBaseSystem.inc");
/** 
 * Single way to access to the database: create an SQL query, performs security checks, then execute the request 
 */
class SQLQuery {
	
	/** @var DataBaseSystem the connection to the database */
	private static $db_system = null;
	/** returns the direct connection to the database
	 * @return DataBaseSystem the connection to the database
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
		if (!SQLQuery::$db_system->selectDatabase("students_".PNApplication::$instance->local_domain))
			PNApplication::error("Unable to connect to the local domain database");
		return SQLQuery::$db_system;
	}
	
	/** Escape a string for the current Database system
	 * @param string $str the string to escape
	 */
	public static function escape($str) {
		return SQLQuery::db()->escapeString($str);
	}
	
	/**
	 * Returns the directo access to the database. This should almost never be used.
	 * @return DataBaseSystem the connection to the database
	 */
	public static function getDataBaseAccessWithoutSecurity() { return SQLQuery::db(); }

 	/** utility method to ease chained usage: SQLQuery::create()->select(...)->field(...)->....
 	 * @return SQLQuery a new instance of SQLQuery
 	 */
 	public static function &create() { $q = new SQLQuery(); return $q; }

 	/** array Pre-selected sub models, automatically used with every new SQLQuery */
 	private static $preselected_submodels = array();
 	/** Pre-select sub models, which will be automatically selected in every new SQLQuery
 	 * @param string $key_table the table of the sub model
 	 * @param number $key the key value identifying the instance of the sub model
 	 */
 	public static function set_submodel($key_table, $key) {
 		SQLQuery::$preselected_submodels[$key_table] = $key;
 	}
 	
 	/**
 	 * Copy a SQLQuery 
 	 * @param SQLQuery $copy the query to copy
 	 */
 	public function __construct($copy = null) {
 		if ($copy) {
 			$this->tables = $copy->tables;
 			$this->fields = $copy->fields;
 			$this->where = $copy->where;
 			$this->joins = $copy->joins;
 			$this->limit_from = $copy->limit_from;
 			$this->limit_number = $copy->limit_number;
 			$this->group_by = $copy->group_by;
 			$this->field_alias_counter = $copy->field_alias_counter;
 			$this->table_alias_counter = $copy->table_alias_counter;
 		}
 		$this->sub_models = array_merge(SQLQuery::$preselected_submodels);
 	}

 	/** @var string database name, or null to keep the default one (the one of the current domain) */
 	public $database = null;
 	/** @var array associative array of sub_model_table/sub_model_instance */
 	public $sub_models = array();
 	/** @var array list of selected tables, with an associative table_name/table_alias (table_alias can be null, as using an alias is optional) */
 	public $tables = array();
 	/** @var array list of selected fields, each one represented by an array(table_name,column_name,alias) with alias which can be null (optional) */
 	public $fields = array();
 	/** @var array either array(null) for COUNT(*), or array(alias) for COUNT(*) AS `alias` */
 	public $count = null;
 	/** @var array array(table_name,column_name,alias) for COUNT(`table_name`.`column_name`) AS `alias` where alias is optional */
 	public $count_one_field = null;
 	/** @var array list of WHERE string expression, which will be joined by AND */
 	public $where = array();
 	/** @var array list of LEFT JOIN with format (table_from,table_to,fields_matching,alias) */
 	public $joins = array();
 	/** @var number to start from a specific index, using LIMIT statment */
 	public $limit_from = 0;
 	/** @var number to specify a maximum number of rows to retrieve, using LIMIT statment, or -1 for no limit */
 	public $limit_number = -1;
 	/** @var array list of (table_name,column_name) to use in a GROUP BY */
 	public $group_by = array();
 	/** @var array list of (table_name,column_name) to use in a ORDER BY */
 	public $order_by = array();
 	/** @var boolean indicates if we have to skip security checks or not */
 	public $bypass_security = false;
 	/** @var boolean indicates to use DISTINCT in the select */
	public $distinct = false;

	/** @var number counter to generate unique field aliases */
 	private $field_alias_counter = 0;
	/** @var number counter to generate unique table aliases */
 	private $table_alias_counter = 0;

	/** Generates a unique field alias name (useful when building a big list) */
	public function generateFieldAlias() { return "f".($this->field_alias_counter++); }
	/** Generates a unique table alias name (useful when building a big list) */
	public function generateTableAlias() { return "t".($this->table_alias_counter++); }

	/** Use a sepcific database, instead of the default one which is the one of the current domain
	 * @param string $db_name name of the database to use
	 * @return SQLQuery this
	 */
	public function &database($db_name) { $this->database = $db_name; return $this; }
	
	/** Select a sub model
	 * @param string $key_table the table defining the sub model
	 * @param number $key the key defining the sub model instance
	 * @return SQLQuery this
	 */
	public function &selectSubModel($key_table, $key) {
		$this->sub_models[$key_table] = $key;
		return $this;
	}
	
	/**
	 * Select a set of sub models
	 * @param array $sub_models associative array of key_table/key
	 * @return this
	 */
	public function &selectSubModels($sub_models) {
		if ($sub_models <> null) foreach ($sub_models as $key_table=>$key) $this->sub_models[$key_table] = $key;
		return $this;
	}
	/** Same as selectSubModel except that the table given is directly the Table object instead of the name of the table
	 * @param datamodel\Table $table_obj the table defining the sub model
	 * @param number $sub_model the key defining the sub model instance
	 * @return this
	 */
	public function &selectSubModelForTable($table_obj, $sub_model) {
		if ($sub_model == null) return $this;
		if (!($table_obj->getModel() instanceof SubDataModel)) return $this;
		$this->sub_models[$table_obj->getModel()->getParentTable()] = $sub_model;
		return $this;
	}
	
	/** Prepares a select in the given tables.<br/>
	 * Usage:<ul>
	 *  <li> 1 parameter as string: the name of the table in which the select will be performed</li>
	 *  <li> 1 parameter as array: array containing the list of table_name=&gt;table_alias</li>
	 *  <li> more than 1 parameter: list of table names (no aliases)</li>
	 * </ul>
	 * @return SQLQuery this
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
	 * Indicates a field should be returned by the query.<br/>
	 * Usage:<ul>
	 *  <li>(field_name): in case only one table was indicated in the select() method, this table will be used together with the given field</li>
	 *  <li>(table_name, field_name)</li>
	 *  <li>(table_name, field_name, field_alias)</li>
	 * </ul>
	 * @param string $table field_name for first option, else table_name
	 * @param string $name field_name, or null for first option
	 * @param string $alias field_alias, or null for first 2 options
	 * @return SQLQuery this 
	 */
	public function &field($table,$name=null,$alias=null) {
		if ($table == null || strlen($table) == 0) throw new Exception("SQLQuery::field: argument \$table cannot be empty!");
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

	/** Remove all selected fields
	 * @return SQLQuery this
	 */
	public function &resetFields() {
		$this->fields = array();
		return $this;
	}
	/** COUNT(*) AS alias
	 * @param string $alias alias or null
	 * @return SQLQuery this
	 */
	public function &count($alias = null) {
		$this->count = array($alias);
		return $this;
	}
	/** COUNT(table.name) AS alias
	 * @param string $table table name
	 * @param string $name field name
	 * @param string $alias alias or null
	 * @return this
	 */
	public function &countOneField($table, $name, $alias=null){
		$this->count_one_field = array($table,$name,$alias);
		return $this;
	}
	
	/** add a DISTINCT in the SELECT
	 * @return SQLQuery this 
	 */
	public function &distinct(){
		$this->distinct = true;
		return $this;
	}
	
	/** Retrieve the alias used for the given field (either in select, or in a join)
	 * @param string $table table name
	 * @param string $field column name in the table
	 * @return string|null the alias, or null if it is not found
	 */
	public function getFieldAlias($table, $field) {
		foreach ($this->fields as $f) {
			if ($f[1] <> $field) continue; // not the good field
			$good_table = false;
			if ($f[0] == $table) $good_table = true;
			else {
				foreach ($this->tables as $t=>$a)
					if ($table == $t && $a == $f[0]) { $good_table = true; break; }
				if (!$good_table)
					foreach ($this->joins as $j)
						if ($j[1] == $table && $f[0] == $j[3]) { $good_table = true; break; }
			}
			if ($good_table) {
				if ($f[2] == null) return $field;
				return $f[2];
			}
		}
		return null;
	}
	/** Retrieve the alias used for the given table
	 * @param string $table table name
	 * @return string|null alias of the table, or null if not found
	 */
	public function getTableAlias($table) {
		foreach ($this->tables as $t=>$a)
			if ($t == $table)
				return $a <> null ? $a : $t;
		foreach ($this->joins as $j)
			if ($j[1] == $table)
				return $j[3] <> null ? $j[3] : $j[1];
		return null;
	}

	/** Disable/Enable security checks. To be used very carefully !
	 * @param boolean $bypass true to disable security checks, false to enable them
	 * @return SQLQuery this
	 */
	public function &bypassSecurity($bypass = true) { $this->bypass_security = $bypass; return $this; }

	/** Add a AND xxx in the WHERE statement.<br/>
	 * Usages:<ul>
	 *  <li> SQL condition: where("`field1`='value'")</li>
	 *  <li> map of values: where(array("field1"=&gt;"value1","field2"=>"value2",...))</li>
	 *  <li> single value:  where("field1","value1")</li>
	 *  <li> condition:     where("field1", "&lt;=", "value1")</li>
	 * </li>
	 * @return SQLQuery this
	 */
	public function &where() {
		$nb = func_num_args();
		if ($nb == 1) {
			$o = func_get_arg(0);
			if (is_array($o))
				foreach (func_get_arg(0) as $field=>$value)
					$this->where($field, "=", utf8_decode($value));
			else {
				if (trim($o) <> "")
					array_push($this->where, $o);
			}
		} else if ($nb == 2)
			$this->where(func_get_arg(0), "=", utf8_decode(func_get_arg(1)));
		else {
			$field = func_get_arg(0);
			$op = func_get_arg(1);
			$value = func_get_arg(2);
			$table = null;
			require_once("component/data_model/Model.inc");
			foreach ($this->tables as $t=>$a) {
				if ($a <> null) $table = $a; else $table = DataModel::get()->internalGetTable($t)->getSQLName($this->sub_models);
				break;
			}
			if ($value === null) {
				if ($op == "=")
					array_push($this->where, "`".$table."`.`".$field."` IS NULL");
				else
					array_push($this->where, "`".$table."`.`".$field."` IS NOT NULL");
			} else
				array_push($this->where, "`".$table."`.`".$field."`".$op."'".SQLQuery::escape(utf8_decode($value))."'");
		}
		return $this;
	}
	
	private function resolveWhereTable($table) {
		// search if this is an alias
		foreach ($this->tables as $tname=>$talias)
			if ($table == $talias) return $table;
		foreach ($this->joins as $j)
			if ($table == $j[3]) return $table;
		// not an alias: get sql name
		try {
			require_once("component/data_model/Model.inc");
			$t = DataModel::get()->getTable($table);
			return $t->getSQLName($this->sub_models);
		} catch (Exception $e) {
			// not a table name
			return $table;
		}
	}
	
	public function &whereValue($table_name, $field_name, $value) {
		$w = "`".$this->resolveWhereTable($table_name)."`.`".$field_name."`";
		if ($value === null) $w .= " IS NULL";
		else $w .= "='".SQLQuery::escape(utf8_decode($value))."'";
		array_push($this->where, $w);
		return $this;
	}
	
	public function &whereNull($table_name, $field_name) {
		$w = "`".$this->resolveWhereTable($table_name)."`.`".$field_name."` IS NULL";
		array_push($this->where, $w);
		return $this;
	}
	public function &whereNotNull($table_name, $field_name) {
		$w = "`".$this->resolveWhereTable($table_name)."`.`".$field_name."` IS NOT NULL";
		array_push($this->where, $w);
		return $this;
	}
	
	/**
	 * Returns a string containing the current WHERE clause, and reset it in this SQLQuery (remove it)
	 */
	public function saveWhere() {
		$where = "";
		$first = true;
		foreach ($this->where as $w) {
			if (trim($w) == "") continue;
			if ($first) $first = false; else $where .= " AND ";
			$where .= $w;
		}
		if (strlen($where) > 0) $where = "(".$where.")";
		$this->where = array();
		return $where;
	}
	
	/** if only one table is used, we can use it as ($field,$values), meaning no need to specify the table */
	public function &whereIn($table, $field, $values = null) {
		if ($values === null) {
			$values = $field;
			$field = $table;
			foreach ($this->tables as $name=>$alias)
				$table = $name;
		}
		$w = "`".$this->resolveWhereTable($table)."`.`".$field."` IN (";
		$first = true;
		foreach ($values as $value) {
			if ($first) $first = false; else $w .= ",";
			$w .= "'".SQLQuery::escape(utf8_decode($value))."'";
		}
		$w .= ")";
		array_push($this->where, $w);
		return $this;
	}
	
	/** if only one table is used, we can use it as ($field,$values), meaning no need to specify the table */
	public function &whereNotIn($table, $field, $values = null) {
		if ($values === null) {
			$values = $field;
			$field = $table;
			foreach ($this->tables as $name=>$alias)
				$table = $name;
		}
		$w = "`".$this->resolveWhereTable($table)."`.`".$field."` NOT IN (";
		$first = true;
		foreach ($values as $value) {
			if ($first) $first = false; else $w .= ",";
			$w .= "'".SQLQuery::escape(utf8_decode($value))."'";
		}
		$w .= ")";
		array_push($this->where, $w);
		return $this;
	}

	public function &join($table_from, $table_to, $fields_matching, $alias = null, $join_on_values = null) {
		$join = array($table_from, $table_to, $fields_matching, $alias);
		if ($join_on_values <> null) array_push($join, $join_on_values);
		array_push($this->joins, $join);
		return $this;
	}
	public function getJoinAlias($table_from, $table_to, $fields_matching) {
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
	public function &groupBy($table, $field=null) {
		if ($field == null) {
			$field = $table;
			foreach ($this->tables as $t=>$a) $table = $t;
		}
		array_push($this->group_by, array($table, $field));
		return $this;
	}

	/** Usage: (table,field,asc) or (alias,asc) or (alias) */
	public function &orderBy($table, $field=null, $asc=true) {
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

		// filter the query according to access rights
		if (!$this->bypass_security) {
			foreach ($this->tables as $t=>$alias) {
				$table = DataModel::get()->getTable($t);
				if ($alias == null) $alias = $table->getSQLName($this->sub_models);
				$table->filterReadAccess($this, $alias);
			}
			for ($i = 0; $i < count($this->joins); $i++) {
				$join = $this->joins[$i];
				$alias = $join[3];
				$table = DataModel::get()->getTable($join[1]);
				if ($alias == null) $alias = $table->getSQLName($this->sub_models);
				$table->filterReadAccess($this, $alias);
			}
		}
		
		// validate the query
		$final_fields = array();
		$fields = array_merge($this->fields);
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
			} else {
				for ($i = 0; $i < count($fields); $i++) {
					$f = $fields[$i];
					if ($f[0] == $t || $f[0] == $alias) {
						$table_name = $f[0] == $t ? $table->getSQLName($this->sub_models) : $alias;
						// check the field is accessible
						if ($this->bypass_security)
							$col = $table->internalGetColumn($f[1]);
						else
							$col = $table->getColumn($f[1]);
						if ($col == null) {
							PNApplication::error("Unknown column ".$f[1]." in table ".$table->getName());
							return null;
						}
						array_push($final_fields, array($table_name, $col->name, $f[2]));
						unset($col);
						array_splice($fields,$i,1);
						$i--;
					}
				}
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
			} else {
				for ($i = 0; $i < count($fields); $i++) {
					$f = $fields[$i];
					if ($f[0] == $join[1] || $f[0] == $alias) {
						// check the field is accessible
						if ($this->bypass_security)
							$col = $table->internalGetColumn($f[1]);
						else
							$col = $table->getColumn($f[1]);
						array_push($final_fields, array($alias, $col->name, $f[2]));
						unset($col);
						array_splice($fields,$i,1);
						$i--;
					}
				}
			}
			unset($table);
		}
		// check if we have remaining fields that do not exist
		foreach ($fields as $f)
			PNApplication::error_html("Field '".$f[1]."' on table '".$f[0]."' does not exist in SQL query: ".$this->dump());

		$this->fields = $final_fields;
		
		$sql = "SELECT ";
		if (count($this->fields) == 0 && $this->count_one_field === null && $this->count === null && !$this->bypass_security)
			throw new Exception("Access denied: you don't have access to any column in this request");
		if (count($this->fields) == 0 && $this->count_one_field === null && $this->count === null && $this->bypass_security){
			if($this->distinct)
				$sql .= "DISTINCT ";
			$sql .= "*";		
		}
		else {
			$first = true;
			foreach ($this->fields as $f) {
				if ($first){
					$first = false;
					if($this->distinct)
						$sql .= "DISTINCT ";
				} else $sql .= ",";
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
				$sql .= " COUNT(";
				if($this->distinct)
					$sql .= "DISTINCT ";
				$sql .= "`".$this->resolveWhereTable($this->count_one_field[0])."`.`".$this->count_one_field[1]."`)";
				if ($this->count_one_field[2] <> null)
					$sql .= " AS `".$this->count_one_field[2]."`";
			}
		}
		$sql .= " FROM ";
		$aliases = array();
		$first = true;
		foreach ($this->tables as $name=>$alias) {
			if ($first) $first = false; else $sql .= ",";
			if ($this->database) $sql .= "`".$this->database."`.";
			$table = DataModel::get()->internalGetTable($name);
			$sql .= "`".$table->getSQLName($this->sub_models)."`";
			if ($alias <> null) {
				$sql .= " AS `".$alias."`";
				array_push($aliases, $alias);
			}
			unset($table);
		}
		foreach ($this->joins as $join) {
			$sql .= " LEFT JOIN ";
			if ($this->database) $sql .= "`".$this->database."`.";
			$table = DataModel::get()->internalGetTable($join[1]);
			$sql .= "`".$table->getSQLName($this->sub_models)."`";
			if ($join[3] <> null) $sql .= " AS `".$join[3]."`";
			$sql .= " ON ";
			$source_table = $join[0];
			if (!in_array($source_table, $aliases))
				$source_table = DataModel::get()->internalGetTable($join[0])->getSQLName($this->sub_models);
			$first = true;
			foreach ($join[2] as $field_from=>$field_to) {
				if ($first) $first = false; else $sql .= " AND ";
				$sql .= "`".$source_table."`.`".$field_from."`=";
				if (is_string($field_to))
					$sql .= "`".($join[3] <> null ? $join[3] : $table->getSQLName($this->sub_models))."`.`".$field_to."`";
				else
					$sql .= "'".SQLQuery::escape($field_to[0])."'";
			}
			if (isset($join[4])) {
				foreach ($join[4] as $field_to=>$value_to) {
					if ($first) $first = false; else $sql .= " AND ";
					$sql .= "`".($join[3] <> null ? $join[3] : $table->getSQLName($this->sub_models))."`.`".$field_to."`";
					if ($value_to === null)
						$sql .= " IS NULL";
					else
						$sql .= "='".SQLQuery::escape($value_to)."'";
				}
			}
			unset($table);
			if ($join[3] <> null) array_push($aliases, $join[3]);
		}
		for ($i = 0; $i < count($this->where); $i++)
			if (strlen(trim($this->where[$i]))==0) {
				array_splice($this->where, $i, 1);
				$i--;
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
				$sql .= "`".$this->resolveWhereTable($g[0])."`.`".$g[1]."`";
			}
		}
		if (count($this->order_by) > 0) {
			$sql .= " ORDER BY ";
			$first = true;
			foreach ($this->order_by as $o) {
				if ($first) $first = false; else $sql .= ",";
				if ($o[0] !== null)
					$sql .= "`".$this->resolveWhereTable($o[0])."`.";
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
		while (($r = SQLQuery::db()->nextRow($res)) !== FALSE) {
			foreach ($r as $key=>$val)
				if ($val <> null) $r[$key] = utf8_encode($val);
			array_push($a, $r);
		}
		return $a;
	}

	public function executeSingleField() {
		$a = $this->execute();
		if ($a === null) return null;
		$a2 = array();
		foreach ($a as $r) array_push($a2, $r[0]);
		return $a2;
	}

	public function executeSingleValue() {
		$a = $this->executeSingleField();
		if ($a === null) return null;
		if (count($a) == 0) return null;
		return $a[0];
	}

	public function executeSingleRow() {
		$sql = $this->generate();
		$res = SQLQuery::db()->execute($sql);
		if ($res === FALSE) return null;
		$r = SQLQuery::db()->nextRow($res);
		if ($r === FALSE) return null;
		foreach ($r as $key=>$val)
			if ($val <> null) $r[$key] = utf8_encode($val);
		return $r;
	}
	
	private function getColumnName($table, $column) {
		$display = $table->getDisplayHandler(null);
		if ($display == null) return $column;
		foreach ($display->getDisplayableData() as $data) {
			foreach ($data->getHandledColumns() as $col)
				if ($col == $column) return $data->getDisplayName();
		}
		return $column;
	}

	public function updateByKey($table, $key, $fields_values, $lock_id = null) {
		$in_transaction = SQLQuery::isTransactionStarted();
		try {
			if (!$in_transaction) SQLQuery::startTransaction();
			require_once("component/data_model/Model.inc");
			/* @var $t datamodel\Table */
			if ($this->bypass_security)
				$t = DataModel::get()->internalGetTable($table);
			else
				$t = DataModel::get()->getTable($table);
			
			// check we are allowed to modify this row
			if (!$this->bypass_security) {
				if (!$t->mayModify()) throw new Exception("Access denied to modify table ".$t->getName());
				
				$q = SQLQuery::create()->bypassSecurity();
				$table_alias = $q->generateTableAlias();
				$q->select(array($table=>$table_alias));
				$q->selectSubModels($this->sub_models);
				if ($t->getPrimaryKey() <> null)
					$q->where($t->getPrimaryKey()->name, $key);
				else {
					$keys = $t->getKey();
					foreach ($keys as $key_name)
						$q->whereValue($table_alias, $key_name, $key[$key_name]);
				}
				if ($t->prepareSelectToDetermineWriteAccess($q, $table_alias)) {
					$rows = $q->execute();
					$rows = $t->filterWriteAccess($rows);
					if (count($rows) == 0) throw new Exception("Access denied to modify this row in table ".$t->getName());
				}
			}
	
			// lock table
			$locked = false;
			require_once("component/data_model/DataBaseLock.inc");
			if ($lock_id <> null) {
				$column = null;
				if (count($fields_values) == 1) { $column = array_keys($fields_values); $column = $column[0]; }
				if (!DataBaseLock::checkLock($lock_id, $t->getSQLName($this->sub_models), $key, $column))
					throw new Exception("Access denied: invalid lock");
			} else {
				$locker = null;
				$lock_id = DataBaseLock::lockRow($t->getSQLName($this->sub_models), $key, $locker, $this->bypass_security);
				if ($lock_id == null)
					throw new Exception("This data is already locked by ".$locker);
				$locked = true;
			}
			
			// validate values, and check unicity
			foreach ($fields_values as $field=>$value) {
				$col = $t->internalGetColumn($field, $this->sub_models);
				if ($col == null) throw new Exception("Unknown column ".$field." in table ".$t->getName());
				try { $fields_values[$field] = $col->validate($value); }
				catch (Exception $e) {
					if ($locked)
						DataBaseLock::unlock($lock_id);
					$name = $this->getColumnName($t, $field);
					throw new Exception($name.": ".$e->getMessage());
				}
				if ($col->unique) {
					$r = SQLQuery::create()->bypassSecurity()->select($t->getName())->selectSubModels($this->sub_models)->where($col->name, $fields_values[$field])->executeSingleRow();
					if ($r <> null) {
						if ($locked)
							DataBaseLock::unlock($lock_id);
						$name = $this->getColumnName($t, $field);
						throw new Exception("The element already exists: ".$name);
					}
				}
			}
			
			// everything is valid and allowed, we can perform the update
			$sql = "UPDATE `".$t->getSQLName($this->sub_models)."` SET ";
			$first = true;
			foreach ($fields_values as $field=>$value) {
				if ($first) $first = false; else $sql .= ",";
				$sql .= "`".$field."`=";
				if ($value === null)
					$sql .= "NULL";
				else
					$sql .= "'".SQLQuery::escape(utf8_decode($value))."'";
			}
			$sql .= " WHERE ";
			if ($t->getPrimaryKey() <> null) {
				$sql .= "`".$t->getPrimaryKey()->name."`='".$key."'";
			} else {
				$keys = $t->getKey();
				$first = true;
				foreach ($keys as $key_name) {
					if ($first) $first = false; else $sql .= " AND ";
					$sql .= "`".$key_name."`='".$key[$key_name]."'";
				}
			}
			SQLQuery::db()->execute($sql);
			if ($locked) {
				$error = DataBaseLock::unlock($lock_id);
				if ($error <> null) throw new Exception($error);
			}
			if (!$in_transaction) SQLQuery::commitTransaction();
		} catch (Exception $e) {
			if (!$in_transaction) SQLQuery::rollbackTransaction();
			throw $e;
		}
	}

	/**
	 * @param string $table table name
	 * @param array $keys_fields_values array of array(array(keys), array(field=>value))
	 * @param unknown $lock_id
	 */
	public function updateByKeys($table, $keys_fields_values, $lock_id = null) {
		$in_transaction = SQLQuery::isTransactionStarted();
		try {
			if (!$in_transaction) SQLQuery::startTransaction();
			require_once("component/data_model/Model.inc");
			/* @var $t datamodel\Table */
			if ($this->bypass_security)
				$t = DataModel::get()->internalGetTable($table);
			else
				$t = DataModel::get()->getTable($table);
		
			// check we are allowed to modify this row
			if (!$this->bypass_security) {
				if (!$t->mayModify()) throw new Exception("Access denied to modify table ".$t->getName());
					
				$q = SQLQuery::create()->bypassSecurity();
				$table_alias = $q->generateTableAlias();
				$q->select(array($table=>$table_alias));
				$q->selectSubModels($this->sub_models);
				if ($t->getPrimaryKey() <> null) {
					$keys = array();
					foreach ($keys_fields_values as $k) array_push($keys, $k[0]);
					$q->whereIn($t->getName(), $t->getPrimaryKey()->name, $keys);
				} else {
					$keys = $t->getKey();
					$w = "";
					foreach ($keys_fields_values as $k) {
						$key = $k[0];
						$kw = "";
						foreach ($keys as $key_name) {
							if (strlen($kw) > 0) $kw .= " AND ";
							$kw .= "`".$table_alias."`.`".$key_name."`='".SQLQuery::escape($key[$key_name])."'";
						}
						if (strlen($w) > 0) $w .= " OR ";
						$w .= "(".$kw.")";
					}
					$q->where($w);
				}
				if ($t->prepareSelectToDetermineWriteAccess($q, $table_alias)) {
					$rows = $q->execute();
					$rows = $t->filterWriteAccess($rows);
					if (count($rows) == 0) throw new Exception("Access denied to modify this row in table ".$t->getName());
				}
			}
		
			// lock table
			$locked = false;
			require_once("component/data_model/DataBaseLock.inc");
			if ($lock_id <> null) {
				$column = null;
				$columns = array();
				foreach ($keys_fields_values as $k)
					$key = $k[0];
					$fields_values = $k[1];
					foreach ($fields_values as $field=>$value)
						if (!in_array($field, $columns)) array_push($columns, $field);
				if (count($columns) == 1) $column = $columns[0];
				if (!DataBaseLock::checkLock($lock_id, $t->getSQLName($this->sub_models), $key, $column))
					throw new Exception("Access denied: invalid lock");
			} else {
				$locker = null;
				$keys = array();
				foreach ($keys_fields_values as $k) array_push($keys, $k[0]);
				if (count($keys) == 1)
					$lock_id = DataBaseLock::lockRow($t->getSQLName($this->sub_models), $keys[0], $locker, $this->bypass_security);
				else
					$lock_id = DataBaseLock::lockTable($t->getSQLName($this->sub_models), $locker, $this->bypass_security);
				if ($lock_id == null)
					throw new Exception("This data is already locked by ".$locker);
				$locked = true;
			}
		
			// validate values, and check unicity
			$columns = array();
			foreach ($keys_fields_values as $k) {
				$key = $k[0];
				$fields_values = $k[1];
				foreach ($fields_values as $field=>$value) {
					$col = $t->internalGetColumn($field, $this->sub_models);
					try { 
						$value = $col->validate($value);
						$fields_values[$field] = $value; 
					} catch (Exception $e) {
						if ($locked)
							DataBaseLock::unlock($lock_id);
						$name = $this->getColumnName($t, $field);
						throw new Exception($name.": ".$e->getMessage());
					}
					if (!isset($columns[$field]))
						$columns[$field] = array($value);
					else
						array_push($columns[$field], $value);				
				}
			}
			foreach ($columns as $field=>&$values) {
				$col = $t->internalGetColumn($field, $this->sub_models);
				if ($col->unique) {
					for ($i = 0; $i < count($values); $i++) {
						for ($j = $i+1; $j < count($values); $j++) {
							if ($values[$i] == $values[$j]) {
								if ($locked)
									DataBaseLock::unlock($lock_id);
								$name = $this->getColumnName($t, $field);
								throw new Exception("You set two rows with the same value in column ".$name);
							}
						}
					}
					$r = SQLQuery::create()
						->bypassSecurity()
						->select($t->getName())
						->selectSubModels($this->sub_models)
						->whereIn($col->name, $values)
						->execute();
					if ($r <> null && count($r) > 0) {
						if ($locked)
							DataBaseLock::unlock($lock_id);
						$name = $this->getColumnName($t, $field);
						throw new Exception("Some elements already exist with same value in column ".$name);
					}
				}
			}
	
			// everything is valid and allowed, we can perform the update
			foreach ($keys_fields_values as $k) {
				$key = $k[0];
				$fields_values = $k[1];
				$sql = "UPDATE `".$t->getSQLName($this->sub_models)."` SET ";
				$first = true;
				foreach ($fields_values as $field=>$value) {
					if ($first) $first = false; else $sql .= ",";
					$sql .= "`".$field."`=";
					if ($value === null)
						$sql .= "NULL";
					else
						$sql .= "'".SQLQuery::escape(utf8_decode($value))."'";
				}
				$sql .= " WHERE ";
				if ($t->getPrimaryKey() <> null) {
					$sql .= "`".$t->getPrimaryKey()->name."`='".$key."'";
				} else {
					$keys = $t->getKey();
					$first = true;
					foreach ($keys as $key_name) {
						if ($first) $first = false; else $sql .= " AND ";
						$sql .= "`".$key_name."`='".$key[$key_name]."'";
					}
				}
				SQLQuery::db()->execute($sql);
			}
			if ($locked) {
				$error = DataBaseLock::unlock($lock_id);
				if ($error <> null) throw new Exception($error);
			}
			if (!$in_transaction) SQLQuery::commitTransaction();
		} catch (Exception $e) {
			if (!$in_transaction) SQLQuery::rollbackTransaction();
			throw $e;
		}
	}

	/** Update all given keys with the same given values
	 */
	public function updateAllKeys($table, $keys, $fields_values, $lock_id = null) {
		if (count($keys) == 0) return;
		
		$in_transaction = SQLQuery::isTransactionStarted();
		try {
			if (!$in_transaction) SQLQuery::startTransaction();
			require_once("component/data_model/Model.inc");
			/* @var $t datamodel\Table */
			if ($this->bypass_security)
				$t = DataModel::get()->internalGetTable($table);
			else
				$t = DataModel::get()->getTable($table);
		
			// check we are allowed to modify this row
			if (!$this->bypass_security) {
				if (!$t->mayModify()) throw new Exception("Access denied to modify table ".$t->getName());
		
				$q = SQLQuery::create()->bypassSecurity();
				$table_alias = $q->generateTableAlias();
				$q->select(array($table=>$table_alias));
				$q->selectSubModels($this->sub_models);
				if ($t->getPrimaryKey() <> null) {
					$q->whereIn($t->getName(), $t->getPrimaryKey()->name, $keys);
				} else {
					$tkeys = $t->getKey();
					$w = "";
					foreach ($keys as $key) {
						$kw = "";
						foreach ($tkeys as $key_name) {
							if (strlen($kw) > 0) $kw .= " AND ";
							$kw .= "`".$table_alias."`.`".$key_name."`='".SQLQuery::escape($key[$key_name])."'";
						}
						if (strlen($w) > 0) $w .= " OR ";
						$w .= "(".$kw.")";
					}
					$q->where($w);
				}
				if ($t->prepareSelectToDetermineWriteAccess($q, $table_alias)) {
					$rows = $q->execute();
					$rows = $t->filterWriteAccess($rows);
					if (count($rows) == 0) throw new Exception("Access denied to modify this row in table ".$t->getName());
				}
			}
		
			// lock table
			$locked = false;
			require_once("component/data_model/DataBaseLock.inc");
			if ($lock_id <> null) {
				$column = null;
				$columns = array();
				foreach ($fields_values as $field=>$value)
					if (!in_array($field, $columns)) array_push($columns, $field);
				if (count($columns) == 1) $column = $columns[0];
				if (!DataBaseLock::checkLock($lock_id, $t->getSQLName($this->sub_models), $key, $column))
					throw new Exception("Access denied: invalid lock");
			} else {
				$locker = null;
				if (count($keys) == 1)
					$lock_id = DataBaseLock::lockRow($t->getSQLName($this->sub_models), $keys[0], $locker, $this->bypass_security);
				else
					$lock_id = DataBaseLock::lockTable($t->getSQLName($this->sub_models), $locker, $this->bypass_security);
				if ($lock_id == null)
					throw new Exception("This data is already locked by ".$locker);
				$locked = true;
			}
		
			// validate values, and check unicity
			foreach ($fields_values as $field=>$value) {
				$col = $t->internalGetColumn($field, $this->sub_models);
				try { $fields_values[$field] = $col->validate($value); }
				catch (Exception $e) {
					if ($locked)
						DataBaseLock::unlock($lock_id);
					$name = $this->getColumnName($t, $field);
					throw new Exception($name.": ".$e->getMessage());
				}
				if ($col->unique)
					throw new Exception("Cannot set same value for multiple rows: ".$name);
			}
		
			// everything is valid and allowed, we can perform the update
			$sql = "UPDATE `".$t->getSQLName($this->sub_models)."` SET ";
			$first = true;
			foreach ($fields_values as $field=>$value) {
				if ($first) $first = false; else $sql .= ",";
				$sql .= "`".$field."`=";
				if ($value === null)
					$sql .= "NULL";
				else
					$sql .= "'".SQLQuery::escape(utf8_decode($value))."'";
			}
			$sql .= " WHERE ";
			if ($t->getPrimaryKey() <> null) {
				$sql .= "`".$t->getPrimaryKey()->name."` IN (";
				$first = true;
				foreach ($keys as $key) {
					if ($first) $first = false; else $sql .= ",";
					$sql .= "'".$key."'";
				}
				$sql .= ")";
			} else {
				$tkeys = $t->getKey();
				$first_key = true;
				foreach ($keys as $key) {
					if ($first_key) $first_key = false; else $sql .= " OR ";
					$first = true;
					$sql .= "(";
					foreach ($tkeys as $key_name) {
						if ($first) $first = false; else $sql .= " AND ";
						$sql .= "`".$key_name."`='".$key[$key_name]."'";
					}
					$sql .= ")";
				}
			}
			SQLQuery::db()->execute($sql);
			if ($locked) {
				$error = DataBaseLock::unlock($lock_id);
				if ($error <> null) throw new Exception($error);
			}
			if (!$in_transaction) SQLQuery::commitTransaction();
		} catch (Exception $e) {
			if (!$in_transaction) SQLQuery::rollbackTransaction();
			throw $e;
		}
	}
	
	/**
	 * Insert an entry into the given table.
	 * @param string $table
	 * @param array $fields_values associated array with column name => value
	 * @param array|null $sub_models
	 * @return integer the ID of the inserted data of any
	 * @throws Exception in case of error (security or database)
	 */
	public function insert($table, $fields_values) {
		require_once("component/data_model/Model.inc");
		/* @var $t datamodel\Table */
		if ($this->bypass_security)
			$t = DataModel::get()->internalGetTable($table);
		else
			$t = DataModel::get()->getTable($table);

		// check we are allowed
		if (!$this->bypass_security) {
			if (!$t->mayInsert()) throw new Exception("Access denied: add data to table ".$t->getName());
			if (!$t->acceptInsert($fields_values)) throw new Exception("Access denied: add data to table ".$t->getName()." denied for those values");
		}
		// lock the table to check it is not locked by anyone else
		require_once("component/data_model/DataBaseLock.inc");
		$locker = null;
		$lock = DataBaseLock::lockTable($t->getSQLName($this->sub_models), $locker, true);
		if ($lock == null)
			throw new Exception("Access denied: the table ".$t->getName()." is locked by ".$locker);
		$columns = $t->internalGetColumns($this->sub_models);
		// validate values
		foreach ($fields_values as $field=>$value)
			try {
				$col = $t->internalGetColumn($field, $this->sub_models);
				if ($col == null)
					throw new Exception("Unknown column '".$field."' in insert");
				$fields_values[$field] = $col->validate($value);
			} catch (Exception $ex) {
				DataBaseLock::unlock($lock);
				$name = $this->getColumnName($t, $field);
				throw new Exception($name.": ".$ex->getMessage());
			}
		// check unicity and presence
		foreach ($columns as $col) {
			if ($col->unique) {
				if (!isset($fields_values[$col->name])) {
					DataBaseLock::unlock($lock);
					throw new Exception("Missing column ".$col->name." to insert data in table ".$t->getName());
				}
				$q = SQLQuery::create();
				if ($this->bypass_security) $q->bypassSecurity();
				$r = $q->select($t->getName())->selectSubModels($this->sub_models)->where($col->name,$fields_values[$col->name])->executeSingleRow();
				if ($r <> null) {
					DataBaseLock::unlock($lock);
					throw new Exception("The element already exists: ".$this->getColumnName($t,$col->name));
				}
			} else if (!$col->can_be_null && !isset($fields_values[$col->name])) {
				DataBaseLock::unlock($lock);
				throw new Exception("Missing column ".$col->name." to insert data in table ".$t->getName());
			}
		}
		
		// insert
		$sql = "INSERT INTO `".$t->getSQLName($this->sub_models)."` (";
		$first = true;
		foreach ($fields_values as $name=>$value) {
			if ($first) $first = false; else $sql .= ",";
			$sql .= "`".$name."`";
		}
		$sql .= ") VALUE (";
		$first = true;
		foreach ($fields_values as $name=>$value) {
			if ($first) $first = false; else $sql .= ",";
			if ($value === null) $sql .= "NULL"; else $sql .= "'".SQLQuery::escape(utf8_decode($value))."'";
		}
		$sql .= ")";
		SQLQuery::db()->execute($sql);
		$id = SQLQuery::db()->getInsertID();
		// sub models
		foreach ($t->_key_submodels as &$sm)
			$sm->createTables($id);
		// unlock the table
		DataBaseLock::unlock($lock);
		// events
		$t->fireInsert($fields_values, $id, $t->getModel() instanceof SubDataModel ? @$this->sub_models[$t->getModel()->getParentTable()] : null);
		return $id;
	}
	
	/**
	 * Insert multiple entries into the given table.
	 * @param string $table
	 * @param array $rows array of array of column name => value
	 * @param array|null $sub_models
	 * @throws Exception in case of error (security or database)
	 */
	public function insertMultiple($table, $rows, $lock_id = null) {
		require_once("component/data_model/Model.inc");
		/* @var $t datamodel\Table */
		if ($this->bypass_security)
			$t = DataModel::get()->internalGetTable($table);
		else
			$t = DataModel::get()->getTable($table);
	
		// check we are allowed
		if (!$this->bypass_security) {
			if (!$t->mayInsert()) throw new Exception("Access denied: add data to table ".$t->getName());
			foreach ($rows as $fields_values)
				if (!$t->acceptInsert($fields_values)) throw new Exception("Access denied: add data to table ".$t->getName()." denied for those values");
		}
		// lock the table to check it is not locked by anyone else
		require_once("component/data_model/DataBaseLock.inc");
		$locker = null;
		if(!isset($lock_id)){
			$lock = DataBaseLock::lockTable($t->getSQLName($this->sub_models), $locker, true);
			if ($lock == null)
				throw new Exception("Access denied: the table ".$t->getName()." is locked by ".$locker);
		} else
			$lock = $lock_id;
		$columns = $t->internalGetColumns($this->sub_models);
		// validate values
		foreach ($rows as &$fields_values)
			foreach ($fields_values as $field=>$value)
				try {
				$col = $t->internalGetColumn($field, $this->sub_models);
				if ($col == null)
					throw new Exception("Unknown column '".$field."' in insert");
				$fields_values[$field] = $col->validate($value);
			} catch (Exception $ex) {
				DataBaseLock::unlock($lock);
				$name = $this->getColumnName($t, $field);
				throw new Exception($name.": ".$ex->getMessage());
			}
		// check unicity and presence
		foreach ($columns as $col) {
			if ($col->unique) {
				$values = array();
				foreach ($rows as &$fields_values) {
					if (!isset($fields_values[$col->name])) {
						DataBaseLock::unlock($lock);
						throw new Exception("Missing column ".$col->name." to insert data in table ".$t->getName());
					}
					array_push($values, $fields_values[$col->name]);
				}
				$q = SQLQuery::create();
				if ($this->bypass_security) $q->bypassSecurity();
				$r = $q->select($t->getName())
					->selectSubModels($this->sub_models)
					->whereIn($col->name,$values)
					->execute();
				if ($r <> null && count($r) > 0) {
					DataBaseLock::unlock($lock);
					throw new Exception("An element already exists with same value in column ".$this->getColumnName($t,$col->name));
				}
			} else if (!$col->can_be_null) {
				foreach ($rows as &$fields_values) {
					if (!isset($fields_values[$col->name])) {
						DataBaseLock::unlock($lock);
						throw new Exception("Missing column ".$col->name." to insert data in table ".$t->getName());
					}
				}
			}
		}
	
		// insert
		$sql = "INSERT INTO `".$t->getSQLName($this->sub_models)."` (";
		$first = true;
		foreach ($fields_values as $name=>$value) {
			if ($first) $first = false; else $sql .= ",";
			$sql .= "`".$name."`";
		}
		$sql .= ") VALUES ";
		$first_value = true;
		foreach ($rows as &$fields_values) {
			if ($first_value) $first_value = false; else $sql .= ",";
			$sql .= "(";
			$first = true;
			foreach ($fields_values as $name=>$value) {
				if ($first) $first = false; else $sql .= ",";
				if ($value === null) $sql .= "NULL"; else $sql .= "'".SQLQuery::escape(utf8_decode($value))."'";
			}
			$sql .= ")";
		}
		SQLQuery::db()->execute($sql);
		$id = SQLQuery::db()->getInsertID();
		$ids = array();
		if ($id <> 0) {
			$nb_rows = SQLQuery::db()->affectedRows();
			if ($nb_rows == count($rows)) {
				$res = SQLQuery::db()->execute("SELECT LAST_INSERT_ID()");
				$row = SQLQuery::db()->nextRow($res);
				$id = $row[0];
				for ($i = 0; $i < $nb_rows; $i++)
					array_push($ids, $id+$i);
			}
		}
		// sub models
		foreach ($t->_key_submodels as &$sm)
			$sm->createTables($id);
		// unlock the table
		DataBaseLock::unlock($lock);
		// events
		if ($id <> 0) {
			$i = 0;
			foreach ($rows as &$fields_values) {
				$t->fireInsert($fields_values, $ids[$i++], $t->getModel() instanceof SubDataModel ? @$this->sub_models[$t->getModel()->getParentTable()] : null);
			}
		}
		return $ids;
	}
	
	/** Remove the data having the given primary key, and all linked data */
	public function removeKey($table, $key) {
		$this->removeKeys($table, array($key));		
	}
	/** Remove entries corresponding to the given rows */
	public function removeRows($table, $rows) {
		require_once("component/data_model/Model.inc");
		$keys = array();
		/* @var $t datamodel\Table */
		if ($this->bypass_security)
			$t = DataModel::get()->internalGetTable($table);
		else
			$t = DataModel::get()->getTable($table);
		$pk = $t->getPrimaryKey()->name; // TODO if no primary key
		foreach ($rows as $r) array_push($keys, $r[$pk]);
		$this->removeKeys($keys);
	}

	/** Remove data with given primary keys */
	public function removeKeys($table, $keys) {
		$in_transaction = SQLQuery::isTransactionStarted();
		try {
			if (!$in_transaction) SQLQuery::startTransaction();
			require_once("component/data_model/Model.inc");
			/* @var $t datamodel\Table */
			if ($this->bypass_security)
				$t = DataModel::get()->internalGetTable($table);
			else
				$t = DataModel::get()->getTable($table);
	
			// browse tables to search for rows to remove and to update
			$sub_model_instance = null;
			if ($t->getModel() instanceof SubDataModel) {
				$sub_model_instance = @$this->sub_models[$t->getModel()->getParentTable()];
				if ($sub_model_instance == null)
					throw new Exception("Try to remove data from a table in a sub data model: no sub data model specified");
			}
			require_once("component/data_model/DataBaseLock.inc");
			$locks = array();
			$to_remove = array();
			$to_update = array();
			$sub_models_to_remove = array();
			try {
				$this->browseTablesForRemove($t, $sub_model_instance, $keys, $locks, $to_remove, $to_update, $sub_models_to_remove);
			} catch (Exception $e) {
				foreach ($locks as $lock) DataBaseLock::unlock($lock);
				throw new Exception("Remove from table '".$table."': ".$e->getMessage());
			}
			
			// everything is allowed, and all tables are locked: do it
			foreach ($to_remove as $table_name=>$to_remove_in_table) {
				if (isset($to_remove_in_table["keys"])) {
					$sql = "DELETE FROM `".$table_name."` WHERE ";
					if (is_array($to_remove_in_table["key_name"])) {
						$first_key = true;
						foreach ($to_remove_in_table["keys"] as $key) {
							if ($first_key) $first_key = false; else $sql .= " OR ";
							$sql .= "(";
							$first = true;
							foreach ($to_remove_in_table["key_name"] as $key_name) {
								if ($first) $first = false; else $sql .= " AND ";
								$sql .= "`".$key_name."`='".SQLQuery::escape($key[$key_name])."'";
							}
							$sql .= ")";
						}					
					} else {
						$sql .= "`".$to_remove_in_table["key_name"]."` IN (";
						$first = true;
						foreach ($to_remove_in_table["keys"] as $key) {
							if ($first) $first = false; else $sql .= ",";
							$sql .= $key;
						}
						$sql .= ")";
					}
					SQLQuery::db()->execute($sql);
				} else {
					$sql = "DELETE FROM `".$table_name."` WHERE ";
					$first = true;
					foreach ($to_remove_in_table["where"] as $where) {
						if ($first) $first = false; else $sql .= " OR ";
						$sql .= "(";
						$f = true;
						foreach ($where as $col=>$val) {
							if ($f) $f = false; else $sql .= " AND ";
							$sql .= "`".$col."`='".SQLQuery::escape(utf8_decode($val))."'";
						}
						$sql .= ")";
					}
					SQLQuery::db()->execute($sql);
				}
			}
			foreach ($to_update as $update) {
				$sql = "UPDATE `".$update[0]."` SET `".$update[1]."`=NULL WHERE `".$update[1]."` IN (";
				$first = true;
				foreach ($update[2] as $key) {
					if ($first) $first = false; else $sql .= ",";
					$sql .= $key;
				}
				$sql .= ")";
				SQLQuery::db()->execute($sql);
				// $t->getSQLName($this->sub_models)
			}

			// end transaction before removing tables of sub-model
			if (!$in_transaction) SQLQuery::commitTransaction();
				
			foreach ($sub_models_to_remove as $sm)
				foreach ($sm[1] as $key)
					$sm[0]->removeTables($key);
			
			// unlock
			foreach ($locks as $lock) DataBaseLock::unlock($lock);
		} catch (Exception $e) {
			if (!$in_transaction) SQLQuery::rollbackTransaction();
			throw $e;
		}
	}
	
	/**
	 * @param datamodel\Table $table
	 * @param string $sub_model_instance
	 * @param array $keys
	 * @param array $locks
	 * @param array $to_remove
	 */
	private function browseTablesForRemove(&$table, $sub_model_instance, $keys, &$locks, &$to_remove, &$to_update, &$sub_models_to_remove) {
		// remove keys that are already marked as to_remove
		if (isset($to_remove[$table->getSQLNameFor($sub_model_instance)])) {
			for ($i = 0; $i < count($keys); $i++) {
				if (in_array($keys[$i], $to_remove[$table->getSQLNameFor($sub_model_instance)]["keys"])) {
					array_splice($keys, $i, 1);
					$i--;
				}
			}
		}
		// array_push($to_update, array($t->getSQLNameFor($sm),$col->name,$keys));
		// array_push($to_update, array($t->getSQLName($this->sub_models),$col->name,$keys));
		
		if (count($keys) == 0) return; // nothing to do
			
		// lock the table to remove
		$locked_by = null;
		$lock_id = DataBaseLock::lockTable($table->getSQLNameFor($sub_model_instance), $locked_by, $this->bypass_security);
		if ($lock_id == null)
			throw new Exception("Table '".$table->getName()."' already locked by ".$locked_by);
		array_push($locks, $lock_id);

		// check sub models
		foreach ($table->_key_submodels as &$sm) {
			array_push($sub_models_to_remove, array($sm,$keys));
			// lock all tables in the sub model
			foreach ($sm->internalGetTables() as $table_name=>$t) {
				foreach ($keys as $key) {
					$locked_by = null;
					$lock_id = DataBaseLock::lockTable($t->getSQLNameFor($key), $locked_by, true);
					if ($lock_id == null)
						throw new Exception("Table '".$table->getName()."' already locked by ".$locked_by);
					array_push($locks, $lock_id);
				}
			}
		}

		// check access rights
		if (!$this->bypass_security) {
			if (!$table->mayRemove())
				throw new Exception("Access denied: remove in table '".$table->getName()."'");
			if (!$table->canRemoveAny()) {
				// we need to apply filters
				$q = SQLQuery::create();
				$q->bypassSecurity();
				$table_alias = $q->generateTableAlias();
				$q->select(array($table->getName()=>$table_alias));
				if ($sub_model_instance <> null)
					$q->selectSubModel($table->getParentTable(), $sub_model_instance);
				if ($table->getPrimaryKey() <> null)
					$q->whereIn($table_alias, $table->getPrimaryKey()->name, $keys);
				else {
					$pk = $table->getKey();
					$where = "";
					foreach ($keys as $key) {
						if (strlen($where) > 0) $where .= " OR ";
						$where .= "(";
						$first = true;
						foreach ($pk as $pk_name) {
							if ($first) $first = false; else $where .= " AND ";
							$where .= "`".$table_alias."`.`".$pk_name."`='".SQLQuery::escape($key[$pk_name])."'";
						}
						$where .= ")";
					}
					$q->where($where);
				}
				$table->prepareSelectToDetermineRemoveAccess($q, $table_alias, $locks);
				$rows = $q->execute();
				if (count($rows) <> count($keys)) throw new Exception("Invalid keys to remove in table '".$table->getName()."': ".count($keys)." keys given, ".count($rows)." rows found.");
				$result = $table->filterRemoveAccess($rows);
				if (count($rows) <> count($result)) throw new Exception("Access denied: ".(count($rows)-count($result))." rows cannot be removed among the ".count($rows)." rows to remove in table '".$table->getName()."'");
				$rows = $result;
			}
		}

		// we can remove those keys
		if (!isset($to_remove[$table->getSQLNameFor($sub_model_instance)])) {
			$key_name = $table->getPrimaryKey() <> null ? $table->getPrimaryKey()->name : $table->getKey();
			$to_remove[$table->getSQLNameFor($sub_model_instance)] = array("keys"=>array(),"key_name"=>$key_name);
		}
		foreach ($keys as $key)
			 array_push($to_remove[$table->getSQLNameFor($sub_model_instance)]["keys"], $key);
			//array_push($to_remove[$table->getSQLName($this->sub_models)]["keys"], $key);
		
		// search for foreign keys in this table, which may imply other remove
		$new_remove = array();
		foreach ($table->internalGetColumnsFor($sub_model_instance) as $col) {
			if (!($col instanceof datamodel\ForeignKey)) continue;
			if (!$col->remove_primary_when_foreign_removed) continue;
			// we have one
			$sub_model = null;
			if ($table->getModel() instanceof SubDataModel) {
				$t = DataModel::get()->internalGetTable($col->foreign_table);
				if ($t->getModel() instanceof SubDataModel && $t->getModel()->getParentTable() == $table->getModel()->getParentTable())
					$sub_model = $sub_model_instance;
			}
			// search corresponding keys
			$q = SQLQuery::create()->bypassSecurity();
			$table_alias = $q->generateTableAlias();
			$q->select(array($table->getName()=>$table_alias));
			if ($sub_model_instance <> null) $q->selectSubModel($table->getModel()->getParentTable(), $sub_model_instance);
			if ($table->getPrimaryKey() <> null)
				$q->whereIn($table_alias, $table->getPrimaryKey()->name, $keys);
			else {
				$pk = $table->getKey();
				$where = "";
				foreach ($keys as $key) {
					if (strlen($where) > 0) $where .= " OR ";
					$where .= "(";
					$first = true;
					foreach ($pk as $pk_name) {
						if ($first) $first = false; else $where .= " AND ";
						$where .= "`".$table_alias."`.`".$pk_name."`='".SQLQuery::escape($key[$pk_name])."'";
					}
					$where .= ")";
				}
				$q->where($where);
			}
			$q->field($table_alias, $col->name);
			$rows = $q->executeSingleField();
			
			if (!isset($new_remove[$col->foreign_table]))
				$new_remove[$col->foreign_table] = array();
			if (!isset($new_remove[$col->foreign_table][$sub_model]))
				$new_remove[$col->foreign_table][$sub_model] = array();
			array_push($new_remove[$col->foreign_table][$sub_model], $rows);
		}
		
		// search for foreign keys in other tables which are linked to the keys we want to remove
		if ($table->getModel() instanceof SubDataModel) {
			// we are in a sub model: everything must be inside this sub model
			foreach ($table->getModel()->internalGetTables() as $t) {
				$sm = $t->getModel() instanceof SubDataModel ? $sub_model_instance : null;
				$this->checkForeignKeysForRemove($table, $sub_model_instance, $keys, $t, $sm, $locks, $to_remove, $to_update, $sub_models_to_remove);
			}
		} else {
			// we are on the root model, we may have link everywhere
			foreach (DataModel::get()->internalGetTables() as $t) {
				if ($t->getModel() instanceof SubDataModel) {
					foreach ($t->getModel()->getExistingInstances() as $smi) {
						$sm = $smi;
						$this->checkForeignKeysForRemove($table, $sub_model_instance, $keys, $t, $sm, $locks, $to_remove, $to_update, $sub_models_to_remove);
					}
				} else {
					$this->checkForeignKeysForRemove($table, $sub_model_instance, $keys, $t, null, $locks, $to_remove, $to_update, $sub_models_to_remove);
				}
			}
		}
	}
	/**
	 * @param datamodel\Table $table
	 * @param string $sub_model_instance
	 * @param array $keys
	 * @param datamodel\Table $t
	 * @param string $sm
	 * @param array $to_remove
	 * @param array $to_update
	 */
	private function checkForeignKeysForRemove(&$table, $sub_model_instance, $keys, &$t, $sm, &$locks, &$to_remove, &$to_update, &$sub_models_to_remove) {
		$cols = $t->internalGetColumnsFor($sm);
		foreach ($cols as $col) {
			if (!($col instanceof \datamodel\ForeignKey)) continue;
			if ($col->foreign_table <> $table->getName()) continue;
			if ($col->remove_foreign_when_primary_removed) {
				$primary = $t->getPrimaryKey();
				if ($primary <> null) {
					// search keys for rows matching
					$q = SQLQuery::create()->bypassSecurity($this->bypass_security);
					$table_alias = $q->generateTableAlias();
					$q->select(array($t->getName()=>$table_alias));
					if ($sm <> null)
						$q->selectSubModel($t->getModel()->getParentTable(), $sm);
					$q->whereIn($table_alias, $col->name, $keys);
					$q->field($table_alias, $primary->name);
					$rows = $q->executeSingleField();
					$this->browseTablesForRemove($t, $sm, $rows, $locks, $to_remove, $to_update, $sub_models_to_remove);
				} else {
					// no primary key, this must be done with a where
					if (!$this->bypass_security && !$t->canRemoveAny()) {
						$q = SQLQuery::create()->bypassSecurity($this->bypass_security);
						$table_alias = $q->generateTableAlias();
						$q->select(array($t->getName()=>$table_alias));
						if ($sm <> null)
							$q->selectSubModel($t->getModel()->getParentTable(), $sm);
						$q->whereIn($table_alias, $col->name, $keys);
						$t->prepareSelectToDetermineRemoveAccess($q, $table_alias, $locks);
						$rows = $q->execute();
						$filtered = $t->filterRemoveAccess($rows);
						if (count($filtered) <> count($rows))
							throw new Exception("Access denied: remove from table '".$t->getName()."' due to link to removed data from table '".$table->getName()."': ".(count($rows)-count($filtered))." row(s) cannot be removed");
					}
					if (!isset($to_remove[$t->getSQLNameFor($sm)]))
						$to_remove[$t->getSQLNameFor($sm)] = array("where"=>array());
					foreach ($keys as $key)
						array_push($to_remove[$t->getSQLNameFor($sm)]["where"], array($col->name=>$key));
				}
			} else {
				if (!$this->bypass_security) {
					if (!$t->mayModify())
						throw new Exception("Access denied: cannot modify table '".$t->getName()."': necessary to remove link to table '".$table->getName()."'");
		
					if (!$t->canModifyAny()) {
						// select matching rows
						$q = SQLQuery::create();
						$table_alias = $q->generateTableAlias();
						$q->select(array($t->getName()=>$table_alias));
						if ($t->getModel() instanceof SubDataModel)
							$q->selectSubModel($t->getModel()->getParentTable(), $sub_model_instance);
						$t->prepareSelectToDetermineWriteAccess($q, $table_alias);
						$prepared_rows = $q->execute();
						if (count($prepared_rows) == 0) continue; // nothing to do there
						$allowed_rows = $t->filterWriteAccess($prepared_rows);
						if (count($allowed) <> count($prepared_rows))
							throw new Exception("Access denied: some rows cannot be modified in table '".$t->getName()."' but are linked to rows which need to be removed in table '".$table->getName()."'");
					}
				}
		
				// we can update
				array_push($to_update, array($t->getSQLNameFor($sm),$col->name,$keys));
			}
		}
	}
	
	
	public function dump() {
		$s = "SQL=<ul>";
		
		// sub models
		$s .= "<li>Sub Models: ";
		if (count($this->sub_models) == 0) $s .= "none";
		else {
			$first = true;
			foreach ($this->sub_models as $t=>$i) {
				if ($first) $first = false; else $s .= ", ";
				$s .= $t."=".$i;
			}
		}
		$s .= "</li>";
		
		// table
		$s .= "<li>SELECT tables: ";
		$first = true;
		foreach ($this->tables as $name=>$alias) {
			if ($first) $first = false; else $s .= ", ";
			$s .= "`".$name."`";
			if ($alias <> null) $s .= " AS `".$alias."`";
		}
		$s .= "</li>";
		
		// joins
		if (count($this->joins) > 0) {
			$s .= "<li>JOIN:<ul>";
			foreach ($this->joins as $j) {
				$s .= "<li>From `".$j[0]."` To `".$j[1]."`";
				if ($j[3] <> null) $s .= " AS `".$j[3]."`";
				$s .= " ON ";
				$first = true;
				foreach ($j[2] as $f1=>$f2) {
					if ($first) $first = false; else $s .= ", ";
					$s .= $f1."=".$f2;
				}
				$s .= "</li>";
			}
			$s .= "</ul></li>";
		}
		
		// fields
		$s .= "<li>FIELDS:<ul>";
		foreach ($this->fields as $f)
			$s .= "<li>".$f[0].".".$f[1]." AS ".$f[2]."</li>";
		$s .= "</ul></li>";
		
		$s .= "</ul>";
		return $s;
	}

	// some useful shortcuts
	public static function getTable($table, $sub_models = null) {
		return SQLQuery::create()->selectSubModels($sub_models)->select($table)->execute();
	}
	public static function getRow($table, $key, $sub_models = null) {
		require_once("component/data_model/Model.inc");
		$t = DataModel::get()->getTable($table);
		return SQLQuery::create()->selectSubModels($sub_models)->select($table)->where($t->getPrimaryKey()->name, $key)->executeSingleRow();
	}

	private static $transaction_started = false;
	public static function startTransaction() {
		SQLQuery::getDataBaseAccessWithoutSecurity()->log("START TRANSACTION", 0, 0, 0);
		SQLQuery::getDataBaseAccessWithoutSecurity()->startTransaction();
		SQLQuery::$transaction_started = true;
	}
	public static function commitTransaction() {
		SQLQuery::getDataBaseAccessWithoutSecurity()->log("COMMIT", 0, 0, 0);
		SQLQuery::getDataBaseAccessWithoutSecurity()->commit();
		SQLQuery::$transaction_started = false;
	}
	public static function rollbackTransaction() {
		SQLQuery::getDataBaseAccessWithoutSecurity()->log("ROLLBACK", 0, 0, 0);
		SQLQuery::getDataBaseAccessWithoutSecurity()->rollback();
		SQLQuery::$transaction_started = false;
	}
	public static function isTransactionStarted() {
		return SQLQuery::$transaction_started;
	}
	
}
?>