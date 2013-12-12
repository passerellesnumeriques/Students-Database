package org.pn.jsdoc.runtime;

import java.util.LinkedList;

public class RuntimeContext {

	public LinkedList<Variable> variables = new LinkedList<>();
	
	public static class Variable {
		public String name;
		public String type;
	}
	
	public void addVariable(String name, String type) {
		Variable v = new Variable();
		v.name = name;
		v.type = type;
		variables.addFirst(v);
	}
	
	public Variable getVariable(String name) {
		for (Variable v : variables)
			if (v.name.equals(name))
				return v;
		return null;
	}
	
}
