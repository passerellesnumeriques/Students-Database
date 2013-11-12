<?php
class ContactDisplayHandler extends datamodel\DataDisplayHandler {
	
	public $type;
	
	public function __construct($type) {
		parent::__construct($type == "people" ? "Personal Information" : "Organization", "Contacts");
		$this->type = $type;
	}
	
	public function getDisplayableData() {
		return array(
			new ContactDataDisplay($this, "email"),
			new ContactDataDisplay($this, "phone"),
			new ContactDataDisplay($this, "IM"),
		);
	}
	
	public function createEntry($data, $sub_model, $preset_fields) {
		PNApplication::error("createEntry not supported by ContactDisplayHandler");
	}
	
	public function stopHere() { return true; }
}

class ContactDataDisplay extends datamodel\JoiningTableDataDisplay {
	
	public $type;
	
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
	
	public function getTypedField($sub_model) {
		return array("field_contact_type","{type:'".$this->type."'}");
	}
	public function isEditable() {
		return true;
	}
	public function getEditLocks($sub_model) {
		if ($this->handler->type == "people")
			return array(array("table"=>"People_contact"));
		return array(array("table"=>"Organization_contact"));
	}
	public function isSortable() {
		return false;
	}
	
	public function retrieveValue($row, $sql_aliases, $path) {
		$table_name = $this->handler->type == "people" ? "People_contact" : "Organization_contact";
		
		$res = \SQLQuery::create()
			->select($table_name)
			->join($table_name, "Contact", array("contact" => "id"), "JOIN_ALIAS")
			->field("JOIN_ALIAS", "id", "KEY")
			->field("JOIN_ALIAS", "sub_type", "SUB_TYPE")
			->field("JOIN_ALIAS", "contact", "CONTACT")
			->where_value($table_name, $this->joining_key_name, $row[$sql_aliases["key"]])
			->where_value("JOIN_ALIAS", "type", $this->type)
			->execute();
		$values = array();
		foreach ($res as $r)
			array_push($values, array("id"=>$r["KEY"],"sub_type"=>$r["SUB_TYPE"],"contact"=>$r["CONTACT"]));
		return array("table"=>$table_name,"key_name"=>$this->joining_key_name,"key_value"=>$row[$sql_aliases["key"]],"contacts"=>$values);
	}
	
	public function getNewData() {
		$table_name = $this->handler->type == "people" ? "People_contact" : "Organization_contact";
		return array("table"=>$table_name,"key_name"=>$this->joining_key_name,"key_value"=>-1,"contacts"=>array());
	}
	
	public function saveData($key, $value, $sub_model) {
		
	}
	
}

class AddressDisplayHandler extends datamodel\DataDisplayHandler {

	public $type;

	public function __construct($type) {
		parent::__construct($type == "people" ? "Personal Information" : "Organization", "Addresses");
		$this->type = $type;
	}

	public function getDisplayableData() {
		return array(
			new AddressDataDisplay($this),
		);
	}

	public function createEntry($data, $sub_model, $preset_fields) {
		PNApplication::error("createEntry not supported by AddressDisplayHandler");
	}
	
	public function stopHere() { return true; }
}
class AddressDataDisplay extends datamodel\JoiningTableDataDisplay {

	public function __construct($handler) {
		switch ($handler->type) {
			case "people": $joining_key_name = "people"; break;
			case "organization": $joining_key_name = "organization"; break;
		}
		parent::__construct($handler, "Address", $joining_key_name, "address", null);
	}

	public function getTypedField($sub_model) {
		return array("field_addresses","{}");
	}
	public function isEditable() {
		return true;
	}
	public function getEditLocks($sub_model) {
		if ($this->handler->type == "people")
			return array(array("table"=>"People_address"));
		return array(array("table"=>"Organization_address"));
	}
	public function isSortable() {
		return false;
	}

