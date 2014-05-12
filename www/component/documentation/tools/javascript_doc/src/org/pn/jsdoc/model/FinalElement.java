package org.pn.jsdoc.model;

public abstract class FinalElement extends Element {

	public FinalElement(Location location) {
		super(location);
	}
	
	public abstract String getType();
	public abstract void setType(String type);
	public abstract String getDescription();
	public abstract void setDescription(String doc);

	public abstract String generate(String indent);
	
}
