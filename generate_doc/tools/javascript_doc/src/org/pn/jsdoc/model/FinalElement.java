package org.pn.jsdoc.model;

public abstract class FinalElement extends Element {

	public FinalElement(Location location) {
		super(location);
	}
	
	public abstract String generate(String indent);
	
}
