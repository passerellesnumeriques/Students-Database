/**
 * Abstract class for a typed field 
 * @constructor
 * @param data
 * @param {Boolean} editable true to create an editable field, false for a read-only field
 * @param {Object} config additional information depending on the implementation
 */
function typed_field(data,editable,config){
	this.element = document.createElement("DIV");
	this.element.style.display = "inline-block";
	this.element.typed_field = this;
	this.originalData = data;
	this.editable = editable;
	this.onchange = new Custom_Event();
	this.ondatachanged = new Custom_Event();
	this.ondataunchanged = new Custom_Event();
	this._datachange = function() {
		this.onchange.fire(this);
		if (this.getCurrentData() == this.getOriginalData())
			this.ondataunchanged.fire(this);
		else
			this.ondatachanged.fire(this);
	};
	this.error = null;
	this.config = config;
	if (this.constructor.name != 'typed_field')
		this._create(data);
}
// TODO toggleEditable
typed_field.prototype = {
	/** Internal function resetting and creating the field */
	_create: function(data) { alert("Function _create not implemented in typed_field: "+this.constructor.name); },
	/**
	 * @returns the HTML element representing the field
	 */
	getHTMLElement: function() { return this.element; },
	/**
	 * @returns true if this field is editable
	 */
	isEditable: function() { return this.editable; },
	/** Set the field as editable or read-only */
	setEditable: function(editable) {
		if (this.editable == editable) return;
		var data = this.getCurrentData();
		this.editable = editable;
		while (this.element.childNodes.length > 0) this.element.removeChild(this.element.childNodes[0]);
		this._create(data);
	},
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
	signal_error: function(error) {
		this.error = error;
	},
	getError: function() {
		return this.error;
	}
};
typed_field.prototype.constructor = typed_field;