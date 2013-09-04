function vscroll_bar() {
	this.element = document.createElement("DIV");
	this.element.style.width = "10px";
	this.element.style.borderLeft = "1px solid black";
	var t=this;
	
	this._layout = function() {
		
	};
	addLayoutEvent(this.element, function() { t._layout(); });
}