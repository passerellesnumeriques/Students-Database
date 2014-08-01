<?php 
// return array(
	// new AccessRightCategory("Information Session",array(
		// new BooleanRight("manage_exam_subject","Manage Exam Subjects Details",array(true=>array("see_exam_subject"=>true,"can_access_selection_data"=>true))),
		// new BooleanRight("manage_information_session","Manage Information Session Details",array(true=>array("edit_information_session"=>true,"can_access_selection_data"=>true,"see_information_session_details"=>true))),
		// new BooleanRight("edit_information_session","Edit Information Session Details",array(true=>array("can_access_selection_data"=>true,"see_information_session_details"=>true))),
		// new BooleanRight("manage_selection_campaign","Edit, Add or Remove a selection campaign",array(true=>array("manage_information_session"=>true,"can_access_selection_data"=>true,"see_information_session_details"=>true))),
	// )),
// );


return array(
	new AccessRightCategory("Information Session",array(
		new BooleanRight("manage_exam_subject","Manage Exam Subjects Details",array(true=>array("see_exam_subject"=>true))),
		new BooleanRight("manage_information_session","Manage Information Session Details",array(true=>array("edit_information_session"=>true,"see_information_session_details"=>true))),
		new BooleanRight("edit_information_session","Edit Information Session Details",array(true=>array("see_information_session_details"=>true))),
		new BooleanRight("manage_selection_campaign","Add/Remove/Configure a selection campaign",array(true=>array("manage_exam_center"=>true,"manage_information_session"=>true,"see_information_session_details"=>true))),
		new BooleanRight("manage_exam_center","Manage exam centers",array(true=>array("see_exam_center_detail"=>true))),
		new BooleanRight("edit_applicants","Create, import, remove and edit applicants",array(true=>array("see_applicant_info"=>true))),
		new BooleanRight("manage_applicant","Manage applicant (personnal info, center assignment, results...)",array(true=>array("can_access_selection_data"=>true))),
		new BooleanRight("edit_application_form_fields","Edit the list of information we can specify for each applicant",array(true=>array("edit_applicants"=>true,"manage_applicants"=>true))),
		new BooleanRight("manage_interview_center","Manage interview centers",array(true=>array("see_interview_center"=>true))),
		new BooleanRight("manage_interview_criteria","Manage Interview Criteria and Rules",array(true=>array("see_interview_criteria"=>true))),
	)),
);


//TODO update the rights for the calendar in selection.inc#createCampaign



?>