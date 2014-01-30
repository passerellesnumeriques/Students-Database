package org.pn.jsdoc.model;

import org.mozilla.javascript.Node;

public class Location {

	public String file;
	public int line;
	public Node node;
	
	public Location() {
		this.file = "";
		this.line = -1;
		this.node = null;
	}
	public Location(String file, Node node) {
		this.file = file;
		this.line = node.getLineno();
		this.node = node;
	}
	
	public String generate() {
		return "new JSDoc_Location(\""+file.replace("\\","\\\\").replace("\"","\\\"")+"\","+line+")";
	}
	
}
