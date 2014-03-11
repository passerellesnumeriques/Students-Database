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

	public function getTypedFieldInList($sub_model) {
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
					$values .= ContactJSON::Contact($q, $r);
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

	public function saveData($key, $value, $sub_model, &$tables_fields) {
		// TODO
	}

}

class AddressDataDisplay extends datamodel\JoiningTableDataDisplay {

	public function __construct($joining_key_name) {
		parent::__construct("Address", $joining_key_name, "address", null);
	}

	public function getTypedFieldInList($sub_model) {
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
					$values .= ContactJSON::PostalAddress($q, $r);
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

	public function saveData($key, $value, $sub_model, &$tables_fields) {
		// TODO
	}

}

$people_category = $model->getDataCategory("Personal Information");
$display = new datamodel\TableDataDisplay("PeopleContact", $people_category);
$display->addDataDisplay(new ContactDataDisplay("email", "people"), "people");
$display->addDataDisplay(new ContactDataDisplay("phone", "people"), "people");
$display->addDataDisplay(new ContactDataDisplay("IM", "people"), "people");
$model->addTableDataDisplay($display);
$display = new datamodel\TableDataDisplay("PeopleAddress", $people_category);
$display->addDataDisplay(new AddressDataDisplay("people"), "people");
$model->addTableDataDisplay($display);

$org_category = new datamodel\DataDisplayCategory("Organization", "/static/contact/organization.png", "/dynamic/contact/page/organization_profile?organization=%Organization.id%");
$model->addDataCategory($org_category);
$display = new datamodel\TableDataDisplay("OrganizationContact", $org_category);
$display->addDataDisplay(new ContactDataDisplay("email", "organization"), "organization");
$display->addDataDisplay(new ContactDataDisplay("phone", "organization"), "organization");
$display->addDataDisplay(new ContactDataDisplay("IM", "organization"), "organization");
$model->addTableDataDisplay($display);
$display = new datamodel\TableDataDisplay("OrganizationAddress", $org_category);
$display->addDataDisplay(new AddressDataDisplay("organization"), "organization");
$model->addTableDataDisplay($display);

$display = new datamodel\TableDataDisplay("Organization", $org_category);
$display->addDataDisplay(new datamodel\SimpleDataDisplay("name", "Name"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("creator", "Managed by", true));
$model->addTableDataDisplay($display);
?>