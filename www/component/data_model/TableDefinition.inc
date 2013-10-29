<?php
namespace datamodel {
class Table {

	private $model;
	private $name;
	private $columns = array();
	private $indexes = array();
	private $table_read_access = array();
	public $table_specific_read_access = array();
	private $table_add_access = array();
	private $table_remove_access = array();
	private $fields_read_access = array();
	private $fields_write_access = array();
	private $is_customized = false;
	private $customization = array();
	private $unique_fields = null;
	private $display_handler = array();
	
	public $_key_submodels = array();

	public function __construct(&$model, $name) {
		$this->model = &$model;
		$this->name = $name;
	}
	
	public function _key_for_submodel(&$sm) {
		array_push($this->_key_submodels, $sm);
	}

	/** Add a primary key (integer ID)
	 *  @return \datamodel\Table
	 */
	public function addPrimaryKey($name, $size=32) {
		$c = new PrimaryKey($this, $name, $size);
		array_push($this->columns, $c);
		array_push($this->indexes, array("primary",$name));
		return $this;
	}

	/**
	 * Add a foreign key to the given table 
	 * @param string $name name of the foreign key
	 * @param string $table table pointed by the foreign key
	 * @param boolean $remove_foreign_when_primary_removed indicates if the row in this table should be removed when the associated primary key is removed.
	 * @param boolean $remove_primary_when_foreign_removed indicates if the associated primary key should be removed when a row in this table is removed.
	 * @param boolean $multiple indicates if several rows in this table can exist with the same foreign key (n to 1 relation)
	 * @param boolean|null $can_be_null indicates if this foreign key can be null. If not specified (null) it will be considered that the foreign key can be null if $remove_foreign_when_primary_removed is set to false (example of overriding this info is when we have a foreign key pointing to the same table to create a tree, and the root level needs to be null).
	 * @return \datamodel\Table
	 */
	public function addForeignKey($name, $table, $remove_foreign_when_primary_removed, $remove_primary_when_foreign_removed, $multiple, $can_be_null = null) {
		$c = new ForeignKey($this, $name, $table, $remove_foreign_when_primary_removed, $remove_primary_when_foreign_removed, $multiple, $can_be_null);
		array_push($this->columns, $c);
		return $this;
	}

	/** Add an integer column. Size is in bits
	 * @return \datamodel\Table 
	 */
	public function addInteger($name, $size=32, $min = null, $max = null, $can_be_null = true, $unique = false) {
		$c = new ColumnInteger($this, $name, $size, $min, $max, $can_be_null, $unique);
		array_push($this->columns, $c);
		return $this;
	}

	/** Add a boolean column */
	public function addBoolean($name, $can_be_null = true) {
		$c = new ColumnBoolean($this, $name, $can_be_null);
		array_push($this->columns, $c);
		return $this;
	}

	/** Add a string column */
	public function addString($name, $max_length=255, $min_length = 0, $can_be_null = true, $unique = false) {
		$c = new ColumnString($this, $name, $max_length, $min_length, $can_be_null, $unique);
		array_push($this->columns, $c);
		return $this;
	}

	/** Add an enum column, with the given possible values */
	public function addEnum($name, $values, $can_be_null = true, $unique = false) {
		$c = new ColumnEnum($this, $name, $values);
		array_push($this->columns, $c);
		return $this;
	}

	/** Add a date column */
	public function addDate($name, $can_be_null = true, $unique = false) {
		$c = new ColumnDate($this, $name, $can_be_null, $unique);
		array_push($this->columns, $c);
		return $this;
	}

	/** Add a time column */
	public function addTime($name, $can_be_null = true, $unique = false) {
		$c = new ColumnTime($this, $name, $can_be_null, $unique);
		array_push($this->columns, $c);
		return $this;
	}
	
	public function &customizable() {
		$this->is_customized = true;
		return $this;
	}
	
	public function &setUnique($fields) {
		$this->unique_fields = $fields;
		return $this;
	}
	
	/** Indicate one or several columns should be indexed for faster access.
	 * As parameters, this method expects the list of columns to be indexed together.
	 * @return \datamodel\Table
	 */
	public function &addIndex() {
		$nb = func_num_args();
		if ($nb == 1)
			array_push($this->indexes, array(func_get_arg(0),func_get_arg(0)));
		else {
			$a = array("index".count($this->indexes));
			for ($i = 0; $i < $nb; $i++)
				array_push($a, func_get_arg($i));
			array_push($this->indexes, $a);
		}
		return $this;
	}

