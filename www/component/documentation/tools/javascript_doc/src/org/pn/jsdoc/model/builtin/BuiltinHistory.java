package org.pn.jsdoc.model.builtin;

import org.pn.jsdoc.model.Class;
import org.pn.jsdoc.model.Function;
import org.pn.jsdoc.model.Global;
import org.pn.jsdoc.model.ObjectClass;

public class BuiltinHistory extends Class implements Builtin {

	public BuiltinHistory(Global global) {
		super(global);
		add("length", new ObjectClass("Number"));
		add("back", new Function(this, "void"));
		add("forward", new Function(this, "void"));
		add("go", new Function(this, "void"));
	}
	
}
