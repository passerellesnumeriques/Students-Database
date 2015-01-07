<?php
namespace datamodel {

/**
 * Abstract class defining how to display/edit/create a data. A data can be:<ul>
 * <li>As simple as a column in the table</li>
 * <li>A group of columns, displayed together as a single data</li>
 * <li>Data from other tables, using foreign keys: for example, the name of something when in the table we only have a foreign key</li>
 * </ul>
 */
abstract class DataDisplay {
	
	/**
	 * @var \datamodel\Table $table the Table this data belongs to
	 */
	private $table;
	/**
	 * @var \datamodel\DataDisplayCategory $category the category this data belongs to
	 */
	private $category;
	
	/** Returns the name of the table on which this data is attached
	 * @return string table name
	 */
	public function getTableName() { return $this->table->getName(); }
	/** Returns the table on which this data is attached
	 * @return \datamodel\Table the table
	 */
	public function getTable() { return $this->table; }
	/** Returns the name of the category on which this data is attached
	 * @return string category name
	 */
	public function getCategoryName() { return $this->category->getName(); }
	/** Returns the category on which this data is attached
	 * @return \datamodel\DataDisplayCategory category
	 */
	public function getCategory() { return $this->category; }

	/** Set the table and category on which this data is attached. This is automatically called by TableDataDisplay
	 * @param \datamodel\Table $table the table
	 * @param \datamodel\DataDisplayCategory $category the category
	 */
	public function setTableAndCategory($table, $category) { $this->table = $table; $this->category = $category; }
	
	/** Get the string to display for this data
	 * @return string the display name
	 */
	public abstract function getDisplayName();
	/** Check if the current user can access this data
	 * @param integer|null $sub_model sub model used, or null
	 * @return boolean true if the current user can access
	 */
	public abstract function canAccess($sub_model);
	/** Returns the list of columns' name this data represents (either a single column, or a group of columns displayed together
	 * @return array of string
	 */
	public abstract function getHandledColumns();
	/** How to display this data, using a JavaScript widget implementing typed_field
	 * @param number|null $sub_model sub model instance
	 * @return array (classname, config) classname: string containing the name of the JavaScript class implementing typed_field, config: string containing a JSON representation of the configuration of the typed_field
	 */
	public abstract function getTypedField($sub_model);
	/** Returns the default horizontal alignment to display this data in a table
	 * @param integer|null $sub_model the sub model used
	 * @return string|null the horizontal alignment, or null if no default
	 */
	public function getHorizontalAlign($sub_model) { return null; }
	/** Indicates if this data can be modified by the user
	 * @return boolean true if this data is editable
	 */
	public abstract function isEditable();
	/** Indicates if this data can be entered by the user when creating a new data
	 * @return boolean true if this data is editable
	 */
	public function isEditableForNewData() { return $this->isEditable(); }
	/** Return a list of elements to lock when the user wants to edit this data
	 * @param number|null $sub_model sub model instance
	 * @return array list of elements to lock, each element being an associative array with at least 'table', and optionally 'column'. Optionally also, 'lock_each_row' (boolean) indicates if the entire column should be locked when editing multiple entries, or each row should be locked individually.
	 */
	public abstract function getEditLocks($sub_model);
	/** Indicates if this data is something that can be sorted like strings or numbers, or dates...
	 * @return boolean true if it can be sorted
	 */
	public abstract function isSortable();
	/** Indicates if this data is mandatory in the table (when creating a new entry/row in the table, this data must be specified)
	 * @param number|null $sub_model sub model instance
	 * @return true if it is mandatory
	 */
	public abstract function isMandatory($sub_model);
	/** Similar to getTypedField, this one is how to filter this data, using a JavaScript widget implementing typed_filter
	 * @param number|null $sub_model sub model instance
	 * @return array (classname, config) classname: string containing the name of the JavaScript class implementing typed_filter, config: string containing a JSON representation of the configuration of the typed_filter
	 */
	public function getTypedFilter($sub_model) { return null; }
	/** Indicates if this data represents a single cell in the database (a single column in a single table)
	 * @return array|null null if not a single cell, else an associative array containing 'table' and 'column'
	 */
	public function getSingleCell() { return null; }
	
	/** If this data can be split into sub-data, returns the sub-data information
	 * @return SubDataDisplay the sub-data information
	 */
	public function getSubDataDisplay() { return null; }
	
	/** Add any necessary select/join/field to the SQLQuery to retrieve this data
	 * @param SQLQuery $q the sql
	 * @param DataPath $path how we reached this data
	 * @return array("key"=&gt;key_alias,"data"=&gt;data_alias): if data is null or not specified, the function performSubRequest must be implemented
	 */
	public abstract function buildSQL(&$q, $path, $filtered);
	/** Get the condition to be added to apply the given filter
	 * @param \SQLQuery $q the query
	 * @param \DataPath $path the path to reach this data
	 * @param array $data_aliases array returned by the buildSQL method
	 * @param array $filter the filter
	 * @return array with 2 keys: <code>type</code>: either 'where' or 'having', <code>condition</code>: the SQL condition to add
	 */
	public abstract function getFilterCondition(&$q, $path, $data_aliases, $filter);
	/** Perform any necessary sub-requests to retrieve the data, once the first one is already executed, and update the rows and aliases as needed so $rows[x][$sql_aliases["data"]] returns the value for row number x
	 * @param SQLQuery $original_query the query that was givven in buildSQL
	 * @param array $rows list of rows that have been retrieved with the original query
	 * @param array $sql_aliases the value returned by buildSQL
	 * @param DataPath $path how we reached this data
	 * @return array of array(sub request,result)
	 */
	public function performSubRequests(&$original_query, &$rows, &$sql_aliases, $path) { return array(); }
	
	/** Structure to use when creating a new data. This structure will be used in the typed_field, to edit the new data
	 * @return array|null the associative array will then be converted into JSON, to give it to the typed_field 
	 */
	public function getNewData() { return null; }
	
	/** Retrieve the value of this data. By default get the result from the data alias.
	 * @param array|null $row the row from the database
	 * @param array $resultFromBuildSQL the result returned by the previous call to buildSQL
	 */
	public function getData($row, $resultFromBuildSQL) {
		return @$row[$resultFromBuildSQL["data"]];
	}
	/** Given a table key, return the key to send. By default returns the same.
	 * @param integer|array $key the key in the table
	 * @return mixed the value to send as the key
	 */
	public function getKeyFromTableKey($key) { return $key; }
	/**
	 * Add a where condition to select only value corresponding to the key received
	 * @param \SQLQueyr $q the original query
	 * @param \datamodel\Table $table the table
	 * @param mixed $key the key received
	 */
	public function selectTableKeyFromKey(&$q, $table, $key) { $q->whereKey($table, $key); }
	
	/** Returns a text corresponding to the value, to be used when exporting in an Excel file
	 * @param mixed $value the value
	 * @param integer|null $sub_model the sub model
	 * @return string the exported value in a text form
	 */
	public function exportValue($value, $sub_model) {
		if ($value == null) return "";
		return $value;
	}
	/** Returns the number of elements to be exported for the given value
	 * @param mixed $value the value
	 * @param integer|null $sub_model the sub model
	 * @return number number of elements. By default 1.
	 */
	public function getExportTimes($value, $sub_model) {
		return 1;
	}
	/** In case several elements need to be exported, returns the nth value to export
	 * @param mixed $value the value
	 * @param integer|null $sub_model the sub model
	 * @param integer $number nth
	 * @return string value to export
	 */
	public function exportValueNumber($value, $sub_model, $number) {
		return $this->exportValue($value, $sub_model);
	}

	/** Save a new value for this data. If it can be done together with other updates/insert, the array tables_fields is filled, else the operations are done directly.
	 * @param number|array $key row key
	 * @param mixed $value the new value, its type and content depends on the type of this data and its typed_field
	 * @param number|null $sub_model sub model instance
	 * @param TablesToUpdate $tables_fields list of values which should be updated/inserted
	 * @param string $joining_key_for_new_data column used to join
	 * @param integer|array $joining_key_value_for_new_data key used to join to this table, when creating a new data
	 */
	public abstract function saveData($key, $value, $sub_model, &$tables_fields, $joining_key_for_new_data, $joining_key_value_for_new_data);
	
	/** Create a string creating a new instance of the DataDisplay object in JavaScript
	 * @param number|null $sub_model sub model instance
	 * @return string the JavaScript string to execute to create the instance of the DataDisplay
	 */
	public final function javascriptDataDisplay($sub_model) {
		$s = "new DataDisplay(";
		$s .= json_encode($this->getCategoryName());
		$s .= ",".json_encode($this->getDisplayName());
		$s .= ",".json_encode($this->getTableName());
		$tf = $this->getTypedField($sub_model);
		$s .= ",".json_encode($tf[0]);
		$s .= ",".$tf[1];
		$s .= ",".json_encode($this->getHorizontalAlign($sub_model));
		$s .= ",".($this->isEditable() ? "true" : "false");
		$s .= ",".json_encode($this->getEditLocks($sub_model));
		$s .= ",".json_encode($this->isSortable());
		$tf = $this->getTypedFilter($sub_model);
		$s .= ",".json_encode($tf <> null ? $tf[0] : null);
		$s .= ",".($tf <> null ? $tf[1] : "null");
		$s .= ",".json_encode($this->getSingleCell());
		$s .= ",".json_encode($this->getNewData());
		$sub = $this->getSubDataDisplay();
		if ($sub == null)
			$s .= ",null";
		else {
			$s .= ",new SubDataDisplay([";
			$names = $sub->getDisplayNames();
			for ($i = 0; $i < count($names); $i++) {
				if ($i > 0) $s .= ",";
				$s .= json_encode($names[$i]);
			}
			$s .= "],[";
			for ($i = 0; $i < count($names); $i++) {
				if ($i > 0) $s .= ",";
				$s .= $sub->isEditable($i) ? "true" : "false";
			}
			$s .= "],[";
			for ($i = 0; $i < count($names); $i++) {
				if ($i > 0) $s .= ",";
				$s .= $sub->isEditableForNew($i) ? "true" : "false";
			}
			$s .= "]";
			$s .= ")";
		}
		$s .= ")";
		return $s;
	}
	
