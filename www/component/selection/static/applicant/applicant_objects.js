/**
 * 
 * @param people_id
 * @param first_name
 * @param last_name
 * @param middle_name
 * @param sex
 * @param birthdate
 * @param applicant_id
 * @param exclusion_reason
 * @param automatic_exclusion_step
 * @param automatic_exclusion_reason
 * @param custom_exclusion
 * @param excluded
 * @param information_session
 * @param exam_center
 * @param exam_center_room
 * @param exam_session
 * @returns {Applicant}
 */
function Applicant(people_id, first_name, last_name, middle_name, sex, birthdate, applicant_id, exclusion_reason, automatic_exclusion_step, automatic_exclusion_reason,custom_exclusion, excluded, information_session, exam_center, exam_center_room,exam_session){
	this.people_id = people_id ;
	this.first_name = first_name ;
	this.last_name = last_name ;
	this.middle_name = middle_name ;
	this.sex = sex ;
	this.birthdate = birthdate ;
	this.applicant_id = applicant_id ;
	this.exclusion_reason = exclusion_reason ;
	this.automatic_exclusion_step = automatic_exclusion_step ;
	this.automatic_exclusion_reason = automatic_exclusion_reason ;
	this.custom_exclusion = custom_exclusion ;
	this.excluded = excluded ;
	this.information_session = information_session ;
	this.exam_center = exam_center ;
	this.exam_center_room = exam_center_room ;
	this.exam_session = exam_session ;
}