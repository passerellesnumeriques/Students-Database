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
		new BooleanRight("manage_exam_subject","Manage Exam Subjects Details",array(true=>array("see_exam_subject"=>true,"can_update_step"=>true))),
		new BooleanRight("manage_information_session","Manage Information Session Details",array(true=>array("edit_information_session"=>true,"see_information_session_details"=>true,"can_update_step"=>true))),
		new BooleanRight("edit_information_session","Edit Information Session Details",array(true=>array("see_information_session_details"=>true,"can_update_step"=>true))),
		new BooleanRight("manage_selection_campaign","Edit, Add or Remove a selection campaign",array(true=>array("manage_exam_center"=>true,"manage_information_session"=>true,"see_information_session_details"=>true,"can_update_step"=>true))),
		new BooleanRight("can_update_step","Update the selection steps",array()),
		new BooleanRight("manage_exam_center","Manage exam centers",array(true=>array("see_exam_center_detail"=>true,"can_update_step"=>true))),
		new BooleanRight("edit_applicants","Create, import, remove and edit applicants",array(true=>array("see_applicant_info"=>true))),
	)),
);


//TODO update the rights for the calendar in selection.inc#createCampaign



?>