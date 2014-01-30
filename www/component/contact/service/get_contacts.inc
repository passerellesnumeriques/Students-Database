<?php
require_once("component/contact/ContactJSON.inc");
function get_contacts($table_contact, $join_contact_column, $table_address, $join_address_column, $join_value, $no_address = false, $no_contacts = false) {
	if(!$no_contacts){	
		$q_contacts = SQLQuery::create()->select($table_contact)
						->join($table_contact,"Contact",array("contact"=>"id"))
						->where($table_contact.".".$join_contact_column." = '".SQLQuery::escape($join_value)."'")
						->orderBy("contact_type");
		ContactJSON::ContactSQL($q_contacts);
		$contacts = $q_contacts->execute();
		
	}
	if(!$no_address){				
		$q_addresses = SQLQuery::create()->select($table_address)
						->join($table_address,"Postal_address",array("address"=>"id"))
						->where($table_address.".".$join_address_column." = '".SQLQuery::escape($join_value)."'");
		ContactJSON::PostalAddressSQL($q_addresses);							
		$addresses = $q_addresses->execute();
	}				
	
	if (!isset($contacts[0]["contact_id"]) && !isset($addresses[0]['address_id'])) echo "{id:".json_encode($join_value).", contacts:[], addresses:[]}";
	else{
		echo "{id:'".$join_value."'";
		if(!$no_contacts){
			echo ", contacts:";
			if(!isset($contacts[0]["contact_id"])){echo "[]";}
			else{
				echo "[";
				$first = true;
				foreach($contacts as $contact){
					if(!$first) echo ", ";
					$first = false;
					echo ContactJSON::Contact(null, $contact);
				}
				echo "]";
			}
		}
		if(!$no_address){
			echo ", addresses:";
			if(!isset($addresses[0]['address_id'])) echo "[]";	
			else{
				echo "[";
				$first = true;
				foreach($addresses as $address){
					if(!$first) echo ", ";
					$first = false;
					echo ContactJSON::PostalAddress(null, $address);
				}
				echo "]";
			}
		}
		echo "}";
	}
}
?>