	public function retrieveValue($row, $sql_aliases, $path) {
		$table_name = $this->handler->type == "people" ? "People_address" : "Organization_address";

		$res = \SQLQuery::create()
			->select($table_name)
			->join($table_name, "Postal_address", array("address" => "id"), "JOIN_ALIAS")
			->field("JOIN_ALIAS", "id", "KEY")
			->field("JOIN_ALIAS","country","country")
			->field("JOIN_ALIAS","geographic_area","geographic_area")
			->field("JOIN_ALIAS","street","street")
			->field("JOIN_ALIAS","street_number","street_number")
			->field("JOIN_ALIAS","building","building")
			->field("JOIN_ALIAS","unit","unit")
			->field("JOIN_ALIAS","additional","additional")
			->field("JOIN_ALIAS","address_type","address_type")
			->where_value($table_name, $this->joining_key_name, $row[$sql_aliases["key"]])
			->execute();
		$values = array();
		foreach ($res as $r)
			array_push($values, 
				array(
					"id"=>$r["KEY"],
					"country"=>$r["country"],
					"geographic_area"=>array("id"=>$r["geographic_area"],"text"=>PNApplication::$instance->geography->get_geographic_area_text($r["geographic_area"])),
					"street_name"=>$r["street"],
					"street_number"=>$r["street_number"],
					"building"=>$r["building"],
					"unit"=>$r["unit"],
					"additional"=>$r["additional"],
					"address_type"=>$r["address_type"]
				)
			);
		return array("table"=>$table_name,"key_name"=>$this->joining_key_name,"key_value"=>$row[$sql_aliases["key"]],"addresses"=>$values);
	}


	public function getNewData() {
		$table_name = $this->handler->type == "people" ? "People_address" : "Organization_address";
		return array("table"=>$table_name,"key_name"=>$this->joining_key_name,"key_value"=>-1,"addresses"=>array());
	}
	
	public function saveData($key, $value, $sub_model) {

	}

}

