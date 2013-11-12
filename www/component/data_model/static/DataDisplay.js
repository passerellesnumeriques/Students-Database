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

function DataPath(s) {
	this.path = s;
	this.parseElement = function(s) {
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
	this.parseElement(s);
	
	this.is_mandatory = function() {
		var p = this.next;
		while (p) {
			if (p.can_be_null || p.direction == '<')
				return false;
			p = p.next;
		}
		return true;
	};
}
