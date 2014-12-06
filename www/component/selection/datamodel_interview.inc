<?php 
/* @var $model DataModel */
/* @var $sm SubDataModel */

$sm->addTable("InterviewCenter")
	->addPrimaryKey("id")
	->addString("name",255,0,false)//optional
	->addForeignKey("geographic_area", "GeographicArea", true, false, true, false, true)//An exam center must be linked to a geographic area, and this area is unique in this table
	->addReadAccess("see_interview_center", true)
	->addWriteAccess("manage_interview_center", true)
	->addRemoveAccess("manage_interview_center", true)
	->addInsertAccess("manage_interview_center", true)
	->onInsert(function($fields,$id,$sub_model_instance) {
		try {
			PNApplication::$instance->news->post("selection", null, array("campaign".$sub_model_instance), "activity", "New Interview Center: <i>".$fields["name"]."</i>");
		} catch (Exception $e) { PNApplication::error($e); }
	});

$sm->addTable("InterviewCenterPartner")
	->addForeignKey("interview_center","InterviewCenter",true,false,true,false)
	->addForeignKey("organization","Organization",true,false,true,false)
	->addBoolean("host")
	->addForeignKey("host_address","PostalAddress",false,false,true,true) //this attributes is set only when host attribute == true: can select a praticular address in case the organization has several ones
	->setKey(array("interview_center","organization"))
	->addReadAccess("see_interview_center",true)
	->addWriteAccess("manage_interview_center",true)
	->addInsertAccess("manage_interview_center",true)
	->addRemoveAccess("manage_interview_center",true);
$model->internalGetTable("Organization")
	->addReadAccessFromForeignTable("InterviewCenterPartner");
$model->internalGetTable("PostalAddress")
	->addReadAccessFromForeignTable("InterviewCenterPartner");

$sm->addTable("InterviewCenterContactPoint")
	->addForeignKey("interview_center","InterviewCenter",true,false,true,false)
	->addForeignKey("organization","Organization",true,false,true,false)
	//This two attributes are a foreignKey for ExamCenter partner table
	->addForeignKey("people","People",true,false,true,false)
	//People + orga = foreignKey for ContactPoint table
	->setKey(array("interview_center","organization","people"))
	->addReadAccess("see_interview_center",true)
	->addWriteAccess("manage_interview_center",true)
	->addInsertAccess("manage_interview_center",true)
	->addRemoveAccess("manage_interview_center",true)
	;

$sm->addTable("InterviewCenterExamCenter")
	->addForeignKey("interview_center", "InterviewCenter", true, false, true)
	->addForeignKey("exam_center", "ExamCenter", true, false, false,false,true)//An information session can only be linked to one exam center
	->setKey(array("interview_center","exam_center"))
	->addReadAccess("see_interview_center",true)
	->addWriteAccess("manage_interview_center",true)
	->addInsertAccess("manage_interview_center",true)
	->addRemoveAccess("manage_interview_center",true)
	;
		
$sm->addTable("InterviewSession")
	->addForeignKey("event", "CalendarEvent", true, true, false, false, true)
	->addForeignKey("interview_center", "InterviewCenter", true, false, true)//An exam center can have several session
	->addInteger("every_minutes",7,5,120,false,false)
	->addInteger("parallel_interviews",8,0,255,false,false)
	->setPrimary("event")//The event is the primary key of a session
	->addReadAccess("see_interview_center",true)
	->addWriteAccess("manage_interview_center",true)
	->addInsertAccess("manage_interview_center",true)
	->addRemoveAccess("manage_interview_center",true);
$model->internalGetTable("CalendarEvent")
	->addReadAccessFromForeignTable("InterviewSession")
	->addRemoveAccessFromForeignTable("InterviewSession")
	;

$sm->addTable("InterviewCriterion")
	->addPrimaryKey("id")
	->addString("name",100,1,false,true)
	->addDecimal("max_score",3,2,0,null,false,false)
	->addReadAccess("see_interview_criteria", true)
	->addWriteAccess("manage_interview_criteria", true)
	->addInsertAccess("manage_interview_criteria", true)
	->addRemoveAccess("manage_interview_criteria", true)
	;

$sm->addTable("InterviewEligibilityRule")
	->addPrimaryKey("id")
	->addForeignKey("parent", "InterviewEligibilityRule", true, false, false, true)//Can only have one parent. If null, root level
	->addDecimal("expected",3,2,null,null,false,false) // expected grade
	->addReadAccess("see_interview_criteria",true)
	->addWriteAccess("manage_interview_criteria",true)
	->addInsertAccess("manage_interview_criteria",true)
	->addRemoveAccess("manage_interview_criteria",true)
	;

$sm->addTable("InterviewEligibilityRuleCriterion")
	->addForeignKey("rule", "InterviewEligibilityRule", true, false, true)
	->addForeignKey("criterion", "InterviewCriterion", true, false, true, false)
	->addDecimal("coefficient",3,2,null,null,true,false) //optional. If empty, considered as 1
	->setKey(array("rule","criterion"))
	->addReadAccess("see_interview_criteria",true)
	->addWriteAccess("manage_interview_criteria",true)
	->addInsertAccess("manage_interview_criteria",true)
	->addRemoveAccess("manage_interview_criteria",true)
	;

?>