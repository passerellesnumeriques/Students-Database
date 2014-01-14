package org.pn.jsdoc.model;

import java.util.HashMap;
import java.util.LinkedList;
import java.util.List;
import java.util.Map;

import org.mozilla.javascript.Node;
import org.mozilla.javascript.Token;
import org.mozilla.javascript.ast.Assignment;
import org.mozilla.javascript.ast.AstNode;
import org.mozilla.javascript.ast.EmptyStatement;
import org.mozilla.javascript.ast.ExpressionStatement;
import org.mozilla.javascript.ast.FunctionCall;
import org.mozilla.javascript.ast.FunctionNode;
import org.mozilla.javascript.ast.IfStatement;
import org.mozilla.javascript.ast.KeywordLiteral;
import org.mozilla.javascript.ast.Name;
import org.mozilla.javascript.ast.NewExpression;
import org.mozilla.javascript.ast.ObjectLiteral;
import org.mozilla.javascript.ast.PropertyGet;
import org.mozilla.javascript.ast.Scope;
import org.pn.jsdoc.model.Evaluable.Context;
import org.pn.jsdoc.model.builtin.Builtin;

public abstract class Container extends FinalElement {

	public Container parent;
	public Map<String,Element> content = new HashMap<String,Element>();
	public LinkedList<ValueToEvaluate> assignments_to_evaluate = new LinkedList<>();
	
	public Container(Container parent, Location location) {
		super(location);
		this.parent = parent;
	}
	
	public void add(String name, Element element) {
		if (content.containsKey(name)) {
			Element current = content.get(name);
			if (current instanceof Evaluable) {
				if (element instanceof Evaluable) {
					content.put(name, new ValuesToEvaluate((Evaluable)current, (Evaluable)element));
					return;
				}
			}
			error("TODO: Duplicate definition of "+name+":\r\n - "+content.get(name).toString()+"\r\n - "+element.toString()+"\r\n", element);
		}
		content.put(name, element);
	}
	
	public void parse(String file, Node script) {
		for (Node node : script)
			parse_node(file, node);
	}
	protected void parse_node(String file, Node node) {
		if (node instanceof EmptyStatement) return;
		if (node instanceof FunctionCall) return; // ignore calls ?
		if (node instanceof IfStatement) {
			parse_node(file, ((IfStatement)node).getThenPart());
			if (((IfStatement)node).getElsePart() != null)
				parse_node(file, ((IfStatement)node).getElsePart());
			return;
		}
		if (node instanceof Scope) {
			for (Node n : ((Scope)node).getStatements())
				parse_node(file, n);
			return;
		}
		if (node instanceof ExpressionStatement) {
			AstNode expr = ((ExpressionStatement)node).getExpression();
			if (expr instanceof Assignment) {
				Assignment assign = (Assignment)expr;
				AstNode target = assign.getLeft();
				if (target instanceof Name) {
					add(((Name)target).getIdentifier(), new ValueToEvaluate(file, assign.getRight(), (AstNode)node));
				} else if (target instanceof PropertyGet) {
					assignments_to_evaluate.add(new ValueToEvaluate(file, assign, (AstNode)node));
				} else {
					error("Target of assignment not supported: "+target.getClass()+": "+expr.toSource(), file, node);
				}
				return;
			}
			if (expr instanceof FunctionCall) return; // ignore calls ?
			error("Expression not supported: "+expr.getClass()+" in "+this.getClass(), file, node);
			return;
		}
		error("Node not supported: "+node.getClass()+" in "+this.getClass(), file, node);
	}
	
	protected LinkedList<String> getIdentifiers(AstNode node) {
		LinkedList<String> names = new LinkedList<>();
		do {
			if (node instanceof Name) {
				names.addFirst(((Name)node).getIdentifier());
				break;
			}
			if (node instanceof PropertyGet) {
				names.addFirst(((PropertyGet)node).getProperty().getIdentifier());
				node = ((PropertyGet)node).getTarget();
				continue;
			}
			if (node instanceof KeywordLiteral) {
				if (node.getType() == Token.THIS) {
					names.addFirst("this");
					break;
				}
				if (node.getType() == Token.NULL) {
					names.addFirst("null");
					break;
				}
				if (node.getType() == Token.TRUE) {
					names.addFirst("true");
					break;
				}
				if (node.getType() == Token.FALSE) {
					names.addFirst("false");
					break;
				}
				error("Unexpected KeywordLiteral in identifiers: "+node.toSource()+" // context: "+node.getParent().getParent().toSource(), null, node);
				break;
			}
			return new LinkedList<String>(); // not just names
			//System.err.println("Unexpected identifier node: "+node.getClass()+": "+node.toSource());
			//break;
		} while (node != null);
		return names;
	}
	
