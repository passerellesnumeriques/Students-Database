<?php 
class service_get_available_fields extends Service {
	
	public function get_required_rights() {
		return array();
	}
	
	public function documentation() { echo "Start from the given table, and search for all reachable fields, and return the list of displayable fields"; }
	public function input_documentation() { echo "<code>table</code>: name of starting table"; }
	public function output_documentation() { 
		/* TODO */
	}
	
	public function execute(&$component, $input) {
		$table = $input["table"];

		require_once("component/data_model/DataPath.inc");
		$paths = DataPathBuilder::search_from($table);
		
		$root = DataPathBuilder::build_paths_tree($paths);
		
		echo "[";
		if ($root <> null) {
			$first = true;
			$this->browse($root, null, $first);
		}
		echo "]";
	}
	
	private function browse($path, $from, &$first) {
		$display = $path->table->getDisplayHandler($from);
		$handled = array();
		if ($display <> null) {
			$data = $display->getDisplayableData();
			foreach ($data as $d) {
				if ($first) $first = false; else echo ",";
				echo "{data:";
				echo $d->javascriptDataDisplay($path->sub_model);
				echo ",path:".json_encode($path->get_string());
				echo "}";
				foreach ($d->getHandledColumns() as $col)
					array_push($handled, $col);
			}
			if ($display->stopHere()) return;
		}
		foreach ($path->children as $c) {
			if ($c->is_reverse())
				$this->browse($c, $c->foreign_key->name, $first);
			else if (!in_array($c->foreign_key->name, $handled))
				$this->browse($c, null, $first);
		}
	}
	
}
?>