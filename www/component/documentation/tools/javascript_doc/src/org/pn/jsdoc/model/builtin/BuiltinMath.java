package org.pn.jsdoc.model.builtin;

import org.pn.jsdoc.model.Function;
import org.pn.jsdoc.model.Global;
import org.pn.jsdoc.model.ObjectAnonymous;
import org.pn.jsdoc.model.ObjectClass;

public class BuiltinMath extends ObjectAnonymous implements Builtin {

	public BuiltinMath(Global global) {
		super(global);
		add("E", new ObjectClass("Number"));
		add("LN2", new ObjectClass("Number"));
		add("LN10", new ObjectClass("Number"));
		add("LOG2E", new ObjectClass("Number"));
		add("LOG10E", new ObjectClass("Number"));
		add("PI", new ObjectClass("Number"));
		add("SQRT1_2", new ObjectClass("Number"));
		add("SQRT2", new ObjectClass("Number"));
		add("abs", new Function(this, "Number"));
		add("acos", new Function(this, "Number"));
		add("asin", new Function(this, "Number"));
		add("atan", new Function(this, "Number"));
		add("atan2", new Function(this, "Number"));
		add("ceil", new Function(this, "Number"));
		add("cos", new Function(this, "Number"));
		add("exp", new Function(this, "Number"));
		add("floor", new Function(this, "Number"));
		add("log", new Function(this, "Number"));
		add("max", new Function(this, "Number"));
		add("min", new Function(this, "Number"));
		add("pow", new Function(this, "Number"));
		add("random", new Function(this, "Number"));
		add("round", new Function(this, "Number"));
		add("sin", new Function(this, "Number"));
		add("sqrt", new Function(this, "Number"));
		add("tan", new Function(this, "Number"));
	}
	
}
