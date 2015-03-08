<?php 
class page_organization_profile extends Page {

	public function getRequiredRights() { return array(); }
	
	public function execute(){
		$id = isset($_GET["organization"]) ? intval($_GET["organization"]) : -1;
		if ($id > 0) {
			$org = SQLQuery::create()->select("Organization")->whereValue("Organization","id",$id)->executeSingleRow();
			$creator = $org["creator"];
			$org_json = ContactJSON::OrganizationFromID($id);
			$all_types = SQLQuery::create()->select("OrganizationType")->whereValue("OrganizationType", "creator", $creator)->execute();
			// TODO can_edit and lock
			$can_edit = true;
		} else {
			$creator = $_GET["creator"];
			$all_types = SQLQuery::create()->select("OrganizationType")->whereValue("OrganizationType", "creator", $creator)->execute();
			$selected_types = array();
			if (isset($_GET["types_names"])) {
				$selected_types_names = explode(";", $_GET["types_names"]);
				foreach ($selected_types_names as $name)
					foreach ($all_types as $type)
						if ($type["name"] == $name) {
							array_push($selected_types, $type["id"]);
							break;
						}
			}
			$name = isset($_GET["name"]) ? $_GET["name"] : "";
			// TODO $_GET["address_area_id"]
			$org_json = "{id:-1,name:".json_encode($name).",creator:".json_encode($creator).",types_ids:".json_encode($selected_types).",general_contacts:[],general_contact_points:[],locations:[]}";
			$can_edit = true;
		}
		// TODO $_GET["onready"]
?>
<div id='org_container'>
</div>
<?php 
		$this->requireJavascript("organization.js");
		$this->onload("new organization('org_container',$org_json,".json_encode($all_types).",".json_encode($can_edit).");");
	}
	
}