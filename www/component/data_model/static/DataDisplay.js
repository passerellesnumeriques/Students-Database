/**
 * Represents a DataDisplay on JavaScript side
 * @param {String} category the name of the category
 * @param {String} name the name of the data
 * @param {String} table table on the data model
 * @param {String} field_classname name of the implementation of typed_field to use to display this data
 * @param {Object} field_config configuration of the typed_field
 * @param {Boolean} editable true if the data can be edited
 * @param {Object} edit_locks information to know what to lock when the user wants to edit the data
 * @param {Boolean} sortable indicates if the data can be sorted or not in a list
 * @param {String} filter_classname implementation of typed_filter to use to display/edit/create a filter on this data
 * @param {Object} filter_config configuration of the typed_filter
 * @param {Object} cell if the data represents a single cell in the database, this object indicates which one
 * @param {Object} new_data structure to use for a new data
 * @returns
 */
function DataDisplay(category, name, table, field_classname, field_config, editable, edit_locks, sortable, filter_classname, filter_config, cell, new_data) {
	this.category = category;
	this.name = name;
	this.table = table;
	this.field_classname = field_classname;
	this.field_config = field_config;
	this.editable = editable;
	this.edit_locks = edit_locks;
	this.sortable = sortable;
	this.filter_classname = filter_classname;
	this.filter_config = filter_config;
	this.cell = cell;
	this.new_data = new_data;
}

/** Parse a DataPath to get information about it on JavaScript side
 * @param {String} s string representation of the DataPath, to be parsed 
 */
function DataPath(s) {
	this.path = s;
	/** Parse the first section of the path in the given string, then call recursively on the remaining string
	 * {String} s the string to parse
	 */
	this._parseElement = function(s) {
		var i = s.indexOf('>');
		var j = s.indexOf('<');
		if (i == -1) i = j;
		if (i == -1) {
			this.table = s;
			this.next = false;
			return;
		}
		this.table = s.substring(0,i);
		s = s.substring(i);
		this.direction = s.charAt(0);
		s = s.substring(1);
		this.multiple = false;
		if (s.charAt(1) == this.direction) {
			this.multiple = true;
			s = s.substring(1);
		}
		this.can_be_null = false;
		if (s.charAt(0) == '?') {
			this.can_be_null = true;
			s = s.substring(1);
		}
		this.next = new DataPath(s);
	};
	this._parseElement(s);
	
	/** Indicates if the data is mandatory or not (if it cannot be null in the data model) */
	this.isMandatory = function() {
		var p = this.next;
		while (p) {
			if (p.can_be_null || p.direction == '<')
				return false;
			p = p.next;
		}
		return true;
	};
}
