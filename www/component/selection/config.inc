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
	// Information Sessions
	"separate_boys_girls_IS" => array("Separate the boys and girls figures for the information sessions",false,array(true,false),"boolean","Information Session",array()),
	"give_name_to_IS" => array("Be able to set a custom name to the informations sessions",true,array(true,false),"boolean","Information Session",array()),
	// array("give_name_to_IS_event","Be able to set the name of the calendar event related to the information session<br/><i>Note: otherwise, the event will be called \"Information session\"</i>",true,array(true,false),"boolean"),
	"default_duration_IS" => array("Pre-selected duration of an Information Session<br/><i>Note: if you select \"All day\" a session won't have specific starting and ending time</i>","3 hours",array("1 hour","2 hours","3 hours","4 hours","6 hours","8 hours","All day"),"enum","Information Session",array()),
	// Exam Subjects
	"set_correct_answer" => array("Set the correct answer of an exam subject question, and correct automatically applicants' answers",false,array(true,false),"boolean","Exam Subject",array()),
	"use_subject_version" => array("Use different versions of each exam subject to avoid cheating",false,array(true,false),"boolean","Exam Subject",array()),
	// Exam Centers and Sessions
	"give_name_to_exam_center" => array("Be able to set a custom name to the exam centers.<br/><i>Note: otherwise, the exam center name is the geographic area where it is located</i>",true,array(true,false),"boolean","Entrance Examination",array()),
	"give_name_to_exam_center_room" => array("Be able to set a custom name to the exam centers rooms.<br/><i>Note: otherwise, the rooms will be named 1, 2, 3...</i>",true,array(true,false),"boolean","Entrance Examination",array()),
	"default_duration_exam_session" => array("Pre-selected duration of an Exam Session","4 hours",array("1 hour","2 hours","3 hours","4 hours","6 hours","8 hours"),"enum","Entrance Examination",array()),
	// Applications
 	"generate_applicant_id" => array("Automatically generate the selection applicant ID.<br/><i>Note: Otherwize, you will have to manually set it (only <b>numbers</b> are allowed)</i>",true,array(true,false),"boolean","Applicant",array()),
	// "number_of_applicant_id_digits" => array("Number of digits for the generated applicant ID<br><i>Note: be aware that you need at least one ID per student</i>","4",array("4","5","6"),"enum","Applicant",array("generate_applicant_id"=>true)),
	"forbid_too_old_applicants" => array("Set a maximum age for the applicants",false,array(true,false),"boolean","Applicant",array()),
	//the param above is set as false by default because no default date is set, so the user will be obliged to fulfill the date field if he validates that param
	"limit_date_of_birth" => array("Pick the date of birth representing the oldest allowed applicant","",array(),"date","Applicant",array("forbid_too_old_applicants" => true)),
	);
	
	//Note: when adding a new category, update #selection#static#selection_utils#getConfigCategoryIndex
?>