	public function &allowReadAccess() {
		$this->table_read_access = null;
		return $this;
	}
	public function &restrictReadAccess($right_name, $right_value) {
		$this->table_read_access[$right_name] = $right_value;
		return $this;
	}
	public function &allowSpecificReadAccess($field, $value_func) {
		array_push($this->table_specific_read_access, array($field, $value_func));
		return $this;
	}
	public function &restrictAddAccess($right_name, $right_value) {
		$this->table_add_access[$right_name] = $right_value;
		return $this;
	}
	public function &restrictRemoveAccess($right_name, $right_value) {
		$this->table_remove_access[$right_name] = $right_value;
		return $this;
	}
	public function &restrictFieldReadAccess($field, $right_name, $right_value) {
		if (!isset($this->fields_read_access[$field]))
			$this->fields_read_access[$field] = array();
		$this->fields_read_access[$field][$right_name] = $right_value;
		return $this;
	}
	public function &restrictFieldWriteAccess($field, $right_name, $right_value) {
		if (!isset($this->fields_write_access[$field]))
			$this->fields_write_access[$field] = array();
		$this->fields_write_access[$field][$right_name] = $right_value;
		return $this;
	}
	public function &restrictWriteAccess($right_name, $right_value) {
		foreach ($this->columns as $col)
			$this->restrictFieldWriteAccess($col->name, $right_name, $right_value);
		return $this;
	}
	public function &preventWriteAccess($field) {
		$this->fields_write_access[$field] = false;
	}
	public function &noRestriction() {
		$this->table_read_access = null;
		$this->fields_read_access = array();
		foreach ($this->columns as $col)
			$this->fields_write_access[$col->name] = true;
		$this->table_add_access = null;
		$this->table_remove_access = null;
		return $this;
	}

	public function canAccess() {
		if ($this->table_read_access === null) return true;
		if (count($this->table_specific_read_access) > 0) return true;
		if (count($this->table_read_access) == 0) return false;
		foreach ($this->table_read_access as $right_name=>$right_value)
			if (!\PNApplication::$instance->user_management->has_right($right_name, $right_value))
				return false;
		return true;
	}
	public function canReadAll() {
		if ($this->table_read_access === null) return true;
		if (count($this->table_read_access) == 0) return false;
		foreach ($this->table_read_access as $right_name=>$right_value)
			if (!\PNApplication::$instance->user_management->has_right($right_name, $right_value))
				return false;
		return true;
	}
	public function canAccessField($field) {
		if (!isset($this->fields_read_access[$field])) return true;
		foreach ($this->fields_read_access[$field] as $right_name=>$right_value)
			if (!\PNApplication::$instance->user_management->has_right($right_name, $right_value))
			return false;
		return true;
	}
	public function canModifyField($field) {
		if (!isset($this->fields_write_access[$field])) return false;
		$val = $this->fields_write_access[$field];
		if ($val === true) return true;
		if ($val === false) return false;
		foreach ($val as $right_name=>$right_value)
			if (!\PNApplication::$instance->user_management->has_right($right_name, $right_value))
				return false;
		return true;
	}
	public function canAdd() {
		if ($this->table_add_access === null) return true;
		if (count($this->table_add_access) == 0)
			return false;
		foreach ($this->table_add_access as $right_name=>$right_value)
			if (!\PNApplication::$instance->user_management->has_right($right_name, $right_value))
				return false;
		return true;
	}
	public function canRemove() {
		if ($this->table_remove_access === null) return true;
		if (count($this->table_remove_access) == 0)
			return false;
		foreach ($this->table_remove_access as $right_name=>$right_value)
			if (!\PNApplication::$instance->user_management->has_right($right_name, $right_value))
			return false;
		return true;
	}

	public function &setDisplayHandler($coming_from, $handler) {
		$this->display_handler[$coming_from] = $handler;
		$handler->setTable($this);
		return $this;
	}
	/**
	 * @param string|null $coming_from the name of the foreign key or null
	 * @return DataDisplayHandler|null
	 */
	public function getDisplayHandler($coming_from) {
		if ($coming_from <> null && isset($this->display_handler[$coming_from]))
			return $this->display_handler[$coming_from];
		return @$this->display_handler[null];
	}

	public function hide($field) {
		for ($i = 0; $i < count($this->columns); $i++)
			if ($this->columns[$i]->name == $field) {
				array_splice($this->columns, $i, 1);
				return;
			}
	}

	public function &getModel() { return $this->model; }
	public function getName() { return $this->name; }
	public function getSQLName($sub_models) {
		if ($this->model instanceof \SubDataModel) {
			if (isset($sub_models[$this->model->getParentTable()]))
				return $this->name."_".$sub_models[$this->model->getParentTable()];
		}
		return $this->name;
	}
	public function getSQLNameFor($sub_model) {
		if ($this->model instanceof \SubDataModel)
			return $this->name."_".$sub_model;
		return $this->name;
	}
	
	public function getPrimaryKey() {
		foreach ($this->columns as $c)
			if ($c instanceof PrimaryKey) {
				if (!$this->canAccessField($c->name)) throw new \Exception("Access denied");
				return $c;
			}
		return null;
	}
	/**
	 * @param string $name name of the column to get
	 * @throws \Exception in case the column is not accessible based on the rights of the user
	 * @return Column|NULL the column or NULL if the column does not exist
	 */
	public function getColumn($name, $sub_models = null) {
		$columns = $this->_getColumns($sub_models);
		foreach ($columns as $c)
			if ($c->name == $name) {
				if (!$this->canAccessField($c->name)) throw new \Exception("Access denied");
				return $c;
			}
		return null;
	}
	public function getColumnFor($name, $sub_model) {
		$sub_models = array();
		if ($sub_model <> null && $this->getModel() instanceof \SubDataModel)
			$sub_models[$this->getModel()->getParentTable()] = $sub_model;
		return $this->getColumn($name, $sub_models);
	}
	public function getColumns($sub_models = null) {
		$columns = $this->_getColumns($sub_models);
		$cols = array();
		foreach ($columns as $c)
			if ($this->canAccessField($c->name))
				array_push($cols, $c);
		return $cols;
	}
	public function getColumnsFor($sub_model = null) {
		$sub_models = array();
		if ($sub_model <> null && $this->model instanceof SubDataModel)
			$sub_models[$this->model->getParentTable()] = $sub_model;
		return $this->getColumns($sub_models);
	}
	
