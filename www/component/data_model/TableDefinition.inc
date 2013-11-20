<?php
namespace datamodel {
/**
 * 3 types of tables:
 *  - with a primary key which auto-increment
 *  - with a foreign key which is also a primary key (1 to 1 relation)
 *  - a table joining 2 other tables (1 to n relation), where only add and remove can be done 
 *
 * Access rights: by default, nothing can be done, then access can be allowed:
 *  - read:
 *    - one specific right give read access: apply to all entries
 *    - only specific rows can be read: where clause will be automatically added to any select to apply the restrictions
 *    - some columns may have specific restrictions
 *  - write:
 *    - one specific right give modification access: apply to all entries
 *    - only specific rows can be modified: first a select will be done based on primary key to update, then filtered so only the rows allowed to be modified will remain in the update
 *  - add:
 *    - one specific right give add access: apply to all entries
 *    - filter: check inserted values
 *  - remove:
 *    - one specific right give remove access: apply to all entries
 *    - only specific rows with filters: same process as for write access 
 */
class Table {

	/** @var \DataModel */
	private $model;
	private $name;
	/** @var Column[] */
	private $columns = array();
	private $indexes = array();
	private $primary_key = null;
	private $key = null;
	
	private $read_access_rights = array();
	private $read_access_filters = array();
	private $read_column_access_rights = array();
	private $write_access_rights = array();
	private $write_access_filters = array();
	private $add_access_rights = array();
	private $add_access_filters = array();
	private $remove_access_rights = array();
	private $remove_access_filters = array();
	
	private $is_customized = false;
	private $customization = array();
	
	/** @var DataDisplayHandler[] */
	private $display_handler = array();
	
	public $_key_submodels = array();

	public function __construct(&$model, $name) {
		$this->model = &$model;
		$this->name = $name;
	}
	
	public function _key_for_submodel(&$sm) {
		array_push($this->_key_submodels, $sm);
	}


	/**
	 * @return \DataModel
	 */
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
	
