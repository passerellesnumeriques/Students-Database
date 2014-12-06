<?php
/* @var $model DataModel */
/* @var $sm SubDataModel */

$sm->addTable("ApplicantInterviewCriterionGrade")
	->addForeignKey("people", "People", true, false, true)
	->addForeignKey("criterion", "InterviewCriterion", true, false, true)
	->setKey(array("people","criterion"))
	->addReadAccess("can_access_selection_data",true)
	->addRemoveAccessFromForeignTable("Applicant")
	->addRemoveAccessFromForeignTable("People")
	->addRemoveAccessFromForeignTable("InterviewCriterion")
	;

$sm->addTable("ApplicantInterviewer")
	->addForeignKey("applicant", "People", true, false, true)
	->addForeignKey("interviewer", "CalendarEventAttendee", true, false, true)
	->setKey(array("applicant","interviewer"))
	->addReadAccess("can_access_selection_data",true)
	->addRemoveAccessFromForeignTable("Applicant")
	->addRemoveAccessFromForeignTable("People")
	->addRemoveAccessFromForeignTable("CalendarEventAttendee")
	;
	
?>