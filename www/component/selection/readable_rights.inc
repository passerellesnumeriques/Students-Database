<?php 
return array(
	new AccessRightCategory("Contact Informations",array(
		new BooleanRight("can_access_selection_data","Right that must be valid to read any data about selection"),
		new BooleanRight("see_information_session_details","See Information Session Details",array(true=>array("can_access_selection_data"=>true))),
		new BooleanRight("see_exam_subject","See Exam Subjects Informations",array(true=>array("can_access_selection_data"=>true))),
		new BooleanRight("see_applicant_info","See Applicants Informations",array(true=>array("can_access_selection_data"=>true))),
		new BooleanRight("see_exam_center_detail", "See Exam Center Data",array(true=>array("can_access_selection_data"=>true))),
		new BooleanRight("see_interview_center", "See Interview Centers",array(true=>array("can_access_selection_data"=>true))),
		new BooleanRight("see_interview_criteria", "See Interview Criteria and Rules",array(true=>array("can_access_selection_data"=>true))),
	)),
	
);
?>