	/**
	 * Utility method to get common filters' condition in a SQLQuery
	 * @param string $table_alias alias of the table
	 * @param \datamodel\Column $col the column
	 * @param array $filter the filter
	 * @param SQLQuery $q the sql query
	 */
	protected function getColumnFilterCondition($table_alias, $col, $filter, &$q) {
		$tf = \PNApplication::$instance->widgets->get_typed_filter($col);
		$cd = $this->getExpressionFilterCondition("`".$table_alias."`.`".$col->name."`", $tf[0], $filter, $q);
		if ($cd == null) return null;
		return array("type"=>"where","condition"=>$cd);
	}
	/**
	 * Returns the SQL condition for the given filter
	 * @param string $expression the SQL expression on which the filter apply
	 * @param string $filter_type filter type: the default supported ones are filter_text, filter_enum, and filter_comparable
	 * @param array $filter the filter
	 * @param \SQLQuery $q the query
	 * @return NULL|string the condition
	 */
	protected function getExpressionFilterCondition($expression, $filter_type, $filter, &$q) {
		if ($filter == null) return null;
		switch ($filter_type) {
			case "filter_text":
				$val = trim($filter["value"]);
				if ($val == "") return null;
				$w = $expression;
				switch ($filter["type"]) {
					case "contains": return $expression." LIKE '%".\SQLQuery::escape($filter["value"])."%'";
					case "starts": return $expression." LIKE '".\SQLQuery::escape($filter["value"])."%'";
					case "ends": return $expression." LIKE '%".\SQLQuery::escape($filter["value"])."'";
					case "exact": return $expression." = '".\SQLQuery::escape($filter["value"])."'";
				}
				return null;
			case "filter_enum":
				if (!isset($filter["values"]) || !is_array($filter["values"])) return null;
				$w = "";
				foreach ($filter["values"] as $enum_value) {
					if ($enum_value === "") continue;
					if (strlen($w) > 0) $w .= " OR ";
					$w .= $expression;
					if ($enum_value === "NULL") $w .= " IS NULL";
					else if ($enum_value === "NOT_NULL") $w .= " IS NOT NULL";
					else $w .= "='".\SQLQuery::escape($enum_value)."'";
				}
				if ($w == "") return null;
				return $w;
			case "filter_comparable":
				$val = trim($filter["value"]);
				if ($val == "" && ($filter["type"] <> "is_set" && $filter["type"] <> "is_not_set")) return null;
				switch ($filter["type"]) {
					case "equals": return $expression."='".\SQLQuery::escape($filter["value"])."'";
					case "not_equals": return "(".$expression."!='".\SQLQuery::escape($filter["value"])."' OR ".$expression." IS NULL)";
					case "less": return $expression."<'".\SQLQuery::escape($filter["value"])."'";
					case "more": return $expression.">'".\SQLQuery::escape($filter["value"])."'";
					case "less_equals": return $expression."<='".\SQLQuery::escape($filter["value"])."'";
					case "more_equals": return $expression.">='".\SQLQuery::escape($filter["value"])."'";
					case "between": if (trim($filter["value_to"])=="") return null; return "(".$expression.">='".\SQLQuery::escape($filter["value"])."' AND ".$expression."<='".\SQLQuery::escape($filter["value_to"])."')";
					case "not_between": if (trim($filter["value_to"])=="") return null; return "(".$expression."<'".\SQLQuery::escape($filter["value"])."' OR ".$expression.">'".\SQLQuery::escape($filter["value_to"])."')";
					case "is_set": return $expression." IS NOT NULL";
					case "is_not_set": return $expression." IS NULL";
				}
				return null;
		}
		return null;
	}
	
}

/**
 * When a DataDisplay can be split into sub-data, this class gives information about the sub-data
 */
abstract class SubDataDisplay {
	
	/**
	 * @var DataDisplay the data display
	 */
	protected $parent;
	
	/**
	 * @param DataDisplay $parent the data display
	 */
	public function __construct($parent) {
		$this->parent = $parent;
	}
	
	/** Returns the names of the sub-data
	 * @return string[] display names
	 */
	public abstract function getDisplayNames();
	/** Indicates if a sub-data can be edited
	 * @param integer $index index of the sub-data
	 * @return boolean true if editable
	 */
	public abstract function isEditable($index);
	/** Indicates if a sub-data can be edited when creating a new data
	 * @param integer $index index of the sub-data
	 * @return boolean true if editable
	 */
	public function isEditableForNew($index) { return $this->isEditable($index); }
	/** Export the value for a sub-data
	 * @param mixed $value the value, coming from buildSQL
	 * @param integer|null $sub_model the sub model
	 * @param integer $index index of the sub-data
	 * @return string the text corresponding to the value
	 */
	public function exportValue($value, $sub_model, $index) { return $value; }
	/** Export the value for a sub-data, when several parent data are contained into the value
	 * @param mixed $value the value, coming from buildSQL
	 * @param integer|null $sub_model the sub model
	 * @param integer $index index of the sub-data
	 * @param integer $number the parent data index in the value
	 * @return string the text corresponding to the value
	 */
	public function exportValueNumber($value, $sub_model, $index, $number) { return $this->exportValue($value, $sub_model, $index); }
	
}

/**
 * Simplest implementation of the DataDisplay: the data is in a single column, and does not need any additional logic (like if the column is an integer, we can just display it as an integer...)
 */
class SimpleDataDisplay extends DataDisplay {

	/** @var string name of the column */
	protected $column_name;
	/** @var string name to display */
	protected $display_name;
	/** @var boolean indicates if we can edit this column */
	protected $readonly;
	/** @var boolean indicates if we can edit this column when creating a new data */
	protected $editable_for_new;
	/** @var string either 'right','left' or 'center' */
	protected $horiz_align;
	
	/**
	 * @param string $column_name name of the column
	 * @param string $display_name name to display
	 * @param boolean $readonly indicates if we can edit this column
	 * @param boolean $editable_for_new indicates if we can edit this column when creating a new data
	 * @param string $horiz_align either 'right','left' or 'center'
	 */
	public function __construct($column_name, $display_name, $readonly = false, $editable_for_new = null, $horiz_align = null) {
		$this->column_name = $column_name;
		$this->display_name = $display_name;
		$this->readonly = $readonly;
		$this->editable_for_new = $editable_for_new === null ? !$readonly : $editable_for_new;
		$this->horiz_align = $horiz_align; 
	}
	
	public function getDisplayName() {
		return $this->display_name;
	}
	public function canAccess($sub_model) {
		return $this->getTable()->canAccess() && $this->getTable()->canAccessColumn($this->column_name);
	}
	public function getHandledColumns() {
		return array($this->column_name);
	}
	public function getSingleCell() {
		return array("table"=>$this->getTableName(), "column"=>$this->column_name);
	}
	
	public function getTypedField($sub_model) {
		$col = $this->getTable()->getColumnFor($this->column_name, $sub_model);
		return \PNApplication::$instance->widgets->get_typed_field($col);
	}
	public function getHorizontalAlign($sub_model) {
		if ($this->horiz_align === null) {
			$col = $this->getTable()->internalGetColumnFor($this->column_name, $sub_model);
			if ($col instanceof \datamodel\ColumnDate) return "right";
		}
		return $this->horiz_align;
	}
	
	public function getTypedFilter($sub_model) {
		$col = $this->getTable()->getColumnFor($this->column_name, $sub_model);
		return \PNApplication::$instance->widgets->get_typed_filter($col);
	}
	
	public function isEditable() {
		if ($this->readonly) return false;
		return $this->getTable()->mayModify();
	}
	public function isEditableForNewData() {
		if (!$this->editable_for_new) return false;
		return $this->getTable()->mayModify();
	}
	public function isSortable() {
		return true;
	}
	public function isMandatory($sub_model) {
		$col = $this->getTable()->getColumnFor($this->column_name, $sub_model);
		return !$col->can_be_null;
	}
	
	public function getEditLocks($sub_model) {
		if ($this->getTable()->canModifyEverything() || $this->getTable()->canModifyColumn($this->column_name))
			return array(array("table"=>$this->getTable()->getName(), "column"=>$this->column_name, "sub_model"=>$sub_model));
		return array(array("table"=>$this->getTable()->getName(), "column"=>$this->column_name, "sub_model"=>$sub_model, "lock_each_row"=>true));
	}
	
	public function buildSQL(&$q, $path, $filtered) {
		$table_alias = \DataPathSQLBuilder::build($path, $q);
		$alias = $q->getFieldAlias($table_alias, $this->column_name);
		if ($alias == null) {
			$alias = $q->generateFieldAlias();
			$q->field($table_alias, $this->column_name, $alias);
		}
		$result = array("data"=>$alias);
		$key = $this->getTable()->getPrimaryKey();
		if ($key <> null) {
			$key = $key->name;
			$alias = $q->getFieldAlias($table_alias, $key);
			if ($alias == null) {
				$alias = $q->generateFieldAlias();
				$q->field($table_alias, $key, $alias);
			}
			$result["key"] = $alias;
		} else {
			$key = $this->getTable()->getKey();
			$aliases = array();
			foreach ($key as $colname) {
				$alias = $q->getFieldAlias($table_alias, $colname);
				if ($alias == null) {
					$alias = $q->generateFieldAlias();
					$q->field($table_alias, $colname, $alias);
				}
				array_push($aliases, $alias);
			}
			$result["key"] = $aliases;
		}
		return $result;
	}
	public function getFilterCondition(&$q, $path, $data_aliases, $filter) {
		$table_alias = $q->getTableAlias($path->table->getName());
		$col = $this->getTable()->getColumnFor($this->column_name, $path->sub_model);
		return $this->getColumnFilterCondition($table_alias, $col, $filter, $q);
	}
	
	public function saveData($key, $value, $sub_model, &$table_fields, $joining_key_for_new_data, $joining_key_value_for_new_data) {
		$table_fields->addValue($this->getTableName(), $sub_model, $key, $this->column_name, $value);
	}
	
}

/**
 * Implementation of DataDisplay, when we have a foreign key representing a choice.<br/>
 * For example, if we have a foreign key <i>country</i> containing the ID of a country, this represents a choice among the list of countries.<br/>
 * When we want to display this data, we should display one column of the foreign table instead of the ID. In our example, this will be the column <i>name</i> in the foreign table (Country), so we display the country name instead of the country ID.<br/>
 * When editing this data, we will display a combo box (HTML select element), so that the user can choose. In our example, what will be displayed is the list of countries' name, but what will be edited in the database of the foreign key containing the country id, not the country name itself. 
 */
class ChoiceDataDisplay extends DataDisplay {

	/** @var string name of this data */
	protected $display_name;
	/** @var string column's name containing the foreign key */
	protected $foreign_key_name;
	/** @var string name of the column in the foreign table to display */
	protected $foreign_column_name;
	/** @var boolean indicates if this can be edited or not */
	protected $editable;
	/** @var boolean indicates if we can edit this column when creating a new data */
	protected $editable_for_new;
	/** @var string either 'right','left' or 'center' */
	protected $horiz_align;
	/** @var array associative array of column/value to filter */
	protected $filter;
	
