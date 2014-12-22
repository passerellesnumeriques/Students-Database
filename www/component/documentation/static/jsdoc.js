function JSDoc_Location(file, line) {
	this.file = file;
	this.line = line;
}

function JSDoc_Namespace(content, location, doc) {
	this.content = content;
	this.location = location;
	this.doc = doc;
}

function JSDoc_Class(extended, no_name_check, content, location, doc) {
	this.content = content;
	this.extended = extended;
	this.location = location;
	this.doc = doc;
	this.no_name_check = no_name_check;
}

function JSDoc_Function(doc, parameters, return_type, return_doc, location, no_name_check, ignore) {
	this.doc = doc;
	this.parameters = parameters;
	this.return_type = return_type;
	this.return_doc = return_doc;
	this.location = location;
	this.no_name_check = no_name_check;
	this.ignore = ignore;
}

function JSDoc_Value(type, doc, location, no_name_check, ignore) {
	this.type = type;
	this.doc = doc;
	this.location = location;
	this.no_name_check = no_name_check;
	this.ignore = ignore;
}

function filter_jsdoc(jsdoc, file) {
	return filter_jsdoc_namespace(jsdoc, file);
}
function filter_jsdoc_namespace(ns, file) {
	var n = new JSDoc_Namespace({},ns.location,ns.doc);
	for (var name in ns.content) {
		var elem = ns.content[name];
		if (elem instanceof JSDoc_Namespace) {
			var ne = filter_jsdoc_namespace(elem, file);
			var has_content = false;
			for (var nn in ne.content) { has_content = true; break; }
			if (has_content) n.content[name] = ne;
		} else if (elem instanceof JSDoc_Class) {
			var ne = filter_jsdoc_class(elem, file);
			var has_content = false;
			for (var nn in ne.content) { has_content = true; break; }
			if (has_content) n.content[name] = ne;
		} else {
			if (elem.location.file != file) continue;
			n.content[name] = elem;
		}
	}
	return n;
}
function filter_jsdoc_class(cl, file) {
	var n = new JSDoc_Class(cl.extended,cl.no_name_check,{},cl.location,cl.doc);
	for (var name in cl.content) {
		var elem = cl.content[name];
		if (elem.location.file != file) continue;
		n.content[name] = elem;
	}
	return n;
}

