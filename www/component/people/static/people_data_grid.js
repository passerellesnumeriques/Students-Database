// #depends[/static/data_model/custom_data_grid.js]

function people_data_grid(container, people_getter, people_columns_container_title) {
	custom_data_grid.call(this, container, function(obj) {
		var people = people_getter(obj);
		return people.id;
	});
	if (!container) return;
	this.people_getter = people_getter;
	var columns = [];
	var col;
	col = new GridColumn("people.first_name", "First Name", null, null, "field_text");
	col.addSorting();
	columns.push(new CustomDataGridColumn(col, function(obj) { return people_getter(obj).first_name; }, true));
	col = new GridColumn("people.middle_name", "Middle Name", null, null, "field_text");
	col.addSorting();
	columns.push(new CustomDataGridColumn(col, function(obj) { return people_getter(obj).middle_name; }, false));
	col = new GridColumn("people.last_name", "Last Name", null, null, "field_text");
	col.addSorting();
	var col_last_name = col;
	columns.push(new CustomDataGridColumn(col, function(obj) { return people_getter(obj).last_name; }, true));
	col = new GridColumn("people.sex", "Gender", null, null, "field_enum", false, null, null, {possible_values:['M','F']});
	col.addSorting();
	columns.push(new CustomDataGridColumn(col, function(obj) { return people_getter(obj).sex; }, false));
	col = new GridColumn("people.birthdate", "Birth Date", null, null, "field_date", false, null, null, null);
	col.addSorting();
	columns.push(new CustomDataGridColumn(col, function(obj) { return people_getter(obj).birthdate; }, false));
	if (people_columns_container_title) {
		var cc = new CustomDataGridColumnContainer(people_columns_container_title, columns);
		this.addColumnContainer(cc);
	} else {
		for (var i = 0; i < columns.length; ++i)
			this.addColumn(columns[i]);
	}
	col_last_name.sort(true);
}
people_data_grid.prototype = new custom_data_grid;
people_data_grid.prototype.constructor = people_data_grid;
people_data_grid.prototype.addPeople = function(obj) { this.addObject(obj); };
people_data_grid.prototype.removePeople = function(obj) { this.removeObject(obj); };
people_data_grid.prototype.addPeopleProfileAction = function() {
	var t=this;
	this.addAction(function(container, obj) {
		var button = document.createElement("BUTTON");
		button.className = "flat small";
		button.title = "Open profile";
		button.innerHTML = "<img src='/static/people/profile_16.png'/>";
		button.onclick = function() {
			window.top.popup_frame('/static/people/profile_16.png', "Profile", "/dynamic/people/page/profile?people="+t.people_getter(obj).id, null, 95, 95);
			return false;
		};
		container.appendChild(button);
	});
};