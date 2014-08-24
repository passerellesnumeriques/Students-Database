<?php 
class service_remove_type extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Remove the given people type from a people"; }
	public function inputDocumentation() { echo "people, type"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$people_id = $input["people"];
		$type_name = $input["type"];
		$people = PNApplication::$instance->people->getPeople($people_id);
		if ($people == null) {
			PNApplication::error("Invalid people id");
			return;
		}
		$types = PNApplication::$instance->people->parseTypes($people["types"]);
		$type = PNApplication::$instance->people->getPeopleTypePlugin($type_name);
		if ($type == null) {
			PNApplication::error("Unknown people type ".$type_name);
			return;
		}
		if (!$type->canRemove()) {
			PNApplication::error("You are not allowed to remove a ".$type->getName());
			return;
		}
		$found = false;
		for ($i = 0; $i < count($types); $i++)
			if ($types[$i] == $type_name) { array_splice($types,$i,1); $found = true; break; }
		if (!$found) {
			PNApplication::error("Invalid type: ".$people["first_name"]." ".$people["last_name"]." is not a ".$type->getName());
			return;
		}
		if (count($types) == 0) {
			PNApplication::error("This people has only the type ".$type_name.". You cannot remove this type using this service, you should remove entirely this people.");
			return;
		}
		SQLQuery::startTransaction();
		$t = "";
		foreach ($types as $type_name) $t .= "/".$type_name."/";
		SQLQuery::create()->updateByKey("People", $people_id, array("types"=>$t));
		$type->remove($people_id);
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>