	/**
	 * @param string $foreign_key_name column's name containing the foreign key
	 * @param string $foreign_column_name name of the column in the foreign table to display
	 * @param string $display_name name of this data
	 * @param boolean $editable indicates if this can be edited or not
	 * @param boolean $editable_for_new indicates if we can edit this column when creating a new data
	 * @param string $horiz_align either 'right','left' or 'center'
	 * @paramr array|null $filter associative array of column/value to filter
	 */
	public function __construct($foreign_key_name, $foreign_column_name, $display_name, $editable = true, $editable_for_new = null, $horiz_align = null, $filter = null) {
		$this->display_name = $display_name;
		$this->foreign_key_name = $foreign_key_name;
		$this->foreign_column_name = $foreign_column_name;
		$this->editable = $editable;
		$this->editable_for_new = $editable_for_new === null ? $editable : $editable_for_new;
		$this->horiz_align = $horiz_align;
		$this->filter = $filter; 
	}
	public function getDisplayName() { return $this->display_name; }
	public function canAccess($sub_model) {
		if (!$this->getTable()->canAccess()) return false;
		if (!$this->getTable()->canAccessColumn($this->foreign_key_name)) return false;
		$foreign_key = $this->getTable()->getColumnFor($this->foreign_key_name, $sub_model);
		$ft = \DataModel::get()->internalGetTable($foreign_key->foreign_table);
		if (!$ft->canAccess()) return false;
		if (!$ft->canAccessColumn($this->foreign_column_name)) return false;
		return true;
	}
	public function getHandledColumns() { return array($this->foreign_key_name); }
	
	/** @var array Cache the possible values */
	protected $possible_values = array();
	/**
	 * Retrieve the list of possible values
	 * @param integer|null $sub_model the sub model
	 * @return array list of possible values 
	 */
	protected function getPossibleValues($sub_model) {
		$foreign_key = $this->getTable()->getColumnFor($this->foreign_key_name, $sub_model);
		if (!isset($this->possible_values[$sub_model])) {
			$t = \DataModel::get()->getTable($foreign_key->foreign_table);
			$q = \SQLQuery::create()->select($t->getName());
			if ($sub_model <> null && ($t->getModel() instanceof \SubDataModel) && ($this->getTable()->getModel() instanceof \SubDataModel) && $t->getModel()->getParentTable() == $this->getTable()->getModel()->getParentTable())
				$q->selectSubModel($t->getModel()->getParentTable(), $sub_model);
			if ($this->filter <> null)
				foreach ($this->filter as $colname=>$value)
					if ($value === null)
						$q->whereNull($t->getName(), $colname);
					else
						$q->whereValue($t->getName(), $colname, $value);
			$q->field($t->getName(), $t->getPrimaryKey()->name, "KEY");
			$q->field($t->getName(), $this->foreign_column_name, "VALUE");
			$this->possible_values[$sub_model] = $q->execute();
		}
		return $this->possible_values[$sub_model];
	}
	public function getTypedField($sub_model) {
		if ($sub_model <> "@link") {
			$foreign_key = $this->getTable()->getColumnFor($this->foreign_key_name, $sub_model);
			$cfg = "{";
			$cfg .= "possible_values:[";
			$res = $this->getPossibleValues($sub_model);
			$first = true;
			foreach ($res as $row) {
				if ($first) $first = false; else $cfg .= ",";
				$cfg .= "[";
				$cfg .= json_encode($row["KEY"]);
				$cfg .= ",".json_encode($row["VALUE"]);
				$cfg .= "]";
			}
			$cfg .= "]";
			$cfg .= ",can_be_null:".($foreign_key->can_be_null ? "true" : "false");
			$cfg .= "}";
			return array("field_enum", $cfg);
		}
		return array("field_text","{can_be_null:true}");
	}
	public function getHorizontalAlign($sub_model) { return $this->horiz_align; }
	
	public function getTypedFilter($sub_model) {
		if ($sub_model <> "@link") {
			$foreign_key = $this->getTable()->getColumnFor($this->foreign_key_name, $sub_model);
			$cfg = "{";
			$cfg .= "possible_values:[";
			$res = $this->getPossibleValues($sub_model);
			$first = true;
			foreach ($res as $row) {
				if ($first) $first = false; else $cfg .= ",";
				$cfg .= "[";
				$cfg .= json_encode($row["KEY"]);
				$cfg .= ",".json_encode($row["VALUE"]);
				$cfg .= "]";
			}
			$cfg .= "]";
			$cfg .= ",can_be_null:".($foreign_key->can_be_null ? "true" : "false");
			$cfg .= "}";
			return array("filter_enum", $cfg);
		}
		return null;
	}
	
	public function isEditable() { return $this->editable; }
	public function isEditableForNewData() { return $this->editable_for_new; }
	public function getEditLocks($sub_model) {
		if (!$this->editable || $sub_model == "@link") return array();
		return array(
			array("table"=>$this->getTable()->getSQLNameFor($sub_model))
		);
	}
	public function isSortable() { return true; }
	public function isMandatory($sub_model) {
		$foreign_key = $this->getTable()->getColumnFor($this->foreign_key_name, $sub_model);
		return !$foreign_key->can_be_null;
	}
	
	public function exportValue($value, $sub_model) {
		if ($value == null || $value == "") return "";
		$values = $this->getPossibleValues($sub_model);
		foreach ($values as $val) {
			if ($value == $val["KEY"])
				return $val["VALUE"];
		}
		return "?";
	}
	
	public function buildSQL(&$q, $path, $filtered) {
		// we need to get the key, and the value to display
		$foreign_key = $this->getTable()->getColumnFor($this->foreign_key_name, $path->sub_model);
		$t = \DataModel::get()->getTable($foreign_key->foreign_table);
		
		// go to the table
		$table_alias = \DataPathSQLBuilder::build($path, $q);
		// join to foreign table
		$alias = $q->getJoinAlias($table_alias, $t->getName(), array($foreign_key->name=>$t->getPrimaryKey()->name));
		if ($alias == null) {
			$alias = $q->generateTableAlias();
			$q->join($table_alias, $t->getName(), array($foreign_key->name=>$t->getPrimaryKey()->name), $alias);
		}
		// get the key and data
		if ($path->sub_model <> null && isset($path->sub_model_from_link)) {
			$key_alias = $q->getFieldAlias($t->getName(), $t->getPrimaryKey()->name);
			if ($key_alias == null) {
				$key_alias = $q->generateFieldAlias();
				$q->field($alias, $t->getPrimaryKey()->name, $key_alias);
			}
			$data_alias = $q->getFieldAlias($t->getName(), $this->foreign_column_name);
			if ($data_alias == null) {
				$data_alias = $q->generateFieldAlias();
				$q->field($t->getName(), $this->foreign_column_name, $data_alias);
			}
		} else {
			$col_name = $this->getTable()->getPrimaryKey()->name;
			$key_alias = $q->getFieldAlias($table_alias, $col_name);
			if ($key_alias == null) {
				$key_alias = $q->generateFieldAlias();
				$q->field($table_alias, $col_name, $key_alias);
			}
			$data_alias = $q->getFieldAlias($table_alias, $foreign_key->name);
			if ($data_alias == null) {
				$data_alias = $q->generateFieldAlias();
				$q->field($table_alias, $foreign_key->name, $data_alias);
			}
		}
		return array("key"=>$key_alias,"data"=>$data_alias);
	}
	public function getFilterCondition(&$q, $path, $data_aliases, $filter) {
		if (!isset($filter["values"]) || !is_array($filter["values"])) return null;
		$table_alias = $q->getTableAlias($path->table->getName());
		$foreign_key = $this->getTable()->getColumnFor($this->foreign_key_name, $path->sub_model);
		$w = "";
		foreach ($filter["values"] as $enum_value) {
			if ($enum_value == "") continue;
			if (strlen($w) > 0) $w .= " OR ";
			$w .= "`".$table_alias."`.`".$foreign_key->name."`";
			if ($enum_value === "NULL") $w .= " IS NULL";
			else if ($enum_value === "NOT_NULL") $w .= " IS NOT NULL";
			else $w .= "='".\SQLQuery::escape($enum_value)."'";
		}
		if ($w == "") return null;
		return array("type"=>"where","condition"=>$w);
	}
	
	public function saveData($key, $value, $sub_model, &$tables_fields, $joining_key_for_new_data, $joining_key_value_for_new_data) {
		$tables_fields->addValue($this->getTableName(), $sub_model, $key, $this->foreign_key_name, $value);
	}
		
}

/**
 * This DataDisplay is used when we have a table joining 2 tables, with a 1 to n relation. For example, if we have a list of emails for a person, we will have several emails for a single person.
 * This will be displayed as a list of element, and each element can be edited/removed/added. 
 */
class JoiningTableDataDisplay extends DataDisplay {

	/** @var string name of this data */
	protected $display_name;
	/** @var string name of the column making the join to the single element in the 1 to n relation */
	protected $joining_key_name;
	/** @var string name of the column containing the foreign key to go to the table containing the n elements */
	protected $foreign_key_name;
	/** @var string name of the column in the foreign table that will be used to display instead of the foreign key ID */
	protected $foreign_table_column_name;
	
	/**
	 * @param string $display_name name of this data
	 * @param string $joining_key_name name of the column making the join to the single element in the 1 to n relation
	 * @param string $foreign_key_name name of the column containing the foreign key to go to the table containing the n elements
	 * @param string $foreign_table_column_name name of the column in the foreign table that will be used to display instead of the foreign key ID
	 */
	public function __construct($display_name, $joining_key_name, $foreign_key_name, $foreign_table_column_name) {
		$this->display_name = $display_name;
		$this->joining_key_name = $joining_key_name;
		$this->foreign_key_name = $foreign_key_name;
		$this->foreign_table_column_name = $foreign_table_column_name;
	}
	
	public function getDisplayName() { return $this->display_name; }
	public function canAccess($sub_model) {
		if (!$this->getTable()->canAccess()) return false;
		if (!$this->getTable()->canAccessColumn($this->foreign_key_name)) return false;
		$foreign_key = $this->getTable()->getColumnFor($this->foreign_key_name, $sub_model);
		$ft = \DataModel::get()->internalGetTable($foreign_key->foreign_table);
		if (!$ft->canAccess()) return false;
		if (!$ft->canAccessColumn($this->foreign_table_column_name)) return false;
		return true;
	}
	public function getHandledColumns() { return array(); } // not editing a specify column
	
	/** @var array Cache the possible values */
	protected $possible_values = array();
	/**
	 * Retrieve the list of possible values
	 * @param integer|null $sub_model the sub model
	 * @return array list of possible values 
	 */
	private function getPossibleValues($sub_model) {
		if (!isset($this->possible_values[$sub_model])) {
			$foreign_table = $this->getTable()->getColumnFor($this->foreign_key_name, $sub_model)->foreign_table;
			$foreign_table = \DataModel::get()->getTable($foreign_table);
			$sub_models = $sub_model == null ? null : array($this->getTable()->getModel()->getParentTable()=>$sub_model);
			$table_name = $foreign_table->getSQLName($sub_models);
			$this->possible_values[$sub_model] = \SQLQuery::create()
				->select($table_name)
				->field($table_name, $this->foreign_table_column_name, "VALUE")
				->field($table_name, $foreign_table->getPrimaryKey()->name, "KEY")
				->execute();
			$col = $foreign_table->getColumn($this->foreign_table_column_name);
			for ($i = 0; $i < count($this->possible_values[$sub_model]); $i++)
				$this->possible_values[$sub_model][$i]["VALUE"] = $col->getDisplayValue($this->possible_values[$sub_model][$i]["VALUE"]);
		}
		return $this->possible_values[$sub_model];
	}
	