function build_jsdoc_type_link(container, type) {
	if (type == null) type = "void";
	var link = document.createElement(type == "void" || type == "?" ? "SPAN" : "A");
	link.className = "codedoc_class";
	link.appendChild(document.createTextNode(type));
	if (type != "void" && type != "?") {
		var href = "/static/documentation/javascript.html?classname="+type;
		switch (type) {
		case "Boolean": href = "http://www.w3schools.com/jsref/jsref_obj_boolean.asp"; break;
		case "Number": href = "http://www.w3schools.com/jsref/jsref_obj_number.asp"; break;
		case "String": href = "http://www.w3schools.com/jsref/jsref_obj_string.asp"; break;
		case "Array": href = "http://www.w3schools.com/jsref/jsref_obj_array.asp"; break;
		case "Date": href = "http://www.w3schools.com/jsref/jsref_obj_date.asp"; break;
		case "window": href = "http://www.w3schools.com/jsref/obj_window.asp"; break;
		case "function": href = null;
		}
		if (href != null)
			link.href = href;
	}
	container.appendChild(link);
}
function build_jsdoc_namespace(container, name, ns, extend_containers, path) {
	var div = document.createElement("DIV"); container.appendChild(div);
	var span = document.createElement("SPAN"); div.appendChild(span);
	span.className = "codedoc_keyword";
	span.appendChild(document.createTextNode("namespace "));
	span = document.createElement("SPAN"); div.appendChild(span);
	span.className = "codedoc_code";
	span.appendChild(document.createTextNode(name));
	var content = document.createElement("DIV"); container.appendChild(content);
	content.style.marginLeft = "20px";
	build_jsdoc_namespace_content(content, ns, extend_containers, path);
}
function build_jsdoc_namespace_content(container, ns, extend_containers, path) {
	// order members
	var names = [];
	for (var n in ns.content) names.push(n);
	names.sort();
	var div, span, link;
	var table = document.createElement("TABLE"); container.appendChild(table);
	// variables
	for (var i = 0; i < names.length; ++i) {
		var name = names[i];
		if (name.substr(0,1) == "_") continue;
		var a = ns.content[name];
		if (!(a instanceof JSDoc_Value)) continue;
		build_jsdoc_variable(table, "public_variable.png", name, a);
	}
	for (var i = 0; i < names.length; ++i) {
		var name = names[i];
		if (name.substr(0,1) != "_") continue;
		var a = ns.content[name];
		if (!(a instanceof JSDoc_Value)) continue;
		build_jsdoc_variable(table, "private_variable.png", name, a);
	}
	// functions
	for (var i = 0; i < names.length; ++i) {
		var name = names[i];
		if (name.substr(0,1) == "_") continue;
		var f = ns.content[name];
		if (!(f instanceof JSDoc_Function)) continue;
		build_jsdoc_function(table, "public_function.png", name, f);
	}
	for (var i = 0; i < names.length; ++i) {
		var name = names[i];
		if (name.substr(0,1) != "_") continue;
		var f = ns.content[name];
		if (!(f instanceof JSDoc_Function)) continue;
		build_jsdoc_function(table, "private_function.png", name, f);
	}
	// sub namespaces
	for (var i = 0; i < names.length; ++i) {
		var name = names[i];
		var sub_ns = ns.content[name];
		if (!(sub_ns instanceof JSDoc_Namespace)) continue;
		if (extend_containers)
			build_jsdoc_namespace(container, name, sub_ns, extend_containers, path+name+".");
		else {
			div = document.createElement("DIV"); container.appendChild(div);
			span = document.createElement("SPAN"); div.appendChild(span);
			span.className = "codedoc_keyword";
			span.appendChild(document.createTextNode("namespace "));
			link = document.createElement("A"); div.appendChild(link);
			link.className = "codedoc_code";
			link.appendChild(document.createTextNode(name));
			link.href = "/static/documentation/javascript.html?namespace="+path+name;
		}
	}
	// classes
	for (var i = 0; i < names.length; ++i) {
		var name = names[i];
		var cl = ns.content[name];
		if (!(cl instanceof JSDoc_Class)) continue;
		if (extend_containers)
			build_jsdoc_class(container, name, cl);
		else {
			div = document.createElement("DIV"); container.appendChild(div);
			span = document.createElement("SPAN"); div.appendChild(span);
			span.className = "codedoc_keyword";
			span.appendChild(document.createTextNode("class "));
			link = document.createElement("A"); div.appendChild(link);
			link.className = "codedoc_class";
			link.appendChild(document.createTextNode(name));
			link.href = "/static/documentation/javascript.html?class="+path+name;
		}
	}
}
function build_jsdoc_class(container, name, cl) {
	// order class members
	var names = [];
	for (var n in cl.content) names.push(n);
	names.sort();
	// create class documentation
	var div = document.createElement("DIV"); container.appendChild(div);
	// title
	var title = document.createElement("DIV"); div.appendChild(title);
	var icon = document.createElement("IMG"); title.appendChild(icon);
	icon.style.marginRight = "5px";
	icon.src = "/static/documentation/class.png";
	var span = document.createElement("SPAN"); title.appendChild(span);
	span.className = "codedoc_keyword";
	span.appendChild(document.createTextNode("class"));
	title.appendChild(document.createTextNode(" "));
	span = document.createElement("SPAN"); title.appendChild(span);
	span.className = "codedoc_class";
	span.appendChild(document.createTextNode(name));
	// content
	var content = document.createElement("DIV"); div.appendChild(content);
	content.style.marginLeft = "20px";
	// 1. extended class
	if (cl.extended) {
		div = document.createElement("DIV"); content.appendChild(div);
		icon = document.createElement("IMG"); div.appendChild(icon);
		icon.style.marginRight = "5px";
		icon.src = "/static/documentation/extends.png";
		span = document.createElement("SPAN"); div.appendChild(span);
		span.className = "codedoc_keyword";
		span.appendChild(document.createTextNode("extends"));
		div.appendChild(document.createTextNode(" "));
		build_jsdoc_type_link(div, cl.extended);
	}
	var table = document.createElement("TABLE"); content.appendChild(table);
	// 2. constructor
	for (var name in cl.content) {
		if (name != "constructor") continue;
		var constr = cl.content[name];
		build_jsdoc_function(table, "constructor.gif", "constructor", constr);
	}
	// 3. attributes
	for (var i = 0; i < names.length; ++i) {
		var name = names[i];
		if (name.substr(0,1) == "_") continue;
		var a = cl.content[name];
		if (!(a instanceof JSDoc_Value)) continue;
		build_jsdoc_variable(table, "public_variable.png", name, a);
	}
	for (var i = 0; i < names.length; ++i) {
		var name = names[i];
		if (name.substr(0,1) != "_") continue;
		var a = cl.content[name];
		if (!(a instanceof JSDoc_Value)) continue;
		build_jsdoc_variable(table, "private_variable.png", name, a);
	}
	// 4. methods
	for (var i = 0; i < names.length; ++i) {
		var name = names[i];
		if (name.substr(0,1) == "_") continue;
		if (name == "constructor") continue;
		var f = cl.content[name];
		if (!(f instanceof JSDoc_Function)) continue;
		build_jsdoc_function(table, "public_function.png", name, f);
	}
	for (var i = 0; i < names.length; ++i) {
		var name = names[i];
		if (name.substr(0,1) != "_") continue;
		var f = cl.content[name];
		if (!(f instanceof JSDoc_Function)) continue;
		build_jsdoc_function(table, "private_function.png", name, f);
	}
}
function build_jsdoc_function(table, icon, name, f) {
	var tr = document.createElement("TR"); table.appendChild(tr);
	tr.title = f.location.file+":"+f.location.line;
	var td = document.createElement("TD"); tr.appendChild(td);
	td.style.verticalAlign = "top";
	var img = document.createElement("IMG"); td.appendChild(img);
	img.src = "/static/documentation/"+icon;
	img.style.marginRight = "5px";
	var span = document.createElement("SPAN"); td.appendChild(span);
	span.className = "codedoc_method";
	span.appendChild(document.createTextNode(name));
	span = document.createElement("SPAN"); td.appendChild(span);
	span.className = "codedoc_code";
	if (f.parameters.length == 0) {
		span.appendChild(document.createTextNode("()"));
	} else {
		span.appendChild(document.createTextNode("("));
	}
	td = document.createElement("TD"); tr.appendChild(td);
	td.className = "codedoc_comment";
	td.innerHTML = f.doc;
	if (f.parameters.length > 0) {
		for (var i = 0; i < f.parameters.length; ++i) {
			var p = f.parameters[i];
			tr = document.createElement("TR"); table.appendChild(tr);
			td = document.createElement("TD"); tr.appendChild(td);
			td.style.paddingLeft = "20px";
			td.style.whiteSpace = "nowrap";
			td.style.verticalAlign = "top";
			span = document.createElement("SPAN"); td.appendChild(span);
			span.className = "codedoc_parameter";
			span.appendChild(document.createTextNode(p.name));
			span = document.createElement("SPAN"); td.appendChild(span);
			span.className = "codedoc_code";
			span.appendChild(document.createTextNode(" : "));
			build_jsdoc_type_link(td, p.type != null ? p.type : "?");
			td = document.createElement("TD"); tr.appendChild(td);
			td.className = "codedoc_comment";
			td.innerHTML = p.doc;
		}
	}
	if (f.parameters.length > 0 || (f.return_type != null && f.return_type != "void")) {
		tr = document.createElement("TR"); table.appendChild(tr);
		td = document.createElement("TD"); tr.appendChild(td);
		span = document.createElement("SPAN"); td.appendChild(span);
		span.className = "codedoc_code";
		span.appendChild(document.createTextNode(")"));
		if (f.return_type != null && f.return_type != "void") {
			span.appendChild(document.createTextNode(" : "));
			build_jsdoc_type_link(td, f.return_type);
			td = document.createElement("TD"); tr.appendChild(td);
			td.className = "codedoc_comment";
			td.innerHTML = f.return_doc;
		} else {
			td = document.createElement("TD"); tr.appendChild(td);
		}
	}
}
function build_jsdoc_variable(table, icon, name, v) {
	var tr = document.createElement("TR"); table.appendChild(tr);
	tr.title = v.location.file+":"+v.location.line;
	var td = document.createElement("TD"); tr.appendChild(td);
	td.style.verticalAlign = "top";
	td.style.whiteSpace = "nowrap";
	var img = document.createElement("IMG"); td.appendChild(img);
	img.src = "/static/documentation/"+icon;
	img.style.marginRight = "5px";
	var span = document.createElement("SPAN"); td.appendChild(span);
	span.className = "codedoc_property";
	span.appendChild(document.createTextNode(name));
	span = document.createElement("SPAN"); td.appendChild(span);
	span.className = "codedoc_code";
	span.appendChild(document.createTextNode(" : "));
	build_jsdoc_type_link(td, v.type);
	td = document.createElement("TD"); tr.appendChild(td);
	td.className = "codedoc_comment";
	td.innerHTML = v.doc;
}