	/**
	 * @param string $name name of the column to get
	 * @throws \Exception in case the column is not accessible based on the rights of the user
	 * @return Column|NULL the column or NULL if the column does not exist
	 */
	public function getColumn($name, $sub_models = null) {
		$columns = $this->_getColumns($sub_models);
		foreach ($columns as $c)
			if ($c->name == $name) {
				if (!$this->canAccessColumn($c->name)) throw new \Exception("Access denied");
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
			if ($this->canAccessColumn($c->name))
				array_push($cols, $c);
		return $cols;
	}
	public function getColumnsFor($sub_model = null) {
		$sub_models = array();
		if ($sub_model <> null && $this->model instanceof SubDataModel)
			$sub_models[$this->model->getParentTable()] = $sub_model;
		return $this->getColumns($sub_models);
	}
	
	public function internalGetColumnsFor($sub_model = null) {
		if ($sub_model == null) $sub_models = null;
		else $sub_models = array($this->model->getParentTable()=>$sub_model);
		return $this->_getColumns($sub_models);
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
	
	/** Add a primary key (integer ID)
	 *  @return \datamodel\Table
	 */
	public function &addPrimaryKey($name, $size=32) {
		$c = new PrimaryKey($this, $name, $size);
		array_push($this->columns, $c);
		array_push($this->indexes, array("primary",$name));
		$this->primary_key = $c;
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
	public function &addForeignKey($name, $table, $remove_foreign_when_primary_removed, $remove_primary_when_foreign_removed, $multiple, $can_be_null = null, $unique = false) {
		$c = new ForeignKey($this, $name, $table, $remove_foreign_when_primary_removed, $remove_primary_when_foreign_removed, $multiple, $can_be_null, $unique);
		array_push($this->columns, $c);
		return $this;
	}
	
	public function &setPrimary($name) {
		$c = null;
		foreach ($this->columns as $col) if ($col->name == $name) { $c = $col; break; }
		if ($c == null) throw new \Exception("Unknown column '".$name."' in table '".$this->name."': setPrimary");
		if (!$c->unique) throw new \Exception("Column '".$name."' in table '".$this->name."' cannot be a primary: it is not unique");
		$this->primary_key = $c;
		return $this;
	}

	public function getPrimaryKey() {
		return $this->primary_key;
	}
	
	/**
	 * Set a list of columns as the key of this table (values on those columns can identify uniquely a row)
	 * @param string[] $columns
	 * @return Table
	 */
	public function &setKey($columns) {
		$this->key = $columns;
		return $this;
	}
	public function getKey() {
		return $this->key;
	}
	
	/** Add an integer column. Size is in bits
	 * @return \datamodel\Table 
	 */
	public function &addInteger($name, $size=32, $min = null, $max = null, $can_be_null = true, $unique = false) {
		$c = new ColumnInteger($this, $name, $size, $min, $max, $can_be_null, $unique);
		array_push($this->columns, $c);
		return $this;
	}

	/** Add a boolean column */
	public function &addBoolean($name, $can_be_null = true) {
		$c = new ColumnBoolean($this, $name, $can_be_null);
		array_push($this->columns, $c);
		return $this;
	}

	/** Add a string column */
	public function &addString($name, $max_length=255, $min_length = 0, $can_be_null = true, $unique = false) {
		$c = new ColumnString($this, $name, $max_length, $min_length, $can_be_null, $unique);
		array_push($this->columns, $c);
		return $this;
	}

	/** Add an enum column, with the given possible values */
	public function &addEnum($name, $values, $can_be_null = true, $unique = false) {
		$c = new ColumnEnum($this, $name, $values);
		array_push($this->columns, $c);
		return $this;
	}

	/** Add a date column */
	public function &addDate($name, $can_be_null = true, $unique = false, $minimum_date = null, $maximum_date = null) {
		$c = new ColumnDate($this, $name, $can_be_null, $unique, $minimum_date, $maximum_date);
		array_push($this->columns, $c);
		return $this;
	}

	/** Add a time column */
	public function &addTime($name, $can_be_null = true, $unique = false) {
		$c = new ColumnTime($this, $name, $can_be_null, $unique);
		array_push($this->columns, $c);
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

	
	public function &addReadAccess($right_name, $right_value) {
		array_push($this->read_access_rights, array($right_name, $right_value));
		return $this;
	}
	/**
	 * Add a filter which will add where conditions in every select
	 * @param function $filter function taking 2 parameters: the SQLQuery to modify, and the table alias
	 */
	public function &addReadFilter($filter) {
		array_push($this->read_access_filters, $filter);
		return $this;
	}
	public function &allowReadAccess() {
		$this->read_access_rights = null;
		return $this;
	}
	public function &addColumnReadAccess($column_name, $right_name, $right_value) {
		if (!isset($this->read_column_access_rights[$column_name]))
			$this->read_column_access_rights[$column_name] = array();
		array_push($this->read_column_access_rights[$column_name], array($right_name, $right_value));
		return $this;
	}
	public function canAccess() {
		if ($this->read_access_rights === null) return true;
		if (count($this->read_access_filters) > 0) return true;
		foreach ($this->read_access_rights as $r)
			if (\PNApplication::$instance->user_management->has_right($r[0],$r[1])) return true;
		foreach ($this->read_column_access_rights as $colname=>$list)
			foreach ($list as $r)
				if (\PNApplication::$instance->user_management->has_right($r[0],$r[1])) return true;
		return false;
	}
	public function canAccessColumn($column_name) {
		if ($this->read_access_rights === null) return true;
		foreach ($this->read_access_rights as $r)
			if (\PNApplication::$instance->user_management->has_right($r[0],$r[1])) return true;
		if (count($this->read_column_access_rights) > 0) {
			if (isset($this->read_column_access_rights[$column_name])) {
				foreach ($this->read_column_access_rights[$column_name] as $r)
					if (\PNApplication::$instance->user_management->has_right($r[0],$r[1])) return true;
				return false;
			}
		}
		return count($this->read_access_filters) > 0;
	}
	public function filterReadAccess(&$q, $table_alias) {
		if ($this->read_access_rights === null) return;
		foreach ($this->read_access_rights as $r)
			if (\PNApplication::$instance->user_management->has_right($r[0],$r[1])) return; // full access
		$where = $q->save_where();
		foreach ($this->read_access_filters as $filter)
			$filter($q, $table_alias);
		$filters_where = $q->save_where();
		$q->where($where);
		if ($filters_where <> "") {
			$key = $this->primary_key;
			if ($key == null)
				foreach ($this->columns as $col)
					if (!$col->can_be_null) { $key = $col; break; }
			if ($key == null) {
				// we cannot do it
				$q->where($filters_where);
			} else {
				// accept rows where there is no entry for this table (join left resulting in NULL values)
				$q->where("(`".$table_alias."`.`".$key->name."` IS NULL OR (".$filters_where."))");
			}
		}
	}
	
	public function &addWriteAccess($right_name, $right_value) {
		array_push($this->write_access_rights, array($right_name, $right_value));
		return $this;
	}
	public function &addWriteFilter($prepare_select, $filter) {
		array_push($this->write_access_filters, array($prepare_select, $filter));
		return $this;
	}
	public function canModifyAny() {
		foreach ($this->write_access_rights as $r)
			if (\PNApplication::$instance->user_management->has_right($r[0], $r[1])) return true;
		return false;
	}
	public function mayModify() {
		if ($this->canModifyAny()) return true;
		if (count($this->write_access_filters) > 0) return true;
		return false;
	}
	public function prepareSelectToDetermineWriteAccess(&$q, $table_alias) {
		foreach ($this->write_access_rights as $r)
			if (\PNApplication::$instance->user_management->has_right($r[0], $r[1])) return false; // full access
		foreach ($this->write_access_filters as $f) {
			$prepare = $f[0];
			$prepare($q, $table_alias);
		}
		return true;
	}
	public function filterWriteAccess($rows) {
		foreach ($this->write_access_rights as $r)
			if (\PNApplication::$instance->user_management->has_right($r[0], $r[1])) return $rows; // full access
		foreach ($this->write_access_filters as $f) {
			$filter = $f[1];
			$rows = $filter($rows);
		}
		return $rows;
	}
	
	public function &addInsertAccess($right_name, $right_value) {
		array_push($this->add_access_rights, array($right_name,$right_value));
		return $this;
	}
	public function &addInsertFilter($filter) {
		array_push($this->add_access_filters, $filter);
		return $this;
	}
	public function mayInsert() {
		if ($this->canInsertAny()) return true;
		return count($this->add_access_filters) > 0;
	}
	public function canInsertAny() {
		foreach ($this->add_access_rights as $r)
			if (\PNApplication::$instance->user_management->has_right($r[0],$r[1])) return true;
		return false;
	}
	public function acceptInsert($values) {
		if ($this->canInsertAny()) return true;
		foreach ($this->add_access_filters as $filter)
			if ($filter($values)) return true;
		return false;
	}
	
	public function &addRemoveAccess($right_name, $right_value) {
		array_push($this->remove_access_rights, array($right_name,$right_value));
		return $this;
	}
	public function &addRemoveFilter($prepare_select, $check) {
		array_push($this->remove_access_filters, array($prepare_select, $check));
		return $this;
	}
	public function canRemoveAny() {
		foreach ($this->remove_access_rights as $r)
			if (\PNApplication::$instance->user_management->has_right($r[0], $r[1])) return true;
		return false;
	}
	public function mayRemove() {
		if ($this->canRemoveAny()) return true;
		return count($this->remove_access_filters) > 0;
	}
	public function prepareSelectToDetermineRemoveAccess(&$q, $table_alias, &$locks) {
		foreach ($this->remove_access_rights as $r)
			if (\PNApplication::$instance->user_management->has_right($r[0], $r[1])) return false; // full access
		foreach ($this->remove_access_filters as $f) {
			$prepare = $f[0];
			$prepare($q, $table_alias, $locks);
		}
		return true;
	}
	public function filterRemoveAccess($rows) {
		foreach ($this->remove_access_rights as $r)
			if (\PNApplication::$instance->user_management->has_right($r[0], $r[1])) return $rows; // full access
		foreach ($this->remove_access_filters as $f) {
			$filter = $f[1];
			$rows = $filter($rows);
		}
		return $rows;
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
	
	/** Try to find the given DataDisplay
	 * @return DataDisplay|null 
	 */
	public function getDataDisplay($category, $name) {
		foreach ($this->display_handler as $from=>$display) {
			if ($display->category <> $category) continue;
			foreach ($display->getDisplayableData() as $data)
				if ($data->getDisplayName() == $name)
					return $data;
		}
		return null;
	}

	
	public function hide($field) {
		for ($i = 0; $i < count($this->columns); $i++)
			if ($this->columns[$i]->name == $field) {
				array_splice($this->columns, $i, 1);
				return;
			}
	}

	
	public function &customizable() {
		$this->is_customized = true;
		return $this;
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
	
	public function validate($value) {
		if ($value === null) {
			if ($this->can_be_null) return null;
			throw new \Exception("Cannot be empty");
		}
		foreach ($this->values as $v)
			if ($value == $v) return $v;
		if ($value == "" && $this->can_be_null) return null;
		$error = "Invalid value '".$value."' (allowed values are: ";
		$first = true;
		foreach ($this->values as $v) {
			if ($first) $first = false; else $error .= ",";
			$error .= "'".$v."'";
		}
		$error .= ")";
		throw new \Exception($error);
	}
}

/**
 * Date column.
 * minimum and maximum dates are constraints and can be: null, a date, or the name of a date column in the same table
 */
class ColumnDate extends Column {

	public $minimum_date;
	public $maximum_date;
	
	public function __construct(&$table, $name, $can_be_null = true, $unique = false, $minimum_date = null, $maximum_date = null) {
		parent::__construct($table, $name, $can_be_null, $unique);
		$this->minimum_date = $minimum_date;
		$this->maximum_date = $maximum_date;
	}

	public function get_sql_type() { return "DATE"; }

	public function validate($value) {
		if ($value === "") $value = null;
		if ($value === null) {
			if (!$this->can_be_null) throw new \Exception("You must enter a valid date");
			return null;
		}
		$s = explode("-", $value);
		if (count($s) <> 3) throw new \Exception("Invalid date format (must be YYYY-MM-DD)");
		$year = intval($s[0]);
		$month = intval($s[1]);
		$day = intval($s[2]);
		if ($year == 0 || $month == 0 || $day == 0) throw new \Exception("Invalid date format (must be YYYY-MM-DD)");
		if ($this->minimum_date <> null) {
			if (ctype_digit(substr($this->minimum_date,0,1))) {
				$s = explode("-", $this->minimum_date);
				$y = intval($s[0]);
				$m = intval($s[1]);
				$d = intval($s[2]);
				if ($year < $y) throw new \Exception("Invalid date: minimum is ".$this->minimum_date);
				if ($year == $y) {
					if ($month < $m) throw new \Exception("Invalid date: minimum is ".$this->minimum_date);
					if ($month == $m) {
						if ($day < $d) throw new \Exception("Invalid date: minimum is ".$this->minimum_date);
					}
				}
			} else {
				// TODO compare with other column
			}
		}
		if ($this->maximum_date <> null) {
			if (ctype_digit(substr($this->maximum_date,0,1))) {
				$s = explode("-", $this->maximum_date);
				$y = intval($s[0]);
				$m = intval($s[1]);
				$d = intval($s[2]);
				if ($year > $y) throw new \Exception("Invalid date: maximum is ".$this->maximum_date);
				if ($year == $y) {
					if ($month > $m) throw new \Exception("Invalid date: maximum is ".$this->maximum_date);
					if ($month == $m) {
						if ($day > $d) throw new \Exception("Invalid date: maximum is ".$this->maximum_date);
					}
				}
			} else {
				// TODO compare with other column
			}
		}
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
	
	public function __construct(&$table, $name, $foreign_table, $remove_foreign_when_primary_removed, $remove_primary_when_foreign_removed, $multiple, $can_be_null, $unique) {
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
		parent::__construct($table, $name, $size, null, null, $can_be_null === null ? !$remove_foreign_when_primary_removed : $can_be_null, $unique);
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
	public abstract function createEntry($data, $sub_model, $preset_fields);
	
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
	public function getSingleCell() { return null; }
	
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
	
	public abstract function getData($key, $sub_model, $row = null, $key_alias = null, $value_alias = null);
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
		$s .= ",".json_encode($this->getSingleCell());
		$s .= ",".json_encode($this->getNewData());
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
	
	public function createEntry($data, $sub_model, $preset_fields) {
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
		if ($preset_fields <> null)
			foreach ($preset_fields as $name=>$value) {
				$col = @$this->table->getColumn($name);
				if ($col <> null)
					$list[$name] = $col->validate($value);
			}
		$sub_models = null;
		if ($sub_model <> null) {
			$sm = $this->table->getModel()->getParentTable();
			$sub_models = array($sm=>$sub_model);
		}
		return \SQLQuery::create()->set_sub_models($sub_models)->insert($this->table->getName(), $list);
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
	
	public function createEntry($data, $sub_model, $preset_fields) {
		// TODO ?
	}
	
	public function stopHere() {
		return $this->stop_here;
	}
}

class ChoiceFieldDisplayHandler extends DataDisplayHandler {
	
	public $foreign_key_name;
	public $foreign_column_name;
	
	public function __construct($category, $display_name, $foreign_key_name, $foreign_column_name) {
		parent::__construct($category, $display_name);
		$this->foreign_key_name = $foreign_key_name;
		$this->foreign_column_name = $foreign_column_name;
	}
	
	public function getDisplayableData() {
		return array(
			new ChoiceDataDisplay($this, $this->foreign_key_name, $this->foreign_column_name, $this->display_name)
		);
	}
	
	public function createEntry($data, $sub_model, $preset_fields) {
		// TODO ?
	}
	
}

class StopDataDisplayHandler extends DataDisplayHandler {
	
	public function __construct() {
		parent::__construct(null, "");
	}
	
	public function getDisplayableData() { return array(); }
	public function stopHere() { return true; }
	public function createEntry($data, $sub_model, $preset_fields) {}
	
}

class SimpleDataDisplay extends DataDisplay {
	
	public $column_name;
	public $display_name;
	public $specific_config;
	
	public function __construct($handler, $column_name, $display_name, $specific_config = null) {
		parent::__construct($handler);
		$this->column_name = $column_name;
		$this->display_name = $display_name;
		$this->specific_config = $specific_config;
	}
	
	public function getDisplayName() {
		return $this->display_name;
	}
	
	public function getHandledColumns() {
		return array($this->column_name);
	}
	public function getSingleCell() {
		return array("table"=>$this->handler->table->getName(), "column"=>$this->column_name);
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
		return $this->handler->table->mayModify();
	}
	public function isSortable() {
		return true;
	}
	public function isMandatory($sub_model) {
		$col = $this->handler->table->getColumnFor($this->column_name, $sub_model);
		return !$col->can_be_null;
	}
	
	public function getEditLocks($sub_model) {
		return array(array("table"=>$this->handler->table->getSQLNameFor($sub_model), "column"=>$this->column_name));
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
		if ($key == null) {
			PNApplication::error("No key for data '".$this->display_name."' in table '".$this->handler->table->getName()."'");
			$result["key"] = null;
			return $result;
		}
		$key = $key->name;
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
	
	public function getData($key, $sub_model, $row = null, $key_alias = null, $value_alias = null) {
		if ($row <> null) return @$row[$value_alias <> null ? $value_alias : $this->column_name];
		$value = \SQLQuery::create()
			->select($this->handler->table->getName())
			->set_sub_model_for_table($this->handler->table, $sub_model)
			->field($this->column_name)
			->where($this->handler->table->getPrimaryKey()->name, $key)
			->execute_single_value();
		return $value;
	}
	
	public function saveData($key, $value, $sub_model) {
		\SQLQuery::create()
			->set_sub_model_for_table($this->handler->table, $sub_model)
			->update_by_key($this->handler->table->getName(), $key, array($this->column_name => $value))
			;
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
			if ($first) $first = false; else $cfg .= ",";
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
			if ($first) $first = false; else $cfg .= ",";
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
	
	public function getData($key, $sub_model, $row = null, $key_alias = null, $value_alias = null) {
		if ($row <> null) return $row[$this->foreign_key_name];
		$q = \SQLQuery::create()->select($this->handler->table->getName());
		if ($sub_model <> null && $t->getModel() instanceof SubDataModel && $this->handler->table->getModel() instanceof SubDataModel && $t->getModel()->getParentTable() == $this->handler->table->getModel()->getParentTable())
			$q->select_sub_model($t->getModel()->getParentTable(), $sub_model);
		$q->field($this->handler->table->getName(), $this->foreign_key_name, "VALUE");
		$col_name = $this->key_column;
		if ($col_name == null) $col_name = $this->handler->table->getPrimaryKey()->name;
		$q->where_value($this->handler->table->getName(), $col_name, $key);
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
	
	public function getData($key, $sub_model, $row = null, $key_alias = null, $value_alias = null) {
		// TODO
	}
	
	public function saveData($key, $value, $sub_model) {
		require_once("component/data_model/DataBaseLock.inc");
		\SQLQuery::create()->remove($this->handler->table->getSQLNameFor($sub_model), array($this->joining_key_name=>$key));
		foreach ($value as $v)
			\SQLQuery::create()->set_sub_model_for_table($this->handler->table, $sub_model)->insert($this->handler->table->getName(), array($this->joining_key_name=>$key,$this->foreign_key_name=>$v));
	}
		
}

} // namespace datamodel
?>