	public function getTypedField($sub_model) {
		if ($sub_model <> "@link") {
			$list = $this->getPossibleValues($sub_model);
			$values = "[";
			$first = true;
			foreach ($list as $v) {
				if ($first) $first = false; else $values .= ",";
				$values .= "[".json_encode($v["KEY"]).",".json_encode($v["VALUE"])."]";
			}
			$values .= "]";
			return array("field_list_of_fixed_values", "{possible_values:$values}");
		}
		// TODO
		return array("field_text","{can_be_null:true}");
	}
	public function getTypedFilter($sub_model) {
		if ($sub_model <> "@link") {
			$cfg = "{";
			$cfg .= "possible_values:[";
			$res = $this->getPossibleValues($sub_model);
			$first = true;
			foreach ($res as $row) {
				if ($first) $first = false; else $cfg .= ",";
				$cfg .= "[";
				$cfg .= json_encode($row["KEY"]);
				$cfg .= ",".json_encode($row["VALUE"]);
				$cfg .= "]";
			}
			$cfg .= "]";
			$cfg .= ",can_be_null:true";
			$cfg .= "}";
			return array("filter_enum", $cfg);
		}
		return null;
	}
	public function exportValue($value, $sub_model) {
		if ($value == null || $value == "") return "";
		$values = $this->getPossibleValues($sub_model);
		$list = array();
		foreach ($values as $val) {
			if (in_array($val["KEY"], $value))
				array_push($list, $val["VALUE"]);
		}
		$s = "";
		for ($i = 0; $i < count($list); $i++) {
			if ($i > 0) $s .= ", ";
			$s .= $list[$i];
		}
		return $s;
	}
	
	public function isEditable() {
		if (!$this->getTable()->mayInsert()) return false;
		if (!$this->getTable()->mayRemove()) return false;
		return true;
	}
	public function getEditLocks($sub_model) {
		return array(
			array("table"=>$this->getTable()->getSQLNameFor($sub_model))
		);
	}
	public function isSortable() {
		return false;
	}
	public function isMandatory($sub_model) {
		return false;
	}
	
	public function buildSQL(&$q, $path, $filtered) {
		if ($path instanceof \DataPath_Table) {
			$this_alias = $q->getTableAlias($path->table->getName());
			$alias = $q->getFieldAlias($this_alias, $this->joining_key_name);
			if ($alias == null) {
				$alias = $q->generateFieldAlias();
				$q->field($this_alias, $this->joining_key_name, $alias);
			}
			return array("key"=>$alias,"data"=>null);
		}
		// we need to go to the parent, and get the joining key
		$parent_alias = $q->getTableAlias($path->parent->table->getName());
		if ($parent_alias == null)
			$parent_alias = \DataPathSQLBuilder::build($path->parent, $q);
		$key_name = $path->isReverse() ? $path->parent->table->getPrimaryKey()->name : $path->foreign_key->name;
		$alias = $q->getFieldAlias($parent_alias, $key_name);
		if ($alias == null) {
			$alias = $q->generateFieldAlias();
			$q->field($parent_alias, $key_name, $alias);
		}

		return array("key"=>$alias,"data"=>null);
	}
	
	public function getFilterCondition(&$q, $path, $data_aliases, $filter) {
		$primary_alias = $q->generateTableAlias();
		$foreign_alias = $q->generateTableAlias();
		
		$w = "";
		if (!isset($filter["values"]) || !is_array($filter["values"])) return null;
		foreach ($filter["values"] as $enum_value) {
			if ($enum_value == "") continue;
			if (strlen($w) > 0) $w .= " OR ";
			$w .= "`$primary_alias`.`".$this->foreign_key_name."`";
			if ($enum_value == "NULL") $w .= " IS NULL";
			else if ($enum_value == "NOT_NULL") $w .= " IS NOT NULL";
			else $w .= "='".\SQLQuery::escape($enum_value)."'";
		}
		if ($w == "") return null;
		$foreign_table = $path->table->getColumnFor($this->foreign_key_name, $path->sub_model)->foreign_table;
		$foreign_table = \DataModel::get()->getTable($foreign_table);
		$parent_alias = $q->getTableAlias($path->parent->table->getName());
		$key_name = $path->isReverse() ? $path->parent->table->getPrimaryKey()->name : $path->foreign_key->name;
		
		$sub_q = \SQLQuery::create()
			->select(array($path->table->getName()=>$primary_alias))
			->join($primary_alias, $foreign_table->getName(), array($this->foreign_key_name=>$foreign_table->getPrimaryKey()->name), $foreign_alias)
			->field($primary_alias, $this->foreign_key_name)
			->where($w)
			;
		
		$sub_q->where("`$primary_alias`.`".$this->joining_key_name."`=`".$parent_alias."`.`".$key_name."`");
		// avoid collisions in table aliases
		$sub_q->avoidAliasCollision($q);
		$sql = $sub_q->generate();
		$q->avoidAliasCollision($sub_q);
		
		return array("type"=>"where","condition"=>"EXISTS ($sql)");
	}
	
	public function performSubRequests(&$original_query, &$rows, &$sql_aliases, $path) {
		$table_name = $path->table->getName($path->sub_model);
		$foreign_table = $path->table->getColumnFor($this->foreign_key_name, $path->sub_model)->foreign_table;
		$foreign_table = \DataModel::get()->getTable($foreign_table);
		$sub_models = $path->sub_model == null ? null : array($path->table->getModel()->getParentTable()=>$path->sub_model);
		
		$data_alias = $sql_aliases["data"] = $original_query->generateFieldAlias();
		$key_alias = $sql_aliases["key"];
		
		$keys = array();
		$rows_keys = array();
		for ($i = count($rows)-1; $i >= 0; $i--) {
			$key = $rows[$i][$key_alias];
			$data = array();
			if (!isset($rows_keys[$key])) {
				array_push($keys, $key);
				$rows_keys[$key] = array(&$data);
			} else
				array_push($rows_keys[$key], $data);
			$rows[$i][$data_alias] = &$data;
			unset($data);
		}
		
		if (count($keys) == 0) {
			$res = array();
			$q = null;
		} else {
			$q = \SQLQuery::create()
				->select($table_name)
				->join($table_name, $foreign_table->getSQLName($sub_models), array($this->foreign_key_name => $foreign_table->getPrimaryKey()->name), "JOIN_ALIAS")
				->field("JOIN_ALIAS", $foreign_table->getPrimaryKey()->name, "VALUE")
				->field($table_name, $this->joining_key_name, "KEY")
				->whereIn($table_name, $this->joining_key_name, $keys)
				;
			$res = $q->execute();
		}

		foreach ($res as $r) {
			$list = @$rows_keys[$r["KEY"]];
			if ($list <> null)
				for ($i = count($list)-1; $i >= 0; $i--)
					array_push($list[$i], $r["VALUE"]);
		}
		if ($q == null) return array();
		return array(array($q,$res));
	}
	
	public function getData($row, $resultFromBuildSQL) {
		return $row[$resultFromBuildSQL["data"]];
	}
	
	public function getKeyFromTableKey($key) {
		if (is_array($key) && isset($key[$this->joining_key_name])) return $key[$this->joining_key_name];
		return $key; 
	}
	public function selectTableKeyFromKey(&$q, $table, $key) {
		$q->whereValue($table->getName(), $this->joining_key_name, $key);
	}
	
	public function saveData($key, $value, $sub_model, &$tables_fields, $joining_key_for_new_data, $joining_key_value_for_new_data) {
		require_once("component/data_model/DataBaseLock.inc");
		if ($key <> null) {
			$rows = \SQLQuery::create()->select($this->getTable()->getName())->whereValue($this->getTable()->getName(), $this->joining_key_name, $key)->execute();
			if (count($rows) > 0)
				\SQLQuery::create()->removeRows($this->getTable()->getName(), $rows);
		} else
			$key = $joining_key_value_for_new_data;
		$to_insert = array();
		foreach ($value as $v)
			array_push($to_insert, array($this->joining_key_name=>$key,$this->foreign_key_name=>$v));
		if (count($to_insert) > 0)
			\SQLQuery::create()->selectSubModelForTable($this->getTable(), $sub_model)->insertMultiple($this->getTableName(), $to_insert);
	}
		
}

/**
 * Used to display an information, that cannot be edited (because needs specific business logic or whatever reason...), and that can be reached from the table, meaning not necessarly directly in this table.
 */
class ReadOnlyDataDisplay extends DataDisplay {

	/** @var string name of this data */
	protected $display_name;
	/** @var string path to reach the column to display */
	protected $data_path;
	/** @var string horizontal alignment */
	protected $horiz_align;
	/** @var string if specified, the filter will be an enum using the foreign key in the given path (which should be a sub-part of the data path) */
	protected $filter_path;
	/** @var string if specified, the typed field will be this one instead of the default one */
	protected $override_typed_field;	
	
	/**
	 * @param string $display_name name of this data
	 * @param string $data_path path to reach the column to display.
	 * The format of the path is a list separated by dots (.).
	 * Each element of the list can be: <ul>
	 * 	<li>A column: if not final, this is a foreign key to follow, else this is the final column to display</li>
	 *  <li>A table containing a foreign key, with a &gt; symbol to separate the table name and the foreign key</li> 
	 * </ul>
	 * Moreover, each element can have a condition (WHERE), between parenthesis.
	 * Examples: <ul>
	 *  <li>country.name means to follow the foreign key 'country' and display the column 'name' of the country</li>
	 *  <li>Contact>people.type means to join table 'Contact' using foreign key 'people' on Contact table, then display the column 'type' on the Contact table</li>
	 *  <li>Contact>people(type=Work).contact means to join table 'Contact' using foreign key 'people' on Contact table and adding a condition on the join, then display the column 'contact' on the Contact table</li>
	 * </ul>
	 * @param string $horiz_align horizontal alignment
	 * @param string $filter_path if specified, the filter will be an enum using the foreign key in the given path (which should be a sub-part of the data path)
	 * @param string $override_typed_field if specified, the typed field will be this one instead of the default one
	 */
	public function __construct($display_name, $data_path, $horiz_align = "left", $filter_path = null, $override_typed_field = null) {
		$this->display_name = $display_name;
		$this->data_path = $data_path;
		$this->horiz_align = $horiz_align;
		$this->filter_path = $filter_path;
		$this->override_typed_field = $override_typed_field;
	}

