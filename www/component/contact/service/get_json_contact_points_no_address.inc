<?php 
require_once("component/people/PeopleJSON.inc");
require_once("component/contact/ContactJSON.inc");
/**
 * Get a JSON object containing all the contact points for a given organization
 * @param number $organization the organization ID
 * @param boolean | null $contacts_details true if the JSON structure shall contain the contact data (type, subtype, contact...) for each contact point
 * @return string a JSON structure: {organization:organization ID, organization_name:, contact_points:[]}<br/>
 * contact_points array contains objects for each contact points. Each object is made of: {people_id, people_first_name, people_last_name, people_designation, contacts:}<br/>
 * If !$contact_details, the contact array is empty. Else, contains object given by ContactJSON#contact method
 */
function get_json_contact_points_no_address($organization, $contacts_details = true){
	$json_result = "";
	$contacts = null;
	$q = SQLQuery::create()
				->select("Organization")
				->join("Organization","ContactPoint",array("id"=>"organization"))
				->join("ContactPoint","People",array("people" => "id"))
				->join("People","PeopleContact",array("id" => "people"))
				->join("PeopleContact","Contact",array("contact" => "id"))
				->field("Organization","id","organization_id")
				->field("Organization","name","organization_name")
				->field("ContactPoint","designation","designation")
				->orderBy("Organization","id")
				->orderBy("People","id");
	PeopleJSON::PeopleSQL($q);
	ContactJSON::ContactSQL($q);
				
	if(is_array($organization)){
		if(count($organization) != 0){
			$q->whereIn("Organization","id",$organization);
			$contacts = $q->execute();
		}
	}
	else{
		$q->whereValue("Organization","id",$organization);
		$contacts = $q->execute();
	}
	
	
	if($contacts <> null){
		$first = true;
		$first_people = true;
		$current_partner = null;
		$current_people = null;
		$json_result.=  "[";
		$length = count($contacts);
// 		$no_contact_before = false;
		$no_people_before = false;
		$i = 0;
		foreach($contacts as $c){
			if($c["organization_id"] != $current_partner){
				if(!$first){
					if($no_people_before) $json_result.= "]}, ";
					else $json_result.=  "]}]}, ";
					$no_people_before = false;
				}
				$current_people = null;
				$first_people = true;
				$first = false;
				$json_result.=  "{";
				$json_result.=  "organization:".json_encode($c["organization_id"]).", ";
				$json_result.=  "organization_name:".json_encode($c["organization_name"]).", ";
				$json_result.=  "contact_points:[";
				if(isset($c["people_id"])){
					$json_result.= "{";
					if($c["people_id"] != $current_people){
						if(!$first_people){
							$json_result.= ']}, ';
// 							$no_contact_before = false;
						}
						$first_people = false;
						
						$json_result.= "people_id:".json_encode($c["people_id"]).", ";
						$json_result.=  "people_last_name:".json_encode($c["last_name"]).", ";
						$json_result.=  "people_first_name:".json_encode($c["first_name"]).", ";
						$json_result.=  "people_designation:".json_encode($c["designation"]).", ";
						$json_result.=  "contacts:[";
						if(isset($c["contact_id"]) && $contacts_details){
							$json_result.= ContactJSON::Contact(null, $c);
						} //else $no_contact_before = true;
						$current_people = $c["people_id"];
					} else {
						$json_result.= ContactJSON::Contact(null, $c);
					}
				} else $no_people_before = true;
				$current_partner = $c["organization_id"];
			} else {
				if(isset($c["people_id"])){
					if($c["people_id"] != $current_people){
						if(!$first_people){
							$json_result.= "]}, ";
// 							$no_contact_before = false;
						}
						$first_people = false;
						$json_result.= "{";
						$json_result.= "people_id:".json_encode($c["people_id"]).", ";
						$json_result.=  "people_last_name:".json_encode($c["last_name"]).", ";
						$json_result.=  "people_first_name:".json_encode($c["first_name"]).", ";
						$json_result.=  "people_designation:".json_encode($c["designation"]).", ";
						$json_result.=  "contacts:[";
						if(isset($c["contact_id"]) && $contacts_details){
							$json_result.= ContactJSON::Contact(null, $c);
							$current_people = $c["people_id"];
						} //else $no_contact_before = true;
					} else {
						$json_result.= ", ";
						ContactJSON::Contact(null, $c);
					}
				}
			}
			if($i == $length -1){
				if($no_people_before) $json_result.= "]}]";
				else $json_result.=  "]}]}]";
			}
			$i++;
		}
		
		
	} else $json_result.=  "[]";
	return $json_result;
}
?>