package org.pn.jsdoc.model.builtin;

import org.pn.jsdoc.model.Class;
import org.pn.jsdoc.model.Function;
import org.pn.jsdoc.model.Global;
import org.pn.jsdoc.model.ObjectClass;

public class BuiltinDOMNode extends Class implements Builtin {

	public BuiltinDOMNode(Global global) {
		super(global);
		add("appendChild", new Function(this, "DOMNode"));
		add("attributes", new ObjectClass("Array"));
		add("childNodes", new ObjectClass("Array"));
		add("className", new ObjectClass("String"));
		add("clientHeight", new ObjectClass("Number"));
		add("clientWidth", new ObjectClass("Number"));
		add("cloneNode", new Function(this, "DOMNode"));
		add("firstChild", new ObjectClass("DOMNode"));
		add("getAttribute", new Function(this, "String"));
		add("getAttributeNode", new Function(this, "DOMNode"));
		add("getElementsByTagName", new Function(this, "Array"));
		add("hasAttribute", new Function(this, "Boolean"));
		add("hasAttributes", new Function(this, "Boolean"));
		add("hasChildNodes", new Function(this, "Boolean"));
		add("id", new ObjectClass("String"));
		add("innerHTML", new ObjectClass("String"));
		add("insertBefore", new Function(this, "DOMNode"));
		add("isDefaultNamespace", new Function(this, "Boolean"));
		add("isEqualsNode", new Function(this, "Boolean"));
		add("isSameNode", new Function(this, "Boolean"));
		add("isSupported", new Function(this, "Boolean"));
		add("lang", new ObjectClass("String"));
		add("lastChild", new ObjectClass("DOMNode"));
		add("namespaceURI", new ObjectClass("String"));
		add("nextSibling", new ObjectClass("DOMNode"));
		add("nodeName", new ObjectClass("String"));
		add("nodeType", new ObjectClass("Number"));
		add("nodeValue", new ObjectClass("String"));
		add("offsetHeight", new ObjectClass("Number"));
		add("offsetWidth", new ObjectClass("Number"));
		add("offsetLeft", new ObjectClass("Number"));
		add("offsetParent", new ObjectClass("DOMNode"));
		add("offsetTop", new ObjectClass("Number"));
		add("ownerDocument", new ObjectClass("document"));
		add("parentNode", new ObjectClass("DOMNode"));
		add("previousSibling", new ObjectClass("DOMNode"));
		add("removeAttribute", new Function(this, "String"));
		add("removeAttributeNode", new Function(this, "DOMNode"));
		add("removeChild", new Function(this, "DOMNode"));
		add("replaceChild", new Function(this, "DOMNode"));
		add("scrollHeight", new ObjectClass("Number"));
		add("scrollWidth", new ObjectClass("Number"));
		add("scrollLeft", new ObjectClass("Number"));
		add("scrollTop", new ObjectClass("Number"));
		add("setAttribute", new Function(this, "void"));
		add("setAttributeNode", new Function(this, "void"));
		add("style", new ObjectClass("Object"));
		add("tabIndex", new ObjectClass("Number"));
		add("tagName", new ObjectClass("String"));
		add("textContent", new ObjectClass("String"));
		add("title", new ObjectClass("String"));
		add("toString", new Function(this, "String"));
	}
	
}
