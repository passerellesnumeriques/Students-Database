package org.pn.jsdoc.model.builtin;

import org.pn.jsdoc.model.Class;
import org.pn.jsdoc.model.Function;
import org.pn.jsdoc.model.Global;
import org.pn.jsdoc.model.ObjectClass;

public class BuiltinString extends Class implements Builtin {

	public BuiltinString(Global global) {
		super(global);
		add("length", new ObjectClass("Number"));
		add("charAt", new Function(this, "String"));
		add("charCodeAt", new Function(this, "Number"));
		add("concat", new Function(this, "String"));
		add("fromCharCode", new Function(this, "String"));
		add("indexOf", new Function(this, "Number"));
		add("lastIndexOf", new Function(this, "Number"));
		add("localeCompare", new Function(this, "Number"));
		add("match", new Function(this, "Array"));
		add("replace", new Function(this, "String"));
		add("search", new Function(this, "Number"));
		add("slice", new Function(this, "String"));
		add("split", new Function(this, "Array"));
		add("substr", new Function(this, "String"));
		add("substring", new Function(this, "String"));
		add("toLocaleLowerCase", new Function(this, "String"));
		add("toLocaleUpperCase", new Function(this, "String"));
		add("toLowerCase", new Function(this, "String"));
		add("toString", new Function(this, "String"));
		add("toUpperCase", new Function(this, "String"));
		add("trim", new Function(this, "String"));
		add("valueOf", new Function(this, "String"));
	}
	
}
