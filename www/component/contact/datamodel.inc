<?php
/* @var $model DataModel */
	$model->addTable("Contact")
		->addPrimaryKey("id")
		->addEnum("type",array("email","phone","IM"),true,false)
		->addString("sub_type",100,0,true,false)
		->addString("contact",100,1,false,false)
		->setDisplayName("Contact")
		->addDisplayableData("type","Contact","Contact Type")
		->addDisplayableData("sub_type","Contact","Contact Sub-Type")
		->addDisplayableData("contact", "Contact", "Contact")
		->allowReadAccess(); // TODO check access with generic services
		
	$model->addTable("People_contact")
		->addForeignKey("contact","Contact",true,false,true,false)
		->addForeignKey("people","People",true,false,true,false)
		->allowSpecificReadAccess("people",function() { return PNApplication::$instance->user_people->user_people_id; })
		->restrictWriteAccess("edit_people_details",true)
		->restrictReadAccess("see_other_people_details",true);
		
	$model->addTable("Postal_address")
		->addPrimaryKey("id")
		->addForeignKey("country","Country",true,false,true,false)
		->addForeignKey("geographic_area","Geographic_area",true,false,true,false)
		->addString("street",100,0,true,false)
		->addString("street_number",50,0,true,false)
		->addString("building",100,0,true,false)
		->addString("unit",100,0,true,false)
		->addString("additional",100,0,true,false)
		->addString("address_type",100,0,true,false)
		->allowReadAccess();
		
	$model->addTable("People_address")
		->addForeignKey("address","Postal_address",true,true,true,false)
		->addForeignKey("people","People",true,false,true,false)
		->allowSpecificReadAccess("people",function() { return PNApplication::$instance->user_people->user_people_id; })
		->restrictWriteAccess("edit_people_details",true)
		->restrictReadAccess("see_other_people_details",true);