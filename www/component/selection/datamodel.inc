<?php
/* @var $model DataModel */

$model->addTable("SelectionCampaign")
		->addPrimaryKey("id")
		->addString("name")
		->addForeignKey("calendar","Calendar",false,false,false,false)//Not automatically removed, must use the calendar functionalities
		->addReadAccess("can_access_selection_data",true)
		->addWriteAccess("manage_selection_campaign",true)
		->addInsertAccess("manage_selection_campaign",true)
		->addRemoveAccess("manage_selection_campaign",true)
	 	->onInsert(function($fields,$id,$sub_model_instance) {
			try {
				PNApplication::$instance->news->post("selection", null, array("campaign".$id), "activity", "Creation of campaign <i>".$fields["name"]."</i>");
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

include "datamodel_is.inc";
include "datamodel_exam.inc";
include "datamodel_interview.inc";
include "datamodel_applicant.inc";		
include "datamodel_applicant_exam.inc";
include "datamodel_applicant_interview.inc";
include "datamodel_trip.inc";
?>