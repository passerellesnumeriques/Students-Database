function typed_filter(data, config) {
	this.element = document.createElement("DIV");
	this.element.style.display = "inline-block";
	this.currentData = data;
	this.originalData = data;
	this.config = config;
	this.onchange = new Custom_Event();
}
typed_filter.prototype = {
	getHTMLElement: function() { return this.element; },
	getCurrentData: function() { return this.currentData; },
	getOriginalData: function() { return this.originalData; }
};