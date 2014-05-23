<?php
class ContactDataDisplay extends datamodel\JoiningTableDataDisplay {

	private $type;

	public function __construct($type, $joining_key_name) {
		switch ($type) {
			case "email": $display_name = "EMail"; break;
			case "phone": $display_name = "Phone"; break;
			case "IM": $display_name = "Instant Messaging"; break;
		}
		parent::__construct($display_name, $joining_key_name, "contact", null);
		$this->type = $type;
	}

	public function getTypedField($sub_model) {
		return array("field_contact_type","{type:'".$this->type."'}");
	}
	public function isEditable() {
		return true;
	}
	public function getEditLocks($sub_model) {
		if ($this->joining_key_name == "people")
			return array(array("table"=>"PeopleContact"));
		return array(array("table"=>"OrganizationContact"));
	}
	public function isSortable() {
		return false;
	}

	public function performSubRequests(&$original_query, &$rows, &$sql_aliases, $path, $filters) {
		$table_name = $this->joining_key_name == "people" ? "PeopleContact" : "OrganizationContact";

		$keys = array();
		foreach ($rows as $row)
			if (!in_array($row[$sql_aliases["key"]], $keys))
			array_push($keys, $row[$sql_aliases["key"]]);

		if (count($keys) == 0)
			$res = array();
		else {
			$q = \SQLQuery::create()
			->select($table_name)
			->join($table_name, "Contact", array("contact" => "id"), "JOIN_ALIAS")
			->field($table_name, $this->joining_key_name, "JOIN_KEY")
			->whereIn($table_name, $this->joining_key_name, $keys)
			->whereValue("JOIN_ALIAS", "type", $this->type)
			;
			require_once("ContactJSON.inc");
			ContactJSON::ContactSQL($q);
			$res = $q->execute();
		}

		$sql_aliases["data"] = $original_query->generateFieldAlias();
		for ($i = 0; $i < count($rows); $i++) {
			$key = $rows[$i][$sql_aliases["key"]];
			$values = "[";
			$first = true;
			foreach ($res as $r) {
				if ($r["JOIN_KEY"] == $key) {
					if ($first) $first = false; else $values .= ",";
					$values .= ContactJSON::Contact($r);
				}
			}
			$values .= "]";
			require_once("component/Service.inc");
			$rows[$i][$sql_aliases["data"]] = json_decode(json_normalize("{type:".json_encode($this->joining_key_name).",type_id:".$row[$sql_aliases["key"]].",contacts:".$values."}"));
		}
	}

	public function getNewData() {
		require_once("component/Service.inc");
		return json_decode(json_normalize("{type:".json_encode($this->joining_key_name).",type_id:-1,contacts:[]}"));
	}

	public function saveData($key, $value, $sub_model, &$tables_fields, $joining_key_for_new_data, $joining_key_value_for_new_data) {
		if ($value["type_id"] == -1) {
			// new people or organization
			$type_id = $joining_key_value_for_new_data;
			$contacts = $value["contacts"];
			foreach ($contacts as $contact)
				if ($joining_key_for_new_data == "people")
					PNApplication::$instance->contact->addContactToPeople($type_id, $contact);
				else 
					PNApplication::$instance->contact->addContactToOrganization($type_id, $contact);
		}
	}

}

class AddressDataDisplay extends datamodel\JoiningTableDataDisplay {

	public function __construct($joining_key_name) {
		parent::__construct("Address", $joining_key_name, "address", null);
	}

	public function getTypedField($sub_model) {
		return array("field_addresses","{}");
	}
	public function isEditable() {
		return true;
	}
	public function getEditLocks($sub_model) {
		if ($this->joining_key_name == "people")
			return array(array("table"=>"PeopleAddress"));
		return array(array("table"=>"OrganizationAddress"));
	}
	public function isSortable() {
		return false;
	}