/* @var $model DataModel */
$model->addTable("Contact")
	->addPrimaryKey("id")
	->addEnum("type",array("email","phone","IM"),true,false)
	->addString("sub_type",10,0,true,false)
	->addString("contact",100,1,false,false)
	->addReadFilter(function(&$q, $table_alias) {
		$table_people = Datamodel::get()->getTable("People_contact");
		$people_alias = $q->get_table_alias("People_contact");
		if ($people_alias == null) {
			$people_alias = $q->table_id();
			$q->join($table_alias, "People_contact", array("id"=>"contact"), $people_alias);
		}
		$where = $q->save_where();
		$table_people->filterReadAccess($q, $people_alias);
		$where_people = $q->save_where();

		$table_org = Datamodel::get()->getTable("Organization_contact");
		$org_alias = $q->get_table_alias("Organization_contact");
		if ($org_alias == null) {
			$org_alias = $q->table_id();
			$q->join($table_alias, "Organization_contact", array("id"=>"contact"), $org_alias);
		}
		$table_org->filterReadAccess($q, $org_alias);
		$where_org = $q->save_where();

		$q->where($where);
		$where = "((`".$people_alias."`.people IS NOT NULL";
		if ($where_people <> "") $where .= " AND ".$where_people;
		$where .= ") OR (`".$org_alias."`.organization IS NOT NULL";
		if ($where_org <> "") $where .= " AND ".$where_org;
		$where .= "))";
		$q->where($where);
	})
	->addWriteFilter(function(&$q, $table_alias){
		$table_people = Datamodel::get()->getTable("People_contact");
		$people_alias = $q->get_table_alias("People_contact");
		if ($people_alias == null) {
			$people_alias = $q->table_id();
			$q->join($table_alias, "People_contact", array("id"=>"contact"), $people_alias);
		}
		$q->field($people_alias, "people", "PEOPLE_CONTACT_FIELD");
		$where = $q->save_where();
		$table_people->prepareSelectToDetermineWriteAccess($q, $people_alias); 
		$where_people = $q->save_where();
		
		$table_org = Datamodel::get()->getTable("Organization_contact");
		$org_alias = $q->get_table_alias("Organization_contact");
		if ($org_alias == null) {
			$org_alias = $q->table_id();
			$q->join($table_alias, "Organization_contact", array("id"=>"contact"), $org_alias);
		}
		$q->field($org_alias, "organization", "ORG_CONTACT_FIELD");
		$table_org->prepareSelectToDetermineWriteAccess($q, $org_alias); 
		$where_org = $q->save_where();

		$q->where($where);
		$where = "((`".$people_alias."`.people IS NOT NULL";
		if ($where_people <> "") $where .= " AND ".$where_people;
		$where .= ") OR (`".$org_alias."`.organization IS NOT NULL";
		if ($where_org <> "") $where .= " AND ".$where_org;
		$where .= "))";
		$q->where($where);
	},function($rows) {
		$people_rows = array();
		$org_rows = array();
		foreach ($rows as $row) {
			if ($row["PEOPLE_CONTACT_FIELD"] <> null) array_push($people_rows, $row);
			if ($row["ORG_CONTACT_FIELD"] <> null) array_push($org_rows, $row);
		}
		$final = array();
		$table_people = Datamodel::get()->getTable("People_contact");
		array_push($final, $table_people->filterWriteAccess($people_rows)); 
		$table_org = Datamodel::get()->getTable("Organization_contact");
		array_push($final, $table_org->filterWriteAccess($org_rows));
		return $final; 
	})
	->addRemoveFilter(function(&$q, $table_alias, &$locks){
		$table_people = Datamodel::get()->getTable("People_contact");
		$people_alias = $q->get_table_alias("People_contact");
		if ($people_alias == null) {
			$people_alias = $q->table_id();
			$q->join($table_alias, "People_contact", array("id"=>"contact"), $people_alias);
		}
		$q->field($people_alias, "people", "PEOPLE_CONTACT_FIELD");
		$where = $q->save_where();
		$table_people->prepareSelectToDetermineRemoveAccess($q, $people_alias, $locks);
		$where_people = $q->save_where();
		
		$table_org = Datamodel::get()->getTable("Organization_contact");
		$org_alias = $q->get_table_alias("Organization_contact");
		if ($org_alias == null) {
			$org_alias = $q->table_id();
			$q->join($table_alias, "Organization_contact", array("id"=>"contact"), $org_alias);
		}
		$q->field($org_alias, "organization", "ORG_CONTACT_FIELD");
		$table_org->prepareSelectToDetermineRemoveAccess($q, $org_alias, $locks);
		$where_org = $q->save_where();
		
		$q->where($where);
		$where = "((`".$people_alias."`.people IS NOT NULL";
		if ($where_people <> "") $where .= " AND ".$where_people;
		$where .= ") OR (`".$org_alias."`.organization IS NOT NULL";
		if ($where_org <> "") $where .= " AND ".$where_org;
		$where .= "))";
		$q->where($where);
		
		require_once("component/data_model/DataBaseLock.inc");
		$locked_by = null;
		$lock_id = DataBaseLock::lock_table("People_contact", $locked_by);
		if ($lock_id <> null) array_push($locks, $lock_id);
		else throw new Exception("The contacts are already locked by ".$locked_by.". Please try again later.");
		$locked_by = null;
		$lock_id = DataBaseLock::lock_table("Organization_contact", $locked_by);
		if ($lock_id <> null) array_push($locks, $lock_id);
		else throw new Exception("The contacts are already locked by ".$locked_by.". Please try again later.");
	}, function($rows) {
		$people_rows = array();
		$org_rows = array();
		foreach ($rows as $row) {
			if ($row["PEOPLE_CONTACT_FIELD"] <> null) array_push($people_rows, $row);
			if ($row["ORG_CONTACT_FIELD"] <> null) array_push($org_rows, $row);
		}
		$final = array();
		$table_people = Datamodel::get()->getTable("People_contact");
		foreach ($table_people->filterWriteAccess($people_rows) as $row) array_push($final, $row);
		$table_org = Datamodel::get()->getTable("Organization_contact");
		foreach ($table_org->filterWriteAccess($org_rows) as $row) array_push($final, $row);
		return $final;
	})
	;
	
