package org.pn.jsdoc.model;

import java.util.LinkedList;

import org.mozilla.javascript.Node;
import org.mozilla.javascript.ast.AstNode;
import org.mozilla.javascript.ast.FunctionNode;
import org.mozilla.javascript.ast.Name;
import org.mozilla.javascript.ast.NodeVisitor;
import org.mozilla.javascript.ast.ReturnStatement;

public class Function extends FinalElement {

	public Container container;
	public LinkedList<Parameter> parameters = new LinkedList<>();
	public String description = "";
	public String return_type = "void";
	public String return_description = "";
	public Node[] docs_nodes;
	public boolean no_name_check = false;
	
	public static class Parameter {
		public String name;
		public String type = null;
		public String description = "";
		public AstNode node;
	}
	
	public Function(Container container, String file, FunctionNode node, Node... docs) {
		super(new Location(file, node));
		this.container = container;
		this.docs_nodes = docs;
		for (AstNode n : node.getParams()) {
			Parameter p = new Parameter();
			p.name = ((Name)n).getIdentifier();
			p.node = n;
			parameters.add(p);
		}
		JSDoc doc = new JSDoc(node, docs);
		this.description = doc.description;
		for (JSDoc.Tag tag : doc.tags) {
			if (tag.name.equals("param")) {
				String s = tag.comment.trim();
				String type = null;
				String name = null;
				String comment = null;
				if (s.startsWith("{")) {
					int i = s.indexOf('}');
					if (i > 0) {
						type = s.substring(1, i);
						s = s.substring(i+1).trim();
					}
				}
				int i = s.indexOf(' ');
				if (i < 0)
					name = s;
				else {
					name = s.substring(0,i).trim();
					comment = s.substring(i+1).trim();
				}
				if (name != null) {
					boolean found = false;
					for (Parameter p : parameters) {
						if (p.name.equals(name)) {
							found = true;
							p.type = type;
							if (comment != null) p.description = comment;
							break;
						}
					}
					if (!found)
						error("Unknown parameter "+name+" in function");
				} else
					error("Invalid JSDoc tag param for function");
			} else if (tag.name.equals("returns")) {
				String s = tag.comment.trim();
				if (s.startsWith("{")) {
					int i = s.indexOf('}');
					if (i > 0) {
						return_type = s.substring(1, i);
						s = s.substring(i+1).trim();
					}
				}
				return_description = s;
			} else if (tag.name.equals("constructor")) {
				// ignore
			} else if (tag.name.equals("no_name_check")) {
				no_name_check = true;
			} else
				error("Unknown JSDoc tag "+tag.name+" for function");
		}
		if (return_type.equals("void")) {
			// try to find if this is really the case
			final LinkedList<String> ok = new LinkedList<>();
			NodeVisitor visitor = new NodeVisitor() {
				@Override
				public boolean visit(AstNode node) {
					if (node instanceof ReturnStatement) {
						AstNode value = ((ReturnStatement)node).getReturnValue();
						if (value == null) { return false; }
						// not nothing !
						if (ok.isEmpty()) ok.add("");
						return false;
					}
					return true;
				}
			};
			node.getBody().visit(visitor);
			if (!ok.isEmpty()) {
				// TODO return not documented
			}
		}
	}
	
	public Function(Container container, String return_type) {
		super(new Location());
		this.container = container;
		this.return_type = return_type;
	}

	public Function(Container container, String file, Node node, String description) {
		super(new Location(file, node));
		this.description = description;
	}
	
	@Override
	public String getType() {
		return return_type;
	}
	@Override
	public void setType(String type) {
		return_type = type;
	}
	
	public String generate(String indent) {
		StringBuilder s = new StringBuilder();
		s.append("new JSDoc_Function(");
		s.append("\"").append(this.description.replace("\\", "\\\\").replace("\"", "\\\"")).append("\",");
		// TODO if no comment
		s.append("[");
		boolean first = true;
		for (Parameter p : parameters) {
			if (first) first = false; else s.append(", ");
			s.append("{name:\"").append(p.name).append("\",type:");
			if (p.type == null) s.append("null"); else s.append("\"").append(p.type).append("\"");
			s.append(",doc:\"").append(p.description.replace("\\", "\\\\").replace("\"", "\\\"")).append("\"");
			// TODO if no comment
			s.append("}");
		}
		s.append("],\"").append(return_type).append("\",\"").append(return_description.replace("\\", "\\\\").replace("\"", "\\\"")).append("\"");
		// TODO if no comment for return
		s.append(",").append(location.generate());
		s.append(",").append(no_name_check ? "true" : "false");
		s.append(")");
		return s.toString();
	}
	
	@Override
	public String getDescription() {
		return description;
	}
	@Override
	public void setDescription(String doc) {
		description = doc;
	}
}
