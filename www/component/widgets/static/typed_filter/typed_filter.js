/**
 * Abstract class defininig a widget providing filter specification.
 * @param {Object} data initial value of the filter
 * @param {Object} config configuration of the filter
 * @param {Boolean} editable indicates if the user can modify the filter or not
 */
function typed_filter(data, config, editable) {
	/** DIV containing the filter */
	this.element = document.createElement("DIV");
	this.element.style.display = "inline-block";
	this.data = data;
	this.config = config;
	this.editable = editable;
	/** Event fired when the user changed the filter value */
	this.onchange = new Custom_Event();
}
typed_filter.prototype = {
	/** Get the DIV element containing the filter
	 * @returns {Element} the DIV
	 */
	getHTMLElement: function() { return this.element; },
	/** Get the current value of the filter
	 * @returns {Object} the current value
	 */
	getData: function() { return this.data; },
	/** Indicates if the filter can be on multiple values */
	can_multiple: false,
	/** Indicates if the filter is filtering something. (i.e. if the user didn't enter anything, the filter won't filter anything) */
	isActive: function() { return true; },
	/** Gives the focus to this filter, like the user wants to enter something right now */
	focus: function() {
		this.element.focus();
	}
};