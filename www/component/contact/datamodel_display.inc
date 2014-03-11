<?php 
class ContactDisplayHandler extends datamodel\DataDisplayHandler {

	public $type;

	public function __construct($type) {
		parent::__construct($type == "people" ? "Personal Information" : "Organization");
		$this->type = $type;
	}

	public function getDisplayableData() {
		return array(
			new ContactDataDisplay($this, "email"),
			new ContactDataDisplay($this, "phone"),
			new ContactDataDisplay($this, "IM"),
		);
	}

	public function canFollow($foreign_key_name) { return false; }
	public function canJoin() { return false; }
}

class ContactDataDisplay extends datamodel\JoiningTableDataDisplay {

	private $type;

	public function __construct($handler, $type) {
		switch ($type) {
			case "email": $display_name = "EMail"; break;
			case "phone": $display_name = "Phone"; break;
			case "IM": $display_name = "Instant Messaging"; break;
		}
		switch ($handler->type) {
			case "people": $joining_key_name = "people"; break;
			case "organization": $joining_key_name = "organization"; break;
		}
		$foreign_key_name = "contact";
		parent::__construct($handler, $display_name, $joining_key_name, $foreign_key_name, null);
		$this->type = $type;
	}

	public function getTypedFieldInList($sub_model) {
		return array("field_contact_type","{type:'".$this->type."'}");
	}
	public function isEditable() {
		return true;
	}
	public function getEditLocks($sub_model) {
		if ($this->getHandler()->type == "people")
			return array(array("table"=>"PeopleContact"));
		return array(array("table"=>"OrganizationContact"));
	}
	public function isSortable() {
		return false;
	}

	public function performSubRequests(&$original_query, &$rows, &$sql_aliases, $path, $filters) {
		$table_name = $this->getHandler()->type == "people" ? "PeopleContact" : "OrganizationContact";

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
			$rows[$i][$sql_aliases["data"]] = json_decode(json_normalize("{type:".json_encode($this->getHandler()->type).",type_id:".$row[$sql_aliases["key"]].",contacts:".$values."}"));
		}
	}

	public function getNewData() {
		require_once("component/Service.inc");
		return json_decode(json_normalize("{type:".json_encode($this->getHandler()->type).",type_id:-1,contacts:[]}"));
	}

	public function saveData($key, $value, $sub_model, &$tables_fields) {
		// TODO
	}

}

class AddressDisplayHandler extends datamodel\DataDisplayHandler {

	public $type;

	public function __construct($type) {
		parent::__construct($type == "people" ? "Personal Information" : "Organization");
		$this->type = $type;
	}

	public function getDisplayableData() {
		return array(
			new AddressDataDisplay($this),
		);
	}

	public function canFollow($foreign_key_name) { return false; }
	public function canJoin() { return false; }
}
class AddressDataDisplay extends datamodel\JoiningTableDataDisplay {

	public function __construct($handler) {
		switch ($handler->type) {
			case "people": $joining_key_name = "people"; break;
			case "organization": $joining_key_name = "organization"; break;
		}
		parent::__construct($handler, "Address", $joining_key_name, "address", null);
	}

	public function getTypedFieldInList($sub_model) {
		return array("field_addresses","{}");
	}
	public function isEditable() {
		return true;
	}
	public function getEditLocks($sub_model) {
		if ($this->getHandler()->type == "people")
			return array(array("table"=>"PeopleAddress"));
		return array(array("table"=>"OrganizationAddress"));
	}
	public function isSortable() {
		return false;
	}

	public function performSubRequests(&$original_query, &$rows, &$sql_aliases, $path, $filters) {
		$table_name = $this->getHandler()->type == "people" ? "PeopleAddress" : "OrganizationAddress";

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
			$rows[$i][$sql_aliases["data"]] = json_decode(json_normalize("{type:".json_encode($this->getHandler()->type).",type_id:".json_encode($row[$sql_aliases["key"]]).",addresses:".$values."}"));
		}
	}

	public function getNewData() {
		require_once("component/Service.inc");
		$table_name = $this->getHandler()->type == "people" ? "PeopleAddress" : "OrganizationAddress";
		return json_decode(json_normalize("{type:".json_encode($this->getHandler()->type).",type_id:-1,addresses:[]}"));
	}

	public function saveData($key, $value, $sub_model, &$tables_fields) {
		// TODO
	}

}

$model->addDataCategoryLink("Contacts", "/dynamic/contact/page/organization_profile?organization=%Organization.id%", "/static/contact/organization.png");

$model->internalGetTable("PeopleContact")
	->setDisplayHandler("people", new ContactDisplayHandler("people"));
$model->internalGetTable("OrganizationContact")
	->setDisplayHandler("organization", new ContactDisplayHandler("organization"));

$model->internalGetTable("PeopleAddress")
	->setDisplayHandler("people", new AddressDisplayHandler("people"));
$model->internalGetTable("OrganizationAddress")
	->setDisplayHandler("organization", new AddressDisplayHandler("organization"));

$model->internalGetTable("Organization")
	->setDisplayHandler(null, new datamodel\FieldsDisplayHandler("Contacts", array(
		"name" => "Name",
		"creator" => "Managed by",
	), array(
		"creator"
	)));

$model->internalGetTable("ContactPoint")
	->setDisplayHandler(null, new datamodel\StopDataDisplayHandler("Organization")); // TODO
?>