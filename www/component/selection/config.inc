<?php
/**
 * Return an array with all the possibles configs for selection component
 * Each config is defined by an array which contains, index by its name (unique):
 * [0]: explanations: will be displayed on the manage_config page
 * [1]: default value. When a new selection campaign will be created, the config will be set with the default values
 * [2]: array containing all the possible values
 * [3]: {string} type, shall be "boolean" | "enum" | "date"
 * [4]: {string} category name for the manage_config screen
 * 		All the config rows will be gathered by category
 * [5]: dependencies: array(name => value) for the manage_config screen
 *		If the dependencies are not respected, this row wont be displayed
 *		The name can only be the one of a boolean parameter
 */
return array(
	// Applicants
 	"generate_applicant_id" => array("Automatically generate the selection applicant ID.<br/><i>Note: Otherwize, you will have to manually set it (only <b>numbers</b> are allowed)</i>",false,array(true,false),"boolean","Applicants",array()),
	"number_of_applicant_id_digits" => array("Number of digits for the generated applicant ID<br><i>Note: be aware that you need at least one ID per student</i>","4",array("4","5","6"),"enum","Applicants",array("generate_applicant_id"=>true)),
	"forbid_too_old_applicants" => array("Set a maximum age for the applicants",false,array(true,false),"boolean","Applicants",array()),
	//the param above is set as false by default because no default date is set, so the user will be obliged to fulfill the date field if he validates that param
	"limit_date_of_birth" => array("Pick the date of birth representing the oldest allowed applicant<br/><i>Note: if you change the date, and you already created some applicants,<br/>we will check the existing applicants for this selection campaign and exclude<br/>the ones too old, or un-exclude the ones who were previously too old, but are<br/>not anymore with the new date</i>","",array(),"date","Applicants",array("forbid_too_old_applicants" => true)),
		
	// Information Sessions
	"separate_boys_girls_IS" => array("Separate the boys and girls figures for the information sessions",false,array(true,false),"boolean","Information Sessions",array()),
	"give_name_to_IS" => array("Be able to set a custom name to the information sessions",true,array(true,false),"boolean","Information Sessions",array()),
	"default_duration_IS" => array("Pre-selected duration of an Information Session<br/><i>Note: if you select \"All day\" a session won't have specific starting and ending time</i>","3 hours",array("1 hour","2 hours","3 hours","4 hours","6 hours","8 hours","All day"),"enum","Information Sessions",array()),
	
	// Exam Subjects
	"set_correct_answer" => array("Set the correct answer of an exam subject question, and correct automatically applicants' answers",false,array(true,false),"boolean","Exam Subjects",array()),
	"use_subject_version" => array("Use different versions of each exam subject to avoid cheating",false,array(true,false),"boolean","Exam Subjects",array()),
	
	// Exam Centers and Sessions
	"give_name_to_exam_center" => array("Be able to set a custom name to the exam centers.<br/><i>Note: otherwise, the exam center name is the geographic area where it is located</i>",true,array(true,false),"boolean","Exam Centers and Sessions",array()),
	"default_duration_exam_session" => array("Pre-selected duration of an Exam Session","4 hours",array("1 hour","2 hours","3 hours","4 hours","6 hours","8 hours"),"enum","Exam Centers and Sessions",array()),
);
?>