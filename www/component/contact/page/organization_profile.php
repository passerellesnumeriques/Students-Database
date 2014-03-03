<?php 
require_once '/component/contact/ContactJSON.inc';
class page_organization_profile extends Page {
	public function get_required_rights() { return array(); }
	public function execute(){
		$id = $_GET["organization"];
		
		require_once("contact.inc");
		require_once("address.inc");
		
		if ($id <> -1) {
			$org = SQLQuery::create()->select("Organization")->whereValue("Organization","id",$id)->executeSingleRow();
			$creator = $org["creator"];
		} else {
			$creator = $_GET["creator"];
		}
		$all_types = SQLQuery::create()->select("OrganizationType")->whereValue("OrganizationType", "creator", $creator)->execute();
		
		$org_structure = "{";
		if ($id <> -1) {
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
			$points = SQLQuery::create()
				->select("ContactPoint")
				->whereValue("ContactPoint", "organization", $id)
				->field("ContactPoint", "designation")
				->join("ContactPoint", "People", array("people"=>"id"))
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
			$org_structure .= ",name:''";
			$org_structure .= ",types_ids:[]";
			$org_structure .= ",contacts:[]";
			if(isset($_GET["address_country_id"]) && isset($_GET["address_area_id"])){
				$new_address = array("address_id" => -1, "country_id" => $_GET["address_country_id"],"geographic_area_id" =>$_GET["address_area_id"]);
				$org_structure .= ",addresses:[".ContactJSON::PostalAddress(null, $new_address)."]";
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
		$this->add_javascript("/static/contact/organization.js");
		$container_id = $this->generateID();
		$this->onload("window.organization = new organization('$container_id',$org_structure,$existing_types,true);");
		echo "<div id='$container_id' style='margin:5px'></div>";
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