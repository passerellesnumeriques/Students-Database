function hscroll_bar() {
	this.element = document.createElement("DIV");
	this.element.style.height = "10px";
	this.element.style.borderTop = "1px solid black";
	var t=this;
	
	this._layout = function() {
		
	};
	addLayoutEvent(this.element, function() { t._layout(); });
}