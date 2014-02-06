package org.pn.jsdoc.model;

import java.util.HashMap;
import java.util.Map;

import org.mozilla.javascript.Node;
import org.mozilla.javascript.Token;
import org.mozilla.javascript.ast.ArrayLiteral;
import org.mozilla.javascript.ast.AstNode;
import org.mozilla.javascript.ast.ConditionalExpression;
import org.mozilla.javascript.ast.FunctionCall;
import org.mozilla.javascript.ast.FunctionNode;
import org.mozilla.javascript.ast.InfixExpression;
import org.mozilla.javascript.ast.KeywordLiteral;
import org.mozilla.javascript.ast.Name;
import org.mozilla.javascript.ast.NewExpression;
import org.mozilla.javascript.ast.NumberLiteral;
import org.mozilla.javascript.ast.ObjectLiteral;
import org.mozilla.javascript.ast.ParenthesizedExpression;
import org.mozilla.javascript.ast.PropertyGet;
import org.mozilla.javascript.ast.StringLiteral;
import org.mozilla.javascript.ast.UnaryExpression;
import org.pn.jsdoc.model.Function.Parameter;

public class ValueToEvaluate extends Element implements Evaluable {

	public AstNode value;
	public Node[] docs;
	
	public ValueToEvaluate(String file, AstNode value, Node... docs) {
		super(new Location(file,value));
		this.value = value;
		this.docs = docs;
	}
	
	@Override
	public AstNode getNode() { return value; }
	@Override
	public Node[] getDocs() { return docs; }
	@Override
	public Location getLocation() { return location; }
	
	@Override
	public String toString() {
		return "ValueToEvaluate["+value.toSource()+"]";
	}
	
	private static class ContextVariable {
		ContextVariable(String type, String description) {
			this.type = type;
			this.description = description;
		}
		String type;
		String description;
	}
	private Map<String,ContextVariable> context = new HashMap<String,ContextVariable>();
	public void addContext_FunctionParameters(Function f) {
		for (Parameter p : f.parameters) {
			context.put(p.name, new ContextVariable(p.type, p.description));
		}
	}
	
	private ValueToEvaluate sub(AstNode value, Node... docs) {
		ValueToEvaluate ve = new ValueToEvaluate(location.file, value, docs);
		for (String name : context.keySet())
			ve.context.put(name, context.get(name));
		return ve;
	}
	
