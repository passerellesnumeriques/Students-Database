<?php 
class service_is_get_host extends Service {
	
	public function getRequiredRights() { return array("can_access_selection_data"); }
	
	public function documentation() { echo "Retrieve the Hosting partner with address from an Information Session"; }
	public function inputDocumentation() { echo "<code>id</code>: the Information Session id"; }
	public function outputDocumentation() { echo "A SelectionPartner JSON object, or null"; }
	
	public function execute(&$component, $input) {
		$id = $input["id"];
		require_once("component/selection/common_centers/SelectionPartnersJSON.inc");
		echo SelectionPartnersJSON::HostingPartnerFromCenterID("InformationSession", $id);
	}
}
?>