<?php
/* @var $model DataModel */

$model->addTable("SelectionCampaign")
	->addPrimaryKey("id")
	->addString("name")
	->addBoolean("frozen",false)
	->addEnum("frozen_reason", array("Campaign Finished","Selection Team Travelling"), true, false)
	->addForeignKey("calendar","Calendar",false,false,false,false)//Not automatically removed, must use the calendar functionalities
	->addForeignKey("batch", "StudentBatch", false, false, false, true)
	->addReadAccess("can_access_selection_data",true)
	->addWriteAccess("manage_selection_campaign",true)
	->addInsertAccess("manage_selection_campaign",true)
	->addRemoveAccess("manage_selection_campaign",true)
 	->onInsert(function($inserted) {
 		foreach ($inserted as $row)
			try {
				PNApplication::$instance->news->post("selection", null, array("campaign".$row["id"]), "activity", "Creation of campaign <i>".$row["fields_values"]["name"]."</i>");
			} catch (Exception $e) { PNApplication::error($e); }
 	})
	;
		
$sm = $model->addSubModel("SelectionCampaign");

$sm->addTable("SelectionCampaignConfig")
	->addString("name",255,1,false,true)//the name must be unique (key...)
	->addString("value",255,1,false,false)
	->setRoot()
	->addReadAccess("can_access_selection_data",true)
	->addInsertAccess("manage_selection_campaign",true)
	->addRemoveAccess("manage_selection_campaign",true)
	->addWriteAccess("manage_selection_campaign",true)
	->setPrimary("name");

$sm->addTable("StaffStatus")
	->addForeignKey("people", "People", true, false, false, false, true)
	->setPrimary("people")
	->addBoolean("is",false)
	->addBoolean("exam",false)
	->addBoolean("interview",false)
	->addBoolean("si",false)
	->addString("comment",2000,0,true,false)
	->addReadAccess("can_access_selection_data",true)
	->addWriteAccess("manage_selection_campaign",true)
	->addInsertAccess("manage_selection_campaign",true)
	->addRemoveAccess("manage_selection_campaign",true)
	;

$sm->addTable("TravelVersion")
	->addString("uid", 50, 30, false, true)
	->setKey("uid")
	->addForeignKey("user", "Users", true, false, false)
	->addString("synch_key", 50, 30, false, false)
	->addForeignKey("database_diff", "Storage", false, false, false)
	;
$sm->addTable("TravelVersionSynchStorage")
	->addForeignKey("storage", "Storage", false, false, false, false, true)
	->setKey("storage")
	->addBoolean("new", false)
	->addInteger("id",32,1,null,false,false)
	;

include "datamodel_is.inc";
include "datamodel_exam.inc";
include "datamodel_interview.inc";
include "datamodel_applicant.inc";		
include "datamodel_applicant_exam.inc";
include "datamodel_applicant_interview.inc";
include "datamodel_si.inc";
include "datamodel_trip.inc";
?>