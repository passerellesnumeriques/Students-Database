<?php 
use datamodel\DataDisplayCategory;
use datamodel\DataDisplay;
/* @var $model DataModel */

$category = new DataDisplayCategory("Finance", "/static/finance/finance_16.png");
$model->addDataCategory($category);

class BalanceDataDisplay extends DataDisplay {
	
	public function __construct($type) {
		$this->type = $type;
	}
	
	private $type;
	
	public function getDisplayName() {
		switch ($this->type) {
			case "current": return "Current Balance";
			case "upcoming": return "Upcoming Due Amount";
			case "final": return "Final Balance";
		}
	}
	public function canAccess($sub_model) {
		return PNApplication::$instance->user_management->hasRight("consult_student_finance");
	}
	public function getHandledColumns() { return array(); }
	public function getTypedField($sub_model) {
		return array("field_decimal","{can_be_null:false}");
	}
	public function getHorizontalAlign($sub_model) { return "right"; }
	public function isEditable() { return false; }
	public function getEditLocks($sub_model) { return array(); }
	public function isSortable() { return true; }
	public function isMandatory($sub_model) { return false; }
	public function getTypedFilter($sub_model) {
		return array("filter_comparable","{value_field_classname:'field_decimal',value_field_config:{can_be_null:false}}");
	}
	/** Add any necessary select/join/field to the SQLQuery to retrieve this data
	 * @param SQLQuery $q the sql
	 * @param DataPath $path how we reached this data
	 * @return array("key"=&gt;key_alias,"data"=&gt;data_alias): if data is null or not specified, the function performSubRequest must be implemented
	 */
	public function buildSQL(&$q, $path) {
		$key_alias = $q->getFieldAlias("People", "id");
		if ($key_alias == null) $key_alias = $q->getFieldAlias("Student","people");
		
		$table_alias = $q->generateTableAlias();
		$sub_q = SQLQuery::create()->select(array("FinanceOperation"=>$table_alias));
		$people_table = $q->getTableAlias("People",true);
		if ($people_table == null) {
			$people_table = $q->getTableAlias("Student",true);
			$people_key = "people";
		} else
			$people_key = "id";
		$sub_q->where("`$table_alias`.`people`=`$people_table`.`$people_key`");
		switch ($this->type) {
			case "current": $sub_q->where("`$table_alias`.`date` <= UNIX_TIMESTAMP()"); break;
			case "upcoming": $sub_q->where("`$table_alias`.`date` > UNIX_TIMESTAMP()"); break;
			case "final": break;
		}
		$alias = $q->generateFieldAlias();
		$sub_q->expression("SUM(`$table_alias`.`amount`)",$alias);
		$sub_q->groupBy($table_alias, "people");
		$data_alias = $q->generateFieldAlias();
		$q->fieldSubQuery($sub_q, $data_alias);
		
		return array("key"=>$key_alias, "data"=>$data_alias);
	}

	public function getFilterCondition(&$q, $path, $data_aliases, $filter) {
		$cd = $this->getExpressionFilterCondition("`".$data_aliases["data"]."`", "filter_comparable", $filter, $q);
		if ($cd == null) return null;
		return array("type"=>"having","condition"=>$cd);
	}
	
	public function saveData($key, $value, $sub_model, &$tables_fields, $joining_key_for_new_data, $joining_key_value_for_new_data) {}
}

$display = new datamodel\TableDataDisplay("FinanceOperation", $category);
$display->addDataDisplay(new BalanceDataDisplay("current"), "people");
$display->addDataDisplay(new BalanceDataDisplay("upcoming"), "people");
$display->addDataDisplay(new BalanceDataDisplay("final"), "people");
$display->addJoinFrom("people", array("types"), function($columns) {
	$types = PNApplication::$instance->people->parseTypes($columns["types"]);
	return in_array("student", $types);
});
$model->addTableDataDisplay($display);

?>