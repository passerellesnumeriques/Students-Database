<?php
namespace datamodel {

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
	 * @param array $filters list of OR filter, with for each element the data of the filter
	 * @return array("key"=>key_alias,"data"=>data_alias): if data is null or not specified, the function retrieveValue will be called to perform a sub-request retrieving the final value
	 */
	public abstract function buildSQL(&$q, $path, $filters);
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
		}
	}
	
}


class SimpleDataDisplay extends DataDisplay {
	
	public $column_name;
	public $display_name;
	public $readonly;
	
	public function __construct($handler, $column_name, $display_name, $readonly = false) {
		parent::__construct($handler);
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
		if ($this->readonly) return false;
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
	
	public function buildSQL(&$q, $path, $filters) {
		$table_alias = \DataPathSQLBuilder::build($path, $q);
		$alias = $q->get_field_alias($table_alias, $this->column_name);
		if ($alias == null) {
			$alias = $q->field_id();
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
			$alias = $q->field_id();
			$q->field($table_alias, $key, $alias);
		}
		$result["key"] = $alias;
		$col = $this->handler->table->getColumnFor($this->column_name, $path->sub_model);
		$this->applyColumnFilter($table_alias, $col, $filters, $q);
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
	public $editable;
	
	public function __construct($handler, $foreign_key_name, $foreign_column_name, $display_name, $editable = true) {
		parent::__construct($handler);
		$this->display_name = $display_name;
		$this->foreign_key_name = $foreign_key_name;
		$this->foreign_column_name = $foreign_column_name;
		$this->editable = $editable;
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
	
	public function isEditable() { return $this->editable; }
	public function getEditLocks($sub_model) {
		if (!$this->editable) return array();
		return array(
			array("table"=>$this->handler->table->getSQLNameFor($sub_model))
		);
	}
	public function isSortable() { return true; }
	public function isMandatory($sub_model) {
		$foreign_key = $this->handler->table->getColumnFor($this->foreign_key_name, $sub_model);
		return !$foreign_key->can_be_null;
	}
	
	public function buildSQL(&$q, $path, $filters) {
		// we need to get the key, and the value to display
		$foreign_key = $this->handler->table->getColumnFor($this->foreign_key_name, $path->sub_model);
		$t = \DataModel::get()->getTable($foreign_key->foreign_table);
		
		// go to the table
		$table_alias = \DataPathSQLBuilder::build($path, $q);
		// join to foreign table
		$alias = $q->get_join_alias($table_alias, $t->getName(), array($foreign_key->name=>$t->getPrimaryKey()->name));
		if ($alias == null) {
			$alias = $q->table_id();
			$q->join($table_alias, $t->getName(), array($foreign_key->name=>$t->getPrimaryKey()->name), $alias);
		}
		// get the key and data
		$col_name = $this->handler->table->getPrimaryKey()->name;
		$key_alias = $q->get_field_alias($table_alias, $col_name);
		if ($key_alias == null) {
			$key_alias = $q->field_id();
			$q->field($table_alias, $col_name, $key_alias);
		}
		$data_alias = $q->get_field_alias($table_alias, $foreign_key->name);
		if ($data_alias == null) {
			$data_alias = $q->field_id();
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
		$col_name = $this->handler->table->getPrimaryKey()->name;
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
	
	public function buildSQL(&$q, $path, $filters) {
		// we need to go to the parent, and get the joining key
		$parent_alias = \DataPathSQLBuilder::build($path->parent, $q);
		$key_name = $path->is_reverse() ? $path->parent->table->getPrimaryKey()->name : $path->foreign_key->name;
		$alias = $q->get_field_alias($parent_alias, $key_name);
		if ($alias == null) {
			$alias = $q->field_id();
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

class ReadOnlyDataDisplay extends DataDisplay {
	
	public $display_name;
	public $data_path;
	
	public function __construct($handler, $display_name, $data_path) {
		parent::__construct($handler);
		$this->display_name = $display_name;
		$this->data_path = $data_path;
	}

	public function getDisplayName() { return $this->display_name; }
	public function getHandledColumns() { return array(); }
	
	private function getColumn($sub_model) {
		$columns = explode(".", $this->data_path);
		$t = $this->handler->table;
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
		return \PNApplication::$instance->widgets->get_typed_filter($this->getColumn($sub_model));
	}
	
	public function getSingleCell() { 
		$col = $this->getColumn(null); 
		return array("table"=>$col->table->getName(), "column"=>$col->name);
	}
	
	public function buildSQL(&$q, $path, $filters) {
		$columns = explode(".", $this->data_path);
		$t = $this->handler->table;
		// go to the table
		$table_alias = \DataPathSQLBuilder::build($path, $q);
		for ($i = 0; $i < count($columns); $i++) {
			$col = $t->getColumnFor($columns[$i], $path->sub_model);
			if ($i < count($columns)-1) {
				$ft = \DataModel::get()->getTable($col->foreign_table);
				$fta = $q->table_id();
				$q->join($table_alias, $col->foreign_table, array($columns[$i]=>$ft->getPrimaryKey()->name), $fta);
				$t = $ft;
				$table_alias = $fta;
			}
		}
		$key_alias = $q->field_id();
		$q->field($table_alias, $t->getPrimaryKey()->name, $key_alias);
		$data_alias = $q->field_id();
		$q->field($table_alias, $col->name, $data_alias);
		$this->applyColumnFilter($table_alias, $col, $filters, $q);
		return array("key"=>$key_alias,"data"=>$data_alias);
	}
	
	public function getData($key, $sub_model, $row = null, $key_alias = null, $value_alias = null) {
		// TODO
	}
	
	public function saveData($key, $value, $sub_model) {
	}
	
}

} // namespace datamodel
?>