<?php 
require_once '/component/contact/ContactJSON.inc';
class page_organization_profile extends Page {
	public function getRequiredRights() { return array(); }
	public function execute(){
		$id = isset($_GET["organization"]) ? intval($_GET["organization"]) : -1;
		
		require_once("contact.inc");
		require_once("address.inc");
		
		if ($id > 0) {
			$org = SQLQuery::create()->select("Organization")->whereValue("Organization","id",$id)->executeSingleRow();
			$creator = $org["creator"];
		} else {
			$creator = $_GET["creator"];
		}
		$all_types = SQLQuery::create()->select("OrganizationType")->whereValue("OrganizationType", "creator", $creator)->execute();
		
		$selected_types = array();
		if ($id <= 0 && isset($_GET["types_names"])) {
			$selected_types_names = explode(";", $_GET["types_names"]);
			foreach ($selected_types_names as $name)
				foreach ($all_types as $type)
					if ($type["name"] == $name) {
						array_push($selected_types, $type["id"]);
						break;
					}
		}
		$name = "";
		if ($id <= 0 && isset($_GET["name"]))
			$name = $_GET["name"];
		
		$org_structure = "{";
		if ($id > 0) {
			$org_structure .= "id:".$org["id"];
			$org_structure .= ",name:".json_encode($org["name"]);
			$org_types = SQLQuery::create()->select("OrganizationTypes")->whereValue("OrganizationTypes", "organization", $id)->execute();
			$org_structure .= ",types_ids:[";
			$first = true;
			foreach ($org_types as $t) {
				if ($first) $first = false; else $org_structure .= ",";
				$org_structure .= $t["type"];
			}
			$org_structure .= "]";
			$org_structure .= ",contacts:".contacts_structure("organization", $id);
			$org_structure .= ",addresses:".addresses_structure("organization", $id);
			$q = SQLQuery::create()
				->select("ContactPoint")
				->whereValue("ContactPoint", "organization", $id)
				->field("ContactPoint", "designation")
				;
			PNApplication::$instance->people->joinPeople($q, "ContactPoint", "people");
			$points = $q 
				->field("People", "id", "people_id")
				->field("People", "first_name")
				->field("People", "last_name")
				->execute();
			
			$org_structure .= ",contact_points:[";
			$first = true;
			foreach ($points as $p) {
				if ($first) $first = false; else $org_structure .= ",";
				$org_structure .= "{designation:".json_encode($p["designation"]).",people_id:".$p["people_id"].",first_name:".json_encode($p["first_name"]).",last_name:".json_encode($p["last_name"])."}";
			}
			$org_structure .= "]";
		} else {
			$org_structure .= "id:-1";
			$org_structure .= ",name:".json_encode($name);
			$org_structure .= ",types_ids:".json_encode($selected_types);
			$org_structure .= ",contacts:[]";
			if(isset($_GET["address_country_id"]) && isset($_GET["address_area_id"])){
				$area = PNApplication::$instance->geography->getArea($_GET["address_area_id"]);
				$new_address = array(
					"postal_address__id" => -1, 
					"postal_address__country_id" => $_GET["address_country_id"],
					"geographic_area_text_area_id" =>$_GET["address_area_id"],
					"geographic_area_text_country_id" => $_GET["address_country_id"],
					"geographic_area_text_country_division_id" => $area["country_division"]
				);
				$org_structure .= ",addresses:[".ContactJSON::PostalAddress($new_address)."]";
			} else 
				$org_structure .= ",addresses:[]";
			$org_structure .= ",contact_points:[]";
		}
		$org_structure .= ",creator:".json_encode($creator);
		$org_structure .= "}";
		$existing_types = "[";
		$first = true;
		foreach ($all_types as $t) {
			if ($first) $first = false; else $existing_types .= ",";
			$existing_types .= "{id:".$t["id"].",name:".json_encode($t["name"])."}";
		}
		$existing_types .= "]";
		$this->addJavascript("/static/contact/organization.js");
		$container_id = $this->generateID();
		$this->onload("window.organization = new organization('$container_id',$org_structure,$existing_types,true);");
		if (isset($_GET["onready"])) $this->onload("window.frameElement.".$_GET["onready"]."(window.organization);");
		echo "<center><div id='$container_id' style='margin:5px;display:inline-block;border:1px solid #808080'></div></center>";
		?>
<!-- 		<table>
			<th style = "height:100px">
				<span  id = 'organization_title'></span>
			</th>
			<tr>
				<td style ='vertical-align:top;'>
					<span id='type'></span>
				</td>
				<td style ='vertical-align:top;'>
					<span  id='address'></span>
					<span  id='contact'></span>
				</td>
			</tr>
		</table> -->
		<?php
// 		$q = SQLQuery::create()->select("Organization")
// 				->field("id")
// 				->where("id = ".$id."");
// 		$exist = $q->execute();
// 		if(isset($exist[0]["id"])){
// 			require_once("contact.inc");
// 			contact($this,"organization","contact",$id);
// 			require_once("address.inc");
// 			address($this,"organization","address",$id);
// 		}
// 		require_once("organization_profile.inc");
// 		organization_profile($this,$id,"type","organization_title");
	}
	
}