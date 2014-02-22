function Specialization(id, name) {
	this.id = id;
	this.name = name;
}

function Batch(id, name, start_date, end_date, periods) {
	this.id = id;
	this.name = name;
	this.start_date = start_date;
	this.end_date = end_date;
	this.periods = periods;
}

function AcademicPeriod(id, name, start_date, end_date, available_specializations, classes) {
	this.id = id;
	this.name = name;
	this.start_date = start_date;
	this.end_date = end_date;
	this.available_specializations = available_specializations;
	this.classes = classes;
}

function StudentClass(id, name, spe_id) {
	this.id = id;
	this.name = name;
	this.spe_id = spe_id;
}