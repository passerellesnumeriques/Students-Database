// #depends[/static/people/people_data_grid.js]

function student_data_grid(container, student_getter, columns_container_title, show_id) {
	var col;
	col = new GridColumn("student.university_id", "ID", null, null, "field_text");
	col.addSorting();
	people_data_grid.call(this, container, student_getter, columns_container_title, columns_container_title ? [new CustomDataGridColumn(col, function(obj) { return student_getter(obj).university_id; }, show_id)] : null);
	if (!columns_container_title)
		this.addColumn(new CustomDataGridColumn(col, function(obj) { return student_getter(obj).university_id; }, show_id), 0);
	if (show_id) col.sort(true);
}
student_data_grid.prototype = new people_data_grid;
student_data_grid.prototype.constructor = student_data_grid;
student_data_grid.prototype.addStudent = function(obj) { this.addPeople(obj); };
student_data_grid.prototype.removeStudent = function(obj) { this.removePeople(obj); };