<?php 
class service_show extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Show again help messages"; }
	public function inputDocumentation() { echo "ids: the help ids to re-ctivate"; }
	public function outputDocumentation() { echo "none"; }
	
	public function execute(&$component, $input) {
		if (count($input["ids"]) == 0) return;
		$rows = SQLQuery::create()
			->bypassSecurity()
			->select("HelpSystemHidden")
			->whereValue("HelpSystemHidden", "user", PNApplication::$instance->user_management->user_id)
			->whereIn("HelpSystemHidden", "help_id", $input["ids"])
			->execute();
		if (count($rows) == 0) return;
		SQLQuery::create()->bypassSecurity()->removeRows("HelpSystemHidden", $rows);
	}
	
}
?>