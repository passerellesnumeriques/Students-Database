<?php
namespace datamodel {
class Table {

	private $model;
	private $name;
	private $columns = array();
	private $indexes = array();
	private $links = array();
	private $table_read_access = array();
	public $table_specific_read_access = array();
	private $table_add_access = array();
	private $table_remove_access = array();
	private $fields_read_access = array();
	private $fields_write_access = array();
	private $displayable_data = array();
	private $is_customized = false;
	private $customization = array();
	private $unique_fields = null;
	
	public $_key_submodels = array();

	public function __construct(&$model, $name) {
		$this->model = &$model;
		$this->name = $name;
	}
	
	public function _key_for_submodel(&$sm) {
		array_push($this->_key_submodels, $sm);
	}

	/** Add a primary key (integer ID) */
	public function addPrimaryKey($name, $size=32) {
		$c = new PrimaryKey($this, $name, $size);
		array_push($this->columns, $c);
		array_push($this->indexes, array("primary",$name));
		return $this;
	}

	/** Add a foreign key to the given table */
	public function addForeignKey($name, $table) {
		$c = new ForeignKey($this, $name, $table);
		array_push($this->columns, $c);
		return $this;
	}

	/** Add an integer column. Size is in bits */
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

	/** Indicate a link to another table, not using a foreign key. */
	public function &addLink($table_name, $fields_matching, $unique) {
		array_push($this->links, new Link($this, $table_name, $fields_matching, $unique));
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

	/** Indicates that a particular column is a displayable data (not internal).
	 *
	 * @param string $category name of the category in which this data will be displayed. This string is supposed to be used with Locale in order to obtain a localized name of the category.
	 * @param string $name name of the data to display. This string is supposed to be used with Locale, in order to display a localized name.
	 * @param string $field column corresponding to the data
	 */
	public function &addDisplayableData($field, $category, $name) {
		$this->displayable_data[$field] = array($category, $name);
		return $this;
	}

	public function hide($field) {
		unset($this->displayable_data[$field]);
	}

	/** returns the list of displayable data in this table: field=>array(category,name). category and name are localized string prefixed by namespace:: */
	public function getDisplayableData() {
		return $this->displayable_data;
	}

	/** Returns the name of the given data */
	public function getDisplayableDataName($field) {
		$data = @$this->displayable_data[$field];
		if ($data == null) return null;
		return $data[1];
	}
	/** Returns the localized category and name of the given data */
	public function getDisplayableDataCategoryAndName($field) {
		$data = @$this->displayable_data[$field];
		if ($data == null) return "?field ".$field." in table ".$this->name."?";
		return $data;
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
	public function getColumns($sub_models = null) {
		$columns = $this->_getColumns($sub_models);
		$cols = array();
		foreach ($columns as $c)
			if ($this->canAccessField($c->name))
				array_push($cols, $c);
		return $cols;
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

	public function getLinks() {
		return $this->links;
	}
	
	public function getUnique() {
		return $this->unique_fields;
	}
	
}

class Link {
	public $table_from;
	public $table;
	public $fields_matching;
	public $unique;
	public function __construct(&$table_from, $table, $fields_matching, $unique) {
		$this->table_from = &$table_from;
		$this->table = $table;
		$this->fields_matching = $fields_matching;
		$this->unique = $unique;
	}
}
class LinkMultiDataBase {
	public $table_from;
	public $table;
	public $table_field_database;
	public $fields_matching;
	public $unique;
	public function __construct(&$table_from, $table, $table_field_database, $fields_matching, $unique) {
		$this->table_from = &$table_from;
		$this->table = $table;
		$this->table_field_database = $table_field_database;
		$this->fields_matching = $fields_matching;
		$this->unique = $unique;
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

	public function create_javascript_field(&$page, $editable = false, $onchanged = null, $onunchanged = null) {
		$page->add_javascript("/static/common/js/field/field_text.js");
		return "new field_text()";
	}

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
		if ($this->can_be_null && $value === null) return null;
		if (!is_integer($value)) $value = intval($value);
		if ($this->min !== null && $value < $this->min) throw new \Exception("Minimum is ".$this->min); // TODO locale
		if ($this->max !== null && $value > $this->max) throw new \Exception("Maximum is ".$this->max); // TODO locale
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
			if (!$this->can_be_null) throw new \Exception(get_locale("common","Cannot be empty"));
			return null;
		}
		if (strlen($value) < $this->min_length) {
			if (strlen($value) == 0) throw new \Exception(get_locale("common","Cannot be empty"));
			throw new \Exception("Must have a length of at least ".$this->min_length); // TODO locale
		}
		if (strlen($value) > $this->max_length)
			throw new \Exception("Cannot be greater than ".$this->max_length); // TODO locale
		return $value;
	}

	public function create_javascript_field(&$page, $editable = false, $onchanged = null, $onunchanged = null) {
		if (!$editable) return parent::create_javascript_field($page, false);
		$page->add_javascript("/static/common/js/field/field_editable_text.js");
		return "new field_editable_text(".$this->max_length.",".($onchanged <> null ? $onchanged : 'null').",".($onunchanged <> null ? $onunchanged : 'null').")";
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
	
	public function create_javascript_field(&$page, $editable = false, $onchanged = null, $onunchanged = null) {
		if (!$editable) return parent::create_javascript_field($page, false);
		$page->add_javascript("/static/common/js/field/field_enum.js");
		$values = "[";
		for ($i = 0; $i < count($this->values); $i++)
		$values .= "]";
		return "new field_enum(".$this->max_length.",".($onchanged <> null ? $onchanged : 'null').",".($onunchanged <> null ? $onunchanged : 'null').")";
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
			if (!$this->can_be_null) throw new \Exception("You must enter a valid date"); // TODO locale
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
			if (!$this->can_be_null) throw new \Exception("You must enter a valid time"); // TODO locale
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

	public function __construct(&$table, $name, $foreign_table) {
		$this->foreign_table = $foreign_table;
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
		parent::__construct($table, $name, $size, true, false);
	}

	public function get_sql_type() { 
		try { $ft = $this->table->getModel()->internalGetTable($this->foreign_table); }
		catch (\Exception $e) { $ft = null; }
		if ($ft == null && $this->table->getModel() instanceof \SubDataModel)
			try { $ft = $this->table->getModel()->getParentModel()->internalGetTable($this->foreign_table); }
			catch (\Exception $e) { $ft = null; }
		return "INT(".$ft->getPrimaryKey()->size.")"; 
	}
}
}
?>