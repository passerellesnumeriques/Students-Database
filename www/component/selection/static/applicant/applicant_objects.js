/**
 * @param {Number} people_id applicant people ID
 * @param {String} first_name applicant first name
 * @param {String} last_name applicant last name
 * @param {String} middle_name applicant middle name
 * @param {String} sex applicant sex
 * @param {String} birthdate applicant birth date
 * @param {Number} applicant_id applicant ID (selection process one)
 * @param {String} exclusion_reason not null if the applicant was exclued only 
 * @param {Boolean} automatic_exclusion_step true if the applicant has been excluded automatically (cheat, failing grade, missing...)
 * @param {String} automatic_exclusion_reason value of the automatic exclusion reason
 * @param {String} custom_exclusion if the applicant was excluded but not for an automatic reason
 * @param {Boolean} excluded true if the applicant is excluded (automatic or custom)
 * @param {Number} information_session ID of the information session to which the applicant is assigned
 * @param {Number} exam_center ID of the exam center to which the applicant is assigned
 * @param {Number} exam_center_room ID of the exam center room to which the applicant is assigned
 * @param {Number} exam_session ID of the exam session to which the applicant is assigned
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