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
	 * @var \datamodel\TableDataDisplay $table_data_display the TableDataDisplay this data belongs to
	 */
	private $table_data_display;
	
	public function getTableDataDisplay() { return $this->table_data_display; }
	public function setTableDataDisplay($table_data_display) { $this->table_data_display = $table_data_display; }
	public function getTableName() { return $this->table_data_display->getTableName(); }
	public function getTable() { return $this->table_data_display->getTable(); }
	public function getCategoryName() { return $this->table_data_display->getCategory()->getName(); }
	public function getCategory() { return $this->table_data_display->getCategory(); }
	
	/** Get the string to display for this data
	 * @return string the display name
	 */
	public abstract function getDisplayName();
	/** Returns the list of columns' name this data represents (either a single column, or a group of columns displayed together
	 * @return array of string
	 */
	public abstract function getHandledColumns();
	/** How to display this data, using a JavaScript widget implementing typed_field
	 * @param number|null $sub_model sub model instance
	 * @return array (classname, config) classname: string containing the name of the JavaScript class implementing typed_field, config: string containing a JSON representation of the configuration of the typed_field
	 */
	public abstract function getTypedField($sub_model);
	/** Indicates if this data can be modified by the user
	 * @return boolean true if this data is editable
	 */
	public abstract function isEditable();
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
	
	/** Add any necessary select/join/field to the SQLQuery to retrieve this data
	 * @param SQLQuery $q the sql
	 * @param DataPath $path how we reached this data
	 * @param array $filters list of OR filter, with for each element the data of the filter
	 * @return array("key"=&gt;key_alias,"data"=&gt;data_alias): if data is null or not specified, the function performSubRequest must be implemented
	 */
	public abstract function buildSQL(&$q, $path, $filters);
	/** Perform any necessary sub-requests to retrieve the data, once the first one is already executed, and update the rows and aliases as needed so $rows[x][$sql_aliases["data"]] returns the value for row number x
	 * @param SQLQuery $original_query the query that was givven in buildSQL
	 * @param array $rows list of rows that have been retrieved with the original query
	 * @param array $sql_aliases the value returned by buildSQL
	 * @param DataPath $path how we reached this data
	 * @param array $filters same as buildSQL
	 */
	public function performSubRequests(&$original_query, &$rows, &$sql_aliases, $path, $filters) {}
	
	/** Structure to use when creating a new data. This structure will be used in the typed_field, to edit the new data
	 * @return array|null the associative array will then be converted into JSON, to give it to the typed_field 
	 */
	public function getNewData() { return null; }
	
	/** Retrieve the value of this data. By default get the result from the data alias.
	 * @param array|null $row the row from the database
	 * @param array $resultFromBuildSQL the result returned by the previous call to buildSQL
	 */
	public function getData($row, $resultFromBuildSQL) {
		return $row[$resultFromBuildSQL["data"]];
	}

	/** Save a new value for this data. If it can be done together with other updates/insert, the array tables_fields is filled, else the operations are done directly.
	 * @param number|array $key row key
	 * @param unknown $value the new value, its type and content depends on the type of this data and its typed_field
	 * @param number|null $sub_model sub model instance
	 * @param TablesToUpdate $tables_fields list of values which should be updated/inserted
	 */
	public abstract function saveData($key, $value, $sub_model, &$tables_fields);
	
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
	
	/**
	 * Utility method to apply common filters in a SQLQuery (adding WHERE)
	 * @param string $table_alias alias of the table
	 * @param \datamodel\Column $col the column
	 * @param array $filters the filters to apply
	 * @param SQLQuery $q the sql on which the filters should be applied
	 */
	protected function applyColumnFilter($table_alias, $col, $filters, &$q) {
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
						$w .= ".`".$col->name."`";
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
			case "filter_enum":
				foreach ($filters as $filter_list) {
					$w = "";
					foreach ($filter_list as $filter) {
						if ($filter["value"] == "") continue;
						if (strlen($w) > 0) $w .= " OR ";
						$w .= "`".$table_alias."`.`".$col->name."`";
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
				break;
		}
	}
	
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
	
	/**
	 * @param string $column_name name of the column
	 * @param string $display_name name to display
	 * @param boolean $readonly indicates if we can edit this column
	 */
	public function __construct($column_name, $display_name, $readonly = false) {
		$this->column_name = $column_name;
		$this->display_name = $display_name;
		$this->readonly = $readonly;
	}
	
	public function getDisplayName() {
		return $this->display_name;
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
	
	public function getTypedFilter($sub_model) {
		$col = $this->getTable()->getColumnFor($this->column_name, $sub_model);
		return \PNApplication::$instance->widgets->get_typed_filter($col);
	}
	
	public function isEditable() {
		if ($this->readonly) return false;
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
			return array(array("table"=>$this->getTable()->getSQLNameFor($sub_model), "column"=>$this->column_name));
		return array(array("table"=>$this->getTable()->getSQLNameFor($sub_model), "column"=>$this->column_name, "lock_each_row"=>true));
	}
	
	public function buildSQL(&$q, $path, $filters) {
		$table_alias = \DataPathSQLBuilder::build($path, $q);
		$alias = $q->getFieldAlias($table_alias, $this->column_name);
		if ($alias == null) {
			$alias = $q->generateFieldAlias();
			$q->field($table_alias, $this->column_name, $alias);
		}
		$result = array("data"=>$alias);
		$key = $this->getTable()->getPrimaryKey();
		if ($key == null) {
			PNApplication::error("No key for data '".$this->display_name."' in table '".$this->getTableName()."'");
			$result["key"] = null;
			return $result;
		}
		$key = $key->name;
		$alias = $q->getFieldAlias($table_alias, $key);
		if ($alias == null) {
			$alias = $q->generateFieldAlias();
			$q->field($table_alias, $key, $alias);
		}
		$result["key"] = $alias;
		$col = $this->getTable()->getColumnFor($this->column_name, $path->sub_model);
		$this->applyColumnFilter($table_alias, $col, $filters, $q);
		return $result;
	}
	
	public function saveData($key, $value, $sub_model, &$table_fields) {
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
	
	/**
	 * @param string $foreign_key_name column's name containing the foreign key
	 * @param string $foreign_column_name name of the column in the foreign table to display
	 * @param string $display_name name of this data
	 * @param boolean $editable indicates if this can be edited or not
	 */
	public function __construct($foreign_key_name, $foreign_column_name, $display_name, $editable = true) {
		$this->display_name = $display_name;
		$this->foreign_key_name = $foreign_key_name;
		$this->foreign_column_name = $foreign_column_name;
		$this->editable = $editable;
	}
	public function getDisplayName() { return $this->display_name; }
	public function getHandledColumns() { return array($this->foreign_key_name); }
	
	private $possible_values = array();
	private function get_possible_values($sub_model) {
		$foreign_key = $this->getTable()->getColumnFor($this->foreign_key_name, $sub_model);
		if (!isset($this->possible_values[$sub_model])) {
			$t = \DataModel::get()->getTable($foreign_key->foreign_table);
			$q = \SQLQuery::create()->select($t->getName());
			if ($sub_model <> null && $t->getModel() instanceof SubDataModel && $this->getTable()->getModel() instanceof SubDataModel && $t->getModel()->getParentTable() == $this->getTable()->getModel()->getParentTable())
				$q->selectSubModel($t->getModel()->getParentTable(), $sub_model);
			$q->field($t->getName(), $t->getPrimaryKey()->name, "KEY");
			$q->field($t->getName(), $this->foreign_column_name, "VALUE");
			$this->possible_values[$sub_model] = $q->execute();
		}
		return $this->possible_values[$sub_model];
	}
	public function getTypedField($sub_model) {
		$foreign_key = $this->getTable()->getColumnFor($this->foreign_key_name, $sub_model);
		$cfg = "{";
		$cfg .= "possible_values:[";
		$res = $this->get_possible_values($sub_model);
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
		$foreign_key = $this->getTable()->getColumnFor($this->foreign_key_name, $sub_model);
		$cfg = "{";
		$cfg .= "possible_values:[";
		$res = $this->get_possible_values($sub_model);
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
	
	public function isEditable() { return $this->editable; }
	public function getEditLocks($sub_model) {
		if (!$this->editable) return array();
		return array(
			array("table"=>$this->getTable()->getSQLNameFor($sub_model))
		);
	}
	public function isSortable() { return true; }
	public function isMandatory($sub_model) {
		$foreign_key = $this->getTable()->getColumnFor($this->foreign_key_name, $sub_model);
		return !$foreign_key->can_be_null;
	}
	
	public function buildSQL(&$q, $path, $filters) {
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
	
	public function saveData($key, $value, $sub_model, &$tables_fields) {
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
	public function getHandledColumns() { return array($this->foreign_key_name); }
	
	public function getTypedField($sub_model) {
		$foreign_table = $this->getTable()->getColumnFor($this->foreign_key_name, $sub_model)->foreign_table;
		$foreign_table = \DataModel::get()->getTable($foreign_table);
		$sub_models = $sub_model == null ? null : array($this->getTable()->getModel()->getParentTable()=>$sub_model);
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
			array("table"=>$this->getTable()->getSQLNameFor($sub_model))
		);
	}
	public function isSortable() {
		return false;
	}
	public function isMandatory($sub_model) {
		return false;
	}
	
	public function buildSQL(&$q, $path, $filters) {
		// we need to go to the parent, and get the joining key
		$parent_alias = \DataPathSQLBuilder::build($path->parent, $q);
		$key_name = $path->isReverse() ? $path->parent->table->getPrimaryKey()->name : $path->foreign_key->name;
		$alias = $q->getFieldAlias($parent_alias, $key_name);
		if ($alias == null) {
			$alias = $q->generateFieldAlias();
			$q->field($parent_alias, $key_name, $alias);
		}
		// TODO filters
		return array("key"=>$alias,"data"=>null);
	}
	
	public function performSubRequests(&$original_query, &$rows, &$sql_aliases, $path, $filters) {
		$table_name = $path->table->getSQLNameFor($path->sub_model);
		$foreign_table = $path->table->getColumnFor($this->foreign_key_name, $path->sub_model)->foreign_table;
		$foreign_table = \DataModel::get()->getTable($foreign_table);
		$sub_models = $path->sub_model == null ? null : array($path->table->getModel()->getParentTable()=>$path->sub_model);
		
		$keys = array();
		foreach ($rows as $row) {
			if (!in_array($row[$sql_aliases["key"]], $keys))
				array_push($keys, $row[$sql_aliases["key"]]);
		}
		
		if (count($keys) == 0)
			$res = array();
		else
			$res = \SQLQuery::create()
				->select($table_name)
				->join($table_name, $foreign_table->getSQLName($sub_models), array($this->foreign_key_name => $foreign_table->getPrimaryKey()->name), "JOIN_ALIAS")
				->field("JOIN_ALIAS", $foreign_table->getPrimaryKey()->name, "VALUE")
				->field($table_name, $this->joining_key_name, "KEY")
				->whereIn($table_name, $this->joining_key_name, $keys)
				->execute();

		$sql_aliases["data"] = $original_query->generateFieldAlias();
		for ($i = 0; $i < count($rows); $i++) {
			$key = $rows[$i][$sql_aliases["key"]];
			$values = array();
			foreach ($res as $r) {
				if ($r["KEY"] == $key)
					array_push($values, $r["VALUE"]);
			}
			$rows[$i][$sql_aliases["data"]] = $values;
		}
	}
	
	public function getData($row, $resultFromBuildSQL) {
		// TODO
	}
	
	public function saveData($key, $value, $sub_model, &$tables_fields) {
		require_once("component/data_model/DataBaseLock.inc");
		\SQLQuery::create()->remove($this->getTable()->getSQLNameFor($sub_model), array($this->joining_key_name=>$key));
		foreach ($value as $v)
			\SQLQuery::create()->selectSubModelForTable($this->getTable(), $sub_model)->insert($this->getTableName(), array($this->joining_key_name=>$key,$this->foreign_key_name=>$v));
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
	
	/**
	 * @param string $display_name name of this data
	 * @param string $data_path path to reach the column to display: format is columns' names separated by a dot (.). Example: country.name means use the column 'country' as a foreign key, then in the foreign table get the column 'name'
	 */
	public function __construct($display_name, $data_path) {
		$this->display_name = $display_name;
		$this->data_path = $data_path;
	}

	public function getDisplayName() { return $this->display_name; }
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
		return \PNApplication::$instance->widgets->get_typed_field($this->getColumn($sub_model));
	}
	
	public function isEditable() {
		return false;
	}
	public function getEditLocks($sub_model) {
		return array();
	}
	public function isSortable() {
		return false; // TODO
	}
	public function isMandatory($sub_model) {
		return false; // TODO
	}

	public function getTypedFilter($sub_model) {
		$columns = explode(".", $this->data_path);
		$t = $this->getTable();
		$last_key = null;
		for ($i = 0; $i < count($columns); $i++) {
			$col = $t->getColumnFor($columns[$i], $sub_model);
			if ($i < count($columns)-1) {
				$t = \DataModel::get()->getTable($col->foreign_table);
				$last_key = $t->getPrimaryKey();
			}
		}
		if ($last_key <> null) {
			$cfg = "{possible_values:[";
			$values = \SQLQuery::create()
				->select($t->getName())
				->field($t->getName(), $last_key->name, "KEY")
				->field($t->getName(), $col->name, "VALUE")
				->execute();
			$first = true;
			foreach ($values as $v) {
				if ($first) $first = false; else $cfg .= ",";
				$cfg .= "[".$v["KEY"].",".json_encode($v["VALUE"])."]";
			}
			$cfg .= "]}";
			$filter = array("filter_enum",$cfg);
			return $filter;
		} else
			return \PNApplication::$instance->widgets->get_typed_filter($this->getColumn($sub_model));
	}
	
	public function getSingleCell() { 
		$col = $this->getColumn(null); 
		return array("table"=>$col->table->getName(), "column"=>$col->name);
	}
	
	public function buildSQL(&$q, $path, $filters) {
		$columns = explode(".", $this->data_path);
		$t = $this->getTable();
		// go to the table
		$table_alias = \DataPathSQLBuilder::build($path, $q);
		$last_key = null;
		for ($i = 0; $i < count($columns); $i++) {
			$col = $t->getColumnFor($columns[$i], $path->sub_model);
			if ($i < count($columns)-1) {
				$ft = \DataModel::get()->getTable($col->foreign_table);
				$last_key = $ft->getPrimaryKey();
				$fta = $q->generateTableAlias();
				$q->join($table_alias, $col->foreign_table, array($columns[$i]=>$ft->getPrimaryKey()->name), $fta);
				$t = $ft;
				$table_alias = $fta;
			}
		}
		$key_alias = $q->generateFieldAlias();
		$q->field($table_alias, $t->getPrimaryKey()->name, $key_alias);
		$data_alias = $q->generateFieldAlias();
		$q->field($table_alias, $col->name, $data_alias);
		if ($last_key == null)
			$this->applyColumnFilter($table_alias, $col, $filters, $q);
		else {
			foreach ($filters as $filter_list) {
				$w = "";
				foreach ($filter_list as $filter) {
					if ($filter["value"] == "") continue;
					if (strlen($w) > 0) $w .= " OR ";
					$w .= "`".$table_alias."`.`".$last_key->name."`";
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
		}
		
		return array("key"=>$key_alias,"data"=>$data_alias);
	}
	
	public function saveData($key, $value, $sub_model, &$tables_fields) {
		// TODO
	}
	
}

} // namespace datamodel
?>