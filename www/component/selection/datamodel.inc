<?php
/* @var $model DataModel */
	$model->addTable("SelectionCampaign")
		->addPrimaryKey("id")
		->addString("name")
		->restrictReadAccess("can_access_selection_data",true)
		->restrictWriteAccess("manage_selection_campaign",true)
		->restrictAddAccess("manage_selection_campaign",true)
		->restrictRemoveAccess("manage_selection_campaign",true);
		
	$sm = $model->addSubModel("SelectionCampaign");
	
	$sm->addTable("Information_session")
		->addPrimaryKey("id")
		->addForeignKey("postal_address","Postal_address",false,false,true,true)
		->addForeignKey("date","CalendarEvent",false,false,true,true)
		->addForeignKey("organization","Organization",false,false,true,true)//This way the IS is linked to one organization: can access its contacts by organization_contact table
		->addInteger("number_expected",10)
		->addInteger("number_real",10)
		->addString("name")
		->restrictWriteAccess("edit_information_session",true)
		->restrictReadAccess("see_information_session_details",true)
		->restrictAddAccess("manage_information_session",true)
		->restrictRemoveAccess("manage_information_session",true);
		
		/**
		 * With this table one IS can be linked to one or several people:
		 * can access its contacts by people_contact table
		 */
		 //Custom contact
	$sm->addTable("Information_session_people")
		->addForeignKey("information_session","Information_session",true,false,true,false)
		->addForeignKey("people","People",true,false,true,false)
		->restrictWriteAccess("edit_information_session",true)
		->restrictReadAccess("see_information_session_details",true)
		->restrictAddAccess("manage_information_session",true)
		->restrictRemoveAccess("manage_information_session",true);
		
		//Contact via organization
	$sm->addTable("Information_session_partner")
		->addForeignKey("information_session","Information_session",true,false,true,true)
		->addForeignKey("organization","Organization",true,false,true,true)
		->addForeignKey("people","People",true,false,true,true) //people => organization + people = primaryKey in contact_point table
		->allowReadAccess()
		->noRestriction();//TODO set restriction
		
	$sm->addTable("Selection_campaign_config")
		->addString("name",255,1,false,true)//the name must be unique (key...)
		->addString("value",255,1,false,false)
		->restrictReadAccess("manage_information_session",true)
		->restrictAddAccess("manage_information_session",true)
		->restrictRemoveAccess("manage_information_session",true);