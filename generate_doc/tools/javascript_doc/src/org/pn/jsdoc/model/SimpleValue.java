package org.pn.jsdoc.model;

import org.mozilla.javascript.Node;
import org.mozilla.javascript.ast.AstNode;
import org.mozilla.javascript.ast.NumberLiteral;
import org.mozilla.javascript.ast.StringLiteral;

public class SimpleValue extends FinalElement {

	public String type;
	public String description = "";
	
	public SimpleValue(String file, AstNode node, Node... docs) {
		super(new Location(file, node));
		if (node instanceof NumberLiteral) {
			type = "Number";
		} else if (node instanceof StringLiteral) {
			type = "String";
		} else {
			System.err.println("Unexpected node "+node.getClass()+" for simple value");
		}
		parse_doc(node, docs);
	}
	public SimpleValue(String file, String type, AstNode node, Node... docs) {
		super(new Location(file, node));
		this.type = type;
		parse_doc(node, docs);
	}
	private void parse_doc(AstNode node, Node... docs) {
		JSDoc doc = new JSDoc(node, docs);
		this.description = doc.description;
		for (JSDoc.Tag tag : doc.tags) {
			System.err.println("Not supported tag for SimpleValue: "+tag.name);
		}
	}
	
	@Override
	public String generate(String indent) {
		return "new JSDoc_Value(\""+this.type+"\",\""+this.description.replace("\\", "\\\\").replace("\"", "\\\"")+"\","+location.generate()+")";
		// TODO if no comment
	}
	
}
