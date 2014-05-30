<?php
class service_get_json_organizations_by_geographic_area extends Service{
	public function getRequiredRights(){return array();}
	public function inputDocumentation(){
		?>
		<ul>
			<li><code>geographic_area</code> the geographic area id</li>
			<li><code>creator</code> creator of organization
		</ul>
		<?php 
	}
	public function outputDocumentation(){
		?>
		Return a JSON object containing two attributes:<ul>
			<li><code>from_area</code> array of Organization objects, containing the organizations retrieved from the given geographic area</li>
			<li><code>from_parent_area</code> array of Organization objects, containing the organizations retrieved from the parent area (the area retrieved from the given area have been excluded from this selection) of the given one (if it exists)</li>
		</ul>
		The Organization objects are populated with addresses, but not with contacts and contact points.
		<?php
	}
	public function documentation(){
		echo "Get all the organizations existings in a given geographic area, and the matching addresses";
	}
	public function execute(&$component,$input){
		require_once("component/contact/ContactJSON.inc");
		
		$area_id = $input["geographic_area"];
		$creator = $input["creator"];
		
		//Get all the children
		$children = PNApplication::$instance->geography->getAreaAllChildrenFlat($area_id);
		array_push($children, $area_id);
		
		$q = SQLQuery::create()
			->select("Organization")
			->whereValue("Organization", "creator", $creator)
			->join("Organization", "OrganizationAddress", array("id"=>"organization"))
			->join("OrganizationAddress", "PostalAddress", array("address"=>"id"))
			->whereIn("PostalAddress", "geographic_area", $children)
			;
		ContactJSON::OrganizationSQL($q);
		ContactJSON::PostalAddressSQL($q);
		
		$rows = $q->execute();
		
		// split rows into list of organizations, with addresses attached
		$organizations = array();
		foreach ($rows as $row) {
			if (!isset($organizations[$row["organization_id"]])) {
				$organizations[$row["organization_id"]] = $row;
				$organizations[$row["organization_id"]]["addresses"] = array();
			}
			array_push($organizations[$row["organization_id"]]["addresses"], $row);
		}

		//Get the parent id
		$parent_id = PNApplication::$instance->geography->getAreaParent($area_id);
		$parent_organizations = array();
		if ($parent_id <> null) { // not the root level
			$children_from_parent = PNApplication::$instance->geography->getAreaAllChildrenFlat($parent_id);
			array_push($children_from_parent, $parent_id);
			//Remove all the ids already retrieved
			for($i = 0; $i < count($children); $i++){
				$index = array_search($children[$i], $children_from_parent);
				if($index <> null)
					array_splice($children_from_parent, $index, 1);
			}
			if (count($children_from_parent) > 0) {
				$q = SQLQuery::create()
					->select("Organization")
					->whereValue("Organization", "creator", $creator)
					->join("Organization", "OrganizationAddress", array("id"=>"organization"))
					->join("OrganizationAddress", "PostalAddress", array("address"=>"id"))
					->whereIn("PostalAddress", "geographic_area", $children_from_parent)
					;
				ContactJSON::OrganizationSQL($q);
				ContactJSON::PostalAddressSQL($q);
				
				$rows = $q->execute();
				// split rows into list of organizations, with addresses attached
				foreach ($rows as $row) {
					if (!isset($parent_organizations[$row["organization_id"]])) {
						$parent_organizations[$row["organization_id"]] = $row;
						$parent_organizations[$row["organization_id"]]["addresses"] = array();
					}
					array_push($parent_organizations[$row["organization_id"]]["addresses"], $row);
				}
			}
		}
		
		echo "{from_area:[";
		$first = true;
		foreach ($organizations as $org) {
			if ($first) $first = false; else echo ",";
			echo ContactJSON::OrganizationJSON($org, null, null, $org["addresses"], null);
		}
		echo "],from_parent_area:[";
		$first = true;
		foreach ($parent_organizations as $org) {
			if ($first) $first = false; else echo ",";
			echo ContactJSON::OrganizationJSON($org, null, null, $org["addresses"], null);
		}
		echo "]}";
	}
	
}
?>