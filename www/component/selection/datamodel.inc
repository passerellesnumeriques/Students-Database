<?php
/* @var $model DataModel */
	$model->addTable("SelectionCampaign")
		->addPrimaryKey("id")
		->addString("name")
		->addForeignKey("calendar","Calendar",false,true,false,false)
		->addReadAccess("can_access_selection_data",true)
		->addWriteAccess("manage_selection_campaign",true)
		->addInsertAccess("manage_selection_campaign",true)
		->addRemoveAccess("manage_selection_campaign",true);
		
	$sm = $model->addSubModel("SelectionCampaign");
	
	$sm->addTable("Information_session")
		->addPrimaryKey("id")
		->addForeignKey("date","CalendarEvent",false,false,true,true)
		->addForeignKey("postal_address","Postal_address",true,false,true,true)
		//This field exists in case the user wants to set an address but no host partner
		// In case a host partner is choosen, this field must be set to null
		->addForeignKey("fake_organization","Organization",true,false,false,true)
		//This field is set to link the custom postal address to an organization
		//Otherwize, the filter of the "contact" component datamodel wont let the user see/manage the address
		->addInteger("number_boys_expected",10)
		->addInteger("number_girls_expected",10)
		->addInteger("number_boys_real",10)
		->addInteger("number_girls_real",10)
		->addString("name")
		->addWriteAccess("edit_information_session",true)
		->addReadAccess("see_information_session_details",true)
		->addInsertAccess("manage_information_session",true)
		->addRemoveAccess("manage_information_session",true);
		
	$sm->addTable("Information_session_partner")
		->addForeignKey("information_session","Information_session",true,false,true,false)
		->addForeignKey("organization","Organization",true,false,true,false)
		->addBoolean("host") //this attributes means that this partner is the one where the IS takes place
		->addForeignKey("host_address","Postal_address",false,false,true,true) //this attributes is set only when host attribute == true: can select a praticular address in case the organization has several ones
		->setKey(array("information_session","organization"))
		->addWriteAccess("edit_information_session",true)
		->addReadAccess("see_information_session_details",true)
		->addInsertAccess("manage_information_session",true)
		->addRemoveAccess("manage_information_session",true);
		
	$sm->addTable("Information_session_contact_point")
		->addForeignKey("information_session","Information_session",true,false,true,false)
		->addForeignKey("organization","Organization",true,false,true,false)
		//This two attributes are a foreignKey for IS partner table
		->addForeignKey("people","People",true,false,true,false)
		//People + orga = foreignKey for contact_point table
		->setKey(array("information_session","organization","people"))
		->addWriteAccess("edit_information_session",true)
		->addReadAccess("see_information_session_details",true)
		->addInsertAccess("manage_information_session",true)
		->addRemoveAccess("manage_information_session",true)
//TODO add a field "remark"		
		// ->setDisplayHandler("people", new datamodel\StopDataDisplayHandler("Selection", "Selection"))
		;		
		
	$sm->addTable("Selection_campaign_config")
		->addString("name",255,1,false,true)//the name must be unique (key...)
		->addString("value",255,1,false,false)
		->addReadAccess("can_access_selection_data",true)
		->addInsertAccess("manage_selection_campaign",true)
		->addRemoveAccess("manage_selection_campaign",true)
		->addWriteAccess("manage_selection_campaign",true)
		->setPrimary("name");