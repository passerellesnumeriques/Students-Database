package org.pn.jsdoc.model.builtin;

import org.pn.jsdoc.model.Class;
import org.pn.jsdoc.model.Function;
import org.pn.jsdoc.model.Global;
import org.pn.jsdoc.model.ObjectClass;

public class BuiltinNumber extends Class implements Builtin {

	public BuiltinNumber(Global global) {
		super(global);
		add("MAX_VALUE", new ObjectClass("Number"));
		add("MIN_VALUE", new ObjectClass("Number"));
		add("NEGATIVE_INFINITY", new ObjectClass("Number"));
		add("NaN", new ObjectClass("Number"));
		add("POSITIVE_INFINITY", new ObjectClass("Number"));
		add("toExponential", new Function(this, "Number"));
		add("toFixed", new Function(this, "String"));
		add("toPrecision", new Function(this, "String"));
		add("toString", new Function(this, "String"));
		add("valueOf", new Function(this, "Number"));
	}
	
}
