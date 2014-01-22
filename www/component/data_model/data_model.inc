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
		$paths = DataPathBuilder::search_from($root_table);
		$root = DataPathBuilder::build_paths_tree($paths);
		$list = array();
		if ($root <> null)
			$this->browse($root, null, $list);
		return $list;
	}
	
	/**
	 * Browse using the DisplayHandler of the table pointed by the path 
	 * @param DataPath $path the path to browse
	 * @param string|null $from the column by which we come from 
	 * @param array $list the list to be filled
	 */
	private function browse($path, $from, &$list) {
		$display = $path->table->getDisplayHandler($from);
		$handled = array();
		if ($display <> null) {
			$data = $display->getDisplayableData();
			foreach ($data as $d) {
				array_push($list, array($d, $path));
				foreach ($d->getHandledColumns() as $col)
					array_push($handled, $col);
			}
		}
		foreach ($path->children as $c) {
			if ($c->is_reverse()) {
				if ($display <> null && !$display->canJoin()) continue;
				$this->browse($c, $c->foreign_key->name, $list);
			} else if (!in_array($c->foreign_key->name, $handled)) {
				if ($display <> null && !$display->canFollow($c->foreign_key->name)) continue;
				$this->browse($c, null, $list);
			}
		}
	}
	
}
?>