function Applicant(people, applicant_id, automatic_exclusion_step, automatic_exclusion_reason,custom_exclusion, excluded, information_session_id, exam_center_id, exam_center_room_id, exam_session_id, exam_attendance, exam_passer, interview_center_id, interview_session_id, interview_attendance, interview_passer, interview_comment, high_school_id, high_school_name, ngo_id, ngo_name){
	this.people = people;
	this.applicant_id = applicant_id ;
	this.automatic_exclusion_step = automatic_exclusion_step ;
	this.automatic_exclusion_reason = automatic_exclusion_reason ;
	this.custom_exclusion = custom_exclusion ;
	this.excluded = excluded ;
	this.information_session_id = information_session_id ;
	this.exam_center_id = exam_center_id;
	this.exam_center_room_id = exam_center_room_id;
	this.exam_session_id = exam_session_id;
	this.exam_attendance = exam_attendance;
	this.exam_passer = exam_passer;
	this.interview_center_id = interview_center_id;
	this.interview_session_id = interview_session_id;
	this.interview_attendance = interview_attendance;
	this.interview_passer = interview_passer;
	this.interview_comment = interview_comment;
	this.high_school_id = high_school_id;
	this.high_school_name = high_school_name;
	this.ngo_id = ngo_id;
	this.ngo_name = ngo_name;
}

function ApplicantExamAnswer(applicant,exam_subject_question,answer,score){
	this.applicant=applicant;
	this.exam_subject_question=exam_subject_question;
	this.answer=answer;
	this.score=score;
}