	public function getDisplayName() { return $this->display_name; }
	public function canAccess($sub_model) {
		$steps = explode(".", $this->data_path);
		$current_table = $this->getTable();
		// take the final column name
		$final_column_name = $steps[count($steps)-1];
		array_splice($steps, count($steps)-1, 1);
		// go through each step
		foreach ($steps as $step) {
			if (!$current_table->canAccess()) return false;
			$next = $this->nextTable($current_table, $step, $sub_model);
			$current_table = $next[0];
		}
		if (!$current_table->canAccess()) return false;
		if (!$current_table->canAccessColumn($final_column_name)) return false;
		return true;
	}
	public function getHandledColumns() { return array(); }
	
	/**
	 * Internal method to get the final column in the data model 
	 * @param string $path the path
	 * @param number|null $sub_model sub model instance
	 * @return \datamodel\Column the final column
	 */
	private function getColumn($path, $sub_model) {
		$steps = explode(".", $path);
		$current_table = $this->getTable();
		$multiple = false;
		// take the final column name
		$final_column_name = $steps[count($steps)-1];
		array_splice($steps, count($steps)-1, 1);
		// go through each step
		foreach ($steps as $step) {
			$next = $this->nextTable($current_table, $step, $sub_model);
			$current_table = $next[0];
			if ($next[3] && $next[1]->multiple) {
				// the column indicates we can have multiple values, and we are on a reverse join
				// check if we have a condition that make it not multiple anymore
				if ($next[2] == null) {
					// no condition => this is multiple
					$multiple = true;
				} else {
					foreach ($next[2] as $col_cd=>$val_cd)
						$col_condition = $col_cd;
					$col_cd = $current_table->internalGetColumnFor($col_condition, $sub_model);
					if ($col_cd instanceof \datamodel\ForeignKey && $col_cd->multiple) {
						// the condition column is multiple
						$key = $current_table->getKey();
						if ($key <> null && count($key) == 2 && 
							(($key[0] == $next[1]->name && $key[1] == $col_cd->name) ||
							 ($key[0] == $col_cd->name && $key[1] == $next[1]->name))) {
							// the key of the table is on the 2 columns, so it is unique !
						} else
							$multiple = true;
					} else
						$multiple = true;
				}
			}
		}
		// take final column
		$col = $current_table->internalGetColumnFor($final_column_name, $sub_model);
		return array($col, $multiple);
	}
	/** Internal method to move to the next table on the data path
	 * @param \datamodel\Table $current_table the current table
	 * @param string $step how to reach the next table
	 * @param integer|null $sub_model the sub model
	 */
	private function nextTable($current_table, $step, $sub_model) {
		$i = strpos($step, "(");
		$condition = null;
		if ($i !== false) {
			// there is a condition
			$j = strrpos($step, ")");
			$condition = substr($step, $i+1, $j-$i-1);
			$step = substr($step, 0, $i);
			$i = strpos($condition, "=");
			$field_cd = trim(substr($condition,0,$i));
			$field_val = trim(substr($condition,$i+1));
			if ($field_val == "null") $field_val = null;
			$condition = array($field_cd=>$field_val);
		}
		$i = strpos($step, ">");
		if ($i !== false) {
			// this is a reverse join
			$table = \DataModel::get()->internalGetTable(substr($step, 0, $i));
			$fk_name = substr($step,$i+1);
			$fk = $table->internalGetColumnFor($fk_name, $sub_model);
			return array($table, $fk, $condition, true);
		}
		// this is a normal join
		$col = $current_table->internalGetColumnFor($step, $sub_model);
		return array(\DataModel::get()->internalGetTable($col->foreign_table), $col, $condition, false);
	}
	
	public function getTypedField($sub_model) {
		if ($sub_model <> "@link") {
			$col = $this->getColumn($this->data_path, $sub_model);
			$tf = $this->override_typed_field == null ? \PNApplication::$instance->widgets->get_typed_field($col[0]) : $this->override_typed_field;
			if ($col[1])
				return array("field_list","{element_type:".json_encode($tf[0]).",element_cfg:".$tf[1]."}");
			return $tf;
		}
		return array("field_text","{can_be_null:true}");
	}
	public function getHorizontalAlign($sub_model){ return $this->horiz_align; }
	
	public function isEditable() { return false; }
	public function getEditLocks($sub_model) { return array(); }
	public function isSortable() {
		return true;
	}
	public function isMandatory($sub_model) {
		return false; // TODO
	}

	/** @var array Cache the possible values */
	private $possible_values = array();
	/**
	 * Retrieve the list of possible values
	 * @param integer|null $sub_model the sub model
	 * @return array list of possible values 
	 */
	private function getPossibleValues($sub_model) {
		if (!isset($this->possible_values[$sub_model])) {
			// get the foreign key used for the enum
			$col = $this->getColumn($this->filter_path, $sub_model);
			$foreign_key = $col[0];
			// take the foreign table
			$t = \DataModel::get()->getTable($foreign_key->foreign_table);
			
			// the choice is the primary key of the foreign table
			$q = \SQLQuery::create()->select($t->getName());
			if ($sub_model <> null && ($t->getModel() instanceof \SubDataModel) && ($this->getTable()->getModel() instanceof \SubDataModel) && $t->getModel()->getParentTable() == $this->getTable()->getModel()->getParentTable())
				$q->selectSubModel($t->getModel()->getParentTable(), $sub_model);
			$q->field($t->getName(), $t->getPrimaryKey()->name, "KEY");
			
			// continue the steps to have the value
			$next_part = substr($this->data_path, strlen($this->filter_path)+1);
			$steps = explode(".", $next_part);
			$current_table = $t;
			// take the final column name
			$final_column_name = $steps[count($steps)-1];
			array_splice($steps, count($steps)-1, 1);
			// go through each step
			foreach ($steps as $step) {
				// check condition
				$i = strpos($step, "(");
				$condition = null;
				if ($i !== false) {
					$j = strrpos($step, ")");
					$condition = substr($step, $i+1, $j-$i-1);
					$step = substr($step, 0, $i);
					$i = strpos($condition, "=");
					$field_cd = trim(substr($condition,0,$i));
					$field_val = trim(substr($condition,$i+1));
					if ($field_val == "null") $field_val = null;
					$condition = array($field_cd=>$field_val);
				}
				// make join
				$i = strpos($step, ">");
				if ($i !== false) {
					// reverse join
					$ft = \DataModel::get()->getTable(substr($step,0,$i));
					$col = $ft->getColumnFor(substr($step,$i+1), $sub_model);
					$q->join($current_table->getName(), $ft->getName(), array($current_table->getPrimaryKey()->name => $col->name), null, $condition);
					$current_table = $ft;
				} else {
					// normal join
					$col = $current_table->getColumnFor($step, $sub_model);
					$ft = \DataModel::get()->getTable($col->foreign_table);
					$q->join($current_table->getName(), $col->foreign_table, array($step=>$ft->getPrimaryKey()->name), null, $condition);
					$current_table = $ft;
				}
			}
			// take final column
			$col = $current_table->internalGetColumnFor($final_column_name, $sub_model);
			$q->field($current_table->getName(), $col->name, "VALUE");
			
			$this->possible_values[$sub_model] = $q->execute();
			for ($i = 0; $i < count($this->possible_values[$sub_model]); $i++)
				$this->possible_values[$sub_model][$i]["VALUE"] = $col->getDisplayValue($this->possible_values[$sub_model][$i]["VALUE"]);
		}
		return $this->possible_values[$sub_model];
	}
	public function getTypedFilter($sub_model) {
		if ($sub_model <> "@link") {
			if ($this->filter_path == null) {
				$col = $this->getColumn($this->data_path, $sub_model);
				$tf = \PNApplication::$instance->widgets->get_typed_filter($col[0]);
				if ($col[1])
					return array("filter_list","{element_type:".json_encode($tf[0]).",element_cfg:".$tf[1]."}");
				return $tf;
			}
			$col = $this->getColumn($this->filter_path, $sub_model);
			// TODO multiple
			$foreign_key = $col[0];
			$cfg = "{";
			$cfg .= "possible_values:[";
			$res = $this->getPossibleValues($sub_model);
			$first = true;
			foreach ($res as $row) {
				if ($first) $first = false; else $cfg .= ",";
				$cfg .= "[";
				$cfg .= json_encode($row["KEY"]);
				$cfg .= ",".json_encode($row["VALUE"]);
				$cfg .= "]";
			}
			$cfg .= "]";
			$cfg .= ",can_be_null:".($foreign_key->can_be_null ? "true" : "false");
			$cfg .= "}";
			return array("filter_enum", $cfg);
		}
		return null;
	}
	
	public function getSingleCell() { 
		$col = $this->getColumn($this->data_path, null); 
		return array("table"=>$col[0]->table->getName(), "column"=>$col[0]->name);
	}
	
