package org.pn.jsdoc.model.builtin;

import org.pn.jsdoc.model.Class;
import org.pn.jsdoc.model.Function;
import org.pn.jsdoc.model.Global;

public class BuiltinBoolean extends Class implements Builtin {

	public BuiltinBoolean(Global global) {
		super(global);
		add("toString", new Function(this, "String"));
		add("valueOf", new Function(this, "Boolean"));
	}
	
}
