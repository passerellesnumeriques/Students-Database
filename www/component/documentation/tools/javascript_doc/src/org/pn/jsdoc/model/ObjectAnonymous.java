package org.pn.jsdoc.model;

import org.mozilla.javascript.Node;
import org.mozilla.javascript.ast.AstNode;
import org.mozilla.javascript.ast.Name;
import org.mozilla.javascript.ast.NumberLiteral;
import org.mozilla.javascript.ast.ObjectLiteral;
import org.mozilla.javascript.ast.ObjectProperty;
import org.mozilla.javascript.ast.StringLiteral;

public class ObjectAnonymous extends Container {

	public String description = "";
	public boolean skip = false;
	
	public ObjectAnonymous(Container parent, String file, ObjectLiteral obj, Node... docs) {
		super(parent, new Location(file, obj));
		JSDoc doc = new JSDoc(obj, docs);
		if (doc.hasTag("no_doc")) {
			skip = true;
			return;
		}
		this.description = doc.description;
		for (ObjectProperty p : obj.getElements()) {
			AstNode left = p.getLeft();
			String n;
			if (left instanceof Name)
				n = ((Name)left).getIdentifier();
			else if (left instanceof StringLiteral)
				n = ((StringLiteral)left).getValue();
			else if (left instanceof NumberLiteral)
				n = ((NumberLiteral)left).getValue();
			else
				throw new RuntimeException("Unexpected type for object property name: "+left.getClass());
			add(n, new ValueToEvaluate(file, p.getRight(), p, p.getLeft()));
		}
	}
	public ObjectAnonymous(Container parent) {
		super(parent, new Location());
	}
	
	@Override
	public boolean skip() { return skip; }

	@Override
	protected String getJSDocConstructor() {
		return "JSDoc_Namespace(";
	}
	@Override
	public String getDescription() {
		return description;
	}
	@Override
	public void setDescription(String doc) {
		description = doc;
	}
	
}
