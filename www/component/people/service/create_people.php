<?php 
class service_create_people extends Service {
	
	public function get_required_rights() { return array(); } // TODO
	
	public function documentation() { echo "Create a new people, from the wizard"; }
	public function input_documentation() { echo "The input is the structure saved by the wizard, and depends on other components data"; }
	public function output_documentation() { echo "On success, returns the id of the newly created people"; }
	
	public function execute(&$component, $input) {
		// TODO transaction
		$types = array_merge($input["types"]);
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof PeoplePlugin)) continue;
				$supported_types = $pi->getCreatePeopleSupportedTypes();
				if ($supported_types == null) continue;
				for ($i = 0; $i < count($types); $i++) {
					if (!in_array($types[$i], $supported_types)) continue;
					if (!$pi->isCreatePeopleAllowed($types[$i])) {
						PNApplication::error("You are not allowed to create a people of type '".$types[$i]."'.");
						return;
					}
					array_splice($types, $i, 1);
					$i--;
				}
			}
		}
		if (count($types) <> 0) {
			foreach ($types as $type)
				PNApplication::error("Invalid people type '".$type."'");
			return;
		}
		$types = $input["types"];
		
		$fields = array_merge($input["people"]);
		unset($fields["people_id"]);
		try {
			$people_id = SQLQuery::create()->bypassSecurity()->insert("People", $fields);
		} catch (Exception $ex) { PNApplication::error($ex); $people_id = 0; }
		if ($people_id == 0) { echo "false"; return; }
		
		$list = array();
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof PeoplePlugin)) continue;
				$cpages = $pi->getCreatePeoplePages($types);
				if ($cpages == null || count($cpages) == 0) continue;
				$min = 99999999;
				foreach ($cpages as $p) if ($p[2] < $min) $min = $p[2];
				array_push($list, array($pi, $min));
			}
		}
		usort($list, "cmp_create_people_component");
		$create_data = array();
		for ($i = 0; $i < count($list); $i++) {
			$pi = $list[$i][0];
			if (!$pi->createPeople($people_id, $types, $input, $create_data)) {
				// failure: roll back
				for ($j = $i-1; $j >= 0; --$j)
					$list[$j][0]->rollbackCreatePeople($people_id, $types, $input, $create_data);
				SQLQuery::create()->bypassSecurity()->removeKey("People", $people_id);
				echo "false";
				return;
			}
		}
		echo "{id:".$people_id."}";
	}
	
}

function cmp_create_people_component($p1,$p2) {
	if ($p1[1] < $p2[1]) return -1;
	if ($p1[1] > $p2[1]) return 1;
	return 0;
}
?>