	public function internalGetColumns($sub_models = null) { 
		return $this->_getColumns($sub_models);
	}
	public function internalGetColumn($name, $sub_models = null) {
		$columns = $this->_getColumns($sub_models);
		foreach ($columns as $c)
			if ($c->name == $name)
				return $c;
		return null;
	}
	
	private function _getColumns($sub_models) {
		if (!$this->is_customized) return $this->columns;
		if ($this->model instanceof \SubDataModel) {
			$name = $this->getSQLName($sub_models);
			if (!isset($this->customization[$name])) {
				$this->customization[$name] = $this->load_customization($name);
			}
			return array_merge($this->columns, $this->customization[$name]);
		}
		if (!isset($this->customization[$this->name])) {
			$this->customization[$this->name] = $this->load_customization($this->name);
		}
		return array_merge($this->columns, $this->customization[$this->name]);
	}
	private function load_customization($table_name) {
		$cols = array();
		$res = \SQLQuery::get_db_system_without_security()->execute("SHOW FULL COLUMNS FROM `".$table_name."`");
		if ($res)
			while (($r = \SQLQuery::get_db_system_without_security()->next_row($res)) <> null) {
				$col_name = $r["Field"];
				// check if this column is a normal one
				$found = false;
				foreach ($this->columns as $c)
					if ($c->name == $col_name) { $found = true; break; }
				if ($found) continue;
				$col_type = $r["Type"];
				$col_size = null;
				$i = strpos($col_type, "(");
				if ($i !== FALSE) {
					$j = strpos($col_type, ")");
					$col_size = intval(substr($col_type, $i+1, $j-$i-1));
					$col_type = substr($col_type, 0, $i);
				}
				$can_be_null = $r["Null"] == "YES";
				$comment = $r["Comment"];
				if (strlen($comment) > 0)
					$comment = json_decode($comment);
				else
					$comment = array();
				
				$col = null;
				if ($col_type == "int") {
					if (isset($comment["foreign"]))
						$col = new ForeignKey($this, $col_name, $comment["foreign"]);
					else {
						if ($col_size == 1)
							$col = new ColumnBoolean($this, $col_name);
						else
							$col = new ColumnInteger($this, $col_name, $col_size);
					}
				} else if ($col_type == "varchar")
					$col = new ColumnString($this, $col_name, $col_size);
				// TODO continue
				if ($col <> null)
					array_push($cols, $col);
			}
		return $cols;
	}

	public function getUnique() {
		return $this->unique_fields;
	}
	
}

abstract class Column {

	public $table;
	public $name;
	public $can_be_null;
	public $unique;

	public function __construct(&$table, $name, $can_be_null, $unique) {
		$this->table = &$table;
		$this->name = $name;
		$this->can_be_null = $can_be_null;
		$this->unique = $unique;
	}

	public function get_sql() {
		$sql = "`".$this->name."` ";
		$sql .= $this->get_sql_type();
		return $sql;
	}
	public abstract function get_sql_type();

	public function validate($value) { return $value; }

}

class ColumnInteger extends Column {

	public $size;
	public $min, $max;

	public function __construct(&$table, $name, $size, $min = null, $max = null, $can_be_null = true, $unique = false) {
		parent::__construct($table, $name, $can_be_null, $unique);
		$this->size = $size;
		$this->min = $min;
		$this->max = $max;
	}

	public function get_sql_type() { return "INT(".$this->size.")"; }

	public function validate($value) {
		if ($this->can_be_null && ($value === null || $value === "")) return null;
		if (!is_integer($value)) $value = intval($value);
		if ($this->min !== null && $value < $this->min) throw new \Exception("Minimum is ".$this->min);
		if ($this->max !== null && $value > $this->max) throw new \Exception("Maximum is ".$this->max);
		return $value;
	}
}

class ColumnBoolean extends ColumnInteger {

	public function __construct(&$table, $name, $can_be_null = false) {
		parent::__construct($table, $name, 1, $can_be_null, false);
	}

	public function validate($value) {
		if ($this->can_be_null && $value === null) return null;
		return $value == true;
	}
}

class ColumnString extends Column {

	public $max_length;
	public $min_length;

	public function __construct(&$table, $name, $max_length, $min_length = 0, $can_be_null = true, $unique = false) {
		parent::__construct($table, $name, $can_be_null, $unique);
		$this->max_length = $max_length;
		$this->min_length = $min_length;
	}

