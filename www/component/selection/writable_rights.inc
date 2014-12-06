<?php 
return array(
	new AccessRightCategory("Selection",array(
		// information session
		new BooleanRight("manage_information_session","Add/Remove/Edit Information Sessions",array(true=>array("see_information_session"=>true))),
		// exam
		new BooleanRight("manage_exam_subject","Edit Exam Subjects",array(true=>array("see_exam_subject"=>true))),
		new BooleanRight("manage_exam_center","Edit Exam centers",array(true=>array("see_exam_center"=>true))),
		new BooleanRight("manage_exam_rules","Edit Exam Eligibility Rules",array(true=>array("manage_exam_subject"=>true,"see_exam_rules"=>true))),
		new BooleanRight("edit_exam_results","Edit Exam Results",array(true=>array("see_exam_center"=>true,"see_exam_subject"=>true,"see_exam_rules"=>true))),
		// interview
		new BooleanRight("manage_interview_center","Manage interview centers",array(true=>array("see_interview_center"=>true))),
		new BooleanRight("manage_interview_criteria","Manage Interview Criteria and Rules",array(true=>array("see_interview_criteria"=>true))),
		new BooleanRight("edit_interview_results","Edit Interview Results",array(true=>array("see_interview_center"=>true,"see_interview_criteria"=>true))),
		// applicants
		new BooleanRight("edit_applicants","Create/Import/Remove/Edit applicants",array(true=>array("see_applicant_info"=>true))),
		new BooleanRight("edit_application_form_fields","Edit the list of information we can specify for each applicant",array(true=>array("edit_applicants"=>true))),
		// general
		new BooleanRight("manage_selection_campaign","Add/Remove/Configure a selection campaign",array(true=>array("can_access_selection_data"=>true))),
		new BooleanRight("manage_trips","Add/Remove/Edit trips",array(true=>array("can_access_selection_data"=>true))),
		new BooleanRight("manage_staff_status","Edit staff status (who can do interviews, investigations...)",array(true=>array("can_access_selection_data"=>true,"consult_staff_list"=>true))),
	)),
);


//TODO update the rights for the calendar in selection.inc#createCampaign



?>