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
				PNApplication::$instance->news->post("selection", null, array("campaign".$id), "Creation of campaign <i>".$fields["name"]."</i>");
			} catch (Exception $e) { PNApplication::error($e); }
	 	})
		;
		
$sm = $model->addSubModel("SelectionCampaign");

$sm->addTable("Steps")
		->addString("name",255,1,false,true)
		->addBoolean("value",false)
		->setPrimary("name")
		->setRoot()
		->addReadAccess("can_access_selection_data",true)
		->addWriteAccess("can_update_step",true)
		->addRemoveAccess("manage_selection_campaign",true)
		->addInsertAccess("manage_selection_campaign",true);

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
		
$sm->addTable("Applicant")
		->addForeignKey("people", "People", true, false, false, false, true)
		->addInteger("applicant_id",32,1,null,false,true)//applicant_id must be a number
		->addString("exclusion_reason", 100, 1, true)
		->addEnum("automatic_exclusion_step", array("exam","eligibility_rule","interview","social_investigation"))
		->addString("automatic_exclusion_reason",255,0,true,false)//This field must be filled up by functionalities ("missing",...) to explain the automatic exclusion step
		->addString("custom_exclusion",255,0,true,false)//This field can be filled up by user
		->addBoolean("excluded",false)
		->addForeignKey("information_session", "InformationSession", false, false, true, true, false)//An applicant can be assigned to an information session
		->addForeignKey("exam_center", "ExamCenter", false, false, true, true, false)//An applicant can be assigned to an exam center (maybe after importing applicant data
		->addForeignKey("exam_center_room", "ExamCenterRoom", false, false, true, true, false) //An applicant can be assigned to an exam center room after(maybe after importing applicant data
		->addForeignKey("exam_session", "ExamSession", false, false, true, true, false) //An applicant can be assigned to an exam session after being assigned to an exam center, or directly assigned to a session
		->addColumnWriteAccess("information_session", "manage_information_session", true)
		->addReadAccess("can_access_selection_data",true) //All the managing rights must be handled by functionalities
	->addInsertAccess("edit_applicants",true)
	->addRemoveAccess("edit_applicants",true)
		->setPrimary("people");//The primary key is the people_id
		//TODO finish + update steps.inc

$sm->addTable("ApplicantMoreInfo")//This table contains all the custom data (based on the application form content)
		->addForeignKey("people", "People", true, false, false, false, true)
		->addReadAccess("can_access_selection_data",true) //All the managing rights must be handled by functionalities
		->setPrimary("people");//The primary key is the people_id
?>