	public function performSubRequests(&$original_query, &$rows, &$sql_aliases, $path, $filters) {
		$table_name = $this->joining_key_name == "people" ? "PeopleAddress" : "OrganizationAddress";

		$keys = array();
		foreach ($rows as $row)
			if (!in_array($row[$sql_aliases["key"]], $keys))
			array_push($keys, $row[$sql_aliases["key"]]);

		if (count($keys) == 0)
			$res = array();
		else {
			$q = \SQLQuery::create()
			->select($table_name)
			->join($table_name, "PostalAddress", array("address" => "id"), "JOIN_ALIAS")
			->field($table_name, $this->joining_key_name, "JOIN_KEY")
			->whereIn($table_name, $this->joining_key_name, $keys)
			;
			require_once("component/contact/ContactJSON.inc");
			ContactJSON::PostalAddressSQL($q);
			$res = $q->execute();
		}

		$sql_aliases["data"] = $original_query->generateFieldAlias();
		for ($i = 0; $i < count($rows); $i++) {
			$key = $rows[$i][$sql_aliases["key"]];
			$values = "[";
			$first = true;
			foreach ($res as $r) {
				if ($r["JOIN_KEY"] == $key) {
					if ($first) $first = false; else $values .= ",";
					$values .= ContactJSON::PostalAddress($r);
				}
			}
			$values .= "]";
			require_once("component/Service.inc");
			$rows[$i][$sql_aliases["data"]] = json_decode(json_normalize("{type:".json_encode($this->joining_key_name).",type_id:".json_encode($row[$sql_aliases["key"]]).",addresses:".$values."}"));
		}
	}

	public function getNewData() {
		require_once("component/Service.inc");
		$table_name = $this->joining_key_name == "people" ? "PeopleAddress" : "OrganizationAddress";
		return json_decode(json_normalize("{type:".json_encode($this->joining_key_name).",type_id:-1,addresses:[]}"));
	}

	public function saveData($key, $value, $sub_model, &$tables_fields, $joining_key_for_new_data, $joining_key_value_for_new_data) {
		if ($value["type_id"] == -1) {
			// new people or organization
			$type_id = $joining_key_value_for_new_data;
			$addresses = $value["addresses"];
			foreach ($addresses as $addr)
				if ($joining_key_for_new_data == "people")
					PNApplication::$instance->contact->addAddressToPeople($type_id, $addr);
				else 
					PNApplication::$instance->contact->addAddressToOrganization($type_id, $addr);
		} else {
			$type_id = $value["type_id"];
			$addresses = $value["addresses"];
			require_once("ContactJSON.inc");
			foreach ($addresses as $addr) {
				$a = ContactJSON::PostalAddress2DB($addr);
				if ($a["id"] <> -1)
					SQLQuery::create()->updateByKey("PostalAddress", $a["id"], $a);
			}
		}
	}
	
	public function getSubDataDisplay() { return new AddressSubDataDisplay($this); }

}

class AddressSubDataDisplay extends datamodel\SubDataDisplay {
	
	public function getDisplayNames() {
		$names = array();
		foreach (PNApplication::$instance->geography->getLocalDivisions() as $div)
			array_push($names, $div["name"]);
		return $names;
	}
	
}

$people_category = $model->getDataCategory("Personal Information");
$display = new datamodel\TableDataDisplay("PeopleContact", $people_category);
$display->addDataDisplay(new ContactDataDisplay("email", "people"), "people");
$display->addDataDisplay(new ContactDataDisplay("phone", "people"), "people");
$display->addDataDisplay(new ContactDataDisplay("IM", "people"), "people");
$display->addJoinFrom("people");
$model->addTableDataDisplay($display);
$display = new datamodel\TableDataDisplay("PeopleAddress", $people_category);
$display->addDataDisplay(new AddressDataDisplay("people"), "people");
$display->addJoinFrom("people");
$model->addTableDataDisplay($display);

