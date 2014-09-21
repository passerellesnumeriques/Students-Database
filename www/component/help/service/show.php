<?php 
class service_show extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Show again all help messages"; }
	public function inputDocumentation() { echo "none"; }
	public function outputDocumentation() { echo "none"; }
	
	public function execute(&$component, $input) {
		$rows = SQLQuery::create()->bypassSecurity()->select("HelpSystemHidden")->whereValue("HelpSystemHidden", "user", PNApplication::$instance->user_management->user_id)->execute();
		SQLQuery::create()->bypassSecurity()->removeRows("HelpSystemHidden", $rows);
	}
	
}
?>