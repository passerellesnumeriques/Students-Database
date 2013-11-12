<?php 
class service_lock_datadisplay extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Lock a DataDisplay"; }
	public function input_documentation() {
		echo "<ul>";
		echo "<li><code>table</code>: the table containing the DataDisplay</li>";
		echo "<li><code>sub_model</code>: the sub model if any</li>";
		echo "<li><code>come_from</code>: the entry point to the table, if any</li>";
		echo "<li><code>name</code>: the name of the DataDisplay</li>";
		echo "<li><code>key</code>: the key representing a single data</li>";
		echo "</ul>";
	}
	public function output_documentation() {
		echo "<ul>";
		echo "<li><code>locks</code>: a list of lock id</li>";
		echo "<li><code>data</code>: the data from the database (updated with latest just after locking it)</li>";
		echo "</ul>";
	}
	
	public function execute(&$component, $input) {
		require_once("component/data_model/Model.inc");
		require_once("component/data_model/DataBaseLock.inc");
		$table = $input["table"];
		$sub_model = @$input["sub_model"];
		$t = DataModel::get()->getTable($table);
		$come_from = @$input["come_from"];
		$display = $t->getDisplayHandler($come_from);
		if ($display == null) {
			PNApplication::error("No DataDisplayHandler on table ".$table);
			return;
		}
		foreach ($display->getDisplayableData() as $data) {
			if ($data->getDisplayName() == $input["name"]) {
				$locks = $data->getEditLocks($sub_model);
				$ids = array();
				foreach ($locks as $lock) {
					if ($lock["table"] == $t->getSQLNameFor($sub_model))
						$lock["row_key"] = $input["key"];
					$id = null;
					$locked_by = null;
					if (isset($lock["column"])) {
						if (isset($lock["row_key"]))
							$id = DataBaseLock::lock_cell($lock["table"], $lock["row_key"], $lock["column"], $locked_by);
						else
							$id = DataBaseLock::lock_column($lock["table"], $lock["column"], $locked_by);
					} else {
						if (isset($lock["row_ley"]))
							$id = DataBaseLock::lock_row($lock["table"], $lock["row_key"], $locked_by);
						else
							$id = DataBaseLock::lock_table($lock["table"], $locked_by);
					}
					if ($id == null) {
						// rollback
						foreach ($ids as $i) DataBaseLock::unlock($i);
						PNApplication::error("Data already locked by ".$locked_by);
						return;
					}
					array_push($ids, $id);
				}
				echo "{locks:".json_encode($ids);
				echo ",data:".json_encode($data->getData($input["key"], $sub_model));
				echo "}";
				return;
			}
		}
		PNApplication::error("Unknown DataDisplay ".$input["name"]." on table ".$table);
	}
	
}
?>