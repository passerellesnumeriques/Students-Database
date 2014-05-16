function Applicant(people, applicant_id, exclusion_reason, automatic_exclusion_step, automatic_exclusion_reason,custom_exclusion, excluded, information_session_id, exam_center_id, exam_center_room_id, exam_session_id){
	this.people = people;
	this.applicant_id = applicant_id ;
	this.exclusion_reason = exclusion_reason ;
	this.automatic_exclusion_step = automatic_exclusion_step ;
	this.automatic_exclusion_reason = automatic_exclusion_reason ;
	this.custom_exclusion = custom_exclusion ;
	this.excluded = excluded ;
	this.information_session_id = information_session_id ;
	this.exam_center_id = exam_center_id;
	this.exam_center_room_id = exam_center_room_id;
	this.exam_session_id = exam_session_id;
}