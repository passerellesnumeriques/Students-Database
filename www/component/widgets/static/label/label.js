function label(name, color, onedit, onremove) {
	var t=this;
	this.element = document.createElement("DIV");
	this.element.style.display = "inline-block";
	this.element.style.padding = "2px";
	this.element.style.fontSize = "8pt";
	this.element.style.border = "1px solid "+color;
	setBorderRadius(this.element, 3,3,3,3,3,3,3,3);
	this.element.style.backgroundColor = color;
	this.name = document.createElement("SPAN");
	this.name.innerHTML = name;
	this.element.appendChild(this.name);
	if (onedit) {
		this.name.style.cursor = 'pointer';
		this.name.title = "Edit";
		this.name.onclick = function() { onedit(t); };
	}
	if (onremove) {
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