$model->addTable("People_contact")
	->addForeignKey("contact","Contact",true,true,true,false)
	->addForeignKey("people","People",true,false,true,false)
	->addReadFilter(function(&$q, $table_alias) {
		$table = Datamodel::get()->getTable("People");
		$people_alias = $q->get_table_alias("People");
		if ($people_alias == null) {
			$people_alias = $q->table_id();
			$q->join($table_alias, "People", array("people"=>"id"), $people_alias);
		}
		$table->filterReadAccess($q, $people_alias);
	})
	->addRemoveFilter(function(&$q,$table_alias,&$locks){
		$table = Datamodel::get()->getTable("People");
		$people_alias = $q->get_table_alias("People");
		if ($people_alias == null) {
			$people_alias = $q->table_id();
			$q->join($table_alias, "People", array("people"=>"id"), $people_alias);
		}
		$table->prepareSelectToDetermineRemoveAccess($q, $people_alias, $locks);
	},function($rows){
		$table = Datamodel::get()->getTable("People");
		return $table->filterRemoveAccess($rows);
	})
	->addInsertFilter(function($values){
		$q = SQLQuery::create();
		$table_alias = $q->table_id();
		$q->select(array("People"=>$table_alias));
		$q->where_value($table_alias, "id", $values["people"]);
		$table = DataModel::get()->getTable("People");
		$table->prepareSelectToDetermineWriteAccess($q, $table_alias);
		$rows = $q->execute();
		$rows = $table->filterWriteAccess($rows);
		if (count($rows) == 0) return false;
		return true;
	})
	->setDisplayHandler("people", new ContactDisplayHandler("people"))
	;
	
$model->addTable("Postal_address")
	->addPrimaryKey("id")
	->addForeignKey("country","Country",true,false,true,true)
	->addForeignKey("geographic_area","Geographic_area",false,false,true,true)
	->addString("street",100,0,true,false)
	->addString("street_number",50,0,true,false)
	->addString("building",100,0,true,false)
	->addString("unit",100,0,true,false)
	->addString("additional",100,0,true,false)
	->addString("address_type",100,0,true,false)
	->addReadFilter(function(&$q, $table_alias) {
		$table_people = Datamodel::get()->getTable("People_address");
		$people_alias = $q->get_table_alias("People_address");
		if ($people_alias == null) {
			$people_alias = $q->table_id();
			$q->join($table_alias, "People_address", array("id"=>"address"), $people_alias);
		}
		$table_people->filterReadAccess($q, $people_alias);

		$table_org = Datamodel::get()->getTable("Organization_address");
		$org_alias = $q->get_table_alias("Organization_address");
		if ($org_alias == null) {
			$org_alias = $q->table_id();
			$q->join($table_alias, "Organization_address", array("id"=>"address"), $org_alias);
		}
		$table_org->filterReadAccess($q, $org_alias);

		$q->where("(`".$people_alias."`.people IS NOT NULL OR `".$org_alias."`.organization IS NOT NULL)");
	})
	->addWriteFilter(function(&$q, $table_alias){
		$table_people = Datamodel::get()->getTable("People_address");
		$people_alias = $q->get_table_alias("People_address");
		if ($people_alias == null) {
			$people_alias = $q->table_id();
			$q->join($table_alias, "People_address", array("id"=>"address"), $people_alias);
		}
		$q->field($people_alias, "people", "PEOPLE_ADDRESS_FIELD");
		$table_people->prepareSelectToDetermineWriteAccess($q, $people_alias); 
		
		$table_org = Datamodel::get()->getTable("Organization_address");
		$org_alias = $q->get_table_alias("Organization_address");
		if ($org_alias == null) {
			$org_alias = $q->table_id();
			$q->join($table_alias, "Organization_address", array("id"=>"address"), $org_alias);
		}
		$q->field($org_alias, "organization", "ORG_ADDRESS_FIELD");
		$table_org->prepareSelectToDetermineWriteAccess($q, $org_alias); 
	},function($rows) {
		$people_rows = array();
		$org_rows = array();
		foreach ($rows as $row) {
			if ($row["PEOPLE_ADDRESS_FIELD"] <> null) array_push($people_rows, $row);
			if ($row["ORG_ADDRESS_FIELD"] <> null) array_push($org_rows, $row);
		}
		$final = array();
		$table_people = Datamodel::get()->getTable("People_address");
		array_push($final, $table_people->filterWriteAccess($people_rows)); 
		$table_org = Datamodel::get()->getTable("Organization_address");
		array_push($final, $table_org->filterWriteAccess($org_rows));
		return $final; 
	})
	->addRemoveFilter(function(&$q, $table_alias, &$locks){
		$table_people = Datamodel::get()->getTable("People_address");
		$people_alias = $q->get_table_alias("People_address");
		if ($people_alias == null) {
			$people_alias = $q->table_id();
			$q->join($table_alias, "People_address", array("id"=>"address"), $people_alias);
		}
		$q->field($people_alias, "people", "PEOPLE_ADDRESS_FIELD");
		$table_people->prepareSelectToDetermineRemoveAccess($q, $people_alias, $locks);
		
		$table_org = Datamodel::get()->getTable("Organization_address");
		$org_alias = $q->get_table_alias("Organization_address");
		if ($org_alias == null) {
			$org_alias = $q->table_id();
			$q->join($table_alias, "Organization_address", array("id"=>"address"), $org_alias);
		}
		$q->field($org_alias, "organization", "ORG_ADDRESS_FIELD");
		$table_org->prepareSelectToDetermineRemoveAccess($q, $org_alias, $locks);

		require_once("component/data_model/DataBaseLock.inc");
		$locked_by = null;
		$lock_id = DataBaseLock::lock_table("People_address", $locked_by);
		if ($lock_id <> null) array_push($locks, $lock_id);
		else throw new Exception("The addresses are already locked by ".$locked_by.". Please try again later.");
		$locked_by = null;
		$lock_id = DataBaseLock::lock_table("Organization_address", $locked_by);
		if ($lock_id <> null) array_push($locks, $lock_id);
		else throw new Exception("The addresses are already locked by ".$locked_by.". Please try again later.");
	}, function($rows) {
		$people_rows = array();
		$org_rows = array();
		foreach ($rows as $row) {
			if ($row["PEOPLE_ADDRESS_FIELD"] <> null) array_push($people_rows, $row);
			if ($row["ORG_ADDRESS_FIELD"] <> null) array_push($org_rows, $row);
		}
		$final = array();
		$table_people = Datamodel::get()->getTable("People_address");
		foreach ($table_people->filterWriteAccess($people_rows) as $row) array_push($final, $row);
		$table_org = Datamodel::get()->getTable("Organization_address");
		foreach ($table_org->filterWriteAccess($org_rows) as $row) array_push($final, $row);
		return $final;
	})
	; // no direct access allowed
	
