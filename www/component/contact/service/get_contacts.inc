<?php
function get_contacts($table_contact, $join_contact_column, $table_address, $join_address_column, $join_value) {
	$q_contacts = SQLQuery::create()->select($table_contact)
					->field("Contact","id","contact_id")
					->field("Contact","type","contact_type")
					->field("Contact","contact","contact")
					->field("Contact","sub_type","contact_sub_type")
					->join($table_contact,"Contact",array("contact"=>"id"))
					->where($table_contact.".".$join_contact_column." = '".SQLQuery::escape($join_value)."'")
					->order_by("contact_type");
	$contacts = $q_contacts->execute();
					
	$q_addresses = SQLQuery::create()->select($table_address)
					->field($table_address,"address","address_id")
					->field("Postal_address","country","country")
					->field("Postal_address","geographic_area","geographic_area")
					->field("Postal_address","street","street")
					->field("Postal_address","street_number","street_number")
					->field("Postal_address","building","building")
					->field("Postal_address","unit","unit")
					->field("Postal_address","additional","additional")
					->field("Postal_address","address_type","address_type")
					->join($table_address,"Postal_address",array("address"=>"id"))
					->where($table_address.".".$join_address_column." = '".SQLQuery::escape($join_value)."'");
	$addresses = $q_addresses->execute();
					
	if (!isset($contacts[0]["contact_id"]) && !isset($addresses[0]['address_id'])) echo "{id:".json_encode($join_value).", contacts:[], addresses:[]}";
	else{
		echo "{id:'".$join_value."', contacts:";
		if(!isset($contacts[0]["contact_id"])){echo "[]";}
		else{
			echo "[";
			$first = true;
			foreach($contacts as $contact){
				if(!$first) echo ", ";
				$first = false;
				echo "{";
				echo "contact_id:".json_encode($contact["contact_id"]);
				echo ", contact_type:".json_encode($contact["contact_type"]);
				echo ", contact:".json_encode($contact["contact"]);
				echo ", contact_sub_type:".json_encode($contact["contact_sub_type"]);
				echo "}";
			}
			echo "]";
		}
		echo ", addresses:";
		if(!isset($addresses[0]['address_id'])){ echo "[]";}
		else{
			echo "[";
			$first = true;
			foreach($addresses as $address){
				if(!$first) echo ", ";
				$first = false;
				echo "{";
				echo "address_id:".json_encode($address["address_id"]);
				echo ", country:".json_encode($address["country"]);
				echo ", geographic_area:".json_encode($address["geographic_area"]);
				echo ", street:".json_encode($address["street"]);
				echo ", street_number:".json_encode($address["street_number"]);
				echo ", building:".json_encode($address["building"]);
				echo ", unit:".json_encode($address["unit"]);
				echo ", additional:".json_encode($address["additional"]);
				echo ", address_type:".json_encode($address["address_type"]);
				echo "}";
			}
			echo "]";
		}
		echo "}";
	}
}
?>