/**
 * A label represented with a small rounded rectangle
 * @param {String} name text of the label
 * @param {String} color CSS color of the label
 * @param {Function} onedit if specified, the label is clickable and this function is called when clicked
 * @param {Function} onremove if specified, a small remove button is included in the label, and this function is called when clicked
 */
function label(name, color, onedit, onremove) {
	var t=this;
	/** The DIV representing the label */
	this.element = document.createElement("DIV");
	this.element.style.display = "inline-block";
	this.element.style.padding = "2px";
	this.element.style.fontSize = "8pt";
	this.element.style.border = "1px solid "+color;
	setBorderRadius(this.element, 3,3,3,3,3,3,3,3);
	this.element.style.backgroundColor = color;
	/** SPAN containing the text */
	this.name = document.createElement("SPAN");
	this.name.appendChild(document.createTextNode(name));
	this.element.appendChild(this.name);
	if (onedit) {
		this.name.style.cursor = 'pointer';
		this.name.title = "Edit";
		this.name.onclick = function() { onedit(t,function(new_name){ t.name.childNodes[0].nodeValue = new_name; layout.changed(t.name); }); };
	}
	if (onremove) {
		/** Remove button */
		this.remove = document.createElement("IMG");
		this.remove.src = theme.icons_10.remove;
		this.remove.style.verticalAlign = "middle";
		this.remove.style.marginLeft = "3px";
		this.remove.style.marginRight = "3px";
		this.element.appendChild(this.remove);
		this.remove.style.cursor = 'pointer';
		this.remove.title = "Remove";
		this.remove.onclick = function() { onremove(t); };
	}
}