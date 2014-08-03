<?php 
class service_find_invalid_keys extends Service {
	
	public function getRequiredRights() { return array(); } // TODO
	
	public function documentation() { echo "Find foreign keys without corresponding primary key"; }
	public function inputDocumentation() { echo "None"; }
	public function outputDocumentation() { /* TODO */ }
	
	public function execute(&$component, $input) {
		require_once("component/data_model/Model.inc");
		$invalid = array();
		foreach (DataModel::get()->internalGetTables() as $table) {
			if ($table->getModel() instanceof SubDataModel) {
				foreach ($table->getModel()->getExistingInstances() as $sub_model)
					$this->find_invalid_keys($table, $sub_model, $invalid);
			} else
				$this->find_invalid_keys($table, null, $invalid);
		}
		echo "[";
		$first = true;
		foreach ($invalid as $table_name=>$columns) {
			if ($first) $first = false; else echo ",";
			echo "{table:".json_encode($table_name).",columns:[";
			$first_col = true;
			foreach ($columns as $col_name=>$keys) {
				if ($first_col) $first_col = false; else echo ",";
				echo "{name:".json_encode($col_name).",keys:".json_encode($keys)."}";
			}
			echo "]}";
		}
		echo "]";
	}
	
	/**
	 * 
	 * @param \datamodel\Table $table
	 * @param integer|null $sub_model
	 */
	private function find_invalid_keys($table, $sub_model, &$invalid) {
		foreach ($table->internalGetColumnsFor($sub_model) as $col) {
			if (!($col instanceof \datamodel\ForeignKey)) continue;
			$ft = $col->foreign_table;
			if ($ft == $table->getName()) {
				// recursive link
				$keys = SQLQuery::create()->bypassSecurity()
#DEV
					->noWarning()
#END
					->selectSubModelForTable($table, $sub_model)
					->select($table->getName())
					->field($col->name)
					->distinct()
					->whereNotNull($table->getName(), $col->name)
					->executeSingleField();
				if (count($keys) == 0) continue;
				$find_keys = SQLQuery::create()->bypassSecurity()
#DEV
					->noWarning()
#END
					->selectSubModelForTable($table, $sub_model)
					->select($table->getName())
					->whereIn($table->getName(), $table->getPrimaryKey()->name, $keys)
					->field($table->getName(), $table->getPrimaryKey()->name)
					->executeSingleField();
				if (count($find_keys) == count($keys)) continue; // ok
				for ($i = 0; $i < count($keys); $i++) {
					if (in_array($keys[$i], $find_keys)) {
						array_splice($keys, $i, 1);
						$i--;
					}
				}
				if (!isset($invalid[$table->getSQLNameFor($sub_model)])) $invalid[$table->getSQLNameFor($sub_model)] = array();
				$invalid[$table->getSQLNameFor($sub_model)][$col->name] = $keys;
			} else {
				$ft = DataModel::get()->internalGetTable($ft);
				$sm = null;
				if ($ft->getModel() instanceof SubDataModel) {
					// we must be in the same
					$sm = $sub_model;				
				}
				$rows = SQLQuery::create()->bypassSecurity()
#DEV
					->noWarning()
#END
					->selectSubModelForTable($table, $sub_model)
					->select($table->getName())
					->join($table->getName(), $ft->getName(), array($col->name=>$ft->getPrimaryKey()->name))
					->field($table->getName(), $col->name, "FOREIGN")
					->whereNotNull($table->getName(), $col->name)
					->whereNull($ft->getName(), $ft->getPrimaryKey()->name)
					->executeSingleField();
				if (count($rows) > 0) {
					if (!isset($invalid[$table->getSQLNameFor($sub_model)])) $invalid[$table->getSQLNameFor($sub_model)] = array();
					$invalid[$table->getSQLNameFor($sub_model)][$col->name] = $rows;
				}
			}
		}
	}
	
}
?>