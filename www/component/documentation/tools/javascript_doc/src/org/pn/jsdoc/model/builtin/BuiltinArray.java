package org.pn.jsdoc.model.builtin;

import org.pn.jsdoc.model.Class;
import org.pn.jsdoc.model.Function;
import org.pn.jsdoc.model.Global;
import org.pn.jsdoc.model.ObjectClass;

public class BuiltinArray extends Class implements Builtin {

	public BuiltinArray(Global global) {
		super(global);
		add("length", new ObjectClass("Number"));
		add("concat", new Function(this, "Array"));
		add("indexOf", new Function(this, "Number"));
		add("join", new Function(this, "String"));
		add("lastIndexOf", new Function(this, "Number"));
		add("pop", new Function(this, "Object"));
		add("push", new Function(this, "Number"));
		add("reverse", new Function(this, "Array"));
		add("shift", new Function(this, "Object"));
		add("slice", new Function(this, "Array"));
		add("sort", new Function(this, "Array"));
		add("splice", new Function(this, "Array"));
		add("toString", new Function(this, "String"));
		add("unshift", new Function(this, "Number"));
		add("valueOf", new Function(this, "Array"));
	}
	
}
