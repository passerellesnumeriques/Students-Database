<?php 
/**
 * Provides functionalities to manipulate the data model
 */
class data_model extends Component {
	
	public function getPluginImplementations() {
		require_once("DataModelAdministrationPlugin.inc");
		return array(
			new DataModelAdministrationPlugin()
		);
	}
	
	/**
	 * Return all reachable DataDisplay from the given root table
	 * @param string $root_table the table to start from
	 * @return array of array(DataDisplay, DataPath)
	 */
	public function getAvailableFields($root_table, $sub_model, $go_to_submodels = false, $sub_models = null, $known_columns = null, $for_new_data = false) {
		require_once("component/data_model/DataPath.inc");
		$paths = DataPathBuilder::searchFrom($root_table, $sub_model, $go_to_submodels, $sub_models);
		$root = DataPathBuilder::buildPathsTree($paths);
		$list = array();
		if ($root <> null) {
			$tables_done = array();
			$this->browse($root, null, $list, $known_columns, $for_new_data, $tables_done);
		}
		return $list;
	}
	
	/**
	 * Browse using the TableDataDisplay of the table pointed by the path 
	 * @param DataPath $path the path to browse
	 * @param string|null $from the column by which we come from 
	 * @param array $list the list to be filled
	 */
	private function browse($path, $from, &$list, $known_columns, $for_new_data, &$tables_done) {
		if (in_array($path->table->getName(), $tables_done)) return;
		array_push($tables_done, $path->table->getName());
		
		$handled = array();
		$screen_found = false;
		foreach (DataModel::get()->getDataScreens() as $screen) {
			if (!($screen instanceof \datamodel\MultipleDataScreen)) continue;
			$tables = $screen->getTables();
			if (!in_array($path->table->getName(), $tables)) continue;
			$screen_found = true;
			foreach ($tables as $t) if ($t <> $path->table->getName()) array_push($tables_done, $t);
			$data = $screen->getDataDisplay($from, $path->sub_model, $for_new_data);
			foreach ($data as $d) {
				array_push($list, array($d, $path));
				foreach ($d->getHandledColumns() as $col)
					array_push($handled, $col);
			}
		}
		$display = DataModel::get()->getTableDataDisplay($path->table->getName());
		if (!$screen_found) {
			if ($display == null) return; // nothing accessible
			$data = $display->getDataDisplay($from, $path->sub_model);
			foreach ($data as $d) {
				array_push($list, array($d, $path));
				foreach ($d->getHandledColumns() as $col)
					array_push($handled, $col);
			}
		}
		foreach ($path->children as $c) {
			if ($c->isReverse()) {
				if ($display == null || ($path->table->getPrimaryKey() <> null && !$display->canJoinToPrimary())) continue;
				$fd = DataModel::get()->getTableDataDisplay($c->table->getName());
				if ($fd == null) continue;
				if (!$fd->canJoinFrom($c->foreign_key->name)) continue;
				$cols = $fd->getNeededColumnsToJoinFrom($c->foreign_key->name);
				if ($cols <> null && count($cols) > 0 && $known_columns <> null) {
					$values = array();
					foreach ($cols as $colname) {
						foreach ($known_columns as $table=>$tcols) {
							if ($table <> $path->table->getName()) continue;
							foreach ($tcols as $tc=>$tv)
								if ($tc == $colname) {
									$values[$tc] = $tv;
									break;								
								}
						}
					}
					if (count($values) == count($cols)) {
						if (!$fd->checkJoinFrom($c->foreign_key->name, $values)) continue;
					}
				}
				$this->browse($c, $c->foreign_key->name, $list, $known_columns, $for_new_data, $tables_done);
			} else if (!in_array($c->foreign_key->name, $handled)) {
				if ($display == null || !$display->canFollow($c->foreign_key->name)) continue;
				$display_foreign = DataModel::get()->getTableDataDisplay($c->table->getName());
				if ($display_foreign <> null && $c->table->getPrimaryKey() <> null && !$display_foreign->canJoinToPrimary()) continue;
				$this->browse($c, null, $list, $known_columns, $for_new_data, $tables_done);
			}
		}
	}
	
}
?>