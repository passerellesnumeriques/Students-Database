<?php 
class service_get_data extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Retrieve data from DataDisplay"; }
	public function input_documentation() { echo "<code>table</code>: table from which to retrieve data<br/><code>data</code>array of DataDisplay names<br/><code>keys</code>: keys of the row to retrieve<br/><code>sub_model</code>: optional, sub model instance"; }
	public function output_documentation() { echo "array of data"; }
	
	public function execute(&$component, $input) {
		require_once("component/data_model/Model.inc");
		require_once("component/data_model/DataPath.inc");
		$table = DataModel::get()->getTable($input["table"]);
		$display = DataModel::get()->getTableDataDisplay($input["table"]);
		$q = SQLQuery::create()->select($table->getName());
		if (isset($input["sub_model"]) && $input["sub_model"] <> null && $table->getModel() instanceof SubDataModel) $q->selectSubModel($table->getModel()->getParentTable(), $input["sub_model"]);
		$aliases = array();
		foreach ($display->getDataDisplay(null) as $data) {
			if (!in_array($data->getDisplayName(), $input["data"])) continue;
			$aliases[$data->getDisplayName()] = $data->buildSQL($q, new DataPath_Table($table, @$input["sub_model"]), array());
		}
		if ($table->getPrimaryKey() <> null)
			$q->whereIn($table->getName(), $table->getPrimaryKey()->name, $input["keys"]);
		else {
			// TODO
		}
		$rows = $q->execute();
		echo "[";
		$first = true;
		foreach ($rows as $r) {
			if ($first) $first = false; else echo ",";
			echo "[";
			$first_data = true;
			foreach ($input["data"] as $dname) {
				if ($first_data) $first_data = false; else echo ",";
				foreach ($display->getDataDisplay(null) as $data) {
					if ($data->getDisplayName() == $dname) {
						echo json_encode($r[$aliases[$dname]["data"]]);
					}
				}
			}
			echo "]";
		}
		echo "]";
	}
	
}
?>