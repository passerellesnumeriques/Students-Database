<?php 
class service_get_peoples extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Retrieve People JSON structures"; }
	public function input_documentation() { echo "<code>ids</code>: people ids"; }
	public function output_documentation() { echo "A list of People JSON objects"; }
	
	public function execute(&$component, $input) {
		require_once("component/people/PeopleJSON.inc");
		$q = SQLQuery::create()->select("People")->whereIn("People", "id", $input["ids"]);
		PeopleJSON::PeopleSQL($q);
		$rows = $q->execute();
		echo PeopleJSON::Peoples($q, $rows);
	}
	
}
?>