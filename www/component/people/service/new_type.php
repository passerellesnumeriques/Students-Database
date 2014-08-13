<?php 
class service_new_type extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	/**
	 * @param $component people
	 */
	public function execute(&$component, $input) {
		$people_id = $input["people"];
		$type = $input["type"];
		SQLQuery::startTransaction();
		$component->addPeopleType($people_id, $type);
		
		$type = PNApplication::$instance->people->getPeopleTypePlugin($type);
		$tables = $type->getTables();
		$screens = array();
		require_once("component/data_model/Model.inc");
		foreach (DataModel::get()->getDataScreens() as $screen) {
			if (!($screen instanceof \datamodel\SimpleDataScreen)) continue;
			$ok = true;
			foreach ($screen->getTables() as $t) if (!in_array($t, $tables)) { $ok = false; break; }
			if (!$ok) continue;
			array_push($screens, $screen);
		}
		
		require_once("component/data_model/DataPath.inc");
		$all_paths = DataPathBuilder::searchFrom("People", null, false, array());
		
		foreach ($screens as $screen) {
			$paths = array();
			$stables = $screen->getTables();
			foreach ($stables as $table_name)
				foreach ($all_paths as $p) if ($p->table->getName() == $table_name) { array_push($paths, $p); break; }
			for ($i = 0; $i < count($paths); $i++) {
				$paths[$i]->columns = array();
				$paths[$i]->value = array();
				$paths[$i]->children = array();
				foreach ($paths[$i]->table->internalGetColumns() as $col)
					if ($col instanceof \datamodel\ForeignKey)
						if ($col->foreign_table == "People")
							$paths[$i]->columns[$col->name] = $people_id;
				foreach ($input["data"] as $data) {
					if ($data["path"] <> $paths[$i]->getString()) continue;
					$paths[$i]->value = $data["value"];
					break;
				}
			}
			$screen->createData($paths, false);
		}
		
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>