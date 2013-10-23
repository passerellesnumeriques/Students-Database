<?php
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
		->restrictRemoveAccess("edit_people_details",true);
		
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
		->restrictRemoveAccess("edit_people_details",true);
		
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