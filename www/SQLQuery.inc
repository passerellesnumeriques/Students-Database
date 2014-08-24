<?php
require_once("DataBaseSystem.inc");
/** 
 * Single way to access to the database: creates an SQL query, performs security checks, then executes the request 
 */
class SQLQuery {
	
	/** @var DataBaseSystem the connection to the database */
	private static $db_system = null;
	/** returns the direct connection to the database
	 * @return DataBaseSystem the connection to the database
	 */
	private static function &db() {
		if (SQLQuery::$db_system <> null) return SQLQuery::$db_system;
		global $db_config;
		require_once("DataBaseSystem_".$db_config["type"].".inc");
		$db_system_class = "DataBaseSystem_".$db_config["type"];
		SQLQuery::$db_system = new $db_system_class;
		$res = SQLQuery::$db_system->connect($db_config["server"], $db_config["user"], $db_config["password"]);
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
	public static function &getDataBaseAccessWithoutSecurity() { return SQLQuery::db(); }

 	/** utility method to ease chained usage: SQLQuery::create()->select(...)->field(...)->....
 	 * @return SQLQuery a new instance of SQLQuery
 	 */
 	public static function &create() { $q = new SQLQuery(); return $q; }

 	/** @var array Pre-selected sub models, automatically used with every new SQLQuery */
 	private static $preselected_submodels = array();
 	/** Pre-select sub models, which will be automatically selected in every new SQLQuery
 	 * @param string $key_table the table of the sub model
 	 * @param number $key the key value identifying the instance of the sub model
 	 */
 	public static function setSubModel($key_table, $key) {
 		if ($key <> null)
 			SQLQuery::$preselected_submodels[$key_table] = $key;
 		else
 			unset(SQLQuery::$preselected_submodels[$key_table]);
 	}
 	public static function getPreselectedSubModel($key_table) {
 		if (isset(SQLQuery::$preselected_submodels[$key_table]))
 			return SQLQuery::$preselected_submodels[$key_table];
 		return null;
 	}
 	
 	/**
 	 * Copy a SQLQuery 
 	 * @param SQLQuery $copy the query to copy
 	 */
 	public function __construct($copy = null) {
#DEV
 		$this->warning = true;
#END
 		if ($copy) {
 			$this->database = $copy->database;
 			$this->sub_models = array_merge($copy->sub_models);
 			$this->tables = array_merge($copy->tables);
 			$this->fields = array_merge($copy->fields);
 			$this->count = $copy->count <> null ? array_merge($copy->count) : null;
 			$this->count_one_field = $copy->count_one_field <> null ? array_merge($copy->count_one_field) : null;
 			$this->expressions = array_merge($copy->expressions);
 			$this->where = array_merge($copy->where);
 			$this->having = array_merge($copy->having);
 			$this->joins = array_merge($copy->joins);
 			$this->limit_from = $copy->limit_from;
 			$this->limit_number = $copy->limit_number;
 			$this->group_by = $copy->group_by;
 			$this->field_alias_counter = $copy->field_alias_counter;
 			$this->table_alias_counter = $copy->table_alias_counter;
 		}
 		$this->sub_models = array_merge(SQLQuery::$preselected_submodels);
 		require_once("component/data_model/Model.inc");
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
 	/** @var array list of SQL expression with alias that will be added to the select, as a result */
 	public $expressions = array();
 	/** @var array list of WHERE string expression, which will be joined by AND */
 	public $where = array();
 	/** @var array list of HAVING string expression, which will be joined by AND */
 	public $having = array();
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
#DEV
 	/** @var boolean indicates if we generate warning when a table is accessed from a component which does not own the table (only in Dev domain) */
 	public $warning;
#END 
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
	public function avoidAliasCollision($other_query) {
		$this->field_alias_counter = $other_query->field_alias_counter;
		$this->table_alias_counter = $other_query->table_alias_counter;
	}
	
#DEV
	/** Do not generate warnings */
	public function noWarning($no = true) {
		$this->warning = !$no;
		return $this;
	}
#END
	
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
	 * @return SQLQuery this
	 */
	public function &selectSubModels($sub_models) {
		if ($sub_models <> null) foreach ($sub_models as $key_table=>$key) $this->sub_models[$key_table] = $key;
		return $this;
	}
	/** Same as selectSubModel except that the table given is directly the Table object instead of the name of the table
	 * @param datamodel\Table $table_obj the table defining the sub model
	 * @param number $sub_model the key defining the sub model instance
	 * @return SQLQuery this
	 */
	public function &selectSubModelForTable($table_obj, $sub_model) {
		if ($sub_model == null) return $this;
		if (!($table_obj instanceof datamodel\Table))
			$table_obj = DataModel::get()->getTable($table_obj);
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
			for ($i = 0; $i < $nb; $i++)
				$this->selectTable(func_get_arg($i), null);
		} else {
			$o = func_get_arg(0);
			if (is_string($o))
				$this->selectTable($o, null);
			else foreach ($o as $table_name=>$alias)
				if (is_string($table_name))
					$this->selectTable($table_name, $alias);
				else
					$this->selectTable($alias, null);
		}
		return $this;
	}
	
	/** Select a table (the table in SELECT .. FROM table ..)
	 * @param string $table_name name of the table (not SQL name, but data model name)
	 * @param string|null $table_alias optionally specify an alias for the table
	 */
	public function &selectTable($table_name, $table_alias = null) {
		$this->tables[$table_name] = $table_alias;
#DEV
		$this->checkTableAccess($table_name);
#END
		return $this;
	}
#DEV
	/** Check which component is called, and generate a warning if the given table is not owned by this component
	 * @param string $table_name name of the table which is accessed
	 */
	private function checkTableAccess($table_name) {
		if (!$this->warning) return;
		try { throw new Exception(); }
		catch (Exception $e) {
			$root = str_replace("\\","/",realpath("component"))."/";
			foreach ($e->getTrace() as $trace) {
				$file = str_replace("\\","/",realpath($trace["file"]));
				if (substr($file,0,strlen($root)) <> $root) continue;
				$component = substr($file,strlen($root));
				$i = strpos($component, "/");
				if ($i === FALSE) continue;
				$component = substr($component,0,$i);
				if ($component == "data_model" && (
						substr($file,strlen($root),19) == "data_model/service/" ||
						substr($file,strlen($root)) == "data_model/Model.inc" ||
						substr($file,strlen($root)) == "data_model/DataDisplay.inc" ||
						substr($file,strlen($root)) == "data_model/DataPath.inc" ||
						substr($file,strlen($root)) == "data_model/DataBaseLock.inc" ||
						substr($file,strlen($root)) == "data_model/DataScreen.inc" ||
						substr($file,strlen($root)) == "data_model/page/data_screen.inc"
				)) break; // generic service of data model
				//if ($component == "data_model" && (
				//	substr($file,strlen($root)) == "data_model/page/data_screen.inc"	
				//)) continue; // generic display, let's see who call it
				$decl = DataModel::get()->internalGetTable($table_name)->declaring_component;
				if ($component <> $decl) {
					$msg = "Select table ".$table_name." from component ".$component." (".substr($file,strlen($root)).":".$trace["line"]."), but this table is own by ".$decl;
					$msg .="<br/>Full stack trace:<ul>";
					foreach ($e->getTrace() as $t)
						if (isset($t["file"]) && isset($t["line"]))
							$msg .= "<li>".$t["file"]." line ".$t["line"]."</li>";
					$msg .= "</ul>";
					PNApplication::warning($msg);
				}
				break;
			}
		}
	}
#END
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
				$this->field($a == null ? $t : $a, $table);
		else {
			foreach ($this->fields as $f)
				if ($f[0] == $table && $f[1] == $name && $f[2] == $alias)
					return $this;
			array_push($this->fields, array($table,$name,$alias));
		}
		return $this;
	}
	
	public function &fieldSubQuery($sub_q, $alias) {
		array_push($this->fields, array($sub_q, $alias));
		return $this;
	}
	
