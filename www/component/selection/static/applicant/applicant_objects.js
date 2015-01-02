/**
 * Applicant
 * @param {People} people people information
 * @param {Number} applicant_id identifier
 * @param {String|null} automatic_exclusion_step if automatically excluded
 * @param {String|null} automatic_exclusion_reason if automatically excluded
 * @param {String|null} custom_exclusion if manually excluded
 * @param {Boolean} excluded indicates if excluded
 * @param {Number|null} information_session_id Information Session
 * @param {Number|null} exam_center_id Exam Center
 * @param {Number|null} exam_center_room_id room if scheduled for an exam session
 * @param {Number|null} exam_session_id scheduled session
 * @param {String|null} exam_attendance attendance of the exam
 * @param {Boolean|null} exam_passer indicates if the applicant passed or failed the exam
 * @param {Number|null} interview_center_id Interview Center
 * @param {Number|null} interview_session_id scheduled interview session
 * @param {Boolean|null} interview_attendance attendance
 * @param {Boolean|null} interview_passer indicates if the applicant passed or failed the interview
 * @param {String|null} interview_comment comment of the interviewers
 * @param {Number|null} high_school_id organisation
 * @param {String|null} high_school_name name of the high school
 * @param {Number|null} ngo_id organisation
 * @param {String|null} ngo_name name of the following NGO
 */
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

/**
 * Answer of an applicant to a question of an exam
 * @param {Number} applicant applicant id
 * @param {Number} exam_subject_question question id
 * @param {String} answer answer
 * @param {Number} score grade for the question
 */
function ApplicantExamAnswer(applicant,exam_subject_question,answer,score){
	this.applicant=applicant;
	this.exam_subject_question=exam_subject_question;
	this.answer=answer;
	this.score=score;
}