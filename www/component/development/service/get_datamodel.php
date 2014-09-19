<?php 
class service_get_datamodel extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function getOutputFormat($input) {
		if (isset($_GET["output"]) && $_GET["output"] == "sql")
			return "text/plain;charset=UTF-8";
		return parent::getOutputFormat($input);
	}
	
	public function execute(&$component, $input) {
		require_once("component/data_model/Model.inc");
		if (isset($_GET["output"]) && $_GET["output"] == "sql") {
			$model = DataModel::get();
			require_once("component/data_model/DataBaseUtilities.inc");
			$ref = new ReflectionClass("DataModel");
			$p = $ref->getProperty("tables");
			$p->setAccessible(true);
			$tables = $p->getValue($model);
			foreach ($tables as $table) {
				echo DataBaseUtilities::createTable(SQLQuery::getDataBaseAccessWithoutSecurity(), $table, null, true);
				echo ";\n";
			}
		} else {
			echo "{\"model\":";
			$this->model(DataModel::get());
			echo "}";
		}
	}
	
	/**
	 * Generate a model
	 * @param DataModel $model
	 */
	private function model($model) {
		echo "{";
		if ($model instanceof SubDataModel) {
			echo "\"parent_table\":".json_encode($model->getParentTable());
			echo ",";
		}
		echo "\"tables\":[";
		$first = true;
		foreach ($model->internalGetTables(false) as $table) {
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "\"name\":".json_encode($table->getName());
			echo ",\"key\":";
			if ($table->getPrimaryKey() <> null)
				echo json_encode($table->getPrimaryKey()->name);
			else
				echo json_encode($table->getKey());
			echo ",\"indexes\":".json_encode($table->getIndexes());
			echo ",\"columns\":[";
			$first_col = true;
			foreach ($table->internalGetColumns(null, false) as $col) {
				if ($first_col) $first_col = false; else echo ",";
				echo "{";
				echo "\"name\":".json_encode($col->name);
				$type = get_class($col);
				$i = strpos($type,"\\");
				$type = substr($type,$i+1);
				echo ",\"type\":".json_encode($type);
				echo ",".$col->getJSONSpec();
				echo "}";
			}
			echo "]";
			echo "}";
		}
		echo "]";
		if (!($model instanceof SubDataModel)) {
			echo ",\"sub_models\":[";
			$first_sm = true;
			foreach ($model->getSubModels() as $sm) {
				if ($first_sm) $first_sm = false; else echo ",";
				$this->model($sm);
			}
			echo "]";
		}
		echo "}";
	}
	
}
?>