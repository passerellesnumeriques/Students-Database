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
	this.menu = document.createElement("DIV");
	this.content = document.createElement("DIV");
	container.appendChild(this.menu);
	container.appendChild(this.content);
	this.menu.style.overflow = "auto";
	this.content.style.overflow = "auto";
	new splitter_vertical(container, 0.2);
	
	this.build = function(component, file) {
		var ns = this.create_namespace_menu("No namespace", this.menu);
		this.browse_namespaces(jsdoc, "", ns, component, file);
	};
	
	this.browse_namespaces = function(parent, path, menu, component, file) {
		var sub_ns = [];
		var classes = [];
		var functions = [];
		var variables = [];
		for (var name in parent.content) {
			var elem = parent.content[name];
			elem.name = name;
			if (elem instanceof JSDoc_Namespace) {
				sub_ns.push({elem:elem,name:name});
			} else if (elem instanceof JSDoc_Class) {
				classes.push({elem:elem,name:name});
			} else if (elem instanceof JSDoc_Function) {
				functions.push({elem:elem,name:name});
			} else if (elem instanceof JSDoc_Value) {
				variables.push({elem:elem,name:name});
			}
		}
		for (var i = 0; i < sub_ns.length; ++i) {
			var ns = this.create_namespace_menu(sub_ns[i].name, path.length > 0 ? menu.ns : this.menu);
			this.browse_namespaces(sub_ns[i].elem, path+name+".", ns, component, file);
		}
		if (classes.length > 0) {
			classes.sort(function(e1,e2) { return e1.name.localeCompare(e2.name); });
			for (var i = 0; i < classes.length; ++i)
				this.create_class_menu(classes[i].name, menu.classes);
		} else {
			menu.classes_container.style.visibility = "hidden";
			menu.classes_container.style.position = "absolute";
			menu.classes_container.style.top = "-10000px";
		}
		if (functions.length > 0) {
			functions.sort(function(e1,e2) { return e1.name.localeCompare(e2.name); });
			for (var i = 0; i < functions.length; ++i)
				this.create_function_menu(functions[i].name, menu.functions);
		} else {
			menu.functions_container.style.visibility = "hidden";
			menu.functions_container.style.position = "absolute";
			menu.functions_container.style.top = "-10000px";
		}
		if (variables.length > 0) {
			variables.sort(function(e1,e2) { return e1.name.localeCompare(e2.name); });
			for (var i = 0; i < variables.length; ++i)
				this.create_variable_menu(variables[i].name, menu.variables);
		} else {
			menu.variables_container.style.visibility = "hidden";
			menu.variables_container.style.position = "absolute";
			menu.variables_container.style.top = "-10000px";
		}
	};
	
	this.create_namespace_menu = function(name, container) {
		var div = document.createElement("DIV"); container.appendChild(div);
		var title = document.createElement("DIV"); div.appendChild(title);
		title.appendChild(this.createElement("IMG",{src:"namespace.gif",style:{verticalAlign:'bottom',paddingRight:'2px'}}));
		title.appendChild(document.createTextNode(name));
		var content = document.createElement("DIV"); div.appendChild(content);
		content.style.paddingLeft = '10px';
		var sub_ns = document.createElement("DIV"); content.appendChild(sub_ns);
		var classes = document.createElement("DIV"); content.appendChild(classes);
		var classes_title = document.createElement("DIV"); classes.appendChild(classes_title);
		classes_title.appendChild(this.createElement("IMG",{src:"class.png",style:{verticalAlign:'bottom',paddingRight:'2px'}}));
		classes_title.appendChild(document.createTextNode("Classes"));
		var classes_content = document.createElement("DIV"); classes.appendChild(classes_content);
		classes_content.style.paddingLeft = "10px";
		var functions = document.createElement("DIV"); content.appendChild(functions);
		var functions_title = document.createElement("DIV"); functions.appendChild(functions_title);
		functions_title.appendChild(this.createElement("IMG",{src:"function.png",style:{verticalAlign:'bottom',paddingRight:'2px'}}));
		functions_title.appendChild(document.createTextNode("Functions"));
		var functions_content = document.createElement("DIV"); functions.appendChild(functions_content);
		functions_content.style.paddingLeft = "10px";
		var variables = document.createElement("DIV"); content.appendChild(variables);
		var variables_title = document.createElement("DIV"); variables.appendChild(variables_title);
		variables_title.appendChild(this.createElement("IMG",{src:"variable.png",style:{verticalAlign:'bottom',paddingRight:'2px'}}));
		variables_title.appendChild(document.createTextNode("Variables"));
		var variables_content = document.createElement("DIV"); variables.appendChild(variables_content);
		variables_content.style.paddingLeft = "10px";
		return {
			ns: sub_ns,
			classes: classes_content,
			classes_container: classes,
			functions: functions_content,
			functions_container: functions,
			variables: variables_content,
			variables_container: variables,
		};
	};
	
	this.create_class_menu = function(name, container) {
		var div = document.createElement("DIV"); container.appendChild(div);
		div.appendChild(document.createTextNode(name));
	};
	
	this.create_function_menu = function(name, container) {
		var div = document.createElement("DIV"); container.appendChild(div);
		div.appendChild(document.createTextNode(name));
	};
	
	this.create_variable_menu = function(name, container) {
		var div = document.createElement("DIV"); container.appendChild(div);
		div.appendChild(document.createTextNode(name));
	};
	
	this.createElement = function(name, attributes) {
		var e = document.createElement(name);
		for (var name in attributes) {
			if (name == "style") {
				for (var s in attributes.style)
					e.style[s] = attributes.style[s];
			} else
				e[name] = attributes[name];
		}
		return e;
	};
}