$org_category = new datamodel\DataDisplayCategory("Organization", "/static/contact/organization.png", "/dynamic/contact/page/organization_profile?organization=%Organization.id%");
$model->addDataCategory($org_category);
$display = new datamodel\TableDataDisplay("OrganizationContact", $org_category);
$display->addDataDisplay(new ContactDataDisplay("email", "organization"), "organization");
$display->addDataDisplay(new ContactDataDisplay("phone", "organization"), "organization");
$display->addDataDisplay(new ContactDataDisplay("IM", "organization"), "organization");
$display->addJoinFrom("organization");
$model->addTableDataDisplay($display);
$display = new datamodel\TableDataDisplay("OrganizationAddress", $org_category);
$display->addDataDisplay(new AddressDataDisplay("organization"), "organization");
$display->addJoinFrom("organization");
$model->addTableDataDisplay($display);

$display = new datamodel\TableDataDisplay("Organization", $org_category);
$display->addDataDisplay(new datamodel\SimpleDataDisplay("name", "Name"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("creator", "Managed by", true));
$display->addJoinToPrimary();
$model->addTableDataDisplay($display);

class ContactsScreen extends datamodel\DataScreen {

	private $type;
	
	public function __construct($type) {
		$this->type = $type;
	}
	
	public function getTables() {
		if ($this->type == "people")
			return array("PeopleContact", "PeopleAddress");
		return array("OrganizationContact","OrganizationAddress");
	}
	
	public function getIcon() { return "/static/contact/contact_16.png"; }
	public function getName() { return "Contacts"; }

	public function generate(&$page, $paths, $values, $prefilled_values, $structure_name) {
		if ($this->type == "people")
			$owner_id = $values->getColumnValue("People", "id");
		else
			$owner_id = $values->getColumnValue("Organization", "id");
		$contact_div_id = $page->generateID();
		$address_div_id = $page->generateID();
		echo "<table><tr><td valign=top id='$contact_div_id'></td><td valign=top id='$address_div_id'></td></tr></table>";
		require_once("page/contact.inc");
		$var = contact($page, $this->type, $contact_div_id, $owner_id);
		if ($structure_name <> null)
			echo "<script type='text/javascript'>$structure_name.push({path:".json_encode($paths[0]->getString()).",contacts:$var,getValue:function(){return this.contacts.getContacts();},validate:function(){return null;}});</script>";
		require_once("page/address.inc");
		$var = address($page, $this->type, $address_div_id, $owner_id);
		if ($structure_name <> null)
			echo "<script type='text/javascript'>$structure_name.push({path:".json_encode($paths[1]->getString()).",addresses:$var,getValue:function(){return this.addresses.getAddresses();},validate:function(){return null;}});</script>";
	}
	
	public function createData($paths, $multiple) {
		$contacts = $paths[0]->value;
		$addresses = $paths[1]->value;
		for ($i = 0; $i < count($contacts); $i++) if (isset($contacts[$i]["id"])) unset($contacts[$i]["id"]);
		for ($i = 0; $i < count($addresses); $i++) if (isset($addresses[$i]["id"])) unset($addresses[$i]["id"]);
		if ($this->type == "people") {
			$join_table = "People";
			$join_key = "people";
		} else {
			$join_table = "Organization";
			$join_key = "organization";
		}
		require_once("ContactJSON.inc");
		if (count($contacts) > 0) {
			$ids = SQLQuery::create()->bypassSecurity()->insertMultiple("Contact", $contacts);
			$join = array();
			foreach ($ids as $id)
				array_push($join, array($join_key=>$paths[0]->columns[$join_key],"contact"=>$id));
			SQLQuery::create()->bypassSecurity()->insertMultiple($join_table."Contact", $join);
		}
		if (count($addresses) > 0) {
			$list = array();
			foreach ($addresses as $a) array_push($list, ContactJSON::PostalAddress2DB($a));
			$ids = SQLQuery::create()->bypassSecurity()->insertMultiple("PostalAddress", $list);
			$join = array();
			foreach ($ids as $id)
				array_push($join, array($join_key=>$paths[0]->columns[$join_key],"address"=>$id));
			SQLQuery::create()->bypassSecurity()->insertMultiple($join_table."Address", $join);
		}
	}
	
}
$model->addDataScreen(new ContactsScreen("people"));
$model->addDataScreen(new ContactsScreen("organization"));

class ContactPointDataScreen extends datamodel\DataScreen {
	
	public function getTables() { return array("ContactPoint"); }
	
	public function getIcon() { return "/static/contact/contact_point.png"; }
	public function getName() { return "Organization Contact Point"; }
	
	public function generate(&$page, $paths, $values, $prefilled_values, $structure_name) {
		$people_types = $values->getColumnValue("People", "types");
		$people_types = PNApplication::$instance->people->parseTypes($people_types);
		$ok = false;
		require_once("ContactPointPeopleTypePlugin.inc");
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof ContactPointPeopleTypePlugin)) continue;
				if (in_array($pi->getId(), $people_types) && $pi->canRead()) {
					$ok = true;
					break;
				}
			}
			if ($ok) break;
		}
		if (!$ok) return;
		
		$path = $paths[0];
		if (!$values->hasColumnValue("ContactPoint", "people"))
			$people_id = null;
		else
			$people_id = $values->getColumnValue("ContactPoint", "people");
		if (!$values->hasColumnValue("ContactPoint", "organization"))
			$org_id = null;
		else
			$org_id = $values->getColumnValue("ContactPoint", "organization");

		$tf = PNApplication::$instance->widgets->get_typed_field(DataModel::get()->getTable("ContactPoint")->getColumn("designation"));
		$page->requireJavascript("typed_field.js");
		$page->requireJavascript($tf[0].".js");
		
		if ($people_id == null) {
			// new people
			$id = $page->generateID();
			echo "Designation ";
			echo "<span id='$id'></span>";
			echo "<script type='text/javascript'>";
			echo "var field = new ".$tf[0]."(null,true,".$tf[1].");";
			echo "document.getElementById('$id').appendChild(field.getHTMLElement());";
			echo "var path = {path:".json_encode($path->getString()).",data:[],columns:{";
			$first = true;
			foreach ($values->getColumns($path->table->getName()) as $cname=>$val) {
				if ($first) $first = false; else echo ",";
				echo $cname.":".json_encode($val);
			}
			echo "}";
			echo ",field:field";
			echo ",getValue:function(){ return this.field.getCurrentData(); }";
			echo ",validate:function(){ return this.field.error; }";
			echo "};\n";
			echo "$structure_name.push(path);\n";
			echo "</script>";
		} else if ($org_id == null) {
			// show all organisations for this people
			$can_edit = PNApplication::$instance->people->canModify($people_id);
			$rows = SQLQuery::create()
				->select("ContactPoint")
				->whereValue("ContactPoint", "people", $people_id)
				->join("ContactPoint", "Organization", array("organization"=>"id"))
				->execute();
			foreach ($rows as $row) {
				$id = $page->generateID();
				echo "Designation for ".htmlentities($row["name"]).": ";
				echo "<span id='$id'></span>";
				if ($can_edit) {
					$page->requireJavascript("editable_cell.js");
					echo "<script type='text/javascript'>";
					echo "new editable_cell('$id','ContactPoint','designation',{people:".json_encode($people_id).",organization:".json_encode($row["organization"])."},".json_encode($tf[0]).",".$tf[1].",".json_encode($row["designation"]).");";
					echo "</script>";
				} else {
					echo "<script type='text/javascript'>";
					echo "document.getElementById('$id').appendChild(new ".$tf[0]."(".json_encode($row["designation"]).",false,".$tf[1].").getHTMLElement());";
					echo "</script>";
				}
			}
		} else {
			// only one organisation and one people
			echo "TODO: designation of this people for this organization";
		}
	}
	
	public function createData($paths, $multiple) {
		$path = $paths[0];
		SQLQuery::create()->insert("ContactPoint", array(
			"people"=>$path->columns["people"],
			"organization"=>$path->columns["organization"],
			"designation"=>$path->value
		));
	}
	
}

$model->addDataScreen(new ContactPointDataScreen());
?>