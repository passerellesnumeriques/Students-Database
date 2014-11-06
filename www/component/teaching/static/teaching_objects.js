/**
 * Teacher assigned to a subject/class
 * @param {Number} people_id ID of the People for the teacher
 * @param {Number} subject_id ID of the CurriculumSubject
 * @param {Number} class_id ID of the AcademicClass
 */
function TeacherAssigned(people_id, subject_id, class_id) {
	this.people_id = people_id;
	this.subject_id = subject_id;
	this.class_id = class_id;
}