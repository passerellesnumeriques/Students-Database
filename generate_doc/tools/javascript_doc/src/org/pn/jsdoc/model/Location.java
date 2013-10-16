package org.pn.jsdoc.model;

import org.mozilla.javascript.Node;

public class Location {

	public String file;
	public int line;
	
	public Location() {
		this.file = "";
		this.line = -1;
	}
	public Location(String file, Node node) {
		this.file = file;
		this.line = node.getLineno();
	}
	
	public String generate() {
		return "new JSDoc_Location(\""+file.replace("\\","\\\\").replace("\"","\\\"")+"\","+line+")";
	}
	
}