	public boolean evaluate(Global global, List<Container> done) {
		if (done.contains(this)) return false;
		done.add(this);
		boolean has_more = false;
		HashMap<String,Element> map = new HashMap<String,Element>(content);
		for (Map.Entry<String, Element> e : map.entrySet()) {
			if (e.getValue() instanceof FinalElement) continue;
			Evaluable te = (Evaluable)e.getValue();
			Context ctx = new Context();
			ctx.container = this;
			ctx.global = global;
			Element val = te.evaluate(ctx);
			while (val instanceof Evaluable) val = ((Evaluable)val).evaluate(ctx);
			if (val != null)
				content.put(e.getKey(), val);
			else
				content.put(e.getKey(), new ObjectClass(te.getLocation().file, "?cannot evaluate?", te.getNode(), te.getDocs()));
		}
		for (Map.Entry<String, Element> e : content.entrySet()) {
			if (e.getValue() instanceof Container)
				has_more |= ((Container)e.getValue()).evaluate(global, done);
		}
		for (ValueToEvaluate e : assignments_to_evaluate) {
			Assignment assign = (Assignment)e.value;
			AstNode target = assign.getLeft();
			LinkedList<String> names = getIdentifiers(target);
			if (names.get(0).equals("window")) names.remove(0);
			if (names.size() == 1) {
				add(names.get(0), new ValueToEvaluate(e.location.file, assign.getRight(), e.value));
				has_more = true;
				continue;
			}
			Container cont = this;
			if (names.get(0).equals("top")) {
				if (global.content.get("window_top") == null)
					global.add("window_top", cont = new Global());
				else
					cont = (Container)global.content.get("window_top");
				names.remove(0);
				if (names.size() == 1) {
					cont.add(names.get(0), new ValueToEvaluate(e.location.file, assign.getRight(), e.value));
					has_more = true;
					continue;
				}
			}
			do {
				if (names.get(0).equals("prototype")) {
					if (cont instanceof Class) {
						if (names.size() == 1) {
							// assignment to a prototype
							if (assign.getRight() instanceof NewExpression) {
								NewExpression n = (NewExpression)assign.getRight();
								AstNode t = n.getTarget();
								if (t instanceof Name) {
									// TODO resolve
									// TODO check not already extended
									((Class)cont).extended_class = ((Name)t).getIdentifier();
									break;
								} else {
									error("Cannot determine class to extend: "+e.value.toSource(), e);
									break;
								}
							} else if (assign.getRight() instanceof ObjectLiteral) {
								Class cl = (Class)cont;
								ObjectAnonymous o = new ObjectAnonymous(cl.parent, e.location.file, (ObjectLiteral)assign.getRight(), e.docs);
								for (Map.Entry<String, Element> entry : o.content.entrySet())
									cl.add(entry.getKey(), entry.getValue());
							} else {
								error("Assignment to a class prototype must be a new expression: "+e.value.toSource(), e);
								break;
							}
						}
						if (names.size() != 2) {
							error("Not supported: more than 1 level after prototype: "+e.value.toSource(), e);
							break;
						}
						if (names.get(1).equals("constructor")) break; // skip
						cont.content.put(names.get(1), new ValueToEvaluate(e.location.file, assign.getRight(), e.value));
						has_more = true;
						break;
					} else {
						error("Cannot use prototype on an element which is not a class", e);
						break;
					}
				}
				Element elem = cont.content.get(names.get(0));
				if (elem == null) {
					error("Unknown element '"+names.get(0)+"' in "+e.value.toSource(), e);
					break;
				}
				if (!(elem instanceof Container)) {
					// handle case of a function which is a class
					if ((elem instanceof Function) && names.size() > 1 && names.get(1).equals("prototype")) {
						// convert into a class
						Class c = new Class(cont, elem.location.file, (FunctionNode)elem.location.node, ((Function)elem).docs_nodes);
						String name = ((Function)elem).container.getName(elem);
						((Function)elem).container.content.put(name, c);
						elem = c;
					} else {
						error("Element '"+names.get(0)+"' is not a container (found:"+elem.getClass().getName()+") in "+e.value.toSource(), e);
						break;
					}
				}
				names.remove(0);
				if (names.size() == 1 && !names.get(0).equals("prototype")) {
					cont.add(names.get(0), new ValueToEvaluate(e.location.file, assign.getRight(), e.value));
					has_more = true;
					break;
				}
				cont = (Container)elem;
			} while (cont != null);
		}
		assignments_to_evaluate.clear();
		return has_more;		
	}
	
	public String getName(Element e) {
		for (Map.Entry<String,Element> entry : content.entrySet())
			if (entry.getValue() == e) return entry.getKey();
		return null;
	}
	
	@Override
	public String getType() {
		if (parent == null) return "";
		String t = parent.getType();
		if (t.length() > 0) t += ".";
		t += parent.getName(this);
		return t;
	}

	protected abstract String getJSDocConstructor();
	protected abstract String getDescription();
	public String generate(String indent) {
		StringBuilder s = new StringBuilder();
		s.append("new ").append(getJSDocConstructor()).append("{\r\n");
		boolean first = true;
		for (Map.Entry<String,Element> e : content.entrySet()) {
			if (e.getValue() instanceof Builtin) continue;
			s.append(indent);
			if (first) first = false; else s.append(",");
			s.append(e.getKey()+": ");
			if (e.getValue() instanceof FinalElement)
				s.append(((FinalElement)e.getValue()).generate(indent+"  "));
			else
				s.append("\"NOT FINAL!\"");
			s.append("\r\n");
		}
		s.append(indent+"},").append(location.generate()).append(",\"").append(this.getDescription().replace("\\", "\\\\").replace("\"", "\\\"")).append("\")");
		return s.toString();
	}
	
}
