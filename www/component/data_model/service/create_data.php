<?php 
class service_create_data extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}
	
	public function execute(&$component, $input) {
		$root_table_name = $input["root"];
		$sub_model = @$input["sub_model"];
		require_once("component/data_model/DataPath.inc");
		$paths = DataPathBuilder::searchFrom($root_table_name, $sub_model);
		$to_create = $input["paths"];
		for ($i = 0; $i < count($paths); $i++)
			$paths[$i]->{"found"} = false;
		foreach ($to_create as $tc)
			for ($i = 0; $i < count($paths); $i++)
				if ($paths[$i]->getString() == $tc["path"]) {
					$paths[$i]->found = true;
					$paths[$i]->{"columns"} = isset($tc["columns"]) ? $tc["columns"] : array();
					$paths[$i]->{"data"} = null;
					if (isset($tc["data"]))
						$paths[$i]->data = $tc["data"];
					else
						$paths[$i]->{"value"} = $tc["value"];
					break;
				}
		for ($i = 0; $i < count($paths); $i++)
			if (!$paths[$i]->found) {
				array_splice($paths, $i, 1);
				$i--;			
			}
		$root = DataPathBuilder::buildPathsTree($paths);
		SQLQuery::startTransaction();
		$this->createData($root);
		if (PNApplication::has_errors())
			SQLQuery::rollbackTransaction();
		else
			SQLQuery::commitTransaction();
	}
	
	private function createData($path) {
		$come_from = null;
		if ($path instanceof DataPath_Join) {
			if ($path->isReverse()) {
				$come_from = $path->foreign_key->name;
			} else {
			}
		}
		if ($path->data !== null) {
			// list of DataDisplay
			$display = DataModel::get()->getTableDataDisplay($path->table->getName());
			$to_update = new TablesToUpdate();
			if ($path->table->getPrimaryKey() <> null)
				$key = @$path->columns[$path->table->getPrimaryKey()->name];
			else {
				$key_cols = $path->table->getKey();
				$key = array();
				foreach ($key_cols as $col)
					if (isset($path->columns[$col]))
						$key[$col] = $path->columns[$col];
					else { $key = null; break; }
			}
			foreach ($path->columns as $cname=>$val)
				$to_update->addValue($path->table->getName(), $path->sub_model, $key, $cname, $val);
			foreach ($display->getDataDisplay($come_from) as $data) {
				$found = false;
				$val = null;
				foreach ($path->data as $cd)
					if ($cd["name"] == $data->getDisplayName()) {
						$found = true;
						$val = $cd["value"];
						break;
					}
				if (!$found) continue;
				$data->saveData($key, $val, $path->sub_model, $to_update);
			}
			$keys = $to_update->execute(true);
			if (isset($keys[$path->table->getName()][$path->sub_model][$key]))
				$key = $keys[$path->table->getName()][$path->sub_model][$key];
			foreach ($path->children as $child)
				if ($child->isReverse())
					$child->columns[$child->foreign_key->name] = $key;
			// check DataScreen for children
			foreach (DataModel::get()->getDataScreens() as $screen) {
				$tables = $screen->getTables();
				$paths = array();
				foreach ($tables as $t) {
					foreach ($path->children as $child)
						if ($child->table->getName() == $t) {
							array_push($paths, $child);
							break;						
						}
				}
				if (count($paths) == count($tables)) {
					$screen->createData($paths);
					// remove children handled by the screen
					foreach ($paths as $p) {
						for ($i = 0; $i < count($path->children); $i++)
							if ($path->children[$i] == $p) {
								array_splice($path->children, $i, 1);
								break;							
							}
					}
				}
			}
			// create data for remaining children
			foreach ($path->children as $child)
				$this->createData($child);
			return $key;
		} else {
			// from custom DataScreen
			foreach (DataModel::get()->getDataScreens() as $screen) {
				$tables = $screen->getTables();
				if (count($tables) == 1 && $tables[0] == $path->table->getName()) {
					$screen->createData(array($path));
					break;
				}				
			}
		}
	}
	
}
?>