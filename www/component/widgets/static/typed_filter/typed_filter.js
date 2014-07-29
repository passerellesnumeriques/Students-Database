function typed_filter(data, config, editable) {
	this.element = document.createElement("DIV");
	this.element.style.display = "inline-block";
	this.data = data;
	this.config = config;
	this.editable = editable;
	this.onchange = new Custom_Event();
}
typed_filter.prototype = {
	getHTMLElement: function() { return this.element; },
	getData: function() { return this.data; },
	can_multiple: false,
	/** Gives the focus to this filter, like the user wants to enter something right now */
	focus: function() {
		this.element.focus();
	}
};