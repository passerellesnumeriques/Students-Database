<?php
class service_picture extends Service {
	public function getRequiredRights() { return array(); }
	public function documentation() { echo "Retrieve the profile picture of a people"; }
	public function inputDocumentation() { echo "<code>people</code>: id of the people to get the picture"; }
	public function outputDocumentation() { echo "storage_id and revision, or sex (if no picture)"; }
	public function execute(&$component, $input) {
		if (!isset($input["people"]) && isset($_GET["people"])) $input = $_GET;
		$people_id = $input["people"];
		/* accept to access the picture of anyone...
		if (!$component->canRead($people_id)) {
			PNApplication::error("Access denied: you cannot access to the data of this person");
			return;
		}
		*/
		$q = SQLQuery::create()
			->select("People")
			->whereValue("People", "id", $people_id)
			->field("picture")
			->field("sex")
			->bypassSecurity() // accept to access the picture of anyone...
			;
		if (isset($input["domain"])) $q->databaseOfDomain($input["domain"]);
		PNApplication::$instance->storage->joinRevision($q, "People", "picture", "revision");
		$people = $q->executeSingleRow();
		if ($people["picture"] <> null) {
			if (@$input["redirect"] == "1")
				header("Location: /dynamic/storage/service/get?id=".$people["picture"]."&revision=".$people["revision"].(isset($input["domain"]) ? "&domain=".$input["domain"] : ""));
			else
				echo "{storage_id:".$people["picture"].",revision:".$people["revision"]."}";
		} else {
			if (@$input["redirect"] == "1")
				header("Location: /static/people/default_".($people["sex"] == "F" ? "female" : "male").".jpg");
			else
				echo "{sex:".json_encode($people["sex"])."}";
		}
	}
}
?>