	public function get_sql_type() { /* TODO choose the type depending on max_length */ return "VARCHAR(".$this->max_length.")"; }

	public function validate($value) {
		if ($value === null) {
			if (!$this->can_be_null) throw new \Exception("Cannot be empty");
			return null;
		}
		if (strlen($value) < $this->min_length) {
			if (strlen($value) == 0) {
				if ($this->can_be_null)
					return null; // convert empty string into null
				throw new \Exception("Cannot be empty");
			}
			throw new \Exception("Must have a length of at least ".$this->min_length);
		}
		if (strlen($value) > $this->max_length)
			throw new \Exception("Cannot be greater than ".$this->max_length);
		return $value;
	}
}

class ColumnEnum extends Column {

	public $values;

	public function __construct(&$table, $name, $values, $can_be_null = true, $unique = false) {
		parent::__construct($table, $name, $can_be_null, $unique);
		$this->values = $values;
	}

	public function get_sql_type() {
		$sql = "ENUM(";
		$first = true;
		foreach ($this->values as $v) {
			if ($first) $first = false; else $sql.=",";
			$sql .= "'".\SQLQuery::escape($v)."'";
		}
		$sql .= ")";
		return $sql;
	}
}

class ColumnDate extends Column {

	public function __construct(&$table, $name, $can_be_null = true, $unique = false) {
		parent::__construct($table, $name, $can_be_null, $unique);
	}

	public function get_sql_type() { return "DATE"; }

	public function validate($value) {
		if ($value === "") $value = null;
		if ($value === null) {
			if (!$this->can_be_null) throw new \Exception("You must enter a valid date");
			return null;
		}
		// TODO check if valid format
		return $value;
	}
}

class ColumnTime extends Column {

	public function __construct(&$table, $name, $can_be_null = true, $unique = false) {
		parent::__construct($table, $name, $can_be_null, $unique);
	}


	public function get_sql_type() { return "TIME"; }

	public function validate($value) {
		if ($value === "") $value = null;
		if ($value === null) {
			if (!$this->can_be_null) throw new \Exception("You must enter a valid time");
			return null;
		}
		// TODO check if valid format
		return $value;
	}
}

class PrimaryKey extends ColumnInteger {

	public function __construct(&$table, $name, $size) {
		parent::__construct($table, $name, $size, false, true);
	}

	public function get_sql_type() { return "INT(".$this->size.") NOT NULL AUTO_INCREMENT"; }
}

class ForeignKey extends ColumnInteger {

	public $foreign_table;
	public $remove_foreign_when_primary_removed;
	public $remove_primary_when_foreign_removed;
	public $multiple;
	
	public function __construct(&$table, $name, $foreign_table, $remove_foreign_when_primary_removed, $remove_primary_when_foreign_removed, $multiple, $can_be_null) {
		$this->foreign_table = $foreign_table;
		$this->remove_foreign_when_primary_removed = $remove_foreign_when_primary_removed;
		$this->remove_primary_when_foreign_removed = $remove_primary_when_foreign_removed;
		$this->multiple = $multiple;
		try { $ft = $table->getModel()->internalGetTable($foreign_table); }
		catch (\Exception $e) { $ft = null; }
		if ($ft == null && $table->getModel() instanceof \SubDataModel)
			try { $ft = $table->getModel()->getParentModel()->internalGetTable($foreign_table); }
			catch (\Exception $e) { $ft = null; }
		if ($ft == null) { 
			\PNApplication::error("Invalid foreign key '".$name."' in table '".$table->getName()."': foreign table '".$foreign_table."' does not exist");
			$size = 32;
		} else
			$size = $ft->getPrimaryKey()->size;
		parent::__construct($table, $name, $size, null, null, $can_be_null === null ? !$remove_foreign_when_primary_removed : $can_be_null, false);
	}

	public function get_sql_type() { 
		try { $ft = $this->table->getModel()->internalGetTable($this->foreign_table); }
		catch (\Exception $e) { $ft = null; }
		if ($ft == null && $this->table->getModel() instanceof \SubDataModel)
			try { $ft = $this->table->getModel()->getParentModel()->internalGetTable($this->foreign_table); }
			catch (\Exception $e) { $ft = null; }
		return "INT(".$ft->getPrimaryKey()->size.")"; 
	}

	public function validate($value) {
		if ($value === null) {
			if (!$this->can_be_null) throw new \Exception("Missing");
			return null;
		}
		return parent::validate($value);
	}
}

abstract class DataDisplayHandler {
	/**
	 * @var \datamodel\Table
	 */
	public $table;
	/**
	 * @var string
	 */
	public $category;
	/** @var string */
	public $display_name;
	
	public function __construct($category, $display_name) {
		$this->category = $category;
		$this->display_name = $display_name;
	}
	
	public function setTable($table) { $this->table = $table; }

	/**
	 * @return DataDisplay[]
	 */
	public abstract function getDisplayableData();
	/**
	 * @return boolean true if we should not follow any foreign key from this table to find other data: everything from here is handled by this handler.
	 */
	public function stopHere() { return false; }
	
