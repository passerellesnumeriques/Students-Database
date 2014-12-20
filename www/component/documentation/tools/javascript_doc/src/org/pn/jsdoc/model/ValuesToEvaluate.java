package org.pn.jsdoc.model;

import org.mozilla.javascript.Node;
import org.mozilla.javascript.ast.AstNode;

public class ValuesToEvaluate extends Element implements Evaluable {

	public ValuesToEvaluate(Evaluable v1, Evaluable v2) {
		super(((Element)v1).location);
		this.v1 = v1;
		this.v2 = v2;
	}
	
	private Evaluable v1, v2;
	
	@Override
	public FinalElement evaluate(Context ctx) {
		Element e1 = v1.evaluate(ctx);
		while (e1 instanceof Evaluable) e1 = ((Evaluable)e1).evaluate(ctx);
		Element e2 = v2.evaluate(ctx);
		while (e2 instanceof Evaluable) e2 = ((Evaluable)e2).evaluate(ctx);
		if (e1 instanceof FinalElement) {
			if (e2 instanceof FinalElement) {
				// TODO check there are similar
				if (((FinalElement)e1).getDescription().length() == 0)
					((FinalElement)e1).setDescription(((FinalElement)e2).getDescription());
				return (FinalElement)e1;
			}
			return (FinalElement)e1;
		}
		if (e2 instanceof FinalElement)
			return (FinalElement)e2;
		return null;
	}
	
	@Override
	public AstNode getNode() { return v1.getNode(); }
	@Override
	public Node[] getDocs() { return v1.getDocs(); }
	@Override
	public Location getLocation() { return location; }
	
	@Override
	public boolean skip() {
		return v1.skip() || v2.skip();
	}
	
}
