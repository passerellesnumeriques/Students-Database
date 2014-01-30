<?php 
require_once("component/people/PeopleJSON.inc");
require_once("component/contact/ContactJSON.inc");
function get_json_contact_points_no_address($organization){
	$json_result = "";
	$contacts = null;
	$q = SQLQuery::create()
				->select("Organization")
				->join("Organization","Contact_point",array("id"=>"organization"))
				->join("Contact_point","People",array("people" => "id"))
				->join("People","People_contact",array("id" => "people"))
				->join("People_contact","Contact",array("contact" => "id"))
				->field("Organization","id","organization_id")
				->field("Contact_point","designation","designation")
				->order_by("Organization","id")
				->order_by("People","id");
	PeopleJSON::PeopleSQL($q);
	ContactJSON::ContactSQL($q);
				
	if(is_array($organization)){
		if(count($organization) != 0){
			$q->where_in("Organization","id",$organization);
			$contacts = $q->execute();
		}
	}
	else{
		$q->where_value("Organization","id",$organization);
		$contacts = $q->execute();
	}
	
	
	if($contacts <> null){
		$first = true;
		$first_people = true;
		$current_partner = null;
		$current_people = null;
		$json_result.=  "[";
		$length = count($contacts);
		$no_contact_before = false;
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
				$json_result.=  "contact_points:[";
				if(isset($c["people_id"])){
					$json_result.= "{";
					if($c["people_id"] != $current_people){
						if(!$first_people){
							$json_result.= ']}, ';
							$no_contact_before = false;
						}
						$first_people = false;
						
						$json_result.= "people_id:".json_encode($c["people_id"]).", ";
						$json_result.=  "people_last_name:".json_encode($c["last_name"]).", ";
						$json_result.=  "people_first_name:".json_encode($c["first_name"]).", ";
						$json_result.=  "people_designation:".json_encode($c["designation"]).", ";
						$json_result.=  "contacts:[";
						if(isset($c["contact_id"])){
							$json_result.= ContactJSON::Contact(null, $c);
						} else $no_contact_before = true;
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
							$no_contact_before = false;
						}
						$first_people = false;
						$json_result.= "{";
						$json_result.= "people_id:".json_encode($c["people_id"]).", ";
						$json_result.=  "people_last_name:".json_encode($c["last_name"]).", ";
						$json_result.=  "people_first_name:".json_encode($c["first_name"]).", ";
						$json_result.=  "people_designation:".json_encode($c["designation"]).", ";
						$json_result.=  "contacts:[";
						if(isset($c["contact_id"])){
							$json_result.= ContactJSON::Contact(null, $c);
							$current_people = $c["people_id"];
						} else $no_contact_before = true;
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