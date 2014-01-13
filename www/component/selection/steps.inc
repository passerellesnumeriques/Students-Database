<?php
/**
 * Return an array with all the steps for a selection campaign
 * The steps order is the same as the one in the selection process
 * Each step is defined by an array which contains:
 * key: step name (unique)
 * [0]: explanations: will be displayed on the selection dashboard
 * [1]: table name: if this table is empty, this step is set as false, else as true
 * [2]: dependencies: all the steps that must be already set as true (respectively false) before setting this one as TRUE
 * 		each dependency is an array that contains:
 * 			[0]: the step name
 *			[1]: the requested value
 * 			[2]: the error message displayed in case this dependency is not respected
 *			[3]: the actions restricted / allowed by this validated step on the table given by this depedency
 *
 * Notes: 
 * (0): in case a step is validated, we shall check in the dependencies of all the already validated steps
 *	that this step can be unvalidated (cf #selection#canUpdateStep method)
 *
 * (1): the 4th parameter of the dependency array is set to allow the user to step back
 * For instance, during the Selection Process, the user can import interview results after each
 * interviewing session, whereas he still wants to import applicants (case using the clickers for instance)
 * In that case, the user must be able to add and update an applicant but not to remove any
 */
return array(
	"information_session" => array("Some informations sessions have been planed","Information_session",array()),
	"import_applicant" => array("Some applicants have been imported","Applicant",array()),
	"manage_exam" => array("Some exam subjects have been created","Exam_subject",array()),
	"import_exam_results" => array("Some exam results have been imported","Exam_question_result",array(
			array("manage_exam", true, "Exam results cannot be imported because no exam is set into the database",array("add" => false,"remove" => false, "update" => false)), //exam must be the same for each applicant
			array("import_applicant",true,"Exam results cannot be imported because no applicant is set in the database",array("add" => true,"remove" => false, "update" => true)), //can update tha applicant info even if he already has results
		)),
	"define_topic_for_eligibility_rules" => array("Some topics for eligibility rules have been defined","Exam_topic_for_eligibility_rule", array(
			// array("manage_exam", true, "Eligibility rules cannot be defined because no exam is set in the database"),
			array(),
		)),
	//TODO define_eligibility_rules: dependencies => array("apply_eligibility_rules" => false)??
	);
?>