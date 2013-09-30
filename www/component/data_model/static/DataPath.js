function DataPath(s) {
	this.path = s;
	this.parseElement = function(s) {
		var i = s.indexOf('(');
		if (i != -1) {
			this.table = s.substring(0,i);
			var j = s.indexOf(')');
			this.foreign_key = s.substring(i+1,j);
			if (j < s.length-1 && s.charAt(j+1) == '.') {
				if (s.charAt(j+2) == '?') {
					this.can_be_null = true;
					this.column = s.substring(j+3);
				} else {
					this.can_be_null = false;
					this.column = s.substring(j+2);
				}
			}
			return;
		}
		i = s.indexOf('.');
		this.table = s.substring(0,i);
		if (s.charAt(i+1) == '?') {
			this.can_be_null = true;
			this.column = s.substring(i+2);
		} else {
			this.can_be_null = false;
			this.column = s.substring(i+1);
		}
	};
	
	var i = s.lastIndexOf('>');
	var j = s.lastIndexOf('<');
	if (i == -1 || (j != -1 && j > i)) i = j;
	if (i != -1) {
		var dir = s.charAt(i);
		var element;
		if (s.charAt(i+1) == '?') {
			this.can_be_null = true;
			element = s.substring(i+2);
		} else {
			this.can_be_null = false;
			element = s.substring(i+1);
		}
		var multiple = false;
		if (s.charAt(i-1) == dir) {
			multiple = true;
			i--;
		}
		s = s.substring(0,i);
		this.parseElement(element);
		this.parent = new DataPath(s);
		this.parent.multiple = multiple;
		this.parent.direction = dir == '>' ? 1 : 2;
		if (multiple) this.can_be_null = true;
	} else {
		this.parent = null;
		this.multiple = false;
		this.direction = 0;
		this.parseElement(s);
	}
	
	this.is_mandatory = function() {
		var list = [];
		var p = this;
		while (p != null) {
			list.splice(0,0,p);
			p = p.parent;
		}
		for (var i = 1; i < list.length; ++i) {
			if (list[i].can_be_null || list[i].direction == '<')
				return false;
		}
		return true;
	};
}