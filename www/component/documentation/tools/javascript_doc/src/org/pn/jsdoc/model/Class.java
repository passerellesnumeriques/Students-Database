package org.pn.jsdoc.model;

import java.util.HashMap;
import java.util.LinkedList;
import java.util.Map;

import org.mozilla.javascript.Node;
import org.mozilla.javascript.ast.Assignment;
import org.mozilla.javascript.ast.AstNode;
import org.mozilla.javascript.ast.Block;
import org.mozilla.javascript.ast.ExpressionStatement;
import org.mozilla.javascript.ast.FunctionNode;
import org.mozilla.javascript.ast.Name;
import org.mozilla.javascript.ast.NodeVisitor;
import org.mozilla.javascript.ast.VariableDeclaration;
import org.mozilla.javascript.ast.VariableInitializer;
import org.pn.jsdoc.model.Function.Parameter;

public class Class extends Container {

	public Function constructor;
	public String name;
	public String description;
	public String extended_class = null;
	
	public Class(Container parent, final String file, FunctionNode node, Node[] docs) {
		super(parent, new Location(file,node));
		JSDoc doc = new JSDoc(node, docs);
		description = doc.description;
		constructor = new Function(this, file, node, node);
		name = node.getName();
		add("constructor", constructor);
		// try to find assignments to this
		Block body = (Block)node.getBody();
		class Visitor implements NodeVisitor {
			LinkedList<String> is_this = new LinkedList<String>();
			HashMap<String,Element> variables = new HashMap<>();
			@Override
			public boolean visit(AstNode node) {
				if (node instanceof ExpressionStatement) {
					AstNode expr = ((ExpressionStatement)node).getExpression();
					if (expr instanceof Assignment) {
						AstNode target = ((Assignment)expr).getLeft();
						LinkedList<String> names = getIdentifiers(target);
						if (names.size() < 2) return true;
						String s = names.get(0);
						for (int i = 1; i < names.size()-1; ++i) s += "."+names.get(i);
						for (String t : is_this)
							if (s.equals(t)) {
								AstNode value = ((Assignment)expr).getRight();
								// TODO add context
								add(names.get(names.size()-1), new ValueToEvaluate(file, value, new Node[] { node, expr, target, value }));
								break;
							}
					}
				} else if (node instanceof VariableDeclaration) {
					for (VariableInitializer vi : ((VariableDeclaration)node).getVariables()) {
						String name = ((Name)vi.getTarget()).getIdentifier();
						if (vi.getInitializer() == null)
							variables.put(name, new ObjectClass(file, "undefined", vi, node));
						else
							variables.put(name, new ValueToEvaluate(file, vi.getInitializer(), node));
						LinkedList<String> names = getIdentifiers(vi.getInitializer());
						if (names.isEmpty()) continue;
						String s = names.get(0);
						for (int i = 1; i < names.size(); ++i) s += "."+names.get(i);
						for (String t : is_this)
							if (s.equals(t)) {
								is_this.add(name);
								break;
							}
					}
				} else if (node instanceof Block) {
					Visitor sub_visitor = new Visitor();
					for (String s : is_this) sub_visitor.is_this.add(s);
					for (Map.Entry<String,Element> e : variables.entrySet()) sub_visitor.variables.put(e.getKey(), e.getValue());
					for (Node n : node)
						if (n instanceof AstNode) ((AstNode)n).visit(sub_visitor);
					return false;
				} else if (node instanceof FunctionNode) {
					// TODO in case we still have this...
					return false;
				}
				return true;
			}
		};
		Visitor v = new Visitor();
		for (Parameter p : constructor.parameters) {
			v.variables.put(p.name, new ObjectClass(file, p.type, p.node, new Node[0]));
		}
		v.is_this.add("this");
		body.visit(v);
	}
	protected Class(Global global) {
		super(global, new Location());
	}
	
	@Override
	protected String getJSDocConstructor() {
		return "JSDoc_Class("+(extended_class != null ? "\""+extended_class+"\"" : "null")+",";
	}
	@Override
	protected String getDescription() {
		return description;
	}
	
}