	/**
	 * Select all fields from the given table
	 * @param string $table name of the table
	 * @return SQLQuery this
	 */
	public function &fieldsOfTable($table) {
		$t = DataModel::get()->getTable($table);
		foreach ($t->getColumns($this->sub_models) as $col)
			$this->field($table, $col->name);
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
	 * @return SQLQuery this
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
	
	/** Add an expression as a selected field
	 * @param string $expr SQL expression
	 * @param string $alias alias
	 * @return SQLQuery this
	 */
	public function &expression($expr, $alias) {
		array_push($this->expressions, array($expr, $alias));
		return $this;
	}
	public function &sum($table, $field, $alias) {
		return $this->expression("SUM(`".DataModel::get()->getTable($table)->getSQLName($this->sub_models)."`.`".$field."`)", $alias);
	}
	
	/** Retrieve the alias used for the given field (either in select, or in a join)
	 * @param string $table table name
	 * @param string $field column name in the table
	 * @return string|null the alias, or null if it is not found
	 */
	public function getFieldAlias($table, $field) {
		foreach ($this->fields as $f) {
			if ($f[0] instanceof SQLQuery) continue; // sub query
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
					$this->where($field, "=", $value);
			else {
				if (trim($o) <> "")
					array_push($this->where, $o);
			}
		} else if ($nb == 2)
			$this->where(func_get_arg(0), "=", func_get_arg(1));
		else {
			$field = func_get_arg(0);
			$op = func_get_arg(1);
			$value = func_get_arg(2);
			$table = null;
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
				array_push($this->where, "`".$table."`.`".$field."`".$op."'".SQLQuery::escape($value)."'");
		}
		return $this;
	}
	
	public function &having($expr) {
		array_push($this->having, $expr);
		return $this;
	} 
	
	/**
	 * Resolve a table name into the alias or table name which should be used in a WHERE
	 * @param string $table the name to resolve
	 * @return string the resolved name
	 */
	private function resolveWhereTable($table) {
		// search if this is an alias
		foreach ($this->tables as $tname=>$talias)
			if ($table == $talias) return $table;
		foreach ($this->joins as $j)
			if ($table == $j[3]) return $table;
		// not an alias: get sql name
		try {
			if ($this->bypass_security)
				$t = DataModel::get()->internalGetTable($table);
			else
				$t = DataModel::get()->getTable($table);
			return $t->getSQLName($this->sub_models);
		} catch (Exception $e) {
			// not a table name
			return $table;
		}
	}
	/**
	 * Resolve a name into a table name: if this is an alias, the corresponding table name is returned
	 * @param string $name the name to resolve
	 * @return string the resolved name
	 */
	private function resolveTableName($name){
		// check if really table name
		try {
			DataModel::get()->internalGetTable($name);
			return $name;				
		} catch (Exception $e) {
			// not a table name
			// search if this is an alias
			foreach ($this->tables as $tname=>$talias)
				if ($name == $talias) return $tname;
			foreach ($this->joins as $j)
				if ($name == $j[3]) return $j[1];
		}
		return null;
	}
	
	/** WHERE `table_name`.`field_name`='value'
	 * @param string $table_name name of table
	 * @param string $field_name name of column
	 * @param string $value value
	 * @return SQLQuery this
	 */
	public function &whereValue($table_name, $field_name, $value) {
		$w = "`".$this->resolveWhereTable($table_name)."`.`".$field_name."`";
		if ($value === false) $value = 0;
		else if ($value === true) $value = 1;
		if ($value === null) $w .= " IS NULL";
		else $w .= "='".SQLQuery::escape($value)."'";
		array_push($this->where, $w);
		return $this;
	}
	/** WHERE `table_name`.`field_name`!='value'
	 * @param string $table_name name of table
	 * @param string $field_name name of column
	 * @param string $value value
	 * @return SQLQuery this
	 */
	public function &whereNotValue($table_name, $field_name, $value) {
		$w = "`".$this->resolveWhereTable($table_name)."`.`".$field_name."`";
		if ($value === false) $value = 0;
		else if ($value === true) $value = 1;
		if ($value === null) $w .= " IS NOT NULL";
		else $w .= "!='".SQLQuery::escape($value)."'";
		array_push($this->where, $w);
		return $this;
	}
	
	/** WHERE `table_name`.`field_name` IS NULL
	 * @param string $table_name name of table
	 * @param unknown $field_name name of column
	 * @return SQLQuery this
	 */
	public function &whereNull($table_name, $field_name) {
		$w = "`".$this->resolveWhereTable($table_name)."`.`".$field_name."` IS NULL";
		array_push($this->where, $w);
		return $this;
	}
	/** WHERE `table_name`.`field_name` IS NOT NULL
	 * @param string $table_name name of table
	 * @param unknown $field_name name of column
	 * @return SQLQuery this
	 */
	public function &whereNotNull($table_name, $field_name) {
		$w = "`".$this->resolveWhereTable($table_name)."`.`".$field_name."` IS NOT NULL";
		array_push($this->where, $w);
		return $this;
	}
	
	/** add a WHERE condition for the key of the given table
	 * @param string $table table name
	 * @param integer|array $key the key to select
	 * @return SQLQuery this
	 */
	public function &whereKey($table, $key) {
		if (!($table instanceof \datamodel\Table)) {
			if ($this->bypass_security)
				$table = DataModel::get()->internalGetTable($table);
			else
				$table = DataModel::get()->getTable($table);
		}
		$table_alias = $this->getTableAlias($table->getName());
		$k = $table->getPrimaryKey();
		if ($k <> null) $this->whereValue($table_alias, $k->name, $key);
		else {
			$k = $table->getKey();
			foreach ($k as $kname) {
				$this->whereValue($table_alias, $kname, $key[$kname]);
			}
		}
		return $this;
	}
	public function &whereKeyIn($table, $keys) {
		if (!($table instanceof \datamodel\Table)) {
			if ($this->bypass_security)
				$table = DataModel::get()->internalGetTable($table);
			else
				$table = DataModel::get()->getTable($table);
		}
		$table_alias = $this->getTableAlias($table->getName());
		$k = $table->getPrimaryKey();
		if ($k <> null) $this->whereIn($table_alias, $k->name, $keys);
		else {
			$k = $table->getKey();
			$where = "(";
			for ($i = 0; $i < count($keys); $i++) {
				if ($i > 0) $where .= " OR ";
				$where .= "(";
				$first = true;
				foreach ($k as $kname) {
					if ($first) $first = false; else $where .= " AND ";
					$where .= "`".$table_alias."`.`".$kname."`";
					$val = $keys[$i][$kname];
					if ($val === null) $where .= " IS NULL";
					else $where .= "='".SQLQuery::escape($val)."'";
				}
				$where .= ")";
			}
			$where .= ")";
			$this->where($where);
		}
		return $this;
	}
	
	/**
	 * Returns a string containing the current WHERE clause, and reset it in this SQLQuery (remove it)
	 * @return string the current WHERE clause
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
	
	/** WHERE `table`.`field` IN (values,...)
	 * Can be used with only 2 parameters: (field,values) in case the table does not need to be specified (only one table selected)
	 * @param string $table name of table
	 * @param string $field name of column
	 * @param array $values list of values to select
	 * @return SQLQuery this
	 */
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
			$w .= "'".SQLQuery::escape($value)."'";
		}
		$w .= ")";
		array_push($this->where, $w);
		return $this;
	}
	
	/** WHERE `table`.`field` NOT IN (values,...)
	 * Can be used with only 2 parameters: (field,values) in case the table does not need to be specified (only one table selected)
	 * @param string $table name of table
	 * @param string $field name of column
	 * @param array $values list of values to do not select
	 * @return SQLQuery this
	 */
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
			$w .= "'".SQLQuery::escape($value)."'";
		}
		$w .= ")";
		array_push($this->where, $w);
		return $this;
	}
	
	/** WHERE EXISTS (SELECT ...)
	 * 
	 * @param SQLQuery $sub_q the sub query
	 * @param string $join_query_table how to join the 2 queries: table in this query
	 * @param string $join_query_field how to join the 2 queries: field in this query
	 * @param string $join_sub_query_table how to join the 2 queries: table in the sub query
	 * @param string $join_sub_query_field how to join the 2 queries: field in the sub query
	 * @return SQLQuery this
	 */
	public function &whereExistsInSubQuery(&$sub_q, $join_query_table, $join_query_field, $join_sub_query_table, $join_sub_query_field) {
		$sub_q->where("`".$join_sub_query_table."`.`".$join_sub_query_field."`=`".$join_query_table."`.`".$join_query_field."`");
		// replace table aliases in sub query to avoid collisions
		$aliases = array();
		foreach ($sub_q->tables as $tname=>$talias)
			if ($talias <> null) array_push($aliases, $talias);
		foreach ($sub_q->joins as $join)
			if ($join[3] <> null)
				array_push($aliases, $join[3]);
		$sub_q->table_alias_counter = $this->table_alias_counter;
		$sql = $sub_q->generate();
		$this->table_alias_counter = $sub_q->table_alias_counter;
		foreach ($aliases as $sub_alias) {
			$new_alias = $this->generateTableAlias();
			$sql = str_replace("`".$sub_alias."`", "`".$new_alias."`", $sql);
		}
		$this->where("EXISTS (".$sql.")");
		return $this;
	}

	/** Make a LEFT JOIN
	 * @param string $table_from name of table from which we are
	 * @param string $table_to name of table to join
	 * @param string $fields_matching list of fields which should match (ON ...) in the form (field_from=&gt;field_to). Special cases:<ul><li>field_from is null: it means field_to is a list of (field=&gt;value) to select only specific values on the joined table</li><li>field_to is an array containing a single element: this element is a value, to select only specific value on the from table</li></ul>
	 * @param string|null $alias alias of the joined table, or null for no alias
	 * @param array|null $join_on_values list of specific values to join (field_to=&gt;value). This is equivalent to put a field_from null in the matching fields.
	 * @return SQLQuery this
	 */
	public function &join($table_from, $table_to, $fields_matching, $alias = null, $join_on_values = null) {
		$join = array($table_from, $table_to, $fields_matching, $alias);
		if ($join_on_values <> null) array_push($join, $join_on_values);
		array_push($this->joins, $join);
#DEV
		$this->checkTableAccess($table_to);
#END
		return $this;
	}
	/** Return the alias of the joined table corresponding to the given join
	 * @param string $table_from name of table from which we are
	 * @param string $table_to name of table to join
	 * @param string $fields_matching list of fields which should match (ON ...)
	 * @return SQLQuery this
	 */
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

	/** LIMIT from,number
	 * @param integer $from starting index
	 * @param integer $number maximum number of rows returned
	 * @return SQLQuery this
	 */
	public function &limit($from, $number) {
		$this->limit_from = $from;
		$this->limit_number = $number;
		return $this;
	}

	/** GROUP BY `table`.`field`
	 * Can be used with only 1 parameter: the field, in case only one table is selected
	 * @param string $table name of table
	 * @param string $field name of column
	 * @return SQLQuery this 
	 */
	public function &groupBy($table, $field=null) {
		if ($field == null) {
			$field = $table;
			foreach ($this->tables as $t=>$a) $table = $t;
		}
		array_push($this->group_by, array($table, $field));
		return $this;
	}

	/** ORDER BY ... Usage: (table,field,asc) or (alias,asc) or (alias)
	 * @param string $table name of table
	 * @param string $field name of column
	 * @param boolean $asc ASC or DESC
	 * @return SQLQuery this 
	 */
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
	
	/** When the SELECT is only used for counting rows, this will remove any unecessary joins */
	public function removeUnusefulJoinsForCounting() {
		// TODO temporarly deactivated: the problem is when the security checks add some conditions, it may impact so the count will not be accurate
		/*
		$used_tables = array();
		foreach ($this->where as $expr) {
			$s = $expr;
			while (strlen($s) > 0) {
				$i = strpos($s, "`.`");
				if ($i === FALSE) break;
				$start = strrpos($s, "`", -(strlen($s)-$i+1));
				$table_name = substr($s, $start+1, $i-$start-1);
				if (!in_array($table_name, $used_tables))
					array_push($used_tables, $table_name);
				$s = substr($s, $i+3);
			}
		}
		// get needed join to reach used tables
		do {
			$added = false;
			foreach ($used_tables as $name) {
				// check if it is in select from
				$ok = false;
				foreach ($this->tables as $tname=>$talias)
					if ($name == $tname || $name == $talias) { $ok = true; break; }
				if ($ok) continue;
				// if it comes from a join, the table used for the join is needed as well
				foreach ($this->joins as $j) {
					if ($name == $j[1] || $name == $j[3]) {
						if (!in_array($j[0], $used_tables)) {
							array_push($used_tables, $j[0]);
							$added = true;
							break;
						}
					}
				}
			}
		} while ($added);
		// remove joins on non-used tables
		for ($i = 0; $i < count($this->joins); $i++) {
			$j = $this->joins[$i];
			if (!in_array($j[1], $used_tables) && !in_array($j[3], $used_tables)) {
				array_splice($this->joins, $i, 1);
				$i--;
			}
		}
		*/
	}

	/** Generate the SELECT
	 * @return string the SELECT query generated
	 */
	public function generate() {
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
				$join_allowed = false;
				$read_access_from_previous_table = $table->getReadAccessFromForeignTable();
				if (count($read_access_from_previous_table) > 0){
					$join_table_resolved = $this->resolveTableName($join[0]);
					foreach ($read_access_from_previous_table as $previous_table_name){
						if($previous_table_name == $join_table_resolved){
							$join_allowed = true; // access is allowed by the previous table
							break;
						}
					}
				}
				if (!$join_allowed) {
					if ($alias == null) $alias = $table->getSQLName($this->sub_models);
					$table->filterReadAccess($this, $alias);
				}
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
			if (count($this->fields) == 0 && $this->count === null && count($this->expressions) == 0) {
				if ($this->bypass_security)
					$columns = $table->internalGetColumns($this->sub_models);
				else
					$columns = $table->getColumns($this->sub_models);
				foreach ($columns as $col)
					array_push($final_fields, array($alias, $col->name, null));
				unset($columns);
			} else {
				for ($i = 0; $i < count($fields); $i++) {
					$f = $fields[$i];
					if ($f[0] instanceof SQLQuery) {
						// sub query will be validated by itself
						array_push($final_fields, $f);
						array_splice($fields,$i,1);
						$i--;
						continue;
					}
					if ($f[0] == $t || $f[0] == $alias) {
						$table_name = $f[0] == $t ? $table->getSQLName($this->sub_models) : $alias;
						// check the field is accessible
						if ($this->bypass_security)
							$col = $table->internalGetColumn($f[1], $this->sub_models);
						else
							$col = $table->getColumn($f[1], $this->sub_models);
						if ($col == null)
							throw new Exception("Unknown column ".$f[1]." in table ".$table->getName());
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
			$table = DataModel::get()->internalGetTable($join[1]);
			if (!$this->bypass_security) {
				$join_allowed = false;
				$read_access_from_previous_table = $table->getReadAccessFromForeignTable();
				if (count($read_access_from_previous_table) > 0){
					$join_table_resolved = $this->resolveTableName($join[0]);
					foreach ($read_access_from_previous_table as $previous_table_name){
						if($previous_table_name == $join_table_resolved){
							$join_allowed = true; // access is allowed by the previous table
							break;
						}
					}
				}
				if (!$join_allowed && !$table->hasReadFilter() && !$table->hasReadRights()) {
					throw new Exception("Access denied to table ".$table->getName()." in request:".$this->dump());
				}
			}
			
			
			$alias = $join[3];
			if ($alias == null) $alias = $table->getSQLName($this->sub_models);
			if (count($this->fields) == 0 && $this->count_one_field === null && $this->count === null && count($this->expressions) == 0) {
				if ($this->bypass_security || $join_allowed)
					$columns = $table->internalGetColumns($this->sub_models);
				else
					$columns = $table->getColumns($this->sub_models);
				foreach ($columns as $col)
					array_push($final_fields, array($alias, $col->name, null));
				unset($columns);
			} else {
				for ($i = 0; $i < count($fields); $i++) {
					$f = $fields[$i];
					if ($f[0] == $join[1] || $f[0] == $alias) {
						// check the field is accessible
						if ($this->bypass_security || $join_allowed)
							$col = $table->internalGetColumn($f[1], $this->sub_models);
						else
							$col = $table->getColumn($f[1], $this->sub_models);
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
			PNApplication::errorHTML("Field '".$f[1]."' on table '".$f[0]."' does not exist in SQL query: ".$this->dump());

		$this->fields = $final_fields;
		
		$sql = "SELECT ";
		if (count($this->fields) == 0 && $this->count_one_field === null && $this->count === null && count($this->expressions) == 0 && !$this->bypass_security)
			throw new Exception("Access denied: you don't have access to any column in this request");
		if (count($this->fields) == 0 && $this->count_one_field === null && $this->count === null && count($this->expressions) == 0 && $this->bypass_security){
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
				if ($f[0] instanceof SQLQuery) {
					// this is a sub query
					$sql .= "(".$f[0]->generate().") AS `".$f[1]."`";
				} else {
					// normal field
					$sql .= "`".$f[0]."`.`".$f[1]."`";
					if ($f[2] <> null) $sql .= " AS `".$f[2]."`";
				}
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
			foreach ($this->expressions as $e) {
				if ($first) $first = false; else $sql .= ",";
				$sql .= $e[0]." AS `".$e[1]."`";
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
				if ($field_from <> null) {
					if ($first) $first = false; else $sql .= " AND ";
					$sql .= "`".$source_table."`.`".$field_from."`=";
					if (is_string($field_to))
						$sql .= "`".($join[3] <> null ? $join[3] : $table->getSQLName($this->sub_models))."`.`".$field_to."`";
					else
						$sql .= "'".SQLQuery::escape($field_to[0])."'";
				} else {
					if (!is_array($field_to)) throw new Exception("Invalid join from ".$source_table." to ".$table->getName().": ".$this->dump());
					foreach ($field_to as $fi=>$val) {
						if ($first) $first = false; else $sql .= " AND ";
						$sql .= "`".($join[3] <> null ? $join[3] : $table->getSQLName($this->sub_models))."`.`".$fi."`";
						if ($val === null) $sql .= " IS NULL";
						$sql .= "='".SQLQuery::escape($val)."'";
					}
				}
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
		if (count($this->having) > 0) {
			$sql .= " HAVING ";
			$first = true;
			foreach ($this->having as $w) {
				if ($first) $first = false; else $sql .= " AND ";
				$sql .= $w;
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

	/** Execute the SELECT, and return all rows as an array
	 * @return array rows returned from the database
	 */
	public function execute() {
		$sql = $this->generate();
		$res = SQLQuery::db()->execute($sql);
		if ($res === FALSE) return null;
		$a = array();
		while (($r = SQLQuery::db()->nextRow($res)) !== FALSE) {
			foreach ($r as $key=>$val)
				if ($val <> null) $r[$key] = $val;
			array_push($a, $r);
		}
		return $a;
	}

	/** Execute a SELECT containing a single field(column) to return, and return an array of values from this field
	 * @return array list of values
	 */
	public function executeSingleField() {
		$a = $this->execute();
		if ($a === null) return null;
		$a2 = array();
		foreach ($a as $r) foreach ($r as $col=>$val) { array_push($a2, $val); break; }
		return $a2;
	}

	/** Execute a SELECT which should return a single field in a single row, meaning a single value.
	 * @return mixed the value or null
	 */
	public function executeSingleValue() {
		$a = $this->executeSingleField();
		if ($a === null) return null;
		if (count($a) == 0) return null;
		return $a[0];
	}

	/** Execute a SELECT which should return a single row, and return this row
	 * @return array the row
	 */
	public function executeSingleRow() {
		$sql = $this->generate();
		$res = SQLQuery::db()->execute($sql);
		if ($res === FALSE) return null;
		$r = SQLQuery::db()->nextRow($res);
		if ($r === FALSE) return null;
		foreach ($r as $key=>$val)
			if ($val <> null) $r[$key] = $val;
		return $r;
	}
	
	/** Only to display error messages, return the name of a column, based on DataDisplay
	 * @param string $table name of the table
	 * @param string $column name of the column
	 * @return string name to display in error messages
	 */
	private function getColumnName($table, $column) {
		$display = DataModel::get()->getTableDataDisplay($table->getName());
		if ($display == null) return $column;
		$sub_model = $this->getTableSubModel($table);
		foreach ($display->getDataDisplay(null,$sub_model) as $data) {
			foreach ($data->getHandledColumns() as $col)
				if ($col == $column) return $data->getDisplayName();
		}
		return $column;
	}
	private function getTableSubModel($table) {
		if (!($table->getModel() instanceof SubDataModel)) return null;
		$pt = $table->getModel()->getParentTable();
		return $this->sub_models[$pt];
	}

	/** UPDATE a row based on its key
	 * @param string $table name of table to update
	 * @param integer|array $key key identifying the row to update
	 * @param array $fields_values list of (column=&gt;value) to update
	 * @param integer|null $lock_id ID of the lock if the table or row is already locked, if null a new lock will be created (if a lock exists, specifying it will improve performance) 
	 */
	public function updateByKey($table, $key, $fields_values, $lock_id = null) {
		$in_transaction = SQLQuery::isTransactionStarted();
		try {
			if (!$in_transaction) SQLQuery::startTransaction();
			/* @var $t datamodel\Table */
			if ($this->bypass_security)
				$t = DataModel::get()->internalGetTable($table);
			else
				$t = DataModel::get()->getTable($table);
			
			if ($t->hasBeforeUpdateListeners()) {
				$sub_model_instance = null;
				if ($t->getModel() instanceof SubDataModel)
					$sub_model_instance = @$this->sub_models[$t->getModel()->getParentTable()];
				$t->fireBeforeUpdate($fields_values, $key, $sub_model_instance);
			}
			
			// check we are allowed to modify this row
			if (!$this->bypass_security) {
				if (!$t->mayModify()) throw new Exception("Access denied to modify table ".$t->getName());
				
				$ok = true;
				if (!$t->canModifyEverything())
					foreach ($fields_values as $colname=>$colvalue)
						if (!$t->canModifyColumn($colname)) { $ok = false; break; }
				
				if (!$ok) {
					$q = SQLQuery::create()->bypassSecurity()
#DEV
						->noWarning()
#END
						;
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
			}
	
			// lock table
			$locked = false;
			require_once("component/data_model/DataBaseLock.inc");
			if ($lock_id <> null) {
				$column = null;
				if (count($fields_values) == 1) { $column = array_keys($fields_values); $column = $column[0]; }
				$err = DataBaseLock::checkLock($lock_id, $t->getSQLName($this->sub_models), $key, $column);
				if ($err <> null)
					throw new Exception("Access denied: invalid lock (".$err.")");
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
				if ($col->unique && !($col instanceof PrimaryKey) && !($col instanceof ForeignKey)) {
					$q = SQLQuery::create()->bypassSecurity()
#DEV
						->noWarning()
#END
						->select($t->getName())->selectSubModels($this->sub_models)->where($col->name, $fields_values[$field]);
					$where = "";
					if ($t->getPrimaryKey() <> null) {
						$where .= "`".$t->getPrimaryKey()->name."`!='".$key."'";
					} else {
						$keys = $t->getKey();
						$first = true;
						$where .= "(";
						foreach ($keys as $key_name) {
							if ($first) $first = false; else $where .= " OR ";
							$where .= "`".$key_name."`!='".$key[$key_name]."'";
						}
						$where .= ")";
					}
					$r = $q->where($where)->executeSingleRow();
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
					$sql .= "'".SQLQuery::escape($value)."'";
			}
			$sql .= " WHERE ";
			if ($t->getPrimaryKey() <> null) {
				$sql .= "`".$t->getPrimaryKey()->name."`='".$key."'";
			} else {
				$keys = $t->getKey();
				$first = true;
				foreach ($keys as $key_name) {
					if ($first) $first = false; else $sql .= " AND ";
					$sql .= "`".$key_name."`";
					if ($key[$key_name] === null)
						$sql .= " IS NULL";
					else
						$sql .= "='".SQLQuery::escape($key[$key_name])."'";
				}
			}
			SQLQuery::db()->execute($sql);
			if ($locked) {
				$error = DataBaseLock::unlock($lock_id);
				if ($error <> null) throw new Exception($error);
			}
			if ($t->hasUpdateListeners()) {
				$sub_model_instance = null;
				if ($t->getModel() instanceof SubDataModel)
					$sub_model_instance = @$this->sub_models[$t->getModel()->getParentTable()];
				$t->fireUpdate($fields_values, $key, $sub_model_instance);
			}
			if (!$in_transaction) SQLQuery::commitTransaction();
		} catch (Exception $e) {
			if (!$in_transaction) SQLQuery::rollbackTransaction();
			throw $e;
		}
	}

	/** UPDATE several rows, based on their key. Several keys can be updated with same value.
	 * @param string $table table name
	 * @param array $keys_fields_values array of array(array(key1,key2...), array(field=&gt;value))
	 * @param integer|null $lock_id ID of the lock if the table is already locked, if null a new lock will be created (if a lock exists, specifying it will improve performance) 
	 */
	public function updateByKeys($table, $keys_fields_values, $lock_id = null) {
		$in_transaction = SQLQuery::isTransactionStarted();
		try {
			if (!$in_transaction) SQLQuery::startTransaction();
			/* @var $t datamodel\Table */
			if ($this->bypass_security)
				$t = DataModel::get()->internalGetTable($table);
			else
				$t = DataModel::get()->getTable($table);
		
			if ($t->hasBeforeUpdateListeners()) {
				$sub_model_instance = null;
				if ($t->getModel() instanceof SubDataModel)
					$sub_model_instance = @$this->sub_models[$t->getModel()->getParentTable()];
				for ($i = 0; $i < count($keys_fields_values); $i++)
					for ($j = 0; $j < count($keys_fields_values[$i][0]); $j++)
						$t->fireBeforeUpdate($keys_fields_values[$i][1], $keys_fields_values[$i][$j], $sub_model_instance);
			}
				
			// check we are allowed to modify this row
			if (!$this->bypass_security) {
				if (!$t->mayModify()) throw new Exception("Access denied to modify table ".$t->getName());
				
				$ok = true;
				if (!$t->canModifyEverything())
					foreach ($keys_fields_values as $k) {
						$keys = $k[0];
						$fields_values = $k[1];
						foreach ($fields_values as $colname=>$colvalue)
							if (!$t->canModifyColumn($colname)) { $ok = false; break; }
						if (!$ok) break;
					}
				
				if (!$ok) {
					$q = SQLQuery::create()->bypassSecurity()
#DEV
						->noWarning()
#END
						;
					$table_alias = $q->generateTableAlias();
					$q->select(array($table=>$table_alias));
					$q->selectSubModels($this->sub_models);
					if ($t->getPrimaryKey() <> null) {
						$q->field($t->getPrimaryKey()->name);
						$keys = array();
						foreach ($keys_fields_values as $k) foreach ($k[0] as $key) array_push($keys, $key);
						$q->whereIn($table_alias, $t->getPrimaryKey()->name, $keys);
					} else {
						$tkeys = $t->getKey();
						$q->field($tkeys[0]);
						$w = "";
						foreach ($keys_fields_values as $k) {
							$keys = $k[0];
							foreach ($keys as $key) {
								$kw = "";
								foreach ($tkeys as $key_name) {
									if (strlen($kw) > 0) $kw .= " AND ";
									$kw .= "`".$table_alias."`.`".$key_name."`='".SQLQuery::escape($key[$key_name])."'";
								}
								if (strlen($w) > 0) $w .= " OR ";
								$w .= "(".$kw.")";
							}
						}
						$q->where($w);
					}
					if ($t->prepareSelectToDetermineWriteAccess($q, $table_alias)) {
						$rows = $q->execute();
						$rows = $t->filterWriteAccess($rows);
						$nb_keys = 0;
						foreach ($keys_fields_values as $k) $nb_keys += count($k[0]);
						if (count($rows) <> $nb_keys) throw new Exception("Access denied to modify ".($nb_keys-count($rows))." rows among the ".($nb_keys)." to update in table ".$t->getName());
					}
				}
			}
		
			// lock table
			$locked = false;
			require_once("component/data_model/DataBaseLock.inc");
			if ($lock_id <> null) {
				$column = null;
				$columns = array();
				$key = null;
				$nb_keys = 0;
				foreach ($keys_fields_values as $k) {
					$keys = $k[0];
					if (count($keys) == 1 && $nb_keys == 0) $key = $keys[0];
					$nb_keys += count($keys);
					$fields_values = $k[1];
					foreach ($fields_values as $field=>$value)
						if (!in_array($field, $columns)) array_push($columns, $field);
				}
				if (count($columns) == 1) $column = $columns[0];
				$err = DataBaseLock::checkLock($lock_id, $t->getSQLName($this->sub_models), $key, $column);
				if ($err <> null)
					throw new Exception("Access denied: invalid lock (".$err.")");
			} else {
				$locker = null;
				$keys = array();
				foreach ($keys_fields_values as $k) foreach ($k[0] as $key) array_push($keys, $key);
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
				$keys = $k[0];
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
				if ($col->unique && !($col instanceof \datamodel\PrimaryKey)) {
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
					$q = SQLQuery::create()
						->bypassSecurity()
#DEV
						->noWarning()
#END
						->select($t->getName())
						->selectSubModels($this->sub_models)
						->whereIn($col->name, $values)
						;
						if ($t->getPrimaryKey() <> null) {
							$keys = array();
							foreach ($keys_fields_values as $k) foreach ($k[0] as $key) array_push($keys, $key);
							$q->whereNotIn($t->getName(), $t->getPrimaryKey()->name, $keys);
						} else {
							$tkeys = $t->getKey();
							$w = "";
							foreach ($keys_fields_values as $k) {
								$keys = $k[0];
								foreach ($keys as $key) {
									$kw = "(";
									foreach ($tkeys as $key_name) {
										if (strlen($kw) > 0) $kw .= " AND ";
										$kw .= "`".$table_alias."`.`".$key_name."`!='".SQLQuery::escape($key[$key_name])."'";
									}
									$kw .= ")";
									if (strlen($w) > 0) $w .= " OR ";
									$w .= "(".$kw.")";
								}
							}
							$q->where($w);
						}
						$r = $q->execute();
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
				$keys = $k[0];
				$fields_values = $k[1];
				$sql = "UPDATE `".$t->getSQLName($this->sub_models)."` SET ";
				$first = true;
				foreach ($fields_values as $field=>$value) {
					if ($first) $first = false; else $sql .= ",";
					$sql .= "`".$field."`=";
					if ($value === null)
						$sql .= "NULL";
					else
						$sql .= "'".SQLQuery::escape($value)."'";
				}
				$sql .= " WHERE ";
				if ($t->getPrimaryKey() <> null) {
					if (count($keys) == 1)
						$sql .= "`".$t->getPrimaryKey()->name."`='".$keys[0]."'";
					else {
						$sql .= "`".$t->getPrimaryKey()->name."` IN (";
						$fk = true;
						foreach ($keys as $k) {
							if ($fk) $fk = false; else $sql .= ",";
							$sql .= "'".$k."'";
						}
						$sql .= ")";
					}
				} else {
					$tkeys = $t->getKey();
					$w = "";
					foreach ($keys as $key) {
						$kw = "(";
						foreach ($tkeys as $key_name) {
							if (strlen($kw) > 1) $kw .= " AND ";
							$kw .= "`".$key_name."`='".SQLQuery::escape($key[$key_name])."'";
						}
						$kw .= ")";
						if (strlen($w) > 0) $w .= " OR ";
						$w .= "(".$kw.")";
					}
					$sql .= $w;
				}
				SQLQuery::db()->execute($sql);
				if ($t->hasUpdateListeners()) {
					$sub_model_instance = null;
					if ($t->getModel() instanceof SubDataModel)
						$sub_model_instance = @$this->sub_models[$t->getModel()->getParentTable()];
					foreach ($keys as $k)
						$t->fireUpdate($fields_values, $k, $sub_model_instance);
				}
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
	 * @param string $table table name
	 * @param array $keys list of keys identifying the rows to update
	 * @param array $fields_values list of (column=&gt;value) specifying the values to update
	 * @param integer|null $lock_id ID of the lock if the table is already locked, if null a new lock will be created (if a lock exists, specifying it will improve performance) 
	 */
	public function updateAllKeys($table, $keys, $fields_values, $lock_id = null) {
		if (count($keys) == 0) return;
		
		$in_transaction = SQLQuery::isTransactionStarted();
		try {
			if (!$in_transaction) SQLQuery::startTransaction();
			/* @var $t datamodel\Table */
			if ($this->bypass_security)
				$t = DataModel::get()->internalGetTable($table);
			else
				$t = DataModel::get()->getTable($table);
		
			if ($t->hasBeforeUpdateListeners()) {
				$sub_model_instance = null;
				if ($t->getModel() instanceof SubDataModel)
					$sub_model_instance = @$this->sub_models[$t->getModel()->getParentTable()];
				foreach ($keys as $key)
					$t->fireBeforeUpdate($fields_values, $key, $sub_model_instance);
			}
				
			// check we are allowed to modify this row
			if (!$this->bypass_security) {
				if (!$t->mayModify()) throw new Exception("Access denied to modify table ".$t->getName());
		
				$ok = true;
				if (!$t->canModifyEverything())
					foreach ($fields_values as $colname=>$colvalue)
					if (!$t->canModifyColumn($colname)) { $ok = false; break; }
				
				if (!$ok) {
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
				$err = DataBaseLock::checkLock($lock_id, $t->getSQLName($this->sub_models), $key, $column);
				if ($err <> null)
					throw new Exception("Access denied: invalid lock (".$err.")");
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
					$sql .= "'".SQLQuery::escape($value)."'";
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
			if ($t->hasUpdateListeners()) {
				$sub_model_instance = null;
				if ($t->getModel() instanceof SubDataModel)
					$sub_model_instance = @$this->sub_models[$t->getModel()->getParentTable()];
				foreach ($keys as $k)
					$t->fireUpdate($fields_values, $k, $sub_model_instance);
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
	
	/**
	 * Insert an entry into the given table.
	 * @param string $table table name
	 * @param array $fields_values associated array with column name =&gt; value
	 * @param integer|null $lock_id ID of the lock if the table is already locked, if null a new lock will be created (if a lock exists, specifying it will improve performance) 
	 * @return integer the ID of the inserted data if any
	 * @throws Exception in case of error (security or database)
	 */
	public function insert($table, $fields_values, $lock_id = null) {
		/* @var $t datamodel\Table */
		if ($this->bypass_security)
			$t = DataModel::get()->internalGetTable($table);
		else
			$t = DataModel::get()->getTable($table);

		// check we are allowed
		if (!$this->bypass_security) {
			if (!$t->mayInsert()) throw new Exception("Access denied: add data to table ".$t->getName());
			if (!$t->acceptInsert($fields_values)) {
				$msg = "Access denied: add data to table ".$t->getName()." denied for those values:\n";
				foreach ($fields_values as $field=>$value)
					$msg .= " ".toHTML($field)." = ".toHTML($value)."\n";
				throw new Exception($msg);
			}
		}
		// lock the table to check it is not locked by anyone else
		require_once("component/data_model/DataBaseLock.inc");
		$locker = null;
		if($lock_id <> null){
			$column = null;
			if (count($fields_values) == 1) { $column = array_keys($fields_values); $column = $column[0]; }
			$err = DataBaseLock::checkLock($lock_id, $t->getSQLName($this->sub_models), null, $column);
			if ($err <> null)
				throw new Exception("Access denied: invalid lock (".$err.")");
			else 
				$lock = $lock_id;
		} else {
			$lock = DataBaseLock::lockTable($t->getSQLName($this->sub_models), $locker, true);
			if ($lock == null)
				throw new Exception("Access denied: the table ".$t->getName()." is locked by ".$locker);
		}
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
			if ($col instanceof \datamodel\PrimaryKey) continue;
			if ($col->unique) {
				if (!isset($fields_values[$col->name])) {
					DataBaseLock::unlock($lock);
					throw new Exception("Missing column ".$col->name." to insert data in table ".$t->getName());
				}
				$q = SQLQuery::create()
#DEV
					->noWarning(!$this->warning)		
#END
					->bypassSecurity($this->bypass_security);
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
			if ($value === null) $sql .= "NULL"; else $sql .= "'".SQLQuery::escape($value)."'";
		}
		$sql .= ")";
		SQLQuery::db()->execute($sql);
		$id = SQLQuery::db()->getInsertID();
		// sub models
		foreach ($t->_key_submodels as &$sm)
			$sm->createTables($id);
		// unlock the table
		if($lock_id == null)
			DataBaseLock::unlock($lock);
		// events
		$t->fireInsert($fields_values, $id, $t->getModel() instanceof SubDataModel ? @$this->sub_models[$t->getModel()->getParentTable()] : null);
		return $id;
	}
	
	/**
	 * Insert multiple entries into the given table.
	 * @param string $table table name
	 * @param array $rows array of array of column name =&gt; value
	 * @param integer|null $lock_id ID of the lock if the table is already locked, if null a new lock will be created (if a lock exists, specifying it will improve performance)
	 * @return array list of ID of the inserted rows 
	 * @throws Exception in case of error (security or database)
	 */
	public function insertMultiple($table, $rows, $lock_id = null) {
		/* @var $t datamodel\Table */
		if ($this->bypass_security)
			$t = DataModel::get()->internalGetTable($table);
		else
			$t = DataModel::get()->getTable($table);
	
		// check we are allowed
		if (!$this->bypass_security) {
			if (!$t->mayInsert()) throw new Exception("Access denied: add data to table ".$t->getName());
			foreach ($rows as $fields_values) {
				if (!$t->acceptInsert($fields_values)) {
					$msg = "Access denied: add data to table ".$t->getName()." denied for those values:\n";
					foreach ($fields_values as $field=>$value)
						$msg .= " ".toHTML($field)." = ".toHTML($value)."\n";
					throw new Exception($msg);
				}
			}
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
			if ($col instanceof \datamodel\PrimaryKey) continue;
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
						throw new Exception("Missing column ".$col->name." to insert data in table ".$t->getName().". Given columns are: ".var_export($fields_values, true));
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
				if ($value === null) $sql .= "NULL"; else $sql .= "'".SQLQuery::escape($value)."'";
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
				$id = $row["LAST_INSERT_ID()"];
				for ($i = 0; $i < $nb_rows; $i++)
					array_push($ids, $id+$i);
			}
		}
		// sub models
		foreach ($t->_key_submodels as &$sm)
			$sm->createTables($id);
		// unlock the table
		if($lock_id == null)
			DataBaseLock::unlock($lock);
		// events
		$i = 0;
		foreach ($rows as &$fields_values)
			$t->fireInsert($fields_values, $id <> 0 ? $ids[$i++] : null, $t->getModel() instanceof SubDataModel ? @$this->sub_models[$t->getModel()->getParentTable()] : null);
		return $ids;
	}
	
	/** Remove the data having the given key, and all linked data
	 * @param string $table table name
	 * @param integer|array $key key identifying the row to remove 
	 */
	public function removeKey($table, $key) {
		$this->removeKeys($table, array($key));		
	}
	/** Remove entries corresponding to the given rows
	 * @param string $table table name
	 * @param array $rows rows previously retrieved by a SELECT, from which we can extract the keys 
	 */
	public function removeRows($table, $rows) {
		if (count($rows) == 0) return;
		$keys = array();
		/* @var $t datamodel\Table */
		if ($this->bypass_security)
			$t = DataModel::get()->internalGetTable($table);
		else
			$t = DataModel::get()->getTable($table);
		$pk = $t->getPrimaryKey();
		if ($pk <> null) {
			foreach ($rows as $r) array_push($keys, $r[$pk->name]);
		} else {
			$k = $t->getKey();
			foreach ($rows as $r) {
				$key = array();
				foreach ($k as $kname) $key[$kname] = $r[$kname];
				array_push($keys, $key);
			}
		}
		$this->removeKeys($table, $keys);
	}
	public function removeRow($table, $row) {
		$this->removeRows($table, array($row));
	}
	
	/** Remove data with given keys
	 * @param string $table table name
	 * @param array $keys list of keys identifying the rows to remove 
	 */
	public function removeKeys($table, $keys) {
		$in_transaction = SQLQuery::isTransactionStarted();
		try {
			if (!$in_transaction) SQLQuery::startTransaction();
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
				$this->browseTablesForRemove($t, $sub_model_instance, $keys, null, $locks, $to_remove, $to_update, $sub_models_to_remove);
			} catch (Exception $e) {
				foreach ($locks as $lock) DataBaseLock::unlock($lock);
				throw new Exception("Remove from table '".$table."': ".$e->getMessage(), null, $e);
			}
			
			// everything is allowed, and all tables are locked: do it
			foreach ($to_remove as $table_name=>$to_remove_in_table) {
				if (isset($to_remove_in_table["keys"])) {
					$where = "";
					if (is_array($to_remove_in_table["key_name"])) {
						$first_key = true;
						foreach ($to_remove_in_table["keys"] as $key) {
							if ($first_key) $first_key = false; else $where .= " OR ";
							$where .= "(";
							$first = true;
							foreach ($to_remove_in_table["key_name"] as $key_name) {
								if ($first) $first = false; else $where .= " AND ";
								$where .= "`".$key_name."`";
								if ($key[$key_name] == null)
									$where .= " IS NULL";
								else
									$where .= "='".SQLQuery::escape($key[$key_name])."'";
							}
							$where .= ")";
						}					
					} else {
						if (count($to_remove_in_table["keys"]) > 0) {
							$where .= "`".$to_remove_in_table["key_name"]."` IN (";
							$first = true;
							foreach ($to_remove_in_table["keys"] as $key) {
								if ($first) $first = false; else $where .= ",";
								$where .= "'".self::escape($key)."'";
							}
							$where .= ")";
						}
					}
					if ($where <> "") {
						$dm_table = DataModel::get()->getTableFromSQLName($table_name, $this->bypass_security);
						if ($dm_table->hasRemoveListeners()) {
							$sub_model = DataModel::get()->getSubModelInstanceFromSQLName($table_name);
							$res = SQLQuery::db()->execute("SELECT * FROM `".$table_name."` WHERE ".$where);
							while (($row = SQLQuery::db()->nextRow($res)) <> null)
								$dm_table->fireRemove($row, $sub_model);
						}
						$sql = "DELETE FROM `".$table_name."` WHERE ".$where;
						SQLQuery::db()->execute($sql);
					}
				} else {
					$w = "";
					$first = true;
					foreach ($to_remove_in_table["where"] as $where) {
						if ($first) $first = false; else $w .= " OR ";
						$w .= "(";
						$f = true;
						foreach ($where as $col=>$val) {
							if ($f) $f = false; else $w .= " AND ";
							$w .= "`".$col."`='".SQLQuery::escape($val)."'";
						}
						$w .= ")";
					}
					if ($w <> "") {
						$dm_table = DataModel::get()->getTableFromSQLName($table_name, $this->bypass_security);
						if ($dm_table->hasRemoveListeners()) {
							$sub_model = DataModel::get()->getSubModelInstanceFromSQLName($table_name);
							$res = SQLQuery::db()->execute("SELECT * FROM `".$table_name."` WHERE ".$w);
							while (($row = SQLQuery::db()->nextRow($res)) <> null)
								$dm_table->fireRemove($row, $sub_model);
						}
						SQLQuery::db()->execute("DELETE FROM `".$table_name."` WHERE ".$w);
					}
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
				// TODO fire update
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
	
	/** Remove rows from <i>table</i> having a link to the row <i>linked_key</i> in table <i>linked_table</i>.
	 * @param string $table the table from where to remove
	 * @param string $linked_table the foreign table
	 * @param integer $linked_key the key in the foreign table
	 */
	public function removeLinkedData($table, $linked_table, $linked_key) {
		$q = SQLQuery::create()->selectSubModels($this->sub_models)->select(array($table=>"the_table"));
		$lt = $this->bypass_security ? DataModel::get()->internalGetTable($linked_table) : DataModel::get()->getTable($linked_table);
		$where = "";
		$cols = $this->bypass_security ? $lt->internalGetColumns($this->sub_models) : $lt->getColumns($this->sub_models);
		foreach ($cols as $col)
			if ($col instanceof \datamodel\ForeignKey && $col->foreign_table == $table) {
				if (strlen($where) > 0) $where .= " OR ";
				$where .= "(`the_table`.`".$col->name."` = '".$linked_key."')";
			}
		if (strlen($where) == 0) return; // no link!
		$q->where($where);
		$rows = $q->execute();
		$this->removeRows($table, $rows);
	}
	
	/** Go through the datamodel to determine what should be removed and updated when removing rows from a table
	 * @param datamodel\Table $table the table where rows are going to be removed
	 * @param string $sub_model_instance sub model or null
	 * @param array $keys list of keys identifying the rows which are going to be removed
	 * @param array $locks list of locks created while going through the datamodel
	 * @param array $to_remove list of entries to remove
	 * @param array $to_update list of entries to update with NULL value to remove links
	 * @param array $sub_models_to_remove list of sub models which will be removed
	 */
	private function browseTablesForRemove(&$table, $sub_model_instance, $keys, $from_table, &$locks, &$to_remove, &$to_update, &$sub_models_to_remove) {
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
		$lock_id = DataBaseLock::lockTable($table->getSQLNameFor($sub_model_instance), $locked_by, $this->bypass_security || ($from_table <> null && $table->canRemoveFromForeignTable($from_table)));
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
		if (!$this->bypass_security && ($from_table == null || !$table->canRemoveFromForeignTable($from_table))) {
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
			if ($this->bypass_security)
				$ft = DataModel::get()->internalGetTable($col->foreign_table);
			else
				$ft = DataModel::get()->getTable($col->foreign_table);
			$this->browseTablesForRemove($ft, $sub_model, $rows, $table->getName(), $locks, $to_remove, $to_update, $sub_models_to_remove);
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
	/** Go through foreign keys to find linked data to remove
	 * @param datamodel\Table $table name of table where data is going to be removed
	 * @param string $sub_model_instance sub model or null
	 * @param array $keys list of keys identifying the rows which are going to be removed
	 * @param datamodel\Table $t linked table
	 * @param string $sm sub model of linked table
	 * @param array $locks list of created locks
	 * @param array $to_remove list of data to remove
	 * @param array $to_update list of data to update
	 * @param array $sub_models_to_remove list of sub models to remove
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
					$q = SQLQuery::create()->bypassSecurity($this->bypass_security)
#DEV
						->noWarning()
#END
						;
					$table_alias = $q->generateTableAlias();
					$q->select(array($t->getName()=>$table_alias));
					if ($sm <> null)
						$q->selectSubModel($t->getModel()->getParentTable(), $sm);
					$q->whereIn($table_alias, $col->name, $keys);
					$q->field($table_alias, $primary->name);
					$rows = $q->executeSingleField();
					$this->browseTablesForRemove($t, $sm, $rows, $table->getName(), $locks, $to_remove, $to_update, $sub_models_to_remove);
				} else {
					// no primary key, this must be done with a where
					if (!$this->bypass_security && !$t->canRemoveAny()) {
						$q = SQLQuery::create()->bypassSecurity($this->bypass_security)
#DEV
							->noWarning()
#END
							;
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
		
					if (!$t->canModifyEverything()) {
						// select matching rows
						$q = SQLQuery::create()
#DEV
							->noWarning()
#END
							;
						$table_alias = $q->generateTableAlias();
						$q->select(array($t->getName()=>$table_alias));
						if ($t->getModel() instanceof SubDataModel)
							$q->selectSubModel($t->getModel()->getParentTable(), $sub_model_instance);
						$q->whereIn($table_alias, $col->name, $keys);
						$t->prepareSelectToDetermineWriteAccess($q, $table_alias);
						$prepared_rows = $q->execute();
						if (count($prepared_rows) == 0) continue; // nothing to do there
						$allowed_rows = $t->filterWriteAccess($prepared_rows);
						if (count($allowed_rows) <> count($prepared_rows))
							throw new Exception("Access denied: some rows cannot be modified in table '".$t->getName()."' but are linked to rows which need to be removed in table '".$table->getName()."'");
					}
				}
		
				// we can update
				array_push($to_update, array($t->getSQLNameFor($sm),$col->name,$keys));
			}
		}
	}
	
	/** For debug purpose, generate information about the current SELECT: useful when the generate throws an exception
	 * @return string HTML containing information about the current SELECT request
	 */
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
					if ($f1 == null) {
						$ff = true;
						foreach ($f2 as $name=>$val) {
							if ($ff) $ff = false; else $s .= " AND ";
							$s .= "`".$j[1]."`.`$name`='$val'";
						}
					} else
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

		// where
		$s .= "<li>WHERE:<ul>";
		foreach ($this->where as $w)
			$s .= "<li>".toHTML($w)."</li>";
		$s .= "</ul></li>";
		
		$s .= "</ul>";
		return $s;
	}

	// some useful shortcuts
	
	/** Create a SELECT * FROM the given table
	 * @param string $table table name
	 * @param string $sub_models sub models or null
	 * @return array list of rows
	 */
	public static function getTable($table, $sub_models = null) {
		return SQLQuery::create()->selectSubModels($sub_models)->select($table)->execute();
	}
	/** Create a SELECT * for a given row
	 * @param string|\datamodel\Table $table table or table name
	 * @param integer|array $key key of the row
	 * @param string $sub_model sub model or null
	 * @return array the row
	 */
	public static function getRow($table, $key, $sub_model = null) {
		if (!($table instanceof \datamodel\Table)) {
			require_once("component/data_model/Model.inc");
			$table = DataModel::get()->getTable($table);
		}
		$q = \SQLQuery::create()->select($table->getName())->selectSubModelForTable($table, $sub_model);
		if ($table->getPrimaryKey() <> null)
			$q->whereValue($table->getName(), $table->getPrimaryKey()->name, $key);
		else foreach ($key as $kname=>$kval)
			$q->whereValue($table->getName(), $kname, $kval);
		return $q->executeSingleRow();
	}
	/** Create a SELECT * for the given rows
	 * @param string|\datamodel\Table $table table or table name
	 * @param integer|array $keys keys of the rows
	 * @param string $sub_model sub model or null
	 * @return array the rows
	 */
	public static function getRows($table, $keys, $sub_model = null) {
		if (count($keys) == 0) return array();
		if (!($table instanceof \datamodel\Table)) {
			require_once("component/data_model/Model.inc");
			$table = DataModel::get()->getTable($table);
		}
		$q = \SQLQuery::create()->select($table->getName())->selectSubModelForTable($table, $sub_model);
		if ($table->getPrimaryKey() <> null)
			$q->whereIn($table->getName(), $table->getPrimaryKey()->name, $keys);
		else {
			$w = "(";
			$first = true;
			foreach ($keys as $key) {
				if ($first) $first = false; else $w .= " OR ";
				$w .= "(";
				$f = true;
				foreach ($key as $kname=>$kval) {
					if ($f) $f = false; else $w .= " AND "; 
					$w .= "`".$table->getSQLNameFor($sub_model)."`.`".$kname."`='".SQLQuery::escape($kval)."'";
				}
				$w .= ")";
			}
			$w .= ")";
			$q->where($w);
		}
		return $q->execute();
	}
	
	/** @var number indicates the number of transaction started (nested transactions) */
	private static $transaction_started = 0;
	/** @var boolean indicates if one of the transaction started asked for a rollback */
	private static $rollback = false;
	
	/** start a transaction, or nested transaction */
	public static function startTransaction() {
		if(SQLQuery::$transaction_started == 0)
			self::performStartTransaction();		
		SQLQuery::$transaction_started++;
	}
	
	/** Commit the current transaction. In case it is nested, nothing is commited yet, else everything is committed and the transaction closed. */
	public static function commitTransaction() {
		SQLQuery::$transaction_started --;
		if(SQLQuery::$transaction_started == 0)
			self::performEndTransaction();
	}
	
	/** Cancel and rollback the current transaction. In case of nested transaction, the root transaction will be rollback. */
	public static function rollbackTransaction() {
		self::$rollback = true;
		self::$transaction_started --;
		if(self::$transaction_started == 0)
			self::performEndTransaction();
	}
	
	/** Indicates if a transaction is currently started
	 * @return boolean true if a transaction is started
	 */
	public static function isTransactionStarted() {
		return self::$transaction_started == 0 ? false : true;
	}
	
	/** Indicates if the current transaction will be rollback, due to a nested transaction which failed
	 * @return boolean true if the current transaction will be rollback
	 */
	public static function transactionFlaggedAsRollback(){
		return self::$rollback;
	}
	
	/** Start a transaction on the database system */
	private static function performStartTransaction(){
#DEV
		self::getDataBaseAccessWithoutSecurity()->log("START TRANSACTION", 0, 0, 0);
#END
		self::getDataBaseAccessWithoutSecurity()->startTransaction();
	}
	
	/** Commit or rollback the root transaction on the database system */
	private static function performEndTransaction(){
		if(self::$rollback){
#DEV
			self::getDataBaseAccessWithoutSecurity()->log("ROLLBACK", 0, 0, 0);
#END
			self::getDataBaseAccessWithoutSecurity()->rollback();
		} else {
#DEV
			self::getDataBaseAccessWithoutSecurity()->log("COMMIT", 0, 0, 0);
#END
			self::getDataBaseAccessWithoutSecurity()->commit();
		}
		self::$rollback = false;
	}
	
}

/**
 * Used to aggregate UPDATE or INSERT, and execute it with minimum number of SQL query, for performance purpose.
 */
class TablesToUpdate {

	/** @var array $list list of things to update */
	private $list = array();
	
	/** Update a cell in a table
	 * @param string $table table name
	 * @param string $sub_model sub model or null
	 * @param integer|array $row_key key identifying the row, or null to do an insert
	 * @param string $column column name
	 * @param mixed $value new value
	 */
	public function addValue($table, $sub_model, $row_key, $column, $value) {
		if (!isset($this->list[$table]))
			$this->list[$table] = array();
		if (!isset($this->list[$table][$sub_model]))
			$this->list[$table][$sub_model] = array();
		if (!isset($this->list[$table][$sub_model][$row_key]))
			$this->list[$table][$sub_model][$row_key] = array();
		$this->list[$table][$sub_model][$row_key][$column] = $value;
	}
	
	/** Execute with minimum number of SQL queries.
	 * @param boolean $only_insert if true, everything will be considered as INSERT, else it is a mixed of UPDATE and INSERT based on the key
	 * @return array list of ID generated by insert
	 */
	public function execute($only_insert = false) {
		$tables_keys = array();
		foreach ($this->list as $table_name=>$table_content) {
			foreach ($table_content as $sub_model=>$sub_model_content) {
				$inserts = array();
				foreach ($sub_model_content as $row_key=>$row_content) {
					if ($row_key == null || intval($row_key) < 0 || $only_insert) {
						if (!isset($inserts[$row_key]))
							$inserts[$row_key] = array();
						foreach ($row_content as $col_name=>$value)
							$inserts[$row_key][$col_name] = $value;
					} else {
						SQLQuery::create()->updateByKey($table_name, $row_key, $row_content);
					}
				}
				if (count($inserts) > 0) {
					$rows = array();
					foreach ($inserts as $row_key => $row) array_push($rows, $row);
					$ids = SQLQuery::create()->insertMultiple($table_name, $rows);
					if (!isset($tables_keys[$table_name]))
						$tables_keys[$table_name] = array();
					if (count($ids) > 0) {
						$tables_keys[$table_name][$sub_model] = array();
						$i = 0;
						foreach ($inserts as $row_key => $row)
							$tables_keys[$table_name][$sub_model][$row_key] = $ids[$i++];
					}
				}
			}
		}
		return $tables_keys;
	}
	
}
?>