	/**
	 * Create a new entry in this table
	 * @param array $data array of array("name"=>the_data_name,"value"=>the_value)
	 */
	public abstract function createEntry($data, $sub_model);
	
}
abstract class DataDisplay {
	
	/**
	 * @var \datamodel\DataDisplayHandler
	 */
	public $handler;
	
	public function __construct($handler) {
		$this->handler = $handler;
	}
	
	public abstract function getDisplayName();
	public abstract function getHandledColumns();
	public abstract function getTypedField($sub_model);
	public abstract function isEditable();
	public abstract function getEditLocks($sub_model);
	public abstract function isSortable();
	public abstract function isMandatory($sub_model);
	public function getTypedFilter($sub_model) { return null; }
	
	public function getNewData() { return null; }
	
	/**
	 * @param SQLQuery $q
	 * @param DataPath $path
	 * @param DataPathSQLBuilder $builder
	 * @return array("key"=>key_alias,"data"=>data_alias): if data is null or not specified, the function retrieveValue will be called to perform a sub-request retrieving the final value
	 */
	public abstract function buildSQL(&$q, $path, &$builder, $filters);
	/** If buildSQL didn't return a data alias, this function is called to perform a sub-request */
	public function retrieveValue($row, $sql_aliases, $path, $filters) {}
	
	public abstract function getData($key, $sub_model, $row = null);
	public abstract function saveData($key, $value, $sub_model);
	
	public final function javascriptDataDisplay($sub_model) {
		$s = "new DataDisplay(";
		$s .= json_encode($this->handler->category);
		$s .= ",".json_encode($this->getDisplayName());
		$s .= ",".json_encode($this->handler->table->getName());
		$tf = $this->getTypedField($sub_model);
		$s .= ",".json_encode($tf[0]);
		$s .= ",".$tf[1];
		$s .= ",".($this->isEditable() ? "true" : "false");
		$s .= ",".json_encode($this->getEditLocks($sub_model));
		$s .= ",".json_encode($this->isSortable());
		$tf = $this->getTypedFilter($sub_model);
		$s .= ",".json_encode($tf <> null ? $tf[0] : null);
		$s .= ",".($tf <> null ? $tf[1] : "null");
		$s .= ")";
		return $s;
	}
	
}

class FieldsDisplayHandler extends DataDisplayHandler {
	
	public $fields;
	public $specific_config;
	public $key_column;
	
	public function __construct($category, $display_name, $fields, $specific_config = array(), $key_column = null) {
		parent::__construct($category, $display_name);
		$this->fields = $fields;
		$this->specific_config = $specific_config;
		$this->key_column = $key_column;
	}
	
	public function getDisplayableData() {
		$list = array();
		foreach ($this->fields as $f=>$d) {
			$col = @$this->table->getColumn($f);
			if ($col <> null)
				array_push($list, new SimpleDataDisplay($this, $f, $d, @$this->specific_config[$f], $this->key_column));
		}
		return $list;
	}
	
	public function createEntry($data, $sub_model) {
		$list = array();
		foreach ($data as $d) {
			$found = false;
			$err = false;
			foreach ($this->fields as $f=>$disp)
				if ($disp == $d["name"]) {
					$found = true;
					try {
						$col = @$this->table->getColumn($f);
						if ($col <> null)
							$list[$f] = $col->validate($d["value"]);
					} catch (\Exception $e) {
						\PNApplication::error("Invalid data ".$disp->getDisplayName().": ".$d["value"]);
						$err = true;
					}
					break;
				}
			if (!$found) {
				\PNApplication::error("Unknown data ".$d["name"]." in DataDisplayHandler of table ".$this->table->getName());
				$err = true;
			}
			if ($err) return null;
		}
		$sub_models = null;
		if ($sub_model <> null) {
			$sm = $this->table->getModel()->getParentTable();
			$sub_models = array($sm=>$sub_model);
		}
		return \SQLQuery::create()->insert($this->table->getName(), $list, $sub_models);
	}

}

class MultipleDisplayHandler extends DataDisplayHandler {
	
	public $content;
	public $stop_here;
	
	public function __construct($category, $display_name, $content, $stop_here = false) {
		parent::__construct($category, $display_name);
		$this->content = $content;
		$this->stop_here = $stop_here;
	}
	
	public function setTable($table) {
		parent::setTable($table);
		foreach ($this->content as $c)
			if ($c instanceof DataDisplayHandler)
				$c->setTable($table);
			else
				$c->handler = $this;
	}
	
	public function getDisplayableData() {
		$list = array();
		foreach ($this->content as $c)
			if ($c instanceof DataDisplayHandler)
				$list = array_merge($list, $c->getDisplayableData());
			else
				array_push($list, $c);
		return $list;
	}
	
	public function createEntry($data, $sub_model) {
		// TODO ?
	}
	
	public function stopHere() {
		return $this->stop_here;
	}
}

class StopDataDisplayHandler extends DataDisplayHandler {
	
	public function __construct() {
		parent::__construct(null, "");
	}
	
