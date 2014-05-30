<?php 
class service_IS_list extends Service {
	
	public function getRequiredRights() { return array("can_access_selection_data"); }
	
	public function documentation() { echo "return the list of Information Sessions"; }
	public function inputDocumentation() { echo "none"; }
	public function outputDocumentation() { echo "A list of InformationSession objects"; }
	
	public function execute(&$component, $input) {
		require_once("component/selection/SelectionInformationSessionJSON.inc");
		$q = SQLQuery::create()->select("InformationSession");
		SelectionInformationSessionJSON::InformationSessionSQL($q);
		echo SelectionInformationSessionJSON::InformationSessionsJSON($q->execute());
	}
	
}
?>