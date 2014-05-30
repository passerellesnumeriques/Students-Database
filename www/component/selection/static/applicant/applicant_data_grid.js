// #depends[/static/people/people_data_grid.js]

function applicant_data_grid(container, applicant_getter) {
	people_data_grid.call(this, container, function(obj) {
		var applicant = applicant_getter(obj);
		return applicant.people;
	});
}
applicant_data_grid.prototype = new people_data_grid;
applicant_data_grid.prototype.constructor = applicant_data_grid;
applicant_data_grid.prototype.addApplicant = function(obj) { this.addPeople(obj); };
applicant_data_grid.prototype.removeApplicant = function(obj) { this.removePeople(obj); };