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
		new BooleanRight("manage_selection_campaign","Edit, Add or Remove a selection campaign",array(true=>array("manage_information_session"=>true,"see_information_session_details"=>true))),
		new BooleanRight("manage_applicant_info","Manage applicant informations",array(true=>array("see_applicant_info"=>true,"create_applicant"=>true))),
		new BooleanRight("create_applicant","Create an applicant",array(true=>array("see_applicant_info"=>true))),
	)),
);


//TODO update the rights for the calendar in selection.inc#create_campaign



?>