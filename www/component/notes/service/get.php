<?php 
class service_get extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Get notes on an item"; }
	public function inputDocumentation() { echo "table, key, sub_model, sub_model_instance: the attached item"; }
	public function outputDocumentation() { echo "A list of notes, the more recent first"; }
	
	public function execute(&$component, $input) {
		if (!$component->getReadAccess($input["table"], $input["key"], $input["sub_model"], $input["sub_model_instance"])) {
			echo "{notes:[]}";
			return;
		}
		$q = SQLQuery::create();
		if ($input["sub_model"] <> null)
			$q->selectSubModel($input["sub_model"], $input["sub_model_instance"]);
		$q->bypassSecurity();
		$q->select("Notes".$input["table"]);
		$q->whereValue("Notes".$input["table"], "attach", $input["key"]);
		$q->join("Notes".$input["table"], "Note", array("note"=>"id"));
		$q->orderBy("Note","timestamp",false);
		$notes = $q->execute();
		$peoples_ids = array();
		foreach ($notes as $n) if (!in_array($n["author"], $peoples_ids)) array_push($peoples_ids, $n["author"]);
		$peoples = PNApplication::$instance->people->getPeoples($peoples_ids, true, false, true, true);
		echo "{notes:[";
		$first = true;
		foreach ($notes as $n) {
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "id:".$n["id"];
			echo ",title:".json_encode($n["title"]);
			echo ",text:".json_encode($n["text"]);
			echo ",author:";
			foreach ($peoples as $p) if ($p["people_id"] == $n["author"]) { echo PeopleJSON::People($p); break; }
			echo ",timestamp:".$n["timestamp"];
			echo "}";
		}
		echo "]}";
	}
	
}
?>