	public function buildSQL(&$q, $path, $filtered) {
		$steps = explode(".", $this->data_path);
		$current_table = $this->getTable();
		$current_table_alias = \DataPathSQLBuilder::build($path, $q);
		// take the final column name
		$final_column_name = $steps[count($steps)-1];
		array_splice($steps, count($steps)-1, 1);
		// go through each step
		$last_key = null;
		$multiple_sub_query = null;
		$multiple_start_alias = null;
		$multiple_start_table = null;
		$multiple_start_column = null;
		$multiple_last_table = null;
		$multiple_last_key = null;
		foreach ($steps as $step) {
			// check condition
			$i = strpos($step, "(");
			$condition = null;
			if ($i !== false) {
				$j = strrpos($step, ")");
				$condition = substr($step, $i+1, $j-$i-1);
				$step = substr($step, 0, $i);
				$i = strpos($condition, "=");
				$field_cd = trim(substr($condition,0,$i));
				$field_val = trim(substr($condition,$i+1));
				if ($field_val == "null") $field_val = null;
				$condition = array($field_cd=>$field_val);
			}
			// make join
			$i = strpos($step, ">");
			if ($i !== false) {
				// reverse join
				$ft = \DataModel::get()->getTable(substr($step,0,$i));
				$col = $ft->getColumnFor(substr($step,$i+1), $path->sub_model);
				
				$is_multiple = false;
				if ($col->multiple) {
					if ($condition == null) {
						// no condition => this is multiple
						$is_multiple = true;
					} else {
						foreach ($condition as $col_cd=>$val_cd)
							$col_condition = $col_cd;
						$col_cd = $ft->internalGetColumnFor($col_condition, $path->sub_model);
						if ($col_cd instanceof \datamodel\ForeignKey && $col_cd->multiple) {
							// the condition column is multiple
							$key = $ft->getKey();
							if ($key <> null && count($key) == 2 &&
								(($key[0] == $col->name && $key[1] == $col_cd->name) ||
								 ($key[0] == $col_cd->name && $key[1] == $col->name))) {
								// the key of the table is on the 2 columns, so it is unique !
							} else
								$is_multiple = true;
						} else {
							$is_multiple = true;
						}
					}
				}				
				
				if ($is_multiple) {
					// multiple: we cannot go through, and need a sub query
					if ($multiple_sub_query == null) {
						$multiple_start_alias = $q->generateFieldAlias();
						$q->field($current_table_alias, $current_table->getPrimaryKey()->name, $multiple_start_alias);
						$multiple_sub_query = \SQLQuery::create();
						$multiple_last_table = $current_table_alias;
						$multiple_last_key = $current_table->getPrimaryKey()->name;
						$current_table = $ft;
						$current_table_alias = $q->generateTableAlias();
						$multiple_start_table = $current_table_alias;
						$multiple_start_column = $col->name;
						$multiple_sub_query->select(array($ft->getName()=>$current_table_alias));
						if ($condition <> null)
							$multiple_sub_query->where($condition);
					} else {
						$fta = $q->generateTableAlias();
						$multiple_sub_query->join($current_table_alias, $ft->getName(), array($current_table->getPrimaryKey()->name => $col->name), $fta, $condition);
						$current_table = $ft;
						$current_table_alias = $fta;
					}
				} else if ($multiple_sub_query <> null) {
					$fta = $q->generateTableAlias();
					$multiple_sub_query->join($current_table_alias, $ft->getName(), array($current_table->getPrimaryKey()->name => $col->name), $fta, $condition);
					$current_table = $ft;
					$current_table_alias = $fta;
				} else {
					// TODO $last_key
					$fta = $q->generateTableAlias();
					$q->join($current_table_alias, $ft->getName(), array($current_table->getPrimaryKey()->name => $col->name), $fta, $condition);
					$current_table = $ft;
					$current_table_alias = $fta;
				}
			} else {
				// normal join
				$col = $current_table->getColumnFor($step, $path->sub_model);
				$ft = \DataModel::get()->getTable($col->foreign_table);
				$last_key = $ft->getPrimaryKey();
				if ($multiple_sub_query <> null) {
					$fta = $q->generateTableAlias();
					$multiple_sub_query->join($current_table_alias, $col->foreign_table, array($step=>$ft->getPrimaryKey()->name), $fta, $condition);
					$current_table = $ft;
					$current_table_alias = $fta;
				} else {
					$fta = $q->generateTableAlias();
					$q->join($current_table_alias, $col->foreign_table, array($step=>$ft->getPrimaryKey()->name), $fta, $condition);
					$current_table = $ft;
					$current_table_alias = $fta;
				}
			}
		}
		// take final column
		$col = $current_table->internalGetColumnFor($final_column_name, $path->sub_model);
		if ($multiple_sub_query == null) {
			$key_alias = $q->generateFieldAlias();
			if ($current_table->getPrimaryKey() <> null) {
				$q->field($current_table_alias, $current_table->getPrimaryKey()->name, $key_alias);
			} else {
				$columns = $current_table->getKey();
				$expr = "";
				foreach ($columns as $c) {
					if ($expr <> "") $expr .= ",'_',";
					$expr .= "`".$current_table_alias."`.`".$c."`";
				}
				$expr = "CONCAT(".$expr.")";
				$q->expression($expr, $key_alias);
			}
			$data_alias = $q->generateFieldAlias();
			$q->field($current_table_alias, $col->name, $data_alias);
	
			return array("key"=>$key_alias,"data"=>$data_alias,"last_table"=>$current_table_alias,"final_column"=>$col);
		} else {
			$key_alias = $q->generateFieldAlias();
			if ($current_table->getPrimaryKey() <> null) {
				$multiple_sub_query->field($current_table_alias, $current_table->getPrimaryKey()->name, $key_alias);
			} else {
				$columns = $current_table->getKey();
				$expr = "";
				foreach ($columns as $c) {
					if ($expr <> "") $expr .= ",'_',";
					$expr .= "`".$current_table_alias."`.`".$c."`";
				}
				$expr = "CONCAT(".$expr.")";
				$multiple_sub_query->expression($expr, $key_alias);
			}
			$data_alias = $q->generateFieldAlias();
			$multiple_sub_query->field($current_table_alias, $col->name, $data_alias);
			return array("key"=>null,"data"=>null,"key_alias"=>$key_alias,"data_alias"=>$data_alias,
				"sub_query"=>$multiple_sub_query,
				"sub_query_start_alias"=>$multiple_start_alias,
				"sub_query_start_table"=>$multiple_start_table,
				"sub_query_start_column"=>$multiple_start_column,
				"last_original_query_table"=>$multiple_last_table,
				"last_original_query_key"=>$multiple_last_key
			);
		}
	}
	
	public function performSubRequests(&$original_query, &$rows, &$sql_aliases, $path) {
		if (!isset($sql_aliases["sub_query"])) return null;
		$data_alias = $original_query->generateFieldAlias();
		$sql_aliases["data"] = $data_alias;
		$q = $sql_aliases["sub_query"];
		$list = array();
		$rows_map = array();
		for ($i = count($rows)-1; $i >= 0; $i--) {
			$key = $rows[$i][$sql_aliases["sub_query_start_alias"]];
			if ($key == null) continue;
			if (!isset($rows_map[$key])) {
				array_push($list, $key);
				$rows_map[$key] = array(&$rows[$i]);
			} else
				array_push($rows_map[$key], $rows[$i]);
		}
		if (count($list) == 0) return;
		$q->whereIn($sql_aliases["sub_query_start_table"], $sql_aliases["sub_query_start_column"], $list);
		$q->field($sql_aliases["sub_query_start_table"], $sql_aliases["sub_query_start_column"], "SUB_QUERY_START");
		$res = $q->execute();
		$values = array();
		foreach ($res as $r) {
			$key = $r["SUB_QUERY_START"];
			if (!isset($values[$key])) $values[$key] = array($r[$sql_aliases["data_alias"]]);
			else array_push($values[$key], $r[$sql_aliases["data_alias"]]);
		}
		foreach ($values as $key=>$list) {
			for ($i = count($rows_map[$key])-1; $i >= 0; $i--)
				$rows_map[$key][$i][$data_alias] = $list;
		}
		return array(array($q,$res));
	}
	
	public function getFilterCondition(&$q, $path, $data_aliases, $filter) {
		if ($this->filter_path == null) {
			$col = $this->getColumn($this->data_path, $path->sub_model);
			if ($col[1]) {
				// multiple, we need to make a sub request
				$sub_q = new \SQLQuery($data_aliases["sub_query"]);
				$sub_q->where("`".$data_aliases["sub_query_start_table"]."`.`".$data_aliases["sub_query_start_column"]."`=`".$data_aliases["last_original_query_table"]."`.`".$data_aliases["last_original_query_key"]."`");
				$field_alias = $data_aliases["data_alias"];
				$field = $sub_q->getFieldFromAlias($field_alias);
				$sub_q->resetFields();
				$sub_q->field($field[0],$field[1]);
				$sub_alias = $q->generateFieldAlias();
				// add condition
				switch ($filter["type"]) {
					case "null": // no value exists
						$sub_q->groupBy($data_aliases["sub_query_start_table"], $data_aliases["sub_query_start_column"]);
						$q->fieldSubQuery($sub_q, $sub_alias);
						return array("type"=>"having","condition"=>"$sub_alias IS NULL");
					case "not_null": // at least one value exists
						$sub_q->groupBy($data_aliases["sub_query_start_table"], $data_aliases["sub_query_start_column"]);
						$q->fieldSubQuery($sub_q, $sub_alias);
						return array("type"=>"having","condition"=>"$sub_alias IS NOT NULL");
					case ">0": // at least one has a specific value
						$table_alias = $sub_q->getTableAlias($col[0]->table->getName());
						$cd = $this->getColumnFilterCondition($table_alias, $col[0], $filter["element_data"], $sub_q);
						if ($cd == null) return null;
						$sub_q->where($cd["condition"]);
						$sub_q->groupBy($data_aliases["sub_query_start_table"], $data_aliases["sub_query_start_column"]);
						$q->fieldSubQuery($sub_q, $sub_alias);
						return array("type"=>"having","condition"=>"$sub_alias IS NOT NULL");
					case "none": // none compy
						$table_alias = $sub_q->getTableAlias($col[0]->table->getName());
						$cd = $this->getColumnFilterCondition($table_alias, $col[0], $filter["element_data"], $sub_q);
						if ($cd == null) return null;
						$sub_q->where($cd["condition"]);
						$sub_q->groupBy($data_aliases["sub_query_start_table"], $data_aliases["sub_query_start_column"]);
						$q->fieldSubQuery($sub_q, $sub_alias);
						return array("type"=>"having","condition"=>"$sub_alias IS NULL");
				}
				return null;
			}
			return $this->getColumnFilterCondition($data_aliases["last_table"], $data_aliases["final_column"], $filter, $q);
		}
		// enum filter
		if (!isset($filter["values"]) || !is_array($filter["values"])) return null;
		
		$col = $this->getColumn($this->filter_path, $path->sub_model);
		$foreign_key = $col[0];
		// TODO multiple
		$table_alias = $q->getTableAlias($foreign_key->table->getName());

		$w = "";
		foreach ($filter["values"] as $enum_value) {
			if ($enum_value == "") continue;
			if (strlen($w) > 0) $w .= " OR ";
			$w .= "`".$table_alias."`.`".$foreign_key->name."`";
			if ($enum_value == "NULL") $w .= " IS NULL";
			else if ($enum_value == "NOT_NULL") $w .= " IS NOT NULL";
			else $w .= "='".\SQLQuery::escape($enum_value)."'";
		}
		if (strlen($w) == 0) return null;
		return array("type"=>"where","condition"=>$w);
	}
	
	public function saveData($key, $value, $sub_model, &$tables_fields, $joining_key_for_new_data, $joining_key_value_for_new_data) {
		// read only
	}

	public function exportValue($value, $sub_model) {
		$col = $this->getColumn($this->data_path, $sub_model);
		return $col->getDisplayValue($value);
	}
	public function getExportTimes($value, $sub_model) {
		$col = $this->getColumn($this->data_path, $sub_model);
		if ($col[1])
			return count($value);
		return 1;
	}
	public function exportValueNumber($value, $sub_model, $number) {
		$col = $this->getColumn($this->data_path, $sub_model);
		if ($col[1]) return $col[0]->getDisplayValue($value[$number]);
		return $this->exportValue($value, $sub_model);
	}
	
}

/**
 * Implementation of DataDisplay, when we have a foreign key representing a choice where the data is 
 * not directly reached by the foreign_key, but with a harder business that makes it not editable.<br/>
 * The data displayed is given by the data_path, but the key handled is the foreignkey in the foreign 
 * table (pointed by the foreign key in the root table)
 */
class ReadOnlyChoiceDataDisplay extends DataDisplay {
	
	/** @var string name of this data */
	protected $display_name;
	/** @var string path to reach the column to display */
	protected $data_path;
	/** @var string column's name containing the foreign key */
	protected $foreign_key_name;
	/** @var string horizontal alignment */
	protected $horiz_align;
	
