// #depends[/static/people/people_data_grid.js]

function applicant_data_grid(container, applicant_getter, show_id) {
	people_data_grid.call(this, container, function(obj) {
		var applicant = applicant_getter(obj);
		return applicant.people;
	});
	var col;
	col = new GridColumn("applicant.id", "ID", null, "right", "field_integer", false, null, null, window.top._applicant_id_padding ? {pad:window.top._applicant_id_padding} : {});
	col.addSorting();
	this.addColumn(new CustomDataGridColumn(col, function(obj) { return applicant_getter(obj).applicant_id; }, show_id), 0);
	if (show_id) col.sort(true);
}
applicant_data_grid.prototype = new people_data_grid;
applicant_data_grid.prototype.constructor = applicant_data_grid;
applicant_data_grid.prototype.addApplicant = function(obj) { this.addPeople(obj); };
applicant_data_grid.prototype.removeApplicant = function(obj) { this.removePeople(obj); };