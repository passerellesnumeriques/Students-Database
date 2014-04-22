<?php 
class service_create_data extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}
	
	public function execute(&$component, $input) {
		$root_table_name = $input["root"];
		$sub_model = @$input["sub_model"];
		$sub_models = @$input["sub_models"];
		$multiple = isset($input["multiple"]);
		require_once("component/data_model/DataPath.inc");
		$paths = DataPathBuilder::searchFrom($root_table_name, $sub_model, false, $sub_models);
		$to_create = $input["paths"];
		for ($i = 0; $i < count($paths); $i++)
			$paths[$i]->{"found"} = false;
		foreach ($to_create as $tc)
			for ($i = 0; $i < count($paths); $i++)
				if ($paths[$i]->getString() == $tc["path"]) {
					$paths[$i]->found = true;
					$paths[$i]->{"columns"} = isset($tc["columns"]) ? $tc["columns"] : array();
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
		$key = $this->createData($root, $multiple);
		if (PNApplication::has_errors())
			SQLQuery::rollbackTransaction();
		else {
			SQLQuery::commitTransaction();
			echo "{key:".json_encode($key)."}";
		}
	}
	
	private function createData($path, $multiple) {
		$come_from = null;
		if ($path instanceof DataPath_Join && $path->isReverse())
			$come_from = $path->foreign_key->name;
		$found = false;
		if (!$multiple)
			foreach (DataModel::get()->getDataScreens() as $screen) {
				$tables = $screen->getTables();
				if (count($tables) == 1 && $tables[0] == $path->table->getName()) {
					$key = $screen->createData(array($path), $multiple);
					$found = true;
					break;
				}				
			}
		if (!$found) {
			$display = DataModel::get()->getTableDataDisplay($path->table->getName());
			$screen = new datamodel\GenericDataScreen($display);
			$key = $screen->createData(array($path), $multiple);
		}
		return $key;
	}
	
}
?>