	/**
	 * @param string $display_name name of the datadisplay
	 * @param string $foreign_key_name name of the foreign key in the root table
	 * @param string $data_path first must be the foreign key and then all the columns name that we will browse through until the column to display. The names must be separated by a dot
	 * @param string $horiz_align horizontal alignment
	 */
	public function __construct($display_name, $foreign_key_name, $data_path, $horiz_align = "left") {
		$this->display_name = $display_name;
		$this->data_path = $data_path;
		$this->foreign_key_name = $foreign_key_name;
		$this->horiz_align = $horiz_align;
	}
	
	public function getDisplayName() { return $this->display_name; }
	public function canAccess($sub_model) {
		$columns = explode(".", $this->data_path);
		$t = $this->getTable();
		if (!$t->canAccess()) return false;
		for ($i = 0; $i < count($columns); $i++) {
			if (!$t->canAccessColumn($columns[$i])) return false;
			$col = $t->getColumnFor($columns[$i], $sub_model);
			if ($i < count($columns)-1) {
				$t = \DataModel::get()->internalGetTable($col->foreign_table);
				if (!$t->canAccess()) return false;
			}
		}
		return true;
	}
	public function getHandledColumns() { return array(); }
	
	/**
	 * Internal method to get the final column in the data model
	 * @param number|null $sub_model sub model instance
	 * @return \datamodel\Column the final column
	 */
	private function getColumn($sub_model) {
		$columns = explode(".", $this->data_path);
		$t = $this->getTable();
		for ($i = 0; $i < count($columns); $i++) {
			$col = $t->getColumnFor($columns[$i], $sub_model);
			if ($i < count($columns)-1)
				$t = \DataModel::get()->getTable($col->foreign_table);
		}
		return $col;
	}
	
	public function getTypedField($sub_model) {
		if ($sub_model <> "@link")
			return \PNApplication::$instance->widgets->get_typed_field($this->getColumn($sub_model));
		return array("field_text","{can_be_null:true}");
	}
	
	public function isEditable() {
		return false;
	}
	public function getEditLocks($sub_model) {
		return array();
	}
	public function isSortable() {
		return true;
	}
	public function isMandatory($sub_model) {
		return false; // TODO
	}
	public function getHorizontalAlign($sub_model) { return $this->horiz_align; }
	
	/**
	 * Internal method to get the possible values to display in the enum for filtering
	 * The possibilities are enumerated by all the existing keys in the table pointed by the 
	 * foreign_key (choice datadisplay aspect) and the displayed names are found into the last 
	 * table of the datapath (aspect readonly datadisplay)
	 * @param number|null $sub_model
	 * @returns array | null null if no value, else array containing array("KEY" =>, "VALUE") for each possible values
	 */
	private function getPossibleValues($sub_model) {
		$columns = explode(".", $this->data_path);
		$t = $this->getTable();		
		$col = $t->getColumnFor($columns[0], $sub_model);
		$last_table = \DataModel::get()->getTable($col->foreign_table);
		$first_table = $last_table;
		$q = \SQLQuery::create()->select($col->foreign_table);
		// go to the table
		for ($i = 1; $i < count($columns)-1; $i++) {
			$col = $last_table->getColumnFor($columns[$i], $sub_model);
			$ft = \DataModel::get()->getTable($col->foreign_table);	
			$q->join($last_table->getName(), $ft->getName(), array($columns[$i]=>$ft->getPrimaryKey()->name));
			$last_table = $ft;					
		}
		
		if (!isset($this->possible_values[$sub_model])) {			
			if ($sub_model <> null && ($t->getModel() instanceof \SubDataModel) && ($this->getTable()->getModel() instanceof \SubDataModel) && $t->getModel()->getParentTable() == $this->getTable()->getModel()->getParentTable())
				$q->selectSubModel($t->getModel()->getParentTable(), $sub_model);
			$q->field($first_table->getName(), $first_table->getPrimaryKey()->name, "KEY");
			$q->field($last_table->getName(), $columns[count($columns)-1], "VALUE");
			$this->possible_values[$sub_model] = $q->execute();
			$col = $last_table->getColumnFor($columns[count($columns)-1], $sub_model);
			for ($i = 0; $i < count($this->possible_values[$sub_model]); $i++)
				$this->possible_values[$sub_model][$i]["VALUE"] = $col->getDisplayValue($this->possible_values[$sub_model][$i]["VALUE"]);
		}
		return $this->possible_values[$sub_model];
	}
	
	public function getTypedFilter($sub_model) {
		if ($sub_model <> "@link") {
			$foreign_key = $this->getTable()->getColumnFor($this->foreign_key_name, $sub_model);
			$cfg = "{";
			$cfg .= "possible_values:[";
			$res = $this->getPossibleValues($sub_model);
			$first = true;
			foreach ($res as $row) {
				if ($first) $first = false; else $cfg .= ",";
				$cfg .= "[";
				$cfg .= json_encode($row["KEY"]);
				$cfg .= ",".json_encode($row["VALUE"]);
				$cfg .= "]";
			}
			$cfg .= "]";
			$cfg .= ",can_be_null:".($foreign_key->can_be_null ? "true" : "false");
			$cfg .= "}";
			return array("filter_enum", $cfg);
		}
		return null;
	}
	
	public function getSingleCell() {
		$col = $this->getColumn(null);
		return array("table"=>$col->table->getName(), "column"=>$col->name);
	}
	
	public function buildSQL(&$q, $path, $filtered) {			
		$columns = explode(".", $this->data_path);
		$last_table = $this->getTable();
		// go to the table
		$table_alias = \DataPathSQLBuilder::build($path, $q);
		$last_key = null;
		for ($i = 0; $i < count($columns); $i++) {
			$col = $last_table->getColumnFor($columns[$i], $path->sub_model);
			if ($i < count($columns)-1) {
				$ft = \DataModel::get()->getTable($col->foreign_table);
				$last_key = $ft->getPrimaryKey();
				$fta = $q->generateTableAlias();
				$q->join($table_alias, $col->foreign_table, array($columns[$i]=>$ft->getPrimaryKey()->name), $fta);
				$last_table = $ft;
				$table_alias = $fta;
			}
		}
		
		//Get the key
		$key_alias = $q->getFieldAlias($this->getTable()->getName(), $this->foreign_key_name);
		if($key_alias == null)
			$key_alias = $q->generateFieldAlias();
		$root_alias = $q->getTableAlias($this->getTable()->getName());
		if($root_alias == null)
			$root_alias = $this->getTable()->getName();
		$q->field($root_alias, $this->foreign_key_name, $key_alias);
		$data_alias = $q->getFieldAlias($table_alias, $col->name);
		if ($data_alias == null)
			$data_alias = $q->generateFieldAlias();
		$q->field($table_alias, $col->name, $data_alias);
	
		return array("key"=>$key_alias,"data"=>$data_alias);
	}
	
	public function getFilterCondition(&$q, $path, $data_aliases, $filter) {
		if (!isset($filter["values"]) || !is_array($filter["values"])) return null;
		$root_alias = $q->getTableAlias($this->getTable()->getName());
		$w = "";
		foreach ($filter["values"] as $enum_value) {
			if ($enum_value == "") continue;
			if (strlen($w) > 0) $w .= " OR ";
			$w .= "`".$root_alias."`.`".$this->foreign_key_name."`";
			if ($enum_value == "NULL") $w .= " IS NULL";
			else if ($enum_value == "NOT_NULL") $w .= " IS NOT NULL";
			else $w .= "='".\SQLQuery::escape($enum_value)."'";
		}
		if (strlen($w) == 0) return null;
		return array("type"=>"where","condition"=>$w);
	}
	
	public function saveData($key, $value, $sub_model, &$tables_fields, $joining_key_for_new_data, $joining_key_value_for_new_data) {
	
	}
}

/**
 * Use a SQL expression to calculate a number
 */
class CalculationDataDisplay extends DataDisplay {
	
	/** @var string the name to display */
	private $display_name;
	/** @var string the SQL expression to calculate */
	private $expression;
	/** @var string horizontal alignment */
	private $horiz_align;
	
	/**
	 * @param string $display_name the name to display
	 * @param string $expression the SQL expression to calculate
	 * @param string $horiz_align horizontal alignment
	 */
	public function __construct($display_name, $expression, $horiz_align = "center") {
		$this->display_name = $display_name;
		$this->expression = $expression;
		$this->horiz_align = $horiz_align;
	}
	
	public function getDisplayName() { return $this->display_name; }
	public function canAccess($sub_model) {
		if (!$this->getTable()->canAccess()) return false;
		$s = $this->expression;
		do {
			$i = strpos($s, "`");
			if ($i === false) break;
			$j = strpos($s, "`", $i+1);
			$p = substr($s, $i+1, $j-$i-1);
			$s = substr($s, $j+1);
				
			$columns = explode(".", $p);
			$t = $this->getTable();
			for ($i = 0; $i < count($columns); $i++) {
				if (!$t->canAccessColumn($columns[$i])) return false;
				$col = $t->getColumnFor($columns[$i], $sub_model);
				if ($i < count($columns)-1) {
					$ft = \DataModel::get()->internalGetTable($col->foreign_table);
					if (!$ft->canAccess()) return false;
					$t = $ft;
					if (!($ft->getModel() instanceof \SubDataModel)) $sub_model = null;
				}
			}
		} while ($s <> "");
		return true;
	}
	public function getHandledColumns() { return array(); }
	
	public function getTypedField($sub_model) { return array("field_text","{}"); }
	public function getHorizontalAlign($sub_model) { return $this->horiz_align; }
	public function getTypedFilter($sub_model) {
		return array("filter_comparable","{value_field_classname:'field_integer',value_field_config:{can_be_null:true}}");
	}
	public function isEditable() { return false; }
	public function getEditLocks($sub_model) { return array(); }
	public function isSortable() { return true; }
	public function isMandatory($sub_model) { return false; }
	public function getSingleCell() { return null; }
	
	/**
	 * Internal method to get the final column in the data model
	 * @param number|null $sub_model sub model instance
	 * @return \datamodel\Column the final column
	 */
	private function getColumn($path, $sub_model) {
		$columns = explode(".", $path);
		$t = $this->getTable();
		for ($i = 0; $i < count($columns); $i++) {
			$col = $t->getColumnFor($columns[$i], $sub_model);
			if ($i < count($columns)-1)
				$t = \DataModel::get()->getTable($col->foreign_table);
		}
		return $col;
	}
	
