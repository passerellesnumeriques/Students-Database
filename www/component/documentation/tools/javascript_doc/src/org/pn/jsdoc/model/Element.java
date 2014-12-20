package org.pn.jsdoc.model;

import org.mozilla.javascript.Node;

public abstract class Element {

	public Location location;
	
	public Element(Location location) {
		this.location = location;
	}
	
	public abstract boolean skip();
	
	protected void error(String message) {
		System.err.println(message+" ("+this.location.file+":"+this.location.line+")");
	}

	protected void error(String message, Element elem) {
		elem.error(message+" ("+this.location.file+":"+this.location.line+")");
	}
	
	protected void error(String message, String file, Node node) {
		System.err.println(message+" ("+(file != null ? file : this.location.file)+":"+node.getLineno()+")");
	}
	
}
