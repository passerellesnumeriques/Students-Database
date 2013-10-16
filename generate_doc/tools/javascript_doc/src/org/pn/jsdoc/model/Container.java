package org.pn.jsdoc.model;

import java.util.HashMap;
import java.util.LinkedList;
import java.util.Map;

import org.mozilla.javascript.Node;
import org.mozilla.javascript.Token;
import org.mozilla.javascript.ast.ArrayLiteral;
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
import org.mozilla.javascript.ast.NumberLiteral;
import org.mozilla.javascript.ast.ObjectLiteral;
import org.mozilla.javascript.ast.PropertyGet;
import org.mozilla.javascript.ast.StringLiteral;
import org.mozilla.javascript.ast.UnaryExpression;

public abstract class Container extends FinalElement {

	public Map<String,Element> content = new HashMap<String,Element>();
	public LinkedList<ValueToEvaluate> assignments_to_evaluate = new LinkedList<>();
	
	public Container(Location location) {
		super(location);
	}
	
	public void add(String name, Element element) {
		if (content.containsKey(name))
			System.err.println("TODO: Duplicate definition of "+name);
		content.put(name, element);
	}
	
	public void parse(String file, Node script) {
		for (Node node : script)
			parse_node(file, node);
	}
	protected void parse_node(String file, Node node) {
		if (node instanceof EmptyStatement) return;
		if (node instanceof FunctionCall) return; // ignore calls ?
		if (node instanceof IfStatement) return; // TODO ?
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
					System.err.println("Target of assignment not supported: "+target.getClass()+": "+expr.toSource());
				}
				return;
			}
			if (expr instanceof FunctionCall) return; // ignore calls ?
			System.err.println("Expression not supported: "+expr.getClass()+" in "+this.getClass());
			return;
		}
		System.err.println("Node not supported: "+node.getClass()+" in "+this.getClass());
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
				System.err.println("Unexpected KeywordLiteral in identifiers: "+node.toSource()+" // context: "+node.getParent().getParent().toSource());
				break;
			}
			return new LinkedList<String>(); // not just names
			//System.err.println("Unexpected identifier node: "+node.getClass()+": "+node.toSource());
			//break;
		} while (node != null);
		return names;
	}
	
	public void evaluate() {
		HashMap<String,Element> map = new HashMap<String,Element>(content);
		for (Map.Entry<String, Element> e : map.entrySet()) {
			if (e.getValue() instanceof FinalElement) continue;
			ValueToEvaluate te = (ValueToEvaluate)e.getValue();
			FinalElement val = evaluate_value(te.location.file, te.value, te.docs);
			if (val != null)
				content.put(e.getKey(), val);
			else
				content.put(e.getKey(), new SimpleValue("?cannot evaluate?", te.value, te.docs));
		}
		for (Map.Entry<String, Element> e : content.entrySet()) {
			if (e.getValue() instanceof Container)
				((Container)e.getValue()).evaluate();
		}
		for (ValueToEvaluate e : assignments_to_evaluate) {
			System.err.println("TODO: evaluate assignment: "+e.value.toSource());
			// TODO
		}
	}

	private FinalElement evaluate_value(String file, AstNode value, Node... docs) {
		FinalElement val = null;
		if (value instanceof FunctionNode) {
			val = new Function(file, (FunctionNode)value, docs);
		} else if (value instanceof ObjectLiteral) {
			val = new ObjectAnonymous(file, (ObjectLiteral)value);
		} else if (value instanceof NumberLiteral || value instanceof StringLiteral) {
			val = new SimpleValue(file, value, docs);
		} else if (value instanceof ArrayLiteral) {
			val = new ObjectClass(file, value, docs);
		} else if (value instanceof KeywordLiteral) {
			switch (value.getType()) {
			case Token.NULL: val = new SimpleValue(file, "null", value, docs); break;
			case Token.TRUE: val = new SimpleValue(file, "Boolean", value, docs); break;
			case Token.FALSE: val = new SimpleValue(file, "Boolean", value, docs); break;
			default: System.err.println("Keyword not supported for value: "+value.toSource());
			}
		} else if (value instanceof UnaryExpression) {
			return evaluate_value(file, ((UnaryExpression)value).getOperand(), docs);
		} else if (value instanceof NewExpression) {
			AstNode target = ((NewExpression)value).getTarget();
			if (target instanceof Name)
				val = new ObjectClass(file, ((Name)target).getIdentifier(), value);
			else
				System.err.println("Cannot determine class to instantiate: "+value.toSource());
		} else {
			System.err.println("Value not supported: "+value.getClass()+": "+value.toSource());
		}
		return val;
	}
	
	protected abstract String getJSDocConstructor();
	public String generate(String indent) {
		StringBuilder s = new StringBuilder();
		s.append("new ").append(getJSDocConstructor()).append("{\r\n");
		boolean first = true;
		for (Map.Entry<String,Element> e : content.entrySet()) {
			s.append(indent);
			if (first) first = false; else s.append(",");
			s.append(e.getKey()+": ");
			if (e.getValue() instanceof FinalElement)
				s.append(((FinalElement)e.getValue()).generate(indent+"  "));
			else
				s.append("\"NOT FINAL!\"");
			s.append("\r\n");
		}
		s.append(indent+"},").append(location.generate()).append(")");
		return s.toString();
	}
	
}
