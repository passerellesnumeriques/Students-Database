<?php
	$join_contact_column = null;
	$join_value = $id;
	if($type == "people"){
		$table_contact = "People_contact";
		$join_contact_column = "people";
	if($type == "organization"){
		$table_contact = "Organization_contact";
		$join_contact_column = "organization";
	}
		->field("Contact","id","contact_id")
		->field("Contact","type","contact_type")
		->field("Contact","contact","contact")
		->field("Contact","sub_type","contact_sub_type")
		->join($table_contact,"Contact",array("contact"=>"id"))
		->where($table_contact.".".$join_contact_column." = '".SQLQuery::escape($join_value)."'")
		->order_by("contact_type");
	$contacts = $q_contacts->bypass_security()->execute();