$model->addTable("People_address")
	->addForeignKey("address","Postal_address",true,true,true,false)
	->addForeignKey("people","People",true,false,true,false)
	->setDisplayHandler("people", new AddressDisplayHandler("people"))
	->addReadFilter(function(&$q, $table_alias) {
		$table = Datamodel::get()->getTable("People");
		$people_alias = $q->get_table_alias("People");
		if ($people_alias == null) {
			$people_alias = $q->table_id();
			$q->join($table_alias, "People", array("people"=>"id"), $people_alias);
		}
		$table->filterReadAccess($q, $people_alias);
	})
	->addRemoveFilter(function(&$q,$table_alias, &$locks){
		$table = Datamodel::get()->getTable("People");
		$people_alias = $q->get_table_alias("People");
		if ($people_alias == null) {
			$people_alias = $q->table_id();
			$q->join($table_alias, "People", array("people"=>"id"), $people_alias);
		}
		$table->prepareSelectToDetermineRemoveAccess($q, $people_alias, $locks);
	},function($rows){
		$table = Datamodel::get()->getTable("People");
		return $table->filterRemoveAccess($rows);
	})
	->addInsertFilter(function($values){
		$q = SQLQuery::create();
		$table_alias = $q->table_id();
		$q->select(array("People"=>$table_alias));
		$q->where_value($table_alias, "id", $values["people"]);
		$table = DataModel::get()->getTable("People");
		$table->prepareSelectToDetermineWriteAccess($q, $table_alias);
		$rows = $q->execute();
		$rows = $table->filterWriteAccess($rows);
		if (count($rows) == 0) return false;
		return true;
	})
	;

