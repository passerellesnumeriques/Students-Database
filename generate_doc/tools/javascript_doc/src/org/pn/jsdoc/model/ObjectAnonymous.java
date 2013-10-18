package org.pn.jsdoc.model;

import org.mozilla.javascript.ast.Name;
import org.mozilla.javascript.ast.ObjectLiteral;
import org.mozilla.javascript.ast.ObjectProperty;

public class ObjectAnonymous extends Container {

	public ObjectAnonymous(String file, ObjectLiteral obj) {
		super(new Location(file, obj));
		for (ObjectProperty p : obj.getElements()) {
			String name = ((Name)p.getLeft()).getIdentifier();
			add(name, new ValueToEvaluate(file, p.getRight(), p, p.getLeft()));
		}
	}
	
	@Override
	protected String getJSDocConstructor() {
		return "JSDoc_Namespace(";
	}
	
}
