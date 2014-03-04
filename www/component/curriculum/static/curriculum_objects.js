/**
 * Specialization in the curriculum
 * @param {Number} id id
 * @param {String} name name
 */
function Specialization(id, name) {
	this.id = id;
	this.name = name;
}

/**
 * A batch of students
 * @param {Number} id id
 * @param {String} name name
 * @param {String} start_date integration date, in SQL format
 * @param {String} end_date graduation date, in SQL format
 * @param {Array} periods list of {@link AcademicPeriod}
 */
function Batch(id, name, start_date, end_date, periods) {
	this.id = id;
	this.name = name;
	this.start_date = start_date;
	this.end_date = end_date;
	this.periods = periods;
}

/**
 * Academic Period (quarter, or semester...)
 * @param {Number} id id
 * @param {String} name name
 * @param {String} start_date date in SQL format
 * @param {String} end_date date in SQL format
 * @param {Array} available_specializations list of specializations' id
 * @param {Array} classes list of {@link StudentClass}
 */
function AcademicPeriod(id, name, start_date, end_date, available_specializations, classes) {
	this.id = id;
	this.name = name;
	this.start_date = start_date;
	this.end_date = end_date;
	this.available_specializations = available_specializations;
	this.classes = classes;
}

/**
 * A class, for a given academic period and batch
 * @param {Number} id id
 * @param {String} name name
 * @param {Number} spe_id id of the specialization if this class is associated to a specialization, or null
 */
function StudentClass(id, name, spe_id) {
	this.id = id;
	this.name = name;
	this.spe_id = spe_id;
}

/**
 * Category of subjects in the curriculum (like IT, General...)
 * @param {Number} id id
 * @param {String} name name
 */
function CurriculumSubjectCategory(id, name) {
	this.id = id;
	this.name = name;
}

/**
 * A subject, in a given academic period
 * @param {Number} id id
 * @param {String} code subject code
 * @param {String} name subject name
 * @param {Number} category_id id of the category this subject belongs to
 * @param {Number} period_id id of the academic period
 * @param {Number} specialization_id id of the specialization, or null
 * @param {Number} hours number of hours of this subject
 * @param {String} hours_type either "Per week" or "Per period"
 */
function CurriculumSubject(id, code, name, category_id, period_id, specialization_id, hours, hours_type) {
	this.id = id;
	this.code = code;
	this.name = name;
	this.category_id = category_id;
	this.period_id = period_id;
	this.specialization_id = specialization_id;
	this.hours = hours;
	this.hours_type = hours_type;
}