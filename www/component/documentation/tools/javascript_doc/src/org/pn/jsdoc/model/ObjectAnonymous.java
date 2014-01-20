package org.pn.jsdoc.model;

import org.mozilla.javascript.Node;
import org.mozilla.javascript.ast.Name;
import org.mozilla.javascript.ast.ObjectLiteral;
import org.mozilla.javascript.ast.ObjectProperty;

public class ObjectAnonymous extends Container {

	public String description = "";
	
	public ObjectAnonymous(Container parent, String file, ObjectLiteral obj, Node... docs) {
		super(parent, new Location(file, obj));
		JSDoc doc = new JSDoc(obj, docs);
		this.description = doc.description;
		for (ObjectProperty p : obj.getElements()) {
			String n = ((Name)p.getLeft()).getIdentifier();
			add(n, new ValueToEvaluate(file, p.getRight(), p, p.getLeft()));
		}
	}
	public ObjectAnonymous(Container parent) {
		super(parent, new Location());
	}
	
	@Override
	protected String getJSDocConstructor() {
		return "JSDoc_Namespace(";
	}
	@Override
	protected String getDescription() {
		return description;
	}
	
}
