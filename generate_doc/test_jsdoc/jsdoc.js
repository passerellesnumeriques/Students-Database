function JSDoc_Location(file, line) {
	this.file = file;
	this.line = line;
}

function JSDoc_Namespace(content, location) {
	this.content = content;
	this.location = location;
}

function JSDoc_Class(content, location) {
	this.content = content;
	this.location = location;
}

function JSDoc_Function(doc, parameters, return_type, return_doc, location) {
	this.doc = doc;
	this.parameters = parameters;
	this.return_type = return_type;
	this.return_doc = return_doc;
	this.location = location;
}

function JSDoc_Value(type, doc, location) {
	this.type = type;
	this.doc = doc;
	this.location = location;
}

function jsdoc_builder(container) {
	if (typeof container == 'string') container = document.getElementById(container);
	
	this.build = function(component, file) {
		var namespaces = this.find_namespaces(jsdoc, "", component, file);
		for (var i = 0; i < namespaces.length; ++i) {
			container.appendChild(document.createTextNode(namespaces[i].path));
			container.appendChild(document.createElement("BR"));
		}
	};
	
	this.find_namespaces = function(parent, path, component, file) {
		var list = [];
		for (var name in parent.content) {
			var elem = parent.content[name];
			elem.name = name;
			if (elem instanceof JSDoc_Namespace) {
				list.push({path:path+name,ns:elem});
				var sub_list = this.find_namespaces(elem, path+name+".", component, file);
				for (var i = 0; i < sub_list.length; ++i) list.push(sub_list[i]);
			}
		}
		return list;
	};
}
