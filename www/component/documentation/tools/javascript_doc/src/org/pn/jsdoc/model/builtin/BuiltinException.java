package org.pn.jsdoc.model.builtin;

import org.pn.jsdoc.model.Class;
import org.pn.jsdoc.model.Global;
import org.pn.jsdoc.model.ObjectClass;

public class BuiltinException extends Class implements Builtin {

	public BuiltinException(Global global) {
		super(global);
		content.put("stack", new ObjectClass("Object"));
		content.put("stacktrace", new ObjectClass("Object"));
		content.put("message", new ObjectClass("String"));
		content.put("filename", new ObjectClass("String"));
		content.put("lineNumber", new ObjectClass("Number"));
	}
	
}
