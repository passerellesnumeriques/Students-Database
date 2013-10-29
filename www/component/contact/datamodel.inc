<?php
class ContactDisplayHandler extends datamodel\DataDisplayHandler {
	
	public $type;
	
	public function __construct($type) {
		parent::__construct("Personal Information", "Contacts");
		$this->type = $type;
	}
	
	public function getDisplayableData() {
		return array(
			new ContactDataDisplay($this, "email"),
			new ContactDataDisplay($this, "phone"),
			new ContactDataDisplay($this, "IM"),
		);
	}
	
	public function createEntry($data, $sub_model) {
		// TODO ?
		PNApplication::error("createEntry not supported by RolesDisplayHandler");
	}
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
		parent::__construct("Personal Information", "Addresses");
		$this->type = $type;
	}

	public function getDisplayableData() {
		return array(
			new AddressDataDisplay($this),
		);
	}

	public function createEntry($data, $sub_model) {
		// TODO ?
		PNApplication::error("createEntry not supported by RolesDisplayHandler");
	}
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
	->noRestriction(); // TODO check access with generic services
	
$model->addTable("People_contact")
	->addForeignKey("contact","Contact",true,true,true,false)
	->addForeignKey("people","People",true,false,true,false)
	->allowSpecificReadAccess("people",function() { return PNApplication::$instance->user_people->user_people_id; })
	->restrictWriteAccess("edit_people_details",true)
	->restrictReadAccess("see_other_people_details",true)
	->restrictAddAccess("edit_people_details",true)
	->restrictRemoveAccess("edit_people_details",true)
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
	->allowReadAccess()
	->noRestriction();//TODO: set restrictions!!
	
$model->addTable("People_address")
	->addForeignKey("address","Postal_address",true,true,true,false)
	->addForeignKey("people","People",true,false,true,false)
	->allowSpecificReadAccess("people",function() { return PNApplication::$instance->user_people->user_people_id; })
	->restrictWriteAccess("edit_people_details",true)
	->restrictReadAccess("see_other_people_details",true)
	->restrictAddAccess("edit_people_details",true)
	->restrictRemoveAccess("edit_people_details",true)
	->setDisplayHandler("people", new AddressDisplayHandler("people"))
	;
	
$model->addTable("Organization")
	->addPrimaryKey("id")
	->addString("name",100,0,true,false)
	->allowReadAccess()
	->noRestriction();//TODO: set restrictions
	
$model->addTable("Organization_contact")
	->addForeignKey("organization","Organization",true,false,true,false)
	->addForeignKey("contact","Contact",true,false,true,false)
	->allowReadAccess()
	->noRestriction(); //TODO: set restrictions
	
$model->addTable("Organization_address")
	->addForeignKey("organization","Organization",true,false,true,false)
	->addForeignKey("address","Postal_address",true,false,true,false)
	->allowReadAccess()
	->noRestriction(); //TODO: set restrictions
	
$model->addTable("Contact_point")
	->addForeignKey("organization","Organization",true,false,true,false)
	->addForeignKey("people","People",true,false,true,false)
	->addString("designation",100,0,true,false)
	->allowReadAccess()
	->noRestriction(); //TODO set restrictions
	
$model->addTable("Organization_type")
	->addPrimaryKey("id")
	->addString("name",100,1,false,true)
	->allowReadAccess()
	->restrictWriteAccess("edit_organization_type",true)
	->restrictAddAccess("edit_organization_type",true)
	->restrictRemoveAccess("edit_organization_type",true);
	//The access to this table must be restricted
	
$model->addTable("Organization_types")
	->addForeignKey("organization","Organization",true,false,true,false)
	->addForeignKey("type","Organization_type",true,false,true,false)
	->allowReadAccess()
	->noRestriction(); //TODO set restrictions