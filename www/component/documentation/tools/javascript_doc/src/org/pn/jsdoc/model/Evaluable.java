package org.pn.jsdoc.model;

import org.mozilla.javascript.Node;
import org.mozilla.javascript.ast.AstNode;

public interface Evaluable {

	public Element evaluate(Context ctx);
	
	public Location getLocation();
	public AstNode getNode();
	public Node[] getDocs();
	
	public static class Context {
		public Global global;
		public Container container;
		public boolean need_reevaluation = false;
	}
	
}
