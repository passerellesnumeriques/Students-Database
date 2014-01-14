package org.pn.jsdoc.model.builtin;

import org.pn.jsdoc.model.Class;
import org.pn.jsdoc.model.Function;
import org.pn.jsdoc.model.Global;

public class BuiltinDate extends Class implements Builtin {

	public BuiltinDate(Global global) {
		super(global);
		content.put("getDate", new Function(this, "Number"));
		content.put("getDay", new Function(this, "Number"));
		content.put("getFullYear", new Function(this, "Number"));
		content.put("getHours", new Function(this, "Number"));
		content.put("getMilliseconds", new Function(this, "Number"));
		content.put("getMinutes", new Function(this, "Number"));
		content.put("getMonth", new Function(this, "Number"));
		content.put("getSeconds", new Function(this, "Number"));
		content.put("getTime", new Function(this, "Number"));
		content.put("getTimezoneOffset", new Function(this, "Number"));
		content.put("getUTCDate", new Function(this, "Number"));
		content.put("getUTCDay", new Function(this, "Number"));
		content.put("getUTCFullYear", new Function(this, "Number"));
		content.put("getUTCHours", new Function(this, "Number"));
		content.put("getUTCMilliseconds", new Function(this, "Number"));
		content.put("getUTCMinutes", new Function(this, "Number"));
		content.put("getUTCMonth", new Function(this, "Number"));
		content.put("getUTCSeconds", new Function(this, "Number"));
		content.put("getYear", new Function(this, "Number"));
		content.put("parse", new Function(this, "Number"));
		content.put("setDate", new Function(this, "void"));
		content.put("setDay", new Function(this, "void"));
		content.put("setFullYear", new Function(this, "void"));
		content.put("setHours", new Function(this, "void"));
		content.put("setMilliseconds", new Function(this, "void"));
		content.put("setMinutes", new Function(this, "void"));
		content.put("setMonth", new Function(this, "void"));
		content.put("setSeconds", new Function(this, "void"));
		content.put("setTime", new Function(this, "void"));
		content.put("setUTCDate", new Function(this, "void"));
		content.put("setUTCDay", new Function(this, "void"));
		content.put("setUTCFullYear", new Function(this, "void"));
		content.put("setUTCHours", new Function(this, "void"));
		content.put("setUTCMilliseconds", new Function(this, "void"));
		content.put("setUTCMinutes", new Function(this, "void"));
		content.put("setUTCMonth", new Function(this, "void"));
		content.put("setUTCSeconds", new Function(this, "void"));
		content.put("setUTCTime", new Function(this, "void"));
		content.put("setYear", new Function(this, "void"));
		content.put("toDateString", new Function(this, "String"));
		content.put("toGMTString", new Function(this, "String"));
		content.put("toISOString", new Function(this, "String"));
		content.put("toJSON", new Function(this, "String"));
		content.put("toLocaleDateString", new Function(this, "String"));
		content.put("toLocaleTimeString", new Function(this, "String"));
		content.put("toLocaleString", new Function(this, "String"));
		content.put("toString", new Function(this, "String"));
		content.put("toTimeString", new Function(this, "String"));
		content.put("toUTCString", new Function(this, "String"));
		content.put("UTC", new Function(this, "Number"));
		content.put("valueOf", new Function(this, "Date"));
	}
	
}
