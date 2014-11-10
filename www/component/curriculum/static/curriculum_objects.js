/**
 * Academic year
 * @param {Number} id ID of the year
 * @param {Number} year starting year
 * @param {String} name name of the academic year
 * @param {Array} periods list of AcademicPeriod
 */
function AcademicYear(id,year,name,periods) {
	this.id = id;
	/** starting year */
	this.year = year ? parseInt(year) : 0;
	this.name = name;
	this.periods = periods;
}

/**
 * AcademicPeriod
 * @param {Number} year_id ID of the AcademicYear
 * @param {Number} id ID of the period
 * @param {String} name name of the period
 * @param {String} start start of the period (SQL date format)
 * @param {String} end end of the period (SQL date format)
 * @param {Number} weeks number of weeks in the period
 * @param {Number} weeks_break number of non-worked weeks during the period
 */
function AcademicPeriod(year_id, id, name, start, end, weeks, weeks_break) {
	this.year_id = year_id;
	this.id = id;
	this.name = name;
	this.start = start;
	this.end = end;
	/** number of weeks in the period */
	this.weeks = weeks ? parseInt(weeks) : 0;
	/** number of non-worked weeks during the period */
	this.weeks_break = weeks_break ? parseInt(weeks_break) : 0;
}

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
 * @param {Array} periods list of {@link BatchPeriod}
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
 * @param {Number} academic_period AcademicPeriod id
 * @param {Array} available_specializations list of specializations' id
 */
function BatchPeriod(id, name, academic_period, available_specializations) {
	this.id = id;
	this.name = name;
	this.academic_period = academic_period;
	this.available_specializations = available_specializations;
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
 * @param {Number} coefficient the weight of the subject
 */
function CurriculumSubject(id, code, name, category_id, period_id, specialization_id, hours, hours_type, coefficient) {
	this.id = id;
	this.code = code;
	this.name = name;
	this.category_id = category_id;
	this.period_id = period_id;
	this.specialization_id = specialization_id;
	this.hours = hours;
	this.hours_type = hours_type;
	this.coefficient = coefficient;
}
