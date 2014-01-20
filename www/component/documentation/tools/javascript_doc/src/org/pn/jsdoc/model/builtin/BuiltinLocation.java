package org.pn.jsdoc.model.builtin;

import org.pn.jsdoc.model.Class;
import org.pn.jsdoc.model.Function;
import org.pn.jsdoc.model.Global;
import org.pn.jsdoc.model.ObjectClass;

public class BuiltinLocation extends Class implements Builtin {

	public BuiltinLocation(Global global) {
		super(global);
		add("hash", new ObjectClass("String"));
		add("host", new ObjectClass("String"));
		add("hostname", new ObjectClass("String"));
		add("href", new ObjectClass("String"));
		add("pathname", new ObjectClass("String"));
		add("port", new ObjectClass("Number"));
		add("protocol", new ObjectClass("String"));
		add("search", new ObjectClass("String"));
		add("assign", new Function(this, "void"));
		add("reload", new Function(this, "void"));
		add("replace", new Function(this, "void"));
	}
	
}
