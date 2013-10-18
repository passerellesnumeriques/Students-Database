package org.pn.jsdoc.model;

import org.mozilla.javascript.Node;
import org.mozilla.javascript.ast.ArrayLiteral;
import org.mozilla.javascript.ast.AstNode;

public class ObjectClass extends FinalElement {

	public String type = null;
	public String description = "";
	
	public ObjectClass(String file, AstNode node, Node... docs) {
		super(new Location(file, node));
		if (node instanceof ArrayLiteral) {
			type = "Array";
		} else {
			System.err.println("Unexpected node "+node.getClass()+" for ObjectClass");
		}
		parse_doc(node, docs);
	}
	public ObjectClass(String file, String type, AstNode node, Node... docs) {
		super(new Location(file, node));
		this.type = type;
		parse_doc(node, docs);
	}
	private void parse_doc(AstNode node, Node... docs) {
		JSDoc doc = new JSDoc(node, docs);
		this.description = doc.description;
		for (JSDoc.Tag tag : doc.tags) {
			System.err.println("Not supported tag for ObjectClass: "+tag.name);
		}
	}
	
	@Override
	public String generate(String indent) {
		return "new JSDoc_Value(\"Object<"+this.type+">\",\""+this.description.replace("\\", "\\\\").replace("\"", "\\\"")+"\","+location.generate()+")";
		// TODO if no comment
	}
	
}
