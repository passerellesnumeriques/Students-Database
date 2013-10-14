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
// TODO toggleEditable
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
	 * @returns the original data (at creation time, or set by setOriginalData)
	 */
	getOriginalData: function() { return this.originalData; },
	/**
	 * Change the original data (for example when the value has been saved, and current data should become the original one)
	 */
	setOriginalData: function(data) { return this.originalData = data; },
	/**
	 *  change data
	 *  @param data new data value
	 */
	setData: function(data) {},
	/**
	 * highlight the field to signal an error
	 * @param {Boolean} error if true, the field is highlighted, else it is not
	 */
	signal_error: function(error) {},
	/**
	 * @param onsaved the instance of the editable_table object
	 * @param container, the one managed by the editable_table object
	 * In case the field manage a data structure associated to a key, this function will save the changes of the structure in the database
	 * This method must replace the role of the hasChanged method (the row_key may not have changed whereas the value pointed by the key have)
	 * onsave method must manage the loading button, ang call unedit method
	 * @return {boolean} true if the data pointed by the key have changed
	 */
	save: function(onsaved) {}
};