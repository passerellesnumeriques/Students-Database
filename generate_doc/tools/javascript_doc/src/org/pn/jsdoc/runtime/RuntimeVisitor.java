package org.pn.jsdoc.runtime;

import org.mozilla.javascript.Node;
import org.mozilla.javascript.ast.AstNode;
import org.mozilla.javascript.ast.Block;
import org.mozilla.javascript.ast.Name;
import org.mozilla.javascript.ast.NodeVisitor;
import org.mozilla.javascript.ast.VariableDeclaration;
import org.mozilla.javascript.ast.VariableInitializer;
import org.pn.jsdoc.model.Container;
import org.pn.jsdoc.model.Element;
import org.pn.jsdoc.runtime.RuntimeContext.Variable;

public class RuntimeVisitor implements NodeVisitor {

	public static interface Listener {
		public boolean visit(AstNode node, RuntimeContext context);
	}
	
	private RuntimeContext context;
	private Listener listener;
	private Element element;
	
	public RuntimeVisitor(Element where, Listener listener) {
		this.context = new RuntimeContext();
		this.listener = listener;
		this.element = where;
	}
	public RuntimeVisitor(RuntimeVisitor parent) {
		this.context = new RuntimeContext();
		this.listener = parent.listener;
		this.element = parent.element;
		for (Variable v : parent.context.variables)
			this.context.variables.add(v);
	}
	
	public RuntimeContext getContext() { return context; }
	
	@Override
	public boolean visit(AstNode node) {
		if (!listener.visit(node, this.context)) return false;
		if (node instanceof Block) {
			// new context
			RuntimeVisitor child = new RuntimeVisitor(this);
			for (Node n : node)
				if (n instanceof AstNode) ((AstNode)n).visit(child);
			return false;
		}
		if (node instanceof VariableDeclaration) {
			VariableDeclaration vd = (VariableDeclaration)node;
			for (VariableInitializer vi : vd.getVariables()) {
				String name = ((Name)vi.getTarget()).getIdentifier();
				Element val = null;
				if (element instanceof Container)
					val = ((Container)element).getValue(vi.getInitializer(), (AstNode)node);
				if (val == null) continue;
				context.addVariable(name, val.getType());
			}
		}
		return true;
	}
	
}
