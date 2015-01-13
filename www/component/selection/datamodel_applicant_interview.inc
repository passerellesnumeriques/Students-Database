<?php
/* @var $model DataModel */
/* @var $sm SubDataModel */

$sm->addTable("ApplicantInterviewCriterionGrade")
	->addForeignKey("people", "People", true, false, true)
	->addForeignKey("criterion", "InterviewCriterion", true, false, true)
	->setKey(array("people","criterion"))
	->addDecimal("grade",4,2,0,null,false,false)
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
	
$sm->addTable("ApplicantInterviewProgramPasser")
	->addForeignKey("applicant", "People", true, false, true, false, false)
	->addForeignKey("program", "SelectionProgram", true, false, true, false, false)
	->setKey(array("applicant","program"))
	->addReadAccess("see_applicant_info", true)
	->addInsertAccess("manage_interview_criteria", true)
	->addInsertAccess("edit_interview_results", true)
	->addRemoveAccess("manage_interview_criteria", true)
	->addRemoveAccess("edit_interview_results", true)
	->addIndex("applicant")
	;

?>