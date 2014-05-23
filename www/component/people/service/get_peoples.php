<?php 
class service_get_peoples extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Retrieve People JSON structures"; }
	public function inputDocumentation() { echo "<code>ids</code>: people ids"; }
	public function outputDocumentation() { echo "A list of People JSON objects"; }
	
	public function execute(&$component, $input) {
		require_once("component/people/PeopleJSON.inc");
		$q = SQLQuery::create()->select("People")->whereIn("People", "id", $input["ids"]);
		PeopleJSON::PeopleSQL($q);
		if (count($input["ids"]) > 0)
			$rows = $q->execute();
		else
			$rows = array();
		echo PeopleJSON::Peoples($q, $rows);
	}
	
}
?>