$model->addTable("Organization")
	->addPrimaryKey("id")
	->addString("name",100,1,false,false)
	->addString("creator",100,1,false,false)
	->addReadFilter(function(&$q, $table_alias){
		require_once("component/contact/OrganizationPlugin.inc");
		$allowed = array();
		foreach (PNApplication::$instance->components as $c) {
			if (!($c instanceof OrganizationPlugin)) continue;
			if ($c->canReadOrganization()) array_push($allowed, $c->getOrganizationCreator());
		}
		$w = "(";
		$first = true;
		foreach ($allowed as $creator) {
			if ($first) $first = false; else $w .= " OR ";
			$w .= "`".$table_alias."`.`creator`='".SQLQuery::escape($creator)."'";
		}
		$w .= ")";
		$q->where($w);
	})
	->addWriteFilter(function(&$q, $table_alias){
		$q->field($table_alias, "creator", "ORG_CREATOR");
	},function($rows){
		$allowed = array();
		foreach (PNApplication::$instance->components as $c) {
			if (!($c instanceof OrganizationPlugin)) continue;
			if ($c->canWriteOrganization()) array_push($allowed, $c->getOrganizationCreator());
		}
		$final = array();
		foreach ($rows as $row)
			if (in_array($row["ORG_CREATOR"], $allowed))
				array_push($final, $row);
		return $final;
	})
	->addInsertFilter(function($values){
		if (!isset($values["creator"])) return false;
		foreach (PNApplication::$instance->components as $c) {
			if (!($c instanceof OrganizationPlugin)) continue;
			if ($c->getOrganizationCreator() == $values["creator"])
				return $c->canInsertOrganization();
		}
		return false;
	})
	->addRemoveFilter(function(&$q, $table_alias, &$locks){
		$q->field($table_alias, "creator", "ORG_CREATOR");
		// TODO locks
	},function($rows){
		$allowed = array();
		foreach (PNApplication::$instance->components as $c) {
			if (!($c instanceof OrganizationPlugin)) continue;
			if ($c->canRemoveOrganization()) array_push($allowed, $c->getOrganizationCreator());
		}
		$final = array();
		foreach ($rows as $row)
			if (in_array($row["ORG_CREATOR"], $allowed))
			array_push($final, $row);
		return $final;
	})
	->setDisplayHandler(null, new datamodel\FieldsDisplayHandler("Contacts", "Contacts", array(
		"name" => "Name",
		"creator" => "Managed by",
	), array(
		"creator"=>array("not_editable")
	)))
	;
$model->addDataCategoryLink("Contacts", "/dynamic/contact/page/organization_profile?organization=%Organization.id%");

$model->addTable("Organization_type")
	->addPrimaryKey("id")
	->addString("name",100,1,false,false)
	->addString("creator",100,1,false,false)
	->addReadFilter(function(&$q, $table_alias){
		require_once("component/contact/OrganizationPlugin.inc");
		$allowed = array();
		foreach (PNApplication::$instance->components as $c) {
			if (!($c instanceof OrganizationPlugin)) continue;
			if ($c->canReadOrganization()) array_push($allowed, $c->getOrganizationCreator());
		}
		$w = "(";
		$first = true;
		foreach ($allowed as $creator) {
			if ($first) $first = false; else $w .= " OR ";
			$w .= "`".$table_alias."`.`creator`='".SQLQuery::escape($creator)."'";
		}
		$w .= ")";
		$q->where($w);
	})
	->addWriteFilter(function(&$q, $table_alias){
		$q->field($table_alias, "creator", "ORG_TYPE_CREATOR");
	},function($rows){
		$allowed = array();
		foreach (PNApplication::$instance->components as $c) {
			if (!($c instanceof OrganizationPlugin)) continue;
			if ($c->canWriteOrganization()) array_push($allowed, $c->getOrganizationCreator());
		}
		$final = array();
		foreach ($rows as $row)
			if (in_array($row["ORG_TYPE_CREATOR"], $allowed))
				array_push($final, $row);
		return $final;
	})
	->addInsertFilter(function($values){
		if (!isset($values["creator"])) return false;
		foreach (PNApplication::$instance->components as $c) {
			if (!($c instanceof OrganizationPlugin)) continue;
			if ($c->getOrganizationCreator() == $values["creator"])
				return $c->canInsertOrganization();
		}
		return false;
	})
	->addRemoveFilter(function(&$q, $table_alias, &$locks){
		$q->field($table_alias, "creator", "ORG_TYPE_CREATOR");
		// TODO locks
	},function($rows){
		$allowed = array();
		foreach (PNApplication::$instance->components as $c) {
			if (!($c instanceof OrganizationPlugin)) continue;
			if ($c->canRemoveOrganization()) array_push($allowed, $c->getOrganizationCreator());
		}
		$final = array();
		foreach ($rows as $row)
			if (in_array($row["ORG_TYPE_CREATOR"], $allowed))
			array_push($final, $row);
		return $final;
	})
	;

