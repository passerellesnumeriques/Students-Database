package org.pn.jsdoc.model.builtin;

import org.pn.jsdoc.model.Class;
import org.pn.jsdoc.model.Function;
import org.pn.jsdoc.model.Global;
import org.pn.jsdoc.model.ObjectClass;

public class BuiltinDocument extends Class implements Builtin {

	public BuiltinDocument(Global global) {
		super(global);
		add("anchors", new ObjectClass("Array"));
		add("applets", new ObjectClass("Array"));
		add("baseURI", new ObjectClass("String"));
		add("body", new ObjectClass("Element"));
		add("close", new Function(this, "void"));
		add("cookie", new ObjectClass("String"));
		add("createAttribute", new Function(this, "Element"));
		add("createComment", new Function(this, "Element"));
		add("createDocumentFragment", new Function(this, "Element"));
		add("createElement", new Function(this, "Element"));
		add("createTextNode", new Function(this, "Element"));
		add("doctype", new ObjectClass("String"));
		add("documentElement", new ObjectClass("document"));
		add("documentURI", new ObjectClass("String"));
		add("domain", new ObjectClass("String"));
		add("forms", new ObjectClass("Array"));
		add("getElementById", new Function(this, "Element"));
		add("getElementsByName", new Function(this, "Array"));
		add("getElementsByTagName", new Function(this, "Array"));
		add("images", new ObjectClass("Array"));
		add("importNode", new Function(this, "Element"));
		add("inputEncoding", new ObjectClass("String"));
		add("lastModified", new ObjectClass("Date"));
		add("links", new ObjectClass("Array"));
		add("title", new ObjectClass("String"));
		add("URL", new ObjectClass("String"));
	}
	
}
