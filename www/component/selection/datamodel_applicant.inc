<?php
/* @var $model DataModel */
/* @var $sm SubDataModel */

$sm->addTable("Applicant")
	->addForeignKey("people", "People", true, false, false, false, true)
	->addInteger("applicant_id",32,1,null,false,true)//applicant_id must be a number
	->addString("exclusion_reason", 100, 1, true)
	->addEnum("automatic_exclusion_step", array("Application Form","Written Exam","Interview","Social Investigation"))
	->addString("automatic_exclusion_reason",255,0,true,false)//This field must be filled up by functionalities ("missing",...) to explain the automatic exclusion step
	->addString("custom_exclusion",255,0,true,false)//This field can be filled up by user
	->addBoolean("excluded",false)
	->addForeignKey("high_school", "Organization", false, false, true)
	->addForeignKey("ngo", "Organization", false, false, true)
	->addForeignKey("information_session", "InformationSession", false, false, true, true, false)//An applicant can be assigned to an information session
	->addForeignKey("exam_center", "ExamCenter", false, false, true, true, false)//An applicant can be assigned to an exam center (maybe after importing applicant data
	->addForeignKey("exam_center_room", "ExamCenterRoom", false, false, true, true, false) //An applicant can be assigned to an exam center room after(maybe after importing applicant data
	->addForeignKey("exam_session", "ExamSession", false, false, true, true, false) //An applicant can be assigned to an exam session after being assigned to an exam center, or directly assigned to a session
	->addForeignKey("interview_center", "InterviewCenter", false, false, true, true, false)//An applicant can be assigned to an exam center (maybe after importing applicant data
	->addForeignKey("interview_session", "InterviewSession", false, false, true, true, false) //An applicant can be assigned to an exam session after being assigned to an exam center, or directly assigned to a session
	->addDecimal("exam_total_score",10,2,0,null,true,false) // ( new June 04th 2014 ) sum of all subject
	->addColumnWriteAccess("information_session", "manage_information_session", true)
	->addReadAccess("can_access_selection_data",true) //All the managing rights must be handled by functionalities
	->addInsertAccess("edit_applicants",true)
	->addRemoveAccess("edit_applicants",true)
	->setPrimary("people")//The primary key is the people_id
	;

$sm->addTable("ApplicantMoreInfo")//This table contains all the custom data (based on the application form content)
	->addForeignKey("people", "People", true, false, false, false, true)
	->customizable("Application Form","edit_application_form_fields")
	->addReadAccess("can_access_selection_data",true) //All the managing rights must be handled by functionalities
	->addWriteAccess("edit_applicants", true)
	->addInsertAccess("edit_applicants",true)
	->addRemoveAccess("edit_applicants",true)
	->setPrimary("people")//The primary key is the people_id
	;

$sm->addSubModelLink("Applicant","People");

?>