	public function buildSQL(&$q, $path, $filtered) {
		// go to the table
		$table_alias = \DataPathSQLBuilder::build($path, $q);
		
		$expr = "";
		$s = $this->expression;
		do {
			$i = strpos($s, "`");
			if ($i === false) { $expr .= $s; break; }
			if ($i > 0) $expr .= substr($s, 0, $i);
			$j = strpos($s, "`", $i+1);
			$p = substr($s, $i+1, $j-$i-1);
			$s = substr($s, $j+1);
			
			$columns = explode(".", $p);
			$t = $this->getTable();
			$talias = $table_alias;
			$sm = $path->sub_model;
			$last_key = null;
			for ($i = 0; $i < count($columns); $i++) {
				$col = $t->getColumnFor($columns[$i], $sm);
				if ($i < count($columns)-1) {
					$ft = \DataModel::get()->getTable($col->foreign_table);
					$last_key = $ft->getPrimaryKey();
					$fta = $q->generateTableAlias();
					$q->join($talias, $col->foreign_table, array($columns[$i]=>$ft->getPrimaryKey()->name), $fta);
					$t = $ft;
					$talias = $fta;
					if (!($ft->getModel() instanceof \SubDataModel)) $sm = null; else $sm = $path->sub_model;
				}
			}
			$expr .= "`".$talias."`.`".$col->name."`";
		} while ($s <> "");

		$alias = $q->generateFieldAlias();
		$q->expression($expr, $alias);
		
		return array("key"=>null,"data"=>$alias);
	}
	
	public function getFilterCondition(&$q, $path, $data_aliases, $filter) {
		$cd = $this->getExpressionFilterCondition("`".$data_aliases["data"]."`", "filter_comparable", $filter, $q);
		if ($cd == null) return null;
		return array("type"=>"having", "condition"=>$cd);
	}
	
	public function saveData($key, $value, $sub_model, &$tables_fields, $joining_key_for_new_data, $joining_key_value_for_new_data) {
	}
	
}

/**
 * Count the number of foreign keys linked to a data
 */
class CountLinksDataDisplay extends DataDisplay {
	
	/** @var string name of this data */
	protected $display_name;
	/** @var string table containing the foreign keys to count */
	protected $table;
	/** @var string name of the column of the foreign key */
	protected $foreign_key;
	/** @var string horizontal alignment */
	protected $horiz_align;
	
	/**
	 * @param string $display_name name of this data
	 * @param string $table table containing the foreign keys to count
	 * @param string $foreign_key name of the column of the foreign key
	 * @param string $horiz_align horizontal alignment
	 */
	public function __construct($display_name, $table, $foreign_key, $horiz_align = "center") {
		$this->display_name = $display_name;
		$this->table = $table;
		$this->foreign_key = $foreign_key;
		$this->horiz_align = $horiz_align;
	}

	public function getDisplayName() { return $this->display_name; }
	public function canAccess($sub_model) {
		if (!$this->getTable()->canAccess()) return false;
		if (!$this->getTable()->canAccessColumn($this->foreign_key)) return false;
		return true;
	}
	public function getHandledColumns() { return array(); }
	
	public function getTypedField($sub_model) {
		return array("field_text","{}");
	}
	public function getHorizontalAlign($sub_model) { return $this->horiz_align; }
	public function getTypedFilter($sub_model) {
		return array("filter_comparable","{value_field_classname:'field_integer',value_field_config:{can_be_null:true,min:0}}");
	}
	
	public function isEditable() {
		return false;
	}
	public function getEditLocks($sub_model) {
		return array();
	}
	public function isSortable() {
		return true;
	}
	public function isMandatory($sub_model) {
		return false;
	}

	public function buildSQL(&$q, $path, $filtered) {
		$t = $this->getTable();
		// go to the table
		$table_alias = \DataPathSQLBuilder::build($path, $q);
		$key_alias = $q->getFieldAlias($t->getName(), $t->getPrimaryKey()->name);
		if ($key_alias == null) {
			$key_alias = $q->generateFieldAlias();
			$q->field($table_alias, $t->getPrimaryKey()->name, $key_alias);
		}
		$table_count = \DataModel::get()->getTable($this->table);
		$table_alias = $q->getTableAlias($path->table->getName(), true);
		$sub_q = \SQLQuery::create()
			->select($this->table)
			->where("`".$table_count->getSQLNameFor($path->sub_model)."`.`".$this->foreign_key."` IN (`".$table_alias."`.`".$t->getPrimaryKey()->name."`)")
			->count();
		$data_alias = $q->generateFieldAlias();
		$q->fieldSubQuery($sub_q, $data_alias);
		return array("key"=>$key_alias,"data"=>$data_alias);
	}
	
	public function getFilterCondition(&$q, $path, $data_aliases, $filter) {
		$cd = $this->getExpressionFilterCondition("`".$data_aliases["data"]."`", "filter_comparable", $filter, $q);
		if ($cd == null) return null;
		return array("type"=>"having", "condition"=>$cd);
	}
	
	public function saveData($key, $value, $sub_model, &$tables_fields, $joining_key_for_new_data, $joining_key_value_for_new_data) {
	}
	
}

/**
 * Count the number of data having a spcific value
 */
class CountDataValueDataDisplay extends DataDisplay {

	/** @var string name of this data */
	protected $display_name;
	/** @var string column containing the value */
	protected $column;
	/** @var string the value */
	protected $value;
	/** @var string foreign table */
	protected $table;
	/** @var string foreign key */
	protected $foreign_key;
	/** @var boolean indicates if this is an integer */
	protected $is_integer;
	/** @var string horizontal alignment */
	protected $horiz_align;

	/**
	 * @param string $display_name name of this data
	 * @param string $column column containing the value
	 * @param string $value the value
	 * @param string $table foreign table
	 * @param string $foreign_key foreign key
	 * @param string $is_integer indicates if this is an integer
	 * @param string $horiz_align horizontal alignment
	 */
	public function __construct($display_name, $column, $value, $table = null, $foreign_key = null, $is_integer = true, $horiz_align = "center") {
		$this->display_name = $display_name;
		$this->column = $column;
		$this->value = $value;
		$this->table = $table;
		$this->foreign_key = $foreign_key;
		$this->is_integer = $is_integer;
		$this->horiz_align = $horiz_align;
	}

	public function getDisplayName() { return $this->display_name; }
	public function canAccess($sub_model) {
		if (!$this->getTable()->canAccess()) return false;
		if (!$this->getTable()->canAccessColumn($this->column)) return false;
		return true;
	}
	public function getHandledColumns() { if ($this->foreign_key == null) return array($this->column); return array(); }

	public function getTypedField($sub_model) {
		if ($this->is_integer)
			return array("field_integer","{can_be_null:false}");
		return array("field_decimal", "{integer_digits:10,decimal_digits:2,can_be_null:false}");
	}
	public function getHorizontalAlign($sub_model) { return $this->horiz_align; }
	public function getTypedFilter($sub_model) {
		if ($this->is_integer)
			return array("filter_comparable","{value_field_classname:'field_integer',value_field_config:{can_be_null:false}}");
		return array("filter_comparable","{value_field_classname:'field_decimal',value_field_config:{can_be_null:false,integer_digits:10,decimal_digits:2}}");
	}

	public function isEditable() {
		return false;
	}
	public function getEditLocks($sub_model) {
		return array();
	}
	public function isSortable() {
		return true;
	}
	public function isMandatory($sub_model) {
		return false;
	}

	public function buildSQL(&$q, $path, $filtered) {
		$t = $this->getTable();
		// go to the table
		$table_alias = \DataPathSQLBuilder::build($path, $q);
		$key_alias = $q->getFieldAlias($t->getName(), $t->getPrimaryKey()->name);
		if ($key_alias == null) {
			$key_alias = $q->generateFieldAlias();
			$q->field($table_alias, $t->getPrimaryKey()->name, $key_alias);
		}
		$table_count = \DataModel::get()->getTable($this->table);
		$sub_q = \SQLQuery::create()
			->select($this->table)
			->where("`".$table_count->getSQLNameFor($path->sub_model)."`.`".$this->foreign_key."`=`".$table_alias."`.`".$t->getPrimaryKey()->name."`")
			->expression("SUM(case when `".$table_count->getSQLNameFor($path->sub_model)."`.`".$this->column."`".($this->value === null ? " IS NULL" : "='".\SQLQuery::escape($this->value)."'")." then 1 else 0 end)","count_data_value")
			;
		$data_alias = $q->generateFieldAlias();
		$q->fieldSubQuery($sub_q, $data_alias);
		return array("key"=>$key_alias,"data"=>$data_alias);
		
// 		if ($this->foreign_key <> null) {
// 			// we go to a different table
// 			if ($this->table <> null) {
// 				// reverse link
// 				$ft_alias = $q->getTableAlias($this->table);
// 				if ($ft_alias == null) {
// 					$ft_alias = $q->generateTableAlias();
// 					$q->join($table_alias, $this->table, array($t->getPrimaryKey()->name=>$this->foreign_key), $ft_alias);
// 				}
// 				$table_alias = $ft_alias;
// 			} else {
// 				$fk = $t->internalGetColumnFor($this->foreign_key, $path->sub_model);
// 				$ft_alias = $q->getTableAlias($fk->foreign_table);
// 				if ($ft_alias == null) {
// 					$ft_alias = $q->generateTableAlias();
// 					$ft = \DataModel::get()->internalGetTable($fk->foreign_table);
// 					$q->join($table_alias, $fk->foreign_table, array($this->foreign_key=>$ft->getPrimaryKey()->name), $ft_alias);
// 				}
// 				$table_alias = $ft_alias;
// 			}
// 		}
	}

	public function getFilterCondition(&$q, $path, $data_aliases, $filter) {
		$cd = $this->getExpressionFilterCondition("`".$data_aliases["data"]."`", "filter_comparable", $filter, $q);
		if ($cd == null) return null;
		return array("type"=>"having", "condition"=>$cd);
	}

	public function saveData($key, $value, $sub_model, &$tables_fields, $joining_key_for_new_data, $joining_key_value_for_new_data) {
	}

}

/**
 * Display an age, using a column containing a date
 */
class AgeDataDisplay extends CalculationDataDisplay {
	
	/**
	 * @param string $display_name name of this data
	 * @param string $birthdate_column column containing the date
	 */
	public function __construct($display_name, $birthdate_column) {
		$this->birthdate_column = $birthdate_column;
		parent::__construct($display_name, "TIMESTAMPDIFF(YEAR,`$birthdate_column`,CURDATE())");
	}
	
	/** @var string column containing the birth date */
	private $birthdate_column;
	
	public function getTypedField($sub_model) {
		return array("field_age","{table:".json_encode($this->getTable()->getName()).",sub_model:".json_encode($sub_model).",column:".json_encode($this->birthdate_column)."}");
	}
	
	public function buildSQL(&$q, $path, $filtered) {
		$keys = parent::buildSQL($q, $path, $filtered);
		$pk = $this->getTable()->getPrimaryKey();
		if ($pk == null) return $keys;
		$key_alias = $q->getFieldAlias($this->getTable()->getName(), $pk->name);
		if ($key_alias == null) {
			$table_alias = $q->getTableAlias($this->getTable()->getName());
			$key_alias = $q->generateFieldAlias();
			$q->field($table_alias, $pk->name, $key_alias);
		}
		$keys["key"] = $key_alias;
		return $keys;
	}
	
}

} // namespace datamodel
?>