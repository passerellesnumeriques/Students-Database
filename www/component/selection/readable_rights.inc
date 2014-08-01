<?php 
return array(
	new AccessRightCategory("Contact Informations",array(
		// general
		new BooleanRight("can_access_selection_data","Right that must be valid to read any data about selection"),
		// information session
		new BooleanRight("see_information_session","See Information Session Details",array(true=>array("can_access_selection_data"=>true))),
		// exam
		new BooleanRight("see_exam_subject","See Exam Subjects",array(true=>array("can_access_selection_data"=>true))),
		new BooleanRight("see_exam_center", "See Exam Centers",array(true=>array("can_access_selection_data"=>true))),
		new BooleanRight("see_exam_rules", "See Exam Eligibility Rules",array(true=>array("can_access_selection_data"=>true,"see_exam_subject"=>true))),
		// interview
		new BooleanRight("see_interview_center", "See Interview Centers",array(true=>array("can_access_selection_data"=>true))),
		new BooleanRight("see_interview_criteria", "See Interview Criteria and Rules",array(true=>array("can_access_selection_data"=>true))),
		// applicant
		new BooleanRight("see_applicant_info","See Applicants Informations",array(true=>array("can_access_selection_data"=>true))),
	)),
	
);
?>