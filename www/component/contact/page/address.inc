<?phpfunction addresses_structure($type, $id) {	$table_address = null;
	$join_address_column = null;
	$join_value = $id;
	if($type == "people"){
		$table_address = "People_address";
		$join_address_column = "people";
	}	if($type == "organization"){		$table_address = "Organization_address";		$join_address_column = "organization";	}	$q_addresses = SQLQuery::create()->select($table_address)
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
	$addresses = $q_addresses->bypass_security()->execute();
		$s = "[";	$first = true;	foreach($addresses as $address){		if($first) $first = false; else $s .= ", ";		$s .= "{";		$s .= "id:".json_encode($address["address_id"]);		$s .= ", country:".json_encode($address["country"]);		if ($address["geographic_area"] == null)			$s .= ", geographic_area: null";		else			$s .= ", geographic_area: { id:".$address["geographic_area"].", text:".json_encode(PNApplication::$instance->geography->get_geographic_area_text($address["geographic_area"]))." }";		$s .= ", street_name:".json_encode($address["street"]);		$s .= ", street_number:".json_encode($address["street_number"]);		$s .= ", building:".json_encode($address["building"]);		$s .= ", unit:".json_encode($address["unit"]);		$s .= ", additional:".json_encode($address["additional"]);		$s .= ", address_type:".json_encode($address["address_type"]);		$s .= "}";	}	$s .= "]";	return $s;}/** * The rights of the user is taken into account to set this page * This page needs no service to be generated * @param $type {string} can be 'people' or 'organization' * @param $container_id {string} the id of the container which will contain the page * @param $id the people_id or organization_id, or -1 for a new one * @return string the name of the javascript constructor to call */ function address(&$page,$type,$container_id,$id){	require_once("component/data_model/Model.inc");	$rights = array();		$table_address = null;	$join_address_column = null;	$join_value = $id;	if($type == "people"){		$table_address = "People_address";		$join_address_column = "people";		if ($id <> -1) {
			$q = SQLQuery::create()->select("People");
			$q->where("id",$id);
			DataModel::get()->getTable("People")->filterReadAccess($q, "People");
			$rows = $q->execute();
			if (count($rows) == 0) {
				PNApplication::error("Cannot access to this People");
				return;
			}
				
			$q = SQLQuery::create()->select("People");
			$q->where("id",$id);			DataModel::get()->getTable("People")->prepareSelectToDetermineRemoveAccess($q, "People", $locks);
			$rows = $q->execute();
			$rows = DataModel::get()->getTable("People")->filterRemoveAccess($rows);
			$rights['remove'] = count($rows) == 1;
				
			$q = SQLQuery::create()->select("People");
			$q->where("id",$id);			DataModel::get()->getTable("People")->prepareSelectToDetermineWriteAccess($q, "People");
			$rows = $q->execute();
			$rows = DataModel::get()->getTable("People")->filterWriteAccess($rows);
			$rights['edit'] = count($rows) == 1;
				
			$rights['add'] = $rights['remove'];
		} else {
			$rights['remove'] = $rights['add'] = $rights['edit'] = true;
		}
	}	if($type == "organization"){		$table_address = "Organization_address";		$join_address_column = "organization";			if ($id <> -1) {			$q = SQLQuery::create()->select("Organization");			$q->where("id",$id);			DataModel::get()->getTable("Organization")->filterReadAccess($q, "Organization");			$rows = $q->execute();			if (count($rows) == 0) {				PNApplication::error("Cannot access to this Organization");				return;			}							$q = SQLQuery::create()->select("Organization");			$q->where("id",$id);			DataModel::get()->getTable("Organization")->prepareSelectToDetermineRemoveAccess($q, "Organization", $locks);			$rows = $q->execute();			$rows = DataModel::get()->getTable("Organization")->filterRemoveAccess($rows);			$rights['remove'] = count($rows) == 1;							$q = SQLQuery::create()->select("Organization");			$q->where("id",$id);			DataModel::get()->getTable("Organization")->prepareSelectToDetermineWriteAccess($q, "Organization");			$rows = $q->execute();			$rows = DataModel::get()->getTable("Organization")->filterWriteAccess($rows);			$rights['edit'] = count($rows) == 1;							$rights['add'] = $rights['remove'];		} else {			$rights['remove'] = $rights['add'] = $rights['edit'] = true;		}	}	if($id <> -1) $addresses = addresses_structure($type, $id);	$addresses_var_name = $page->generate_id();	?>	<script type='text/javascript'>	<?php	echo $addresses_var_name." = ";	if ($id <> -1) echo $addresses;	else echo "[]";	$page->add_javascript("/static/contact/addresses.js");	$var_id = $page->generate_id();	$page->onload("window.$var_id = new addresses(".json_encode($container_id).",true,".json_encode($table_address).",".json_encode($join_address_column).",".json_encode($id).",".$addresses_var_name.",".json_encode($rights['edit']).",".json_encode($rights['add']).",".json_encode($rights['remove']).");");	?>	</script>	<?php	return $var_id;}?>