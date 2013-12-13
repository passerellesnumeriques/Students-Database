package org.pn.jsdoc.model;

import java.util.Iterator;
import java.util.LinkedList;

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

public class Global extends Container {

	public Global() {
		super(new Location());
	}
	
	@Override
	protected String getJSDocConstructor() {
		return "JSDoc_Namespace(";
	}
	
	@Override
	protected void parse_node(String file, Node node) {
		if (node instanceof FunctionNode) {
			parse_function(file, (FunctionNode)node);
			return;
		}
		if (node instanceof VariableDeclaration) {
			VariableDeclaration vd = (VariableDeclaration)node;
			for (VariableInitializer vi : vd.getVariables()) {
				String name = ((Name)vi.getTarget()).getIdentifier();
				add(name, new ValueToEvaluate(file, vi.getInitializer(), (AstNode)node));
			}
			return;
		}
		super.parse_node(file, node);
	}
	
	private void parse_function(String file, FunctionNode node) {
		// look if this is a constructor
		boolean constructor = false;
		JSDoc doc = new JSDoc(node, node);
		for (Iterator<JSDoc.Tag> it = doc.tags.iterator(); it.hasNext(); ) {
			JSDoc.Tag tag = it.next();
			if (tag.name.equals("constructor")) {
				constructor = true;
			}
		}
		if (!constructor) {
			Block body = (Block)node.getBody();
			class MutableBoolean {
				boolean value = false;
			}
			final MutableBoolean is_constructor = new MutableBoolean();
			class Visitor implements NodeVisitor {
				LinkedList<String> is_this = new LinkedList<String>();
				@Override
				public boolean visit(AstNode node) {
					if (is_constructor.value) return false;
					if (node instanceof ExpressionStatement) {
						AstNode expr = ((ExpressionStatement)node).getExpression();
						if (expr instanceof Assignment) {
							AstNode target = ((Assignment)expr).getLeft();
							LinkedList<String> names = getIdentifiers(target);
							if (names.size() == 0) return true;
							String s = names.get(0);
							for (int i = 1; i < names.size(); ++i) s += "."+names.get(i);
							for (String t : is_this)
								if (s.equals(t) || s.startsWith(t+".")) {
									is_constructor.value = true;
									return false;
								}
						}
					} else if (node instanceof VariableDeclaration) {
						for (VariableInitializer vi : ((VariableDeclaration)node).getVariables()) {
							String name = ((Name)vi.getTarget()).getIdentifier();
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
			v.is_this.add("this");
			body.visit(v);
			constructor = is_constructor.value;
		}
		if (constructor) {
			add(node.getName(), new Class(file, node));
		} else {
			add(node.getName(), new Function(file, node, node));
		}
	}
	
}
