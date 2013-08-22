/**
 * Abstract class for a typed field 
 * @constructor
 * @param data
 * @param {Boolean} editable true to create an editable field, false for a read-only field
 * @param {function} onchanged called when the field is editable, and the user changed the data: onchanged(field, data)
 * @param {function} onunchanged called when the field is editable, and the user changed the data but come back to the original data: onunchanged(field)
 * @param {Object} config additional information depending on the implementation
 */
function typed_field(data,editable,onchanged,onunchanged,config){
	this.element = null;
	this.originalData = data;
	this.editable = editable;
	this.onchanged = onchanged;
	this.onunchanged = onunchanged;
}
typed_field.prototype = {
	/**
	 * @returns the HTML element representing the field
	 */
	getHTMLElement: function() { return this.element; },
	/**
	 * @returns true if this field is editable
	 */
	isEditable: function() { return this.editable; },
	/**
	 * @returns {Boolean} true if the data has been changed by the user since the creation of the field
	 */
	hasChanged: function() { return this.getCurrentData() != this.getOriginalData(); },
	/**
	 * @returns the current data (the edited one)
	 */
	getCurrentData: function() { return this.originalData; },
	/**
	 * @returns the original data (at creation time)
	 */
	getOriginalData: function() { return this.originalData; },
	/**
	 *  change data
	 *  @param data new data value
	 */
	setData: function(data) {}
};