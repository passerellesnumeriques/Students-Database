<?php
class service_lock_entity_field extends Service {
	public function get_required_rights() { return array(); }
	public function documentation() { echo "Request a lock for a specific cell in a table"; }
	public function input_documentation() {
?>
<ul>
	<li><code>table</code>: table name of the entity</li>
	<li><code>field</code>: column name of the cell to lock</li>
	<li><code>key</code>: primary key of the row to lock</li>
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
	public function execute(&$component) {
		$table = $_POST["table"];
		$field = $_POST["field"];
		$key = $_POST["key"];
		require_once("component/data_model/DataBaseLock.inc");
		require_once("component/data_model/Model.inc");
		$model = DataModel::get();
		$table = $model->getTable($table); // here check is done is the user can access this table
		if (!$table->canModifyField($field)) {
			PNApplication::error("Access denied to column '".$field."' in table '".$table->getName()."'");
			return;
		}
		$locked_by = null;
		$lock = DataBaseLock::lock($table->getName(), array($table->getPrimaryKey()->name=>$key), $locked_by, false);
		if ($lock == null) {
			PNApplication::error("This data is already locked by ".$locked_by);
			return;
		}
		$value = SQLQuery::create()->select($table->getName())->field($field)->where($table->getPrimaryKey()->name,$key)->execute_single_value();
		echo "{lock:".json_encode($lock).",value:".json_encode($value)."}";
	}
} 
?>