	public function getDisplayableData() { return array(); }
	public function stopHere() { return true; }
	public function createEntry($data, $sub_model) {}
	
}

class SimpleDataDisplay extends DataDisplay {
	
	public $column_name;
	public $display_name;
	public $specific_config;
	public $key_column;
	
	public function __construct($handler, $column_name, $display_name, $specific_config = null, $key_column = null) {
		parent::__construct($handler);
		$this->column_name = $column_name;
		$this->display_name = $display_name;
		$this->specific_config = $specific_config;
		$this->key_column = $key_column;
	}
	
	public function getDisplayName() {
		return $this->display_name;
	}
	
	public function getHandledColumns() {
		return array($this->column_name);
	}
	
	public function getTypedField($sub_model) {
		$col = $this->handler->table->getColumnFor($this->column_name, $sub_model);
		return \PNApplication::$instance->widgets->get_typed_field($col);
	}
	
	public function getTypedFilter($sub_model) {
		$col = $this->handler->table->getColumnFor($this->column_name, $sub_model);
		return \PNApplication::$instance->widgets->get_typed_filter($col);
	}
	
	public function isEditable() {
		if ($this->specific_config <> null && in_array("not_editable", $this->specific_config))
			return false;
		return true;
	}
	public function isSortable() {
		return true;
	}
	public function isMandatory($sub_model) {
		$col = $this->handler->table->getColumnFor($this->column_name, $sub_model);
		return !$col->can_be_null;
	}
	
	public function getEditLocks($sub_model) {
		$key = $this->handler->table->getPrimaryKey();
		if ($key == null) {
			// TODO ?
			return array();
		} else {
			return array(array("table"=>$this->handler->table->getSQLNameFor($sub_model), "column"=>$this->column_name));
		}
	}
	
	public function buildSQL(&$q, $path, &$builder, $filters) {
		$table_alias = $builder->build($path, $q);
		$alias = $q->get_field_alias($table_alias, $this->column_name);
		if ($alias == null) {
			$alias = $builder->new_alias();
			$q->field($table_alias, $this->column_name, $alias);
		}
		$result = array("data"=>$alias);
		$key = $this->handler->table->getPrimaryKey();
		if ($key == null) $key = $this->key_column; else $key = $key->name;
		if ($key == null) {
			PNApplication::error("No key for data '".$this->display_name."' in table '".$this->handler->table->getName()."'");
			$result["key"] = null;
			return $result;
		}
		$alias = $q->get_field_alias($table_alias, $key);
		if ($alias == null) {
			$alias = $builder->new_alias();
			$q->field($table_alias, $key, $alias);
		}
		$result["key"] = $alias;
		$col = $this->handler->table->getColumnFor($this->column_name, $path->sub_model);
		$tf = \PNApplication::$instance->widgets->get_typed_filter($col);
		switch ($tf[0]) {
			case "filter_text":
				foreach ($filters as $filter_list) {
					$w = "";
					foreach ($filter_list as $filter) {
						$val = trim($filter["value"]);
						if ($val == "") continue;
						if (strlen($w) > 0) $w .= " OR ";
						$w .= "`".$table_alias."`";
						$w .= ".`".$this->column_name."`";
						switch ($filter["type"]) {
							case "contains": $w .= " LIKE '%".\SQLQuery::escape($filter["value"])."%'"; break;
							case "starts": $w .= " LIKE '".\SQLQuery::escape($filter["value"])."%'"; break;
							case "ends": $w .= " LIKE '%".\SQLQuery::escape($filter["value"])."'"; break;
							case "exact": $w .= " = '".\SQLQuery::escape($filter["value"])."'"; break;
						}
					}
					if (strlen($w) > 0)
						$q->where($w);
				}
				break;
		}
		return $result;
	}
	
	public function getData($key, $sub_model, $row = null) {
		if ($row <> null) return @$row[$this->column_name];
		$key_name = $this->handler->table->getPrimaryKey();
		if ($key_name == null) $key_name = $this->key_column; else $key_name = $key->name;
		$value = \SQLQuery::create()
			->select($this->handler->table->getSQLNameFor($sub_model))
			->field($this->column_name)
			->where($key_name, $key)
			->execute_single_value();
		return $value;
	}
	
	public function saveData($key, $value, $sub_model) {
		$key_name = $this->handler->table->getPrimaryKey();
		if ($key_name == null) $key_name = $this->key_column; else $key_name = $key->name;
		\SQLQuery::create()->update(
			$this->handler->table->getSQLNameFor($sub_model),
			array($this->column_name => $value),
			array($key_name => $key)
		);
	}
	
}

class ChoiceDataDisplay extends DataDisplay {
	
	public $display_name;
	public $foreign_key_name;
	public $foreign_column_name;
	public $key_column;
	
	public function __construct($handler, $foreign_key_name, $foreign_column_name, $display_name, $key_column = null) {
		parent::__construct($handler);
		$this->display_name = $display_name;
		$this->foreign_key_name = $foreign_key_name;
		$this->foreign_column_name = $foreign_column_name;
		$this->key_column = $key_column;
	}
	public function getDisplayName() { return $this->display_name; }
	public function getHandledColumns() { return array($this->foreign_key_name); }
	
