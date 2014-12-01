<?php 
require_once("component/data_model/DataDisplay.inc");
class GeographicAreaDataDisplay extends \datamodel\DataDisplay {
	
	public function __construct($col_name, $display_name, $can_be_null) {
		$this->col_name = $col_name;
		$this->display_name = $display_name;
		$this->can_be_null = $can_be_null;
	}
	
	private $col_name;
	private $display_name;
	private $can_be_null;
	
	public function getDisplayName() { return $this->display_name; }
	public function canAccess($sub_model) { return true; }
	public function getHandledColumns() { return array($this->col_name); }
	public function getTypedField($sub_model) {
		return array("field_geographic_area","{can_be_null:".json_encode($this->can_be_null)."}");
	}
	public function isEditable() { return true; }
	public function getEditLocks($sub_model) {
		return array(array("table"=>$this->getTable()->getName(), "column"=>$this->col_name, "sub_model"=>$sub_model));
	}
	public function isSortable() { return false; }
	public function isMandatory($sub_model) { return !$this->can_be_null; }
	
	public function buildSQL(&$q, $path) {
		$table_alias = \DataPathSQLBuilder::build($path, $q);
		$alias = $q->getFieldAlias($table_alias, $this->col_name);
		if ($alias == null) {
			$alias = $q->generateFieldAlias();
			$q->field($table_alias, $this->col_name, $alias);
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
		return null; // TODO
	}
	
	public function saveData($key, $value, $sub_model, &$tables_fields, $joining_key_for_new_data, $joining_key_value_for_new_data) {
		$tables_fields->addValue("ApplicantMoreInfo", $sub_model, $key, $this->col_name, $value);
	}
	
}
?>