package org.pn.jsdoc.model;

import org.mozilla.javascript.Node;
import org.mozilla.javascript.ast.AstNode;

public class ObjectClass extends FinalElement {

	public String type = null;
	public String description = "";
	
	public ObjectClass(String file, String type, AstNode node, Node... docs) {
		super(new Location(file, node));
		this.type = type;
		parse_doc(node, docs);
	}
	public ObjectClass(String type) {
		super(new Location());
		this.type = type;
	}
	public ObjectClass(String file, String type, AstNode node, String description) {
		super(new Location(file, node));
		this.type = type;
		this.description = description;
	}
	private void parse_doc(AstNode node, Node... docs) {
		JSDoc doc = new JSDoc(node, docs);
		this.description = doc.description;
		for (JSDoc.Tag tag : doc.tags) {
			error("Not supported tag for ObjectClass: "+tag.name);
		}
	}
	
	@Override
	public String getType() {
		return type;
	}
	
	@Override
	public String generate(String indent) {
		return "new JSDoc_Value(\""+this.type+"\",\""+this.description.replace("\\", "\\\\").replace("\"", "\\\"")+"\","+location.generate()+")";
		// TODO if no comment
	}
	
}