	public FinalElement evaluate(Context ctx) {
		FinalElement val = null;
		if (value instanceof FunctionNode) {
			val = new Function(ctx.container, this.location.file, (FunctionNode)value, docs);
		} else if (value instanceof ObjectLiteral) {
			val = new ObjectAnonymous(ctx.container, this.location.file, (ObjectLiteral)value, docs);
		} else if (value instanceof NumberLiteral) {
			val = new ObjectClass(this.location.file, "Number", value, docs);
		} else if (value instanceof StringLiteral) {
			val = new ObjectClass(this.location.file, "String", value, docs);
		} else if (value instanceof ArrayLiteral) {
			val = new ObjectClass(this.location.file, "Array", value, docs);
		} else if (value instanceof KeywordLiteral) {
			switch (value.getType()) {
			case Token.NULL: {
				JSDoc doc = new JSDoc(value, docs);
				String s = doc.description.trim();
				if (s.startsWith("{")) {
					int i = s.indexOf('}');
					String type = s.substring(1, i);
					doc.description = s.substring(i+1).trim();
					if (type.toLowerCase().equals("function")) {
						val = new Function(ctx.container, this.location.file, value, doc.description);
					} else
						val = new ObjectClass(this.location.file, type, value, doc.description); break;
				} else
					val = new ObjectClass(this.location.file, "null", value, docs); break;
			}
			case Token.TRUE: val = new ObjectClass(this.location.file, "Boolean", value, docs); break;
			case Token.FALSE: val = new ObjectClass(this.location.file, "Boolean", value, docs); break;
			default: System.err.println("Keyword not supported for value: "+value.toSource());
			}
		} else if (value instanceof UnaryExpression) {
			return sub(((UnaryExpression)value).getOperand(), docs).evaluate(ctx);
		} else if (value instanceof NewExpression) {
			AstNode target = ((NewExpression)value).getTarget();
			if (target instanceof Name)
				val = new ObjectClass(this.location.file, ((Name)target).getIdentifier(), value, docs);
			else
				error("Cannot determine class to instantiate: "+value.toSource(), this.location.file, value);
		} else if (value instanceof Name) {
			String name = ((Name)value).getIdentifier();
			if (context.containsKey(name)) {
				ContextVariable v = context.get(name);
				val = new ObjectClass(this.location.file, v.type, value, docs);
				if (((ObjectClass)val).description.length() == 0)
					((ObjectClass)val).description = v.description;
			} else {
				Object o = ctx.container.content.get(name);
				if (o == this) o = null;
				if (o == null) o = ctx.global.content.get(name);
				if (o == this) o = null;
				if (o == null) {
					String known = "";
					for (Map.Entry<String, ContextVariable> e : context.entrySet())
						known += (known.length() > 0 ? "," : "")+e.getKey();
					error("Unknown name <i>"+name+"</i>; known in this context: "+known, this.location.file, value);
					return null;
				}
				if (o instanceof FinalElement) val = new ObjectClass(this.location.file, ((FinalElement)o).getType(), value, docs);
			}
		} else if (value instanceof PropertyGet) {
			FinalElement left = sub(((PropertyGet)value).getLeft(), docs).evaluate(ctx);
			if (left != null) {
				Container cont = null;
				if (left instanceof Container) cont = (Container)left;
				else if (left instanceof ObjectClass) cont = getContainer(((ObjectClass)left).type, ctx);
				if (cont == null) {
					error("Cannot find container of "+value.toSource(), this.location.file, value);
				} else {
					AstNode right = ((PropertyGet)value).getRight();
					if (!(right instanceof Name)) {
						error("Unexpected "+right.getClass().getName()+" on the right side of "+value.toSource(), this.location.file, value);
					} else {
						String name = ((Name)right).getIdentifier();
						Element o = cont.content.get(name);
						if (o == null)
							error("Container "+cont.getType()+" does not have element "+name);
						else if (o instanceof FinalElement) 
							val = new ObjectClass(this.location.file, ((FinalElement)o).getType(), value, docs);
					}
				}
			}
		} else if (value instanceof FunctionCall) {
			val = sub(((FunctionCall)value).getTarget(), docs).evaluate(ctx);
		} else if (value instanceof ConditionalExpression) {
			ConditionalExpression ce = (ConditionalExpression)value;
			val = new ValuesToEvaluate(
				sub(ce.getTrueExpression(), docs), 
				sub(ce.getFalseExpression(), docs)
			).evaluate(ctx);
			if (val == null)
				error("Cannot evaluate conditional expression: "+value.toSource(), this.location.file, value);
		} else if (value instanceof ParenthesizedExpression) {
			return sub(((ParenthesizedExpression)value).getExpression(), docs).evaluate(ctx);
		} else if (value instanceof InfixExpression) {
			AstNode left = ((InfixExpression)value).getLeft();
			AstNode right = ((InfixExpression)value).getRight();
			FinalElement left_e = sub(left, docs).evaluate(ctx);
			FinalElement right_e = sub(right, docs).evaluate(ctx);
			if (left_e != null && right_e != null) {
				if ("String".equals(left_e.getType())) val = left_e;
				else if ("String".equals(right_e.getType())) val = right_e;
				else if ("Number".equals(left_e.getType()) && "Number".equals(right_e.getType())) val = left_e;
				else error("Unable to determine type for operation between '"+left_e.getType()+"' and '"+right_e.getType()+"': "+value.toSource(), this.location.file, value);
			}
		} else {
			error("Value not supported: "+value.getClass()+": "+value.toSource(), this.location.file, value);
		}
		return val;
	}
	
	private Container getContainer(String type, Context ctx) {
		if (type == null) return null;
		if (type.equals("void")) return null;
		if (type.length() == 0) return null;
		Container c = getContainer(type, ctx.container);
		if (c != null) return c;
		c = getContainer(type, ctx.global);
		if (c != null) return c;
		return null;
	}
	
	private Container getContainer(String type, Container container) {
		int i = type.indexOf('.');
		String name;
		if (i < 0) {
			name = type;
			type = null;
		} else {
			name = type.substring(0,i);
			type = type.substring(i+1);
		}
		Element e = container.content.get(name);
		if (!(e instanceof Container)) return null;
		if (type == null) return (Container)e;
		return getContainer(type, (Container)e);
	}
}
