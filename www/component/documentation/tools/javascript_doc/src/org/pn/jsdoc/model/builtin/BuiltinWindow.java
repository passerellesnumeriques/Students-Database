package org.pn.jsdoc.model.builtin;

import org.pn.jsdoc.model.Class;
import org.pn.jsdoc.model.Global;
import org.pn.jsdoc.model.ObjectClass;

public class BuiltinWindow extends Class implements Builtin {

	public BuiltinWindow(Global global) {
		super(global);
		this.content = global.content;
		add("closed", new BuiltinAttribute("Boolean"));
		add("defaultStatus", new BuiltinAttribute("String"));
		add("closed", new BuiltinAttribute("Boolean"));
		add("frames", new BuiltinAttribute("Array"));
		add("innerHeight", new BuiltinAttribute("Number"));
		add("innerWidth", new BuiltinAttribute("Number"));
		add("name", new BuiltinAttribute("String"));
		add("outerHeight", new BuiltinAttribute("Number"));
		add("outerWidth", new BuiltinAttribute("Number"));
		add("pageXOffset", new BuiltinAttribute("Number"));
		add("pageYOffset", new BuiltinAttribute("Number"));
		add("parent", new BuiltinAttribute("window"));
		add("top", new BuiltinAttribute("window"));
		add("self", new BuiltinAttribute("window"));
		add("screenLeft", new BuiltinAttribute("Number"));
		add("screenTop", new BuiltinAttribute("Number"));
		add("screenX", new BuiltinAttribute("Number"));
		add("screenY", new BuiltinAttribute("Number"));
		add("status", new BuiltinAttribute("String"));
	}
	
}
