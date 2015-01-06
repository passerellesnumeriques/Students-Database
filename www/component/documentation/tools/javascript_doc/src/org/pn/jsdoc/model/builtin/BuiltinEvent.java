package org.pn.jsdoc.model.builtin;

import org.pn.jsdoc.model.Class;
import org.pn.jsdoc.model.Global;
import org.pn.jsdoc.model.ObjectClass;

public class BuiltinEvent extends Class implements Builtin {

	public BuiltinEvent(Global global) {
		super(global);
		add("cancelable", new ObjectClass("Boolean"));
		add("currentTarget", new ObjectClass("Element"));
		add("defaultPrevented", new ObjectClass("Boolean"));
		add("target", new ObjectClass("Element"));
		add("timeStamp", new ObjectClass("Number"));
		add("type", new ObjectClass("String"));
		add("view", new ObjectClass("Window"));
	}
	
}
