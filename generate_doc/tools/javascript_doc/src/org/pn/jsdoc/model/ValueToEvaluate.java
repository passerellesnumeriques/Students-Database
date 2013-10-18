package org.pn.jsdoc.model;

import org.mozilla.javascript.Node;
import org.mozilla.javascript.ast.AstNode;

public class ValueToEvaluate extends Element {

	public AstNode value;
	public Node[] docs;
	
	public ValueToEvaluate(String file, AstNode value, Node... docs) {
		super(new Location(file,value));
		this.value = value;
		this.docs = docs;
	}
	
}
