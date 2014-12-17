<?php 
class DataModelJSON {
	
	/**
	 * Generate a model
	 * @param DataModel $model
	 * @return string the JSON structure of the data model
	 */
	public static function model($model) {
		$s = "{";
		if ($model instanceof SubDataModel) {
			$s .= "\"parent_table\":".json_encode($model->getParentTable());
			$s .= ",";
		}
		$s .= "\"tables\":[";
		$first = true;
		foreach ($model->internalGetTables(false) as $table) {
			if ($first) $first = false; else $s .= ",";
			$s .= "{";
			$s .= "\"name\":".json_encode($table->getName());
			$s .= ",\"key\":";
			if ($table->getPrimaryKey() <> null)
				$s .= json_encode($table->getPrimaryKey()->name);
			else
				$s .= json_encode($table->getKey());
			$s .= ",\"indexes\":".json_encode($table->getIndexes());
			$s .= ",\"columns\":[";
			$first_col = true;
			foreach ($table->internalGetColumns(null, false) as $col) {
				if ($first_col) $first_col = false; else $s .= ",";
				$s .= "{";
				$s .= "\"name\":".json_encode($col->name);
				$type = get_class($col);
				$i = strpos($type,"\\");
				$type = substr($type,$i+1);
				$s .= ",\"type\":".json_encode($type);
				$s .= ",".$col->getJSONSpec();
				$s .= "}";
			}
			$s .= "]";
			$s .= "}";
		}
		$s .= "]";
		if (!($model instanceof SubDataModel)) {
			$s .= ",\"sub_models\":[";
			$first_sm = true;
			foreach ($model->getSubModels() as $sm) {
				if ($first_sm) $first_sm = false; else $s .= ",";
				self::model($sm);
			}
			$s .= "]";
		}
		$s .= "}";
		return $s;
	}
		
}
?>