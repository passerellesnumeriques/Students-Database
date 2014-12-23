<?php 
class service_applicant_remove extends Service {
	
	public function getRequiredRights() { return array("edit_applicants"); }
	
	public function documentation() { echo "Remove applicants from the database"; }
	public function inputDocumentation() { echo "applicants"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$applicants_ids = $input["applicants"];
		
		$peoples = PNApplication::$instance->people->getPeoples($applicants_ids);
		$to_remove = array();
		$to_remove_type = array();
		foreach ($peoples as $p) {
			$types = PNApplication::$instance->people->parseTypes($p["types"]);
			$nb = count($types);
			if (!in_array("applicant", $types)) {
				PNApplication::error("One of the people is not an applicant!");
				return;
			}
			$nb--;
			if (in_array("family_member", $types))
				$nb--;
			if ($nb == 0)
				array_push($to_remove, $p["id"]);
			else
				array_push($to_remove_type, $p["id"]);
		}
		
		if (count($to_remove) > 0) {
			foreach ($to_remove as $people_id)
				PNApplication::$instance->people->removePeople($people_id);
		}
		if (count($to_remove_type) > 0) {
			PNApplication::$instance->people->removePeoplesType($to_remove_type, "applicant");
		}
		if (!PNApplication::hasErrors()) echo "true";
	}
	
}
?>