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
	public function getTypedFilter($sub_model) {
		return array("filter_text","null");
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
	
	public function getFilterCondition(&$q, $path, $data_aliases, $filter) {
		$val = trim($filter["value"]);
		if ($val == "") return null;
		$w = "`FOREIGN_TABLE`.`contact`";
		switch ($filter["type"]) {
			case "contains": $w .= " LIKE '%".\SQLQuery::escape($filter["value"])."%'"; break;
			case "starts": $w .= " LIKE '".\SQLQuery::escape($filter["value"])."%'"; break;
			case "ends": $w .= " LIKE '%".\SQLQuery::escape($filter["value"])."'"; break;
			case "exact": $w .= " = '".\SQLQuery::escape($filter["value"])."'"; break;
		}
		$foreign_table = $path->table->getColumnFor($this->foreign_key_name, $path->sub_model)->foreign_table;
		$foreign_table = \DataModel::get()->getTable($foreign_table);
	
		$parent_alias = \DataPathSQLBuilder::build($path->parent, $q);
		
		$sub_q = \SQLQuery::create()
			->select(array($path->table->getName()=>"PRIMARY"))
			->bypassSecurity()
			->where("(`PRIMARY`.`".$this->joining_key_name."`=`$parent_alias`.`id`)")
			->join("PRIMARY", $foreign_table->getName(), array($this->foreign_key_name=>$foreign_table->getPrimaryKey()->name), "FOREIGN_TABLE")
			->where($w)
			->field("PRIMARY", $this->foreign_key_name)
			->limit(0,1)
			;
		$alias = $q->generateFieldAlias();
		$q->fieldSubQuery($sub_q, $alias);
		return array("type"=>"having","condition"=>"(`$alias` IS NOT NULL)");
	}
	
	public function performSubRequests(&$original_query, &$rows, &$sql_aliases, $path) {
		$table_name = $this->joining_key_name == "people" ? "PeopleContact" : "OrganizationContact";

		$keys = array();
		foreach ($rows as $row)
			if (!in_array($row[$sql_aliases["key"]], $keys))
			array_push($keys, $row[$sql_aliases["key"]]);

		if (count($keys) == 0) {
			$res = array();
			$q = null;
		} else {
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
			$rows[$i][$sql_aliases["data"]] = json_decode(json_normalize("{type:".json_encode($this->joining_key_name).",type_id:".$rows[$i][$sql_aliases["key"]].",contacts:".$values."}"));
		}
		if ($q == null) return array();
		return array(array($q,$res));
	}

	public function getData($row, $resultFromBuildSQL) {
		return @$row[$resultFromBuildSQL["data"]];
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
	
	public function getExportTimes($value, $sub_model) {
		$nb = 0;
		foreach ($value->contacts as $c) if ($c->type == $this->type) $nb++;
		return $nb;
	}
	public function exportValueNumber($value, $sub_model, $number) {
		$nb = 0;
		foreach ($value->contacts as $c)
			if ($c->type == $this->type) {
				if ($nb < $number) $nb++;
				else return $c->contact;
			}
		return "";
	}
	
}

class AddressDataDisplay extends datamodel\JoiningTableDataDisplay {

	public function __construct($joining_key_name) {
		parent::__construct("Address", $joining_key_name, "address", null);
	}

	public function getTypedField($sub_model) {
		return array("field_addresses","{}");
	}
	public function getTypedFilter($sub_model) {
		$cfg = "{";
		$cfg .= "types:";
		$types = SQLQuery::create()->select("PostalAddress")->field("address_type")->distinct()->executeSingleField();
		$cfg .= json_encode($types);
		$cfg .= "}";
		return array("filter_addresses",$cfg);
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

	public function performSubRequests(&$original_query, &$rows, &$sql_aliases, $path) {
		$table_name = $this->joining_key_name == "people" ? "PeopleAddress" : "OrganizationAddress";

		$keys = array();
		foreach ($rows as $row)
			if (!in_array($row[$sql_aliases["key"]], $keys))
			array_push($keys, $row[$sql_aliases["key"]]);

		if (count($keys) == 0) {
			$res = array();
			$texts = array();
			$q = null;
		} else {
			$q = \SQLQuery::create()
				->select($table_name)
				->join($table_name, "PostalAddress", array("address" => "id"), "JOIN_ALIAS")
				->field($table_name, $this->joining_key_name, "JOIN_KEY")
				->whereIn($table_name, $this->joining_key_name, $keys)
				;
			require_once("component/contact/ContactJSON.inc");
			ContactJSON::PostalAddressSQL($q);
			$res = $q->execute();
			$texts = GeographyJSON::prepareGeographicAreasTexts($res);
		}

		$sql_aliases["data"] = $original_query->generateFieldAlias();
		for ($i = 0; $i < count($rows); $i++) {
			$key = $rows[$i][$sql_aliases["key"]];
			$match = array();
			$match_texts = array();
			for ($j = 0; $j < count($res); ++$j) {
				if ($res[$j]["JOIN_KEY"] <> $key) continue; 
				array_push($match, $res[$j]);
				array_push($match_texts, $texts[$j]);
			}
			$values = ContactJSON::PostalAddresses($match, $match_texts);
			require_once("component/Service.inc");
			$rows[$i][$sql_aliases["data"]] = json_decode(json_normalize("{type:".json_encode($this->joining_key_name).",type_id:".json_encode($rows[$i][$sql_aliases["key"]]).",addresses:".$values."}"));
		}
		if ($q == null) return array();
		return array(array($q,$res));
	}
	
	public function getData($row, $resultFromBuildSQL) {
		return @$row[$resultFromBuildSQL["data"]];
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
	
	public function exportValue($value, $sub_model) {
		return null; // will be done in exportValueNumber
	}
	public function getExportTimes($value, $sub_model) {
		return count($value->addresses);
	}
	public function exportValueNumber($value, $sub_model, $number) {
		$addr = $value->addresses[$number];
		$list = array();
		if ($addr->building) array_push($list, "Building ".$addr->building);
		if ($addr->unit) array_push($list, "Unit ".$addr->unit);
		if ($addr->street_number) array_push($list, $addr->street_number);
		if ($addr->street) array_push($list, $addr->street);
		if ($addr->geographic_area && $addr->geographic_area->text) array_push($list, $addr->geographic_area->text); 
		if ($addr->additional) array_push($list, $addr->additional);
		$s = "";
		foreach ($list as $l) {
			if ($s <> "") $s .= ", ";
			$s .= $l;
		}
		return $s;
	}
	
	public function getFilterCondition(&$q, $path, $data_aliases, $filter) {
		$types = $filter["types"];
		$w = "";
		if (count($types) > 0) {
			$w .= "(`FOREIGN_TABLE`.`address_type` IN (";
			$first = true;
			foreach ($types as $type) {
				if ($first) $first = false; else $w .= ",";
				$w .= "'".SQLQuery::escape($type)."'";
			} 
			$w .= "))";
		}
		$areas = $filter["area_path"];
		if ($areas <> null && count($areas) > 0) {
			if ($w <> "") $w .= " AND ";
			$w .= "(";
			$w .= "`FOREIGN_TABLE`.`geographic_area` IN (";
			$first = true;
			foreach ($areas as $area_id) {
				if ($first) $first = false; else $w .= ",";
				$w .= "'".SQLQuery::escape($area_id)."'";
			} 
			$w .= "))";
		}
		if ($w == "") return null;
		$foreign_table = $path->table->getColumnFor($this->foreign_key_name, $path->sub_model)->foreign_table;
		$foreign_table = \DataModel::get()->getTable($foreign_table);
	
		$parent_alias = \DataPathSQLBuilder::build($path->parent, $q);
	
		$sub_q = \SQLQuery::create()
			->select(array($path->table->getName()=>"PRIMARY"))
			->bypassSecurity()
			->where("(`PRIMARY`.`".$this->joining_key_name."`=`$parent_alias`.`id`)")
			->join("PRIMARY", $foreign_table->getName(), array($this->foreign_key_name=>$foreign_table->getPrimaryKey()->name), "FOREIGN_TABLE")
			->where($w)
			->field("PRIMARY", $this->foreign_key_name)
			->limit(0,1)
		;
		$alias = $q->generateFieldAlias();
		$q->fieldSubQuery($sub_q, $alias);
		return array("type"=>"having","condition"=>"(`$alias` IS NOT NULL)");
	}
	
	public function getSubDataDisplay() { return new AddressSubDataDisplay($this); }

}

class AddressSubDataDisplay extends datamodel\SubDataDisplay {
	
	public function getDisplayNames() {
		$names = array("Type");
		foreach (PNApplication::$instance->geography->getLocalDivisions() as $div)
			array_push($names, $div["name"]);
		return $names;
	}
	
	public function isEditable($index) { return true; }
	
	public function exportValueNumber($value, $sub_model, $index, $number) { 
		$addr = $value->addresses[$number];
		if ($index == 0)
			return $addr->address_type;
		if ($addr->geographic_area && $addr->geographic_area->id) {
			$divisions = PNApplication::$instance->geography->getCountryDivisions($addr->geographic_area->country_id);
			for ($division_index = 0; $division_index < count($divisions); $division_index++)
				if ($divisions[$division_index]["id"] == $addr->geographic_area->division_id)
					break;
			if ($division_index < count($divisions)) {
				if ($index-1 <= $division_index) {
					$area_id = $addr->geographic_area->id;
					while ($division_index > $index-1) {
						$area_id = PNApplication::$instance->geography->getAreaParent($area_id);
						$division_index--;
					}
					$area = PNApplication::$instance->geography->getArea($area_id);
					return $area["name"];
				}
			}
		}
		return "";
	}
	
}

class OrganizationDataDisplay extends datamodel\ChoiceDataDisplay {
	
	private $accepted_creators;
	private $restrict_types;
	
	public function __construct($foreign_key_name, $display_name, $accepted_creators, $restrict_types_names = null, $editable = true, $editable_for_new = null) {
		parent::__construct($foreign_key_name, "name", $display_name, $editable, $editable_for_new, "left", null);
		$this->accepted_creators = $accepted_creators;
		$this->restrict_types = $restrict_types_names;
	}
	
	protected function get_possible_values($sub_model) {
		$foreign_key = $this->getTable()->getColumnFor($this->foreign_key_name, $sub_model);
		if (!isset($this->possible_values[$sub_model])) {
			$q = \SQLQuery::create()->select("Organization");
			$q->whereIn("Organization", "creator", $this->accepted_creators);
			$q->whereValue("Organization", "obsolete", 0);
			if ($this->restrict_types <> null) {
				$q->field("Organization", "id");
				$orgs_ids = $q->executeSingleField();
				if (count($orgs_ids) == 0)
					$this->possible_values[$sub_model] = array();
				else {
					$types_ids = \SQLQuery::create()
						->select("OrganizationType")
						->whereIn("OrganizationType", "creator", $this->accepted_creators)
						->whereIn("OrganizationType", "name", $this->restrict_types)
						->field("OrganizationType","id")
						->executeSingleField();
					if (count($types_ids) == 0)
						$this->possible_values[$sub_model] = array();
					else {
						$q = \SQLQuery::create()
							->select("OrganizationTypes")
							->whereIn("OrganizationTypes", "organization", $orgs_ids)
							->whereIn("OrganizationTypes", "type", $types_ids)
							->groupBy("OrganizationTypes", "organization")
							->join("OrganizationTypes", "Organization", array("organization"=>"id"))
							->whereValue("Organization", "obsolete", 0)
							->field("Organization", "id", "id")
							->field("Organization", "name", "name")
							->orderBy("Organization","name")
							;
						$this->possible_values[$sub_model] = $q->execute();
					}
				}
			} else {
				$q->field("Organization", "id", "id");
				$q->field("Organization", "name", "name");
				$q->orderBy("Organization","name");
				$this->possible_values[$sub_model] = $q->execute();
			}
			// attach geographic areas to each organization
			if (count($this->possible_values[$sub_model]) > 0) {
				$orgs_ids = array();
				foreach ($this->possible_values[$sub_model] as $o) array_push($orgs_ids, $o["id"]);
				$q = SQLQuery::create()->select("OrganizationAddress")
					->whereIn("OrganizationAddress", "organization", $orgs_ids)
					->join("OrganizationAddress", "PostalAddress", array("address"=>"id"))
					;
				PNApplication::$instance->geography->joinGeographicArea($q, "PostalAddress", "geographic_area");
				$q->field("OrganizationAddress", "organization", "org_id");
				$q->field("GeographicArea", "id", "area_id");
				$geo = $q->execute();
				for ($i = 0; $i < count($this->possible_values[$sub_model]); $i++) {
					$this->possible_values[$sub_model][$i]["areas"] = array();
					foreach ($geo as $g)
						if ($g["org_id"] == $this->possible_values[$sub_model][$i]["id"])
							array_push($this->possible_values[$sub_model][$i]["areas"], $g["area_id"]);
				}
			}
		}
		return $this->possible_values[$sub_model];
	}
	public function getTypedField($sub_model) {
		if ($sub_model <> "@link") {
			$cfg = "{";
			$cfg .= "list:[";
			$list = $this->get_possible_values($sub_model);
			$first = true;
			foreach ($list as $org) {
				if ($first) $first = false; else $cfg .= ",";
				$cfg .= "{id:".$org["id"].",name:".json_encode($org["name"]).",areas:".json_encode($org["areas"])."}";
			}
			$cfg .= "]";
			$cfg .= ",creators:".json_encode($this->accepted_creators);
			$cfg .= ",types:".json_encode($this->restrict_types);
			$foreign_key = $this->getTable()->getColumnFor($this->foreign_key_name, $sub_model);
			$cfg .= ",can_be_null:".($foreign_key->can_be_null ? "true" : "false");
			$cfg .= ",name:".json_encode($this->display_name);
			$cfg .= "}";
			return array("field_organization",$cfg);
		}
		return array("field_text","{can_be_null:true}");
	}
	
	public function getTypedFilter($sub_model) {
		if ($sub_model <> "@link") {
			$cfg = "{";
			$cfg .= "list:[";
			$list = $this->get_possible_values($sub_model);
			$first = true;
			foreach ($list as $org) {
				if ($first) $first = false; else $cfg .= ",";
				$cfg .= "{id:".$org["id"].",name:".json_encode($org["name"]).",areas:".json_encode($org["areas"])."}";
			}
			$cfg .= "]";
			$cfg .= ",creators:".json_encode($this->accepted_creators);
			$cfg .= ",types:".json_encode($this->restrict_types);
			$foreign_key = $this->getTable()->getColumnFor($this->foreign_key_name, $sub_model);
			$cfg .= ",can_be_null:".($foreign_key->can_be_null ? "true" : "false");
			$cfg .= ",name:".json_encode($this->display_name);
			$cfg .= "}";
			return array("filter_organization",$cfg);
		}
		return null;
	}
	public function getFilterCondition(&$q, $path, $data_aliases, $filter) {
		if ($filter == null) return null;
		$table_alias = $q->getTableAlias($path->table->getName());
		$foreign_key = $this->getTable()->getColumnFor($this->foreign_key_name, $path->sub_model);
		$w = "";
		foreach ($filter as $value) {
			if ($value == "") continue;
			if (strlen($w) > 0) $w .= " OR ";
			$w .= "`".$table_alias."`.`".$foreign_key->name."`";
			if ($value === "NULL") $w .= " IS NULL";
			else if ($value === "NOT_NULL") $w .= " IS NOT NULL";
			else $w .= "='".\SQLQuery::escape($value)."'";
		}
		if ($w == "") return null;
		return array("type"=>"where","condition"=>$w);
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
$display->addDataDisplay(new datamodel\SimpleDataDisplay("obsolete", "Is obsolete (hidden)"));
$display->addJoinToPrimary();
$model->addTableDataDisplay($display);

$display = new datamodel\TableDataDisplay("OrganizationTypes", $org_category);
$display->addDataDisplay(new datamodel\JoiningTableDataDisplay("Types", "organization", "type", "name"));
$display->addJoinFromAll();
$model->addTableDataDisplay($display);

class ContactsScreen implements datamodel\SimpleDataScreen {

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
	public function generateEndOfSection(&$page, $paths, $values, $prefilled_values, $structure_name) {}
	
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

class ContactPointDataScreen implements datamodel\SimpleDataScreen {
	
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
			echo "<div style='padding:2px'>";
			echo "Designation ";
			echo "<span id='$id'></span>";
			echo "</div>";
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
				echo "Designation for ".toHTML($row["name"]).": ";
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
	public function generateEndOfSection(&$page, $paths, $values, $prefilled_values, $structure_name) {}
	
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

/*
$display = new datamodel\TableDataDisplay("ContactPoint", $org_category);
$display->addDataDisplay(new datamodel\SimpleDataDisplay("designation", "Designation"));
$display->addJoinFrom("people", array("types"), function($columns) {
	$types = PNApplication::$instance->people->parseTypes($columns["types"]);
	foreach ($types as $type) if (substr($type, 0, 8) == "contact_") return true;
	return false;
});
$model->addTableDataDisplay($display);
*/
?>