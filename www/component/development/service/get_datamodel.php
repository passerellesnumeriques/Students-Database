<?php 
class service_get_datamodel extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		require_once("component/data_model/Model.inc");
		echo "{\"model\":";
		$this->model(DataModel::get());
		echo "}";
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
		foreach ($model->internalGetTables() as $table) {
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "\"name\":".json_encode($table->getName());
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