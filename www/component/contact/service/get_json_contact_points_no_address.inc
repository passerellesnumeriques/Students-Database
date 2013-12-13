<?php
/**
 * @param organization
 * organization can be an array(id1, id2, id3) or only on id
 * $json_result.=  a string with the following structure:
 * [{organization: ,contact_points:[{people_id: ,people_last_name: ,people_first_name: ,people_designation: ,contacts:[{id: ,type: ,sub_type: ,contact:},{...}]}]]
 * each contact in array contacts of a people can be used in contact_type.js as the "contacts" parameter to be displayed
 */
 
 //TODO: update doc!!
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
				->field("People","first_name","first_name")
				->field("People","id","id")
				->field("People","last_name","last_name")
				->field("Contact","id","contact_id")
				->field("Contact","type","type")
				->field("Contact","sub_type","sub_type")
				->field("Contact","contact","contact")
				->order_by("Organization","id")
				->order_by("People","id");
				
	if(is_array($organization)){
		if(count($organization) != 0){
			$q->where_in("Organization","id",$organization);
			$contacts = $q->execute();
		}
	}
	else{
		$q->where("Organization","id",$organization);
		$contacts = $q->execute();
	}
	
	
	if(isset($contacts[0]["organization_id"]) && $contacts[0]["organization_id"] <> null){
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
					else if($no_contact_before){
						$json_result.= "]}]}, ";
						$no_contact_before = false;
					}
					else $json_result.=  "}]}]}, ";
					$no_people_before = false;
				}
				$current_people = null;
				$first_people = true;
				$first = false;
				$json_result.=  "{";
				$json_result.=  "organization:".json_encode($c["organization_id"]).", ";
				$json_result.=  "contact_points:[";
				if(isset($c["id"]) && $c["id"] <> null){
					$json_result.= "{";
					if($c["id"] != $current_people){
						if(!$first_people){
							if($no_contact_before) $json_result.= "]}, ";
							else $json_result.= "}]}, ";
							$no_contact_before = false;
						}
						$first_people = false;
						
						$json_result.= "people_id:".json_encode($c["id"]).", ";
						$json_result.=  "people_last_name:".json_encode($c["last_name"]).", ";
						$json_result.=  "people_first_name:".json_encode($c["first_name"]).", ";
						$json_result.=  "people_designation:".json_encode($c["designation"]).", ";
						$json_result.=  "contacts:[";
						if(isset($c["contact_id"]) && $c["contact_id"] <> null){
							$json_result.=  "{id:".json_encode($c["contact_id"]).", ";
							$json_result.=  "type:".json_encode($c["type"]).", ";
							$json_result.=  "sub_type:".json_encode($c["sub_type"]).", ";
							$json_result.=  "contact:".json_encode($c["contact"]);
						} else $no_contact_before = true;
						$current_people = $c["id"];
					} else {
						$json_result.=  "id:".json_encode($c["contact_id"]).", ";
						$json_result.=  "type:".json_encode($c["type"]).", ";
						$json_result.=  "sub_type:".json_encode($c["sub_type"]).", ";
						$json_result.=  "contact:".json_encode($c["contact"]);
					}
				} else $no_people_before = true;
				$current_partner = $c["organization_id"];
			} else {
				if(isset($c["id"]) && $c["id"] <> null){
					if($c["id"] != $current_people){
						if(!$first_people){
							if($no_contact_before) $json_result.= "]}, ";
							else $json_result.= "}]}, ";
							$no_contact_before = false;
						}
						$first_people = false;
						$json_result.= "{";
						$json_result.= "people_id:".json_encode($c["id"]).", ";
						$json_result.=  "people_last_name:".json_encode($c["last_name"]).", ";
						$json_result.=  "people_first_name:".json_encode($c["first_name"]).", ";
						$json_result.=  "people_designation:".json_encode($c["designation"]).", ";
						$json_result.=  "contacts:[";
						if(isset($c["contact_id"]) && $c["contact_id"] <> null){
							$json_result.=  "{id:".json_encode($c["contact_id"]).", ";
							$json_result.=  "type:".json_encode($c["type"]).", ";
							$json_result.=  "sub_type:".json_encode($c["sub_type"]).", ";
							$json_result.=  "contact:".json_encode($c["contact"]);
							$current_people = $c["id"];
						} else $no_contact_before = true;
					} else {
						$json_result.= "}, {";
						$json_result.=  "id:".json_encode($c["contact_id"]).", ";
						$json_result.=  "type:".json_encode($c["type"]).", ";
						$json_result.=  "sub_type:".json_encode($c["sub_type"]).", ";
						$json_result.=  "contact:".json_encode($c["contact"]);
					}
				}
			}
			if($i == $length -1){
				if($no_people_before) $json_result.= "]}]";
				else if($no_contact_before) $json_result.= "]}]}]";
				else $json_result.=  "}]}]}]";
			}
			$i++;
		}
		
		
	} else $json_result.=  "[]";
	return $json_result;
}
	
	
	
	// if(isset($contacts[0]["organization_id"]) && $contacts[0]["organization_id"] <> null){
		// $first = true;
		// $first_people = true;
		// $current_partner = null;
		// $current_people = null;
		// $json_result.=  "[";
		// $length = count($contacts);
		// $i = 0;
		// foreach($contacts as $c){
			// if($c["organization_id"] != $current_partner){
				// if(!$first){
					// $json_result.=  "}]}]}, ";
				// }
				// $current_people = null;
				// $first_people = true;
				// $first = false;
				// $json_result.=  "{";
				// $json_result.=  "organization:".json_encode($c["organization_id"]).", ";
				// $json_result.=  "contact_points:[";
				// if(isset($c["contact_id"]) && $c["contact_id"] <> null){
					// $json_result.= "{";
					// if($c["id"] != $current_people){
						// if(!$first_people){
							// $json_result.= "}]}, ";
						// }
						// $first_people = false;
						
						// $json_result.= "people_id:".json_encode($c["id"]).", ";
						// $json_result.=  "people_last_name:".json_encode($c["last_name"]).", ";
						// $json_result.=  "people_first_name:".json_encode($c["first_name"]).", ";
						// $json_result.=  "people_designation:".json_encode($c["designation"]).", ";
						// $json_result.=  "contacts:[{";
						// $json_result.=  "id:".json_encode($c["contact_id"]).", ";
						// $json_result.=  "type:".json_encode($c["type"]).", ";
						// $json_result.=  "sub_type:".json_encode($c["sub_type"]).", ";
						// $json_result.=  "contact:".json_encode($c["contact"]);
						// $current_people = $c["id"];
					// } else {
						// $json_result.=  "id:".json_encode($c["contact_id"]).", ";
						// $json_result.=  "type:".json_encode($c["type"]).", ";
						// $json_result.=  "sub_type:".json_encode($c["sub_type"]).", ";
						// $json_result.=  "contact:".json_encode($c["contact"]);
					// }
				// }
				// $current_partner = $c["organization_id"];
			// } else {
				// if(isset($c["contact_id"]) && $c["contact_id"] <> null){
					// if($c["id"] == $current_people) $json_result.= "}, {";
					// if($c["id"] != $current_people){
						// if(!$first_people){
							// $json_result.= "}]}, ";
						// }
						// $first_people = false;
						// $json_result.= "{";
						// $json_result.= "people_id:".json_encode($c["id"]).", ";
						// $json_result.=  "people_last_name:".json_encode($c["last_name"]).", ";
						// $json_result.=  "people_first_name:".json_encode($c["first_name"]).", ";
						// $json_result.=  "people_designation:".json_encode($c["designation"]).", ";
						// $json_result.=  "contacts:[{";
						// $json_result.=  "id:".json_encode($c["contact_id"]).", ";
						// $json_result.=  "type:".json_encode($c["type"]).", ";
						// $json_result.=  "sub_type:".json_encode($c["sub_type"]).", ";
						// $json_result.=  "contact:".json_encode($c["contact"]);
						// $current_people = $c["id"];
					// } else {
						// $json_result.=  "id:".json_encode($c["contact_id"]).", ";
						// $json_result.=  "type:".json_encode($c["type"]).", ";
						// $json_result.=  "sub_type:".json_encode($c["sub_type"]).", ";
						// $json_result.=  "contact:".json_encode($c["contact"]);
					// }
				// }
			// }
			// if($i == $length -1){
				// if(!isset($c["contact_id"]) || $c["contact_id"] === null) $json_result.= "]}]";
				// else $json_result.=  "}]}]}]";
			// }
			// $i++;
		// }
		
		
	// } else $json_result.=  "[]";
	// return $json_result;

?>