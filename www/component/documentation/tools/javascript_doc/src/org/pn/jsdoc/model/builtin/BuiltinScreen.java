package org.pn.jsdoc.model.builtin;

import org.pn.jsdoc.model.Class;
import org.pn.jsdoc.model.Global;
import org.pn.jsdoc.model.ObjectClass;

public class BuiltinScreen extends Class implements Builtin {

	public BuiltinScreen(Global global) {
		super(global);
		add("availHeight", new ObjectClass("Number"));
		add("availWidth", new ObjectClass("Number"));
		add("colorDepth", new ObjectClass("Number"));
		add("height", new ObjectClass("Number"));
		add("pixelDepth", new ObjectClass("Number"));
		add("width", new ObjectClass("Number"));
	}
	
}
