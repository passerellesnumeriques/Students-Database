<?php
class service_lock_cell extends Service {
	public function get_required_rights() { return array(); }
	public function documentation() { echo "Request a lock for a specific cell in a table"; }
	public function input_documentation() {
?>
<ul>
	<li><code>table</code>: table name of the entity</li>
	<li><code>column</code>: column name of the cell to lock</li>
	<li><code>row_key</code>: primary key of the row to lock</li>
	<li><code>sub_model</code>: optional</li>
</ul>
<?php
	}
	public function output_documentation() {
?>
<ul>
	<li><code>lock</code>: id of the lock</li>
	<li><code>value</code>: value of the cell</li>
</ul>
<?php
	}
	public function execute(&$component, $input) {
		$table = $input["table"];
		$field = $input["column"];
		$key = $input["row_key"];
		require_once("component/data_model/DataBaseLock.inc");
		require_once("component/data_model/Model.inc");
		$model = DataModel::get();
		$table = $model->getTable($table); // here check is done is the user can access this table
		// TODO check rights, and create test scenario
// 		if (!$table->may) {
// 			PNApplication::error("Access denied to column '".$field."' in table '".$table->getName()."'");
// 			return;
// 		}
		$locked_by = null;
		$sub_model = @$input["sub_model"];
		$lock = DataBaseLock::lockCell($table->getSQLNameFor($sub_model), $key, $field, $locked_by);
		if ($lock == null) {
			PNApplication::error("This data is already locked by ".$locked_by);
			return;
		}
		
		$q = SQLQuery::create();
		$table_alias = $q->generateTableAlias();
		$q->select(array($table->getName()=>$table_alias));
		if ($sub_model <> null) $q->selectSubModelForTable($table, $sub_model);
		$q->field($table_alias, $field);
		if ($table->getPrimaryKey() <> null)
			$q->whereValue($table_alias, $table->getPrimaryKey()->name, $key);
		else {
			$pk = $table->getKey();
			foreach ($pk as $pk_name)
				$q->whereValue($table_alias, $pk_name, $key[$pk_name]);
		}
		$value = $q->executeSingleValue();
		
		echo "{lock:".json_encode($lock).",value:".json_encode($value)."}";
	}
} 
?>