<?php 
class service_create_people extends Service {
	
	public function get_required_rights() { return array(); } // TODO
	
	public function documentation() { echo "Create a new people, from the wizard"; }
	public function input_documentation() { echo "The input is the structure saved by the wizard, and depends on other components data"; }
	public function output_documentation() { echo "On success, returns the id of the newly created people"; }
	
	public function execute(&$component, $input) {
		$people_id = @$input["people"]["people_id"];
		$people_created = false;
		if ($people_id == null) {
			// new people
			$people_created = true;
			$fields = array_merge($input["people"]);
			unset($fields["people_id"]);
			try {
				$people_id = SQLQuery::create()->insert("People", $fields);
			} catch (Exception $ex) { PNApplication::error($ex); $people_id = 0; }
			if ($people_id == 0) { echo "false"; return; }
		}
		
		$list = array();
		require_once("component/people/ProfileGeneralInfoPlugin.inc");
		foreach (PNApplication::$instance->components as $c) {
			if (!($c instanceof ProfileGeneralInfoPlugin)) continue;
			$cpages = $c->get_create_people_pages($input["people_type"]);
			if (count($cpages) == 0) continue;
			$min = 99999999;
			foreach ($cpages as $p) if ($p[2] < $min) $min = $p[2];
			array_push($list, array($c, $min));
		}
		usort($list, "cmp_create_people_component");
		$create_data = array();
		for ($i = 0; $i < count($list); $i++) {
			$c = $list[$i][0];
			if (!$c->create_people($people_id, $input["people_type"], $input, $create_data)) {
				// failure: roll back
				for ($j = $i-1; $j >= 0; --$j)
					$list[$j][0]->rollback_create_people($people_id, $input["people_type"], $input, $create_data);
				if ($people_created)
					SQLQuery::create()->remove_key("People", $people_id);
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