	public function getTypedField($sub_model) {
		$foreign_key = $this->handler->table->getColumnFor($this->foreign_key_name, $sub_model);
		$cfg = "{";
		$cfg .= "possible_values:[";
		$t = \DataModel::get()->getTable($foreign_key->foreign_table);
		$q = \SQLQuery::create()->select($t->getName());
		if ($sub_model <> null && $t->getModel() instanceof SubDataModel && $this->handler->table->getModel() instanceof SubDataModel && $t->getModel()->getParentTable() == $this->handler->table->getModel()->getParentTable())
			$q->select_sub_model($t->getModel()->getParentTable(), $sub_model);
		$q->field($t->getName(), $t->getPrimaryKey()->name, "KEY");
		$q->field($t->getName(), $this->foreign_column_name, "VALUE");
		$res = $q->execute();
		$first = true;
		foreach ($res as $row) {
			if ($first) $first = false; else echo ",";
			$cfg .= "[";
			$cfg .= json_encode($row["KEY"]);
			$cfg .= ",".json_encode($row["VALUE"]);
			$cfg .= "]";
		}
		$cfg .= "]";
		$cfg .= ",can_be_empty:".($foreign_key->can_be_null ? "true" : "false");
		$cfg .= "}";
		return array("field_enum", $cfg);
	}
	
	public function getTypedFilter($sub_model) {
		$foreign_key = $this->handler->table->getColumnFor($this->foreign_key_name, $sub_model);
		$cfg = "{";
		$cfg .= "possible_values:[";
		$t = \DataModel::get()->getTable($foreign_key->foreign_table);
		$q = \SQLQuery::create()->select($t->getName());
		if ($sub_model <> null && $t->getModel() instanceof SubDataModel && $this->handler->table->getModel() instanceof SubDataModel && $t->getModel()->getParentTable() == $this->handler->table->getModel()->getParentTable())
			$q->select_sub_model($t->getModel()->getParentTable(), $sub_model);
		$q->field($t->getName(), $t->getPrimaryKey()->name, "KEY");
		$q->field($t->getName(), $this->foreign_column_name, "VALUE");
		$res = $q->execute();
		$first = true;
		foreach ($res as $row) {
			if ($first) $first = false; else echo ",";
			$cfg .= "[";
			$cfg .= json_encode($row["KEY"]);
			$cfg .= ",".json_encode($row["VALUE"]);
			$cfg .= "]";
		}
		$cfg .= "]";
		$cfg .= ",can_be_empty:".($foreign_key->can_be_null ? "true" : "false");
		$cfg .= "}";
		return array("filter_enum", $cfg);
	}
	
	public function isEditable() { return true; }
	public function getEditLocks($sub_model) {
		return array(
			array("table"=>$this->handler->table->getSQLNameFor($sub_model))
		);
	}
	public function isSortable() { return true; }
	public function isMandatory($sub_model) {
		$foreign_key = $this->handler->table->getColumnFor($this->foreign_key_name, $sub_model);
		return !$foreign_key->can_be_null;
	}
	
	public function buildSQL(&$q, $path, &$builder, $filters) {
		// we need to get the key, and the value to display
		$foreign_key = $this->handler->table->getColumnFor($this->foreign_key_name, $path->sub_model);
		$t = \DataModel::get()->getTable($foreign_key->foreign_table);
		
		// go to the table
		$table_alias = $builder->build($path, $q);
		// join to foreign table
		$alias = $q->get_join_alias($table_alias, $t->getName(), array($foreign_key->name=>$t->getPrimaryKey()->name));
		if ($alias == null) {
			$alias = $builder->new_alias();
			$q->join($table_alias, $t->getName(), array($foreign_key->name=>$t->getPrimaryKey()->name), $alias);
		}
		// get the key and data
		$col_name = $this->key_column;
		if ($col_name == null) $col_name = $this->handler->table->getPrimaryKey()->name;
		$key_alias = $q->get_field_alias($table_alias, $col_name);
		if ($key_alias == null) {
			$key_alias = $builder->new_alias();
			$q->field($table_alias, $col_name, $key_alias);
		}
		$data_alias = $q->get_field_alias($table_alias, $foreign_key->name);
		if ($data_alias == null) {
			$data_alias = $builder->new_alias();
			$q->field($table_alias, $foreign_key->name, $data_alias);
		}
		
		foreach ($filters as $filter_list) {
			$w = "";
			foreach ($filter_list as $filter) {
				if ($filter["value"] == "") continue;
				if (strlen($w) > 0) $w .= " OR ";
				$w .= "`".$table_alias."`.`".$foreign_key->name."`";
				if ($filter["value"] == "NULL")
					$w .= " IS NULL";
				else if ($filter["value"] == "NOT_NULL")
					$w .= " IS NOT NULL";
				else
					$w .= "='".\SQLQuery::escape($filter["value"])."'";
			}
			if (strlen($w) > 0)
				$q->where($w);
		}
		
		return array("key"=>$key_alias,"data"=>$data_alias);
	}
	
