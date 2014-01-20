package org.pn.jsdoc.model.builtin;

import org.pn.jsdoc.model.Class;
import org.pn.jsdoc.model.Function;
import org.pn.jsdoc.model.Global;
import org.pn.jsdoc.model.ObjectClass;

public class BuiltinNavigator extends Class implements Builtin {

	public BuiltinNavigator(Global global) {
		super(global);
		add("appCodeName", new ObjectClass("String"));
		add("appName", new ObjectClass("String"));
		add("appVersion", new ObjectClass("String"));
		add("cookieEnabled", new ObjectClass("Boolean"));
		add("language", new ObjectClass("String"));
		add("onLine", new ObjectClass("Boolean"));
		add("platform", new ObjectClass("String"));
		add("product", new ObjectClass("String"));
		add("userAgent", new ObjectClass("String"));
		add("javaEnabled", new Function(this, "Boolean"));
		add("taintEnabled", new Function(this, "Boolean"));
	}
	
}
