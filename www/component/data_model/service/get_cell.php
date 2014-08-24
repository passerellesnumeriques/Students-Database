<?php 
class service_get_cell extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Retrieve a value from database"; }
	public function inputDocumentation() { echo "table, column, row_key"; }
	public function outputDocumentation() { echo "{value:xxx}"; }
	
	public function execute(&$component, $input) {
		require_once("component/data_model/Model.inc");
		$table = DataModel::get()->getTable($input["table"]);
		$value = SQLQuery::create()->select($input["table"])->where($table->getPrimaryKey()->name, $input["row_key"])->field($input["column"])->executeSingleValue();
		echo "{value:".json_encode($value)."}";
	}
	
}
?>