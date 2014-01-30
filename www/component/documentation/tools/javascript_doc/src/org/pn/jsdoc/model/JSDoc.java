package org.pn.jsdoc.model;

import java.util.LinkedList;

import org.mozilla.javascript.Node;
import org.mozilla.javascript.ast.AstNode;

public class JSDoc {

	public String description = "";
	public LinkedList<Tag> tags = new LinkedList<Tag>();
	
	public static class Tag {
		String name;
		String comment;
	}
	
	public JSDoc(AstNode node, Node... docs) {
		String doc = node.getJsDoc();
		if (doc == null) doc = "";
		doc = doc.trim();
		for (int i = 0; i < docs.length; ++i) {
			String jsdoc = docs[i].getJsDoc();
			if (jsdoc != null && jsdoc.trim().length() > 0)
				doc = jsdoc.trim()+(doc.length() > 0 ? " " : "")+doc;
		}

		if (doc.length() == 0) return;
		if (!doc.startsWith("/**")) {
			System.err.println("Invalid JSDoc: "+doc);
			return;
		}
		doc = doc.substring(3);
		int i = doc.indexOf("*/");
		doc = doc.substring(0,i);
		LinkedList<String> lines = new LinkedList<String>();
		while (doc.length() > 0) {
			i = doc.indexOf('\r');
			int j = doc.indexOf('\n');
			if (i == -1 || (j != -1 && j < i)) i = j;
			if (i == -1) {
				lines.add(doc.trim());
				break;
			}
			lines.add(doc.substring(0,i).trim());
			doc = doc.substring(i+1);
		}
		for (String line : lines) {
			if (line.startsWith("*"))
				line = line.substring(1);
			line = line.trim();
			if (line.length() == 0) continue;
			if (line.startsWith("@")) {
				i = line.indexOf(' ');
				Tag tag = new Tag();
				if (i > 0) {
					tag.name = line.substring(1, i);
					tag.comment = line.substring(i+1);
				} else {
					tag.name = line.substring(1);
					tag.comment = "";
				}
				this.tags.add(tag);
			} else
				this.description += line+" ";
		}
	}
	
}
