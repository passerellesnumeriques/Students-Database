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
	public function getAvailableFields($root_table) {
		require_once("component/data_model/DataPath.inc");
		$paths = DataPathBuilder::searchFrom($root_table);
		$root = DataPathBuilder::buildPathsTree($paths);
		$list = array();
		if ($root <> null)
			$this->browse($root, null, $list);
		return $list;
	}
	
	/**
	 * Browse using the TableDataDisplay of the table pointed by the path 
	 * @param DataPath $path the path to browse
	 * @param string|null $from the column by which we come from 
	 * @param array $list the list to be filled
	 */
	private function browse($path, $from, &$list) {
		$display = DataModel::get()->getTableDataDisplay($path->table->getName());
		if ($display == null) return; // nothing accessible
		$handled = array();
		if ($display <> null) {
			$data = $display->getDataDisplay($from);
			foreach ($data as $d) {
				array_push($list, array($d, $path));
				foreach ($d->getHandledColumns() as $col)
					array_push($handled, $col);
			}
		}
		foreach ($path->children as $c) {
			if ($c->isReverse()) {
				if (!$display->canJoinToPrimary()) continue;
				$fd = DataModel::get()->getTableDataDisplay($c->table->getName());
				if ($fd == null) continue;
				if (!$fd->canJoinFrom($c->foreign_key->name)) continue;
				$this->browse($c, $c->foreign_key->name, $list);
			} else if (!in_array($c->foreign_key->name, $handled)) {
				if (!$display->canFollow($c->foreign_key->name)) continue;
				$this->browse($c, null, $list);
			}
		}
	}
	
}
?>