$model->addTable("Organization_types")
	->addForeignKey("organization","Organization",true,false,true,false)
	->addForeignKey("type","Organization_type",true,false,true,false)
	->setKey(array("organization","type"))
	->addReadFilter(function(&$q, $table_alias){
		$org_alias = $q->get_table_alias("Organization");
		if ($org_alias == null) {
			$org_alias = $q->table_id();
			$q->join($table_alias, "Organization", array("organization"=>"id"), $org_alias);
		}

		require_once("component/contact/OrganizationPlugin.inc");
		$allowed = array();
		foreach (PNApplication::$instance->components as $c) {
			if (!($c instanceof OrganizationPlugin)) continue;
			if ($c->canReadOrganization()) array_push($allowed, $c->getOrganizationCreator());
		}
		$w = "(";
		$first = true;
		foreach ($allowed as $creator) {
			if ($first) $first = false; else $w .= " OR ";
			$w .= "`".$org_alias."`.`creator`='".SQLQuery::escape($creator)."'";
		}
		$w .= ")";
		$q->where($w);
	})
	->addWriteFilter(function(&$q, $table_alias){
		$org_alias = $q->get_table_alias("Organization");
		if ($org_alias == null) {
			$org_alias = $q->table_id();
			$q->join($table_alias, "Organization", array("organization"=>"id"), $org_alias);
		}
		$q->field($org_alias, "creator", "ORG_CREATOR");
	},function($rows){
		$allowed = array();
		foreach (PNApplication::$instance->components as $c) {
			if (!($c instanceof OrganizationPlugin)) continue;
			if ($c->canWriteOrganization()) array_push($allowed, $c->getOrganizationCreator());
		}
		$final = array();
		foreach ($rows as $row)
			if (in_array($row["ORG_CREATOR"], $allowed))
				array_push($final, $row);
		return $final;
	})
	->addInsertFilter(function($values){
		try {
			$org = SQLQuery::create()->select("Organization")->where_value("Organization", "id", $values["organization"])->execute_single_row();
		} catch (Exception $e) { return false; }
		if ($org == null) return false;
		
		foreach (PNApplication::$instance->components as $c) {
			if (!($c instanceof OrganizationPlugin)) continue;
			if ($c->getOrganizationCreator() == $org["creator"])
				return $c->canInsertOrganization();
		}
		return false;
	})
	->addRemoveFilter(function(&$q, $table_alias, &$locks){
		$org_alias = $q->get_table_alias("Organization");
		if ($org_alias == null) {
			$org_alias = $q->table_id();
			$q->join($table_alias, "Organization", array("organization"=>"id"), $org_alias);
		}
		$q->field($org_alias, "creator", "ORG_CREATOR");
		// TODO locks
	},function($rows){
		$allowed = array();
		foreach (PNApplication::$instance->components as $c) {
			if (!($c instanceof OrganizationPlugin)) continue;
			if ($c->canRemoveOrganization()) array_push($allowed, $c->getOrganizationCreator());
		}
		$final = array();
		foreach ($rows as $row)
			if (in_array($row["ORG_CREATOR"], $allowed))
			array_push($final, $row);
		return $final;
	})
	;

$model->addTable("Organization_contact")
	->addForeignKey("organization","Organization",true,false,true,false)
	->addForeignKey("contact","Contact",true,false,true,false)
	->addReadFilter(function(&$q, $table_alias) {
		$table = Datamodel::get()->getTable("Organization");
		$org_alias = $q->get_table_alias("Organization");
		if ($org_alias == null) {
			$org_alias = $q->table_id();
			$q->join($table_alias, "Organization", array("organization"=>"id"), $org_alias);
		}
		$table->filterReadAccess($q, $org_alias);
	})
	->addRemoveFilter(function(&$q,$table_alias,&$locks){
		$table = Datamodel::get()->getTable("Organization");
		$org_alias = $q->get_table_alias("Organization");
		if ($org_alias == null) {
			$org_alias = $q->table_id();
			$q->join($table_alias, "Organization", array("organization"=>"id"), $org_alias);
		}
		$table->prepareSelectToDetermineRemoveAccess($q, $org_alias, $locks);
	},function($rows){
		$table = Datamodel::get()->getTable("Organization");
		return $table->filterRemoveAccess($rows);
	})
	->addInsertFilter(function($values){
		$q = SQLQuery::create();
		$table_alias = $q->table_id();
		$q->select(array("Organization"=>$table_alias));
		$q->where_value($table_alias, "id", $values["organization"]);
		$table = DataModel::get()->getTable("Organization");
		$table->prepareSelectToDetermineWriteAccess($q, $table_alias);
		$rows = $q->execute();
		$rows = $table->filterWriteAccess($rows);
		if (count($rows) == 0) return false;
		return true;
	})
	->setDisplayHandler("organization", new ContactDisplayHandler("organization"))
	;
	