	public function getData($key, $sub_model, $row = null) {
		if ($row <> null) return $row[$this->foreign_key_name];
		$q = \SQLQuery::create()->select($this->handler->table->getName());
		if ($sub_model <> null && $t->getModel() instanceof SubDataModel && $this->handler->table->getModel() instanceof SubDataModel && $t->getModel()->getParentTable() == $this->handler->table->getModel()->getParentTable())
			$q->select_sub_model($t->getModel()->getParentTable(), $sub_model);
		$q->field($this->handler->table->getName(), $this->foreign_key_name, "VALUE");
		$col_name = $this->key_column;
		if ($col_name == null) $col_name = $this->handler->table->getPrimaryKey()->name;
		$q->where($this->handler->table->getName(), $col_name, $key);
		return $q->execute_single_value();
	}
	
	public function saveData($key, $value, $sub_model) {
		// TODO
	}
		
}

class JoiningTableDataDisplay extends DataDisplay {
	
	public $display_name;
	public $joining_key_name;
	public $foreign_key_name;
	public $foreign_table_column_name;
	
	public function __construct($handler, $display_name, $joining_key_name, $foreign_key_name, $foreign_table_column_name) {
		parent::__construct($handler);
		$this->display_name = $display_name;
		$this->joining_key_name = $joining_key_name;
		$this->foreign_key_name = $foreign_key_name;
		$this->foreign_table_column_name = $foreign_table_column_name;
	}
	
	public function getDisplayName() { return $this->display_name; }
	public function getHandledColumns() { return array($this->foreign_key_name); }
	
	public function getTypedField($sub_model) {
		$foreign_table = $this->handler->table->getColumnFor($this->foreign_key_name, $sub_model)->foreign_table;
		$foreign_table = \DataModel::get()->getTable($foreign_table);
		$sub_models = $sub_model == null ? null : array($this->handler->table->getModel()->getParentTable()=>$sub_model);
		$table_name = $foreign_table->getSQLName($sub_models);
		$list = \SQLQuery::create()
			->select($table_name)
			->field($table_name, $this->foreign_table_column_name, "VALUE")
			->field($table_name, $foreign_table->getPrimaryKey()->name, "KEY")
			->execute();
		$values = "[";
		$first = true;
		foreach ($list as $v) {
			if ($first) $first = false; else $values .= ",";
			$values .= "[".json_encode($v["KEY"]).",".json_encode($v["VALUE"])."]";
		}
		$values .= "]";
		return array("field_list_of_fixed_values", "{possible_values:$values}");
	}
	
	public function isEditable() {
		return true;
	}
	public function getEditLocks($sub_model) {
		return array(
			array("table"=>$this->handler->table->getSQLNameFor($sub_model))
		);
	}
	public function isSortable() {
		return false;
	}
	public function isMandatory($sub_model) {
		return false;
	}
	
	public function buildSQL(&$q, $path, &$builder, $filters) {
		// we need to go to the parent, and get the joining key
		$parent_alias = $builder->build($path->parent, $q);
		$key_name = $path->is_reverse() ? $path->parent->table->getPrimaryKey()->name : $path->foreign_key->name;
		$alias = $q->get_field_alias($parent_alias, $key_name);
		if ($alias == null) {
			$alias = $builder->new_alias();
			$q->field($parent_alias, $key_name, $alias);
		}
		// TODO filters
		return array("key"=>$alias,"data"=>null);
	}
	
	public function retrieveValue($row, $sql_aliases, $path) {
		$table_name = $path->table->getSQLNameFor($path->sub_model);
		$foreign_table = $path->table->getColumnFor($this->foreign_key_name, $path->sub_model)->foreign_table;
		$foreign_table = \DataModel::get()->getTable($foreign_table);
		$sub_models = $path->sub_model == null ? null : array($path->table->getModel()->getParentTable()=>$path->sub_model);
		
		$res = \SQLQuery::create()
			->select($table_name)
			->join($table_name, $foreign_table->getSQLName($sub_models), array($this->foreign_key_name => $foreign_table->getPrimaryKey()->name), "JOIN_ALIAS")
			//->field("JOIN_ALIAS", $this->foreign_table_column_name, "VALUE")
			->field("JOIN_ALIAS", $foreign_table->getPrimaryKey()->name, "KEY")
			->where_value($table_name, $this->joining_key_name, $row[$sql_aliases["key"]])
			->execute();
		$values = array();
		foreach ($res as $r)
			//array_push($values, array("key"=>$r["KEY"],"value"=>$r["VALUE"]));
			array_push($values, $r["KEY"]);
		return $values;
	}
	
	public function getData($key, $sub_model, $row = null) {
		// TODO
	}
	
	public function saveData($key, $value, $sub_model) {
		require_once("component/data_model/DataBaseLock.inc");
		\SQLQuery::create()->remove($this->handler->table->getSQLNameFor($sub_model), array($this->joining_key_name=>$key));
		foreach ($value as $v)
			\SQLQuery::create()->insert($this->handler->table->getSQLNameFor($sub_model), array($this->joining_key_name=>$key,$this->foreign_key_name=>$v));
	}
		
}

} // namespace datamodel
?>