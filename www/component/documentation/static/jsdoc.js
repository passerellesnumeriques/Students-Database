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

function build_jsdoc(container, jsdoc) {
	if (typeof container == 'string') container = document.getElementById(container);
	
	build_jsdoc_namespace(container, jsdoc);
}
function build_jsdoc_namespace(container, ns) {
	var sub_ns = [];
	var classes = [];
	var functions = [];
	var variables = [];
	for (var name in ns.content) {
		var elem = ns.content[name];
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

	var table, tr, td;

	// first the variables
	if (variables.length > 0) {
		table = document.createElement("TABLE");
		table.className = 'codedoc_table';
		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.className = "codedoc_table_title";
		td.innerHTML = "Variables";
		td.colSpan = 3;
		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.className = "codedoc_table_title2";
		td.innerHTML = "Name";
		tr.appendChild(td = document.createElement("TD"));
		td.className = "codedoc_table_title2";
		td.innerHTML = "Type";
		tr.appendChild(td = document.createElement("TD"));
		td.className = "codedoc_table_title2";
		td.innerHTML = "Description";
		for (var i = 0; i < variables.length; ++i) {
			table.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TD"));
			td.className = "codedoc_property";
			td.appendChild(document.createTextNode(variables[i].name));
			tr.appendChild(td = document.createElement("TD"));
			td.className = "codedoc_class";
			if (variables[i].elem.type)
				td.appendChild(document.createTextNode(variables[i].elem.type));
			tr.appendChild(td = document.createElement("TD"));
			td.innerHTML = variables[i].elem.doc;
		}
		container.appendChild(table);
	}
	
	// the functions
	if (functions.length > 0) {
		table = document.createElement("TABLE");
		table.className = 'codedoc_table';
		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.className = "codedoc_table_title";
		td.innerHTML = "Functions";
		td.colSpan = 3;
		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.className = "codedoc_table_title2";
		td.innerHTML = "Name";
		tr.appendChild(td = document.createElement("TD"));
		td.className = "codedoc_table_title2";
		td.innerHTML = "Parameters";
		tr.appendChild(td = document.createElement("TD"));
		td.className = "codedoc_table_title2";
		td.innerHTML = "Description";
		for (var i = 0; i < functions.length; ++i) {
			table.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TD"));
			td.className = "codedoc_method";
			td.appendChild(document.createTextNode(functions[i].name));
			tr.appendChild(td = document.createElement("TD"));
			var code = "";
			for (var j = 0; j < functions[i].elem.parameters.length; ++j) {
				var param = functions[i].elem.parameters[j];
				code += "<span class='codedoc_parameter'>"+param.name+"</span>";
				if (param.type)
					code += " : <span class='codedoc_class'>"+param.type+"</span>";
				if (param.doc)
					code += "<div style='margin-left:15px;background-color:#FFFFD0'>"+param.doc+"</div>";
				else
					code += "<br/>";
			}
			// TODO return
			td.innerHTML = code;
			tr.appendChild(td = document.createElement("TD"));
			td.innerHTML = functions[i].elem.doc;
		}
		container.appendChild(table);
	}
	
	// the classes
	for (var i = 0; i < classes.length; ++i) {
		table = document.createElement("TABLE");
		table.className = 'codedoc_table';
		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.className = "codedoc_table_title";
		td.innerHTML = "Class "+classes[i].name;
		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		build_jsdoc_namespace(td, classes[i].elem);
		container.appendChild(table);
	}
	
	// the sub namespaces
	for (var i = 0; i < sub_ns.length; ++i) {
		table = document.createElement("TABLE");
		table.className = 'codedoc_table';
		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.className = "codedoc_table_title";
		td.innerHTML = "Namespace "+sub_ns[i].name;
		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		build_jsdoc_namespace(td, sub_ns[i].elem);
		container.appendChild(table);
	}
	
	
}