$model->addTable("Organization_address")
	->addForeignKey("organization","Organization",true,false,true,false)
	->addForeignKey("address","Postal_address",true,false,true,false)
	->setDisplayHandler("organization", new AddressDisplayHandler("organization"))
	->addReadFilter(function(&$q, $table_alias) {
		$table = Datamodel::get()->getTable("Organization");
		$org_alias = $q->get_table_alias("Organization");
		if ($org_alias == null) {
			$org_alias = $q->table_id();
			$q->join($table_alias, "Organization", array("organization"=>"id"), $org_alias);
		}
		$table->filterReadAccess($q, $org_alias);
	})
	->addRemoveFilter(function(&$q,$table_alias, &$locks){
		$table = Datamodel::get()->getTable("Organization");
		$org_alias = $q->get_table_alias("Organization");
		if ($org_alias == null) {
			$org_alias = $q->table_id();
			$q->join($table_alias, "Organization", array("organization"=>"id"), $org_alias);
		}
		$table->prepareSelectToDetermineRemoveAccess($q, $org_alias, $locks);
	},function($rows){
		$table = Datamodel::get()->getTable("Organization");
		return $table->filterRemoveAccess($rows);
	})
	->addInsertFilter(function($values){
		$q = SQLQuery::create();
		$table_alias = $q->table_id();
		$q->select(array("Organization"=>$table_alias));
		$q->where_value($table_alias, "id", $values["organization"]);
		$table = DataModel::get()->getTable("Organization");
		$table->prepareSelectToDetermineWriteAccess($q, $table_alias);
		$rows = $q->execute();
		$rows = $table->filterWriteAccess($rows);
		if (count($rows) == 0) return false;
		return true;
	})
	;
	
$model->addTable("Contact_point")
	->addForeignKey("organization","Organization",true,false,true,false)
	->addForeignKey("people","People",true,false,true,false)
	->setKey(array("organization","people"))
	->addString("designation",100,0,true,false)
	->addReadFilter(function(&$q, $table_alias) {
		$table = Datamodel::get()->getTable("Organization");
		$org_alias = $q->get_table_alias("Organization");
		if ($org_alias == null) {
			$org_alias = $q->table_id();
			$q->join($table_alias, "Organization", array("organization"=>"id"), $org_alias);
		}
		$table->filterReadAccess($q, $org_alias);
	})
	->addRemoveFilter(function(&$q,$table_alias, &$locks){
		$table = Datamodel::get()->getTable("Organization");
		$org_alias = $q->get_table_alias("Organization");
		if ($org_alias == null) {
			$org_alias = $q->table_id();
			$q->join($table_alias, "Organization", array("organization"=>"id"), $org_alias);
		}
		$table->prepareSelectToDetermineRemoveAccess($q, $org_alias, $locks);
	},function($rows){
		$table = Datamodel::get()->getTable("Organization");
		return $table->filterRemoveAccess($rows);
	})
	->addInsertFilter(function($values){
		$q = SQLQuery::create();
		$table_alias = $q->table_id();
		$q->select(array("Organization"=>$table_alias));
		$q->where_value($table_alias, "id", $values["organization"]);
		$table = DataModel::get()->getTable("Organization");
		$table->prepareSelectToDetermineWriteAccess($q, $table_alias);
		$rows = $q->execute();
		$rows = $table->filterWriteAccess($rows);
		if (count($rows) == 0) return false;
		return true;
	})
	->setDisplayHandler(null, new datamodel\StopDataDisplayHandler("Organization", "Contacts Points")) // TODO
	;