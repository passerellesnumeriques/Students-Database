/**
 * Abstract class for a typed field 
 * @param {Object} data data, depending on the type (boolean, date, integer...)
 * @param {Boolean} editable true to create an editable field, false for a read-only field
 * @param {Object} config additional information depending on the implementation
 */
function typed_field(data,editable,config){
	if (!window.to_cleanup) window.to_cleanup = [];
	window.to_cleanup.push(this);
	/** Initial data. Used to compare with currently edited data. Can be set when new data is saved */
	this.originalData = data;
	this.editable = editable;
	/** Event fired when data is changed */
	this.onchange = new Custom_Event();
	/** Event fired when data is changed and not the same as the original one */
	this.ondatachanged = new Custom_Event();
	/** Event fired when data is changed and comes back to its original value */
	this.ondataunchanged = new Custom_Event();
	/** Event fired when the field get the focus */
	this.onfocus = new Custom_Event();
	/** Keep the current data */
	this._data = data;
	this.config = config;
	var cl = getObjectClassName(this);
	if (cl != 'typed_field' && cl != 'typed_field_multiple') {
		this.element = document.createElement("DIV");
		this.element.style.display = "inline-block";
		if (config && config.lang) this.element.lang = config.lang;
		this._create(data);
		this.validate();
	}
}
typed_field.prototype = {
	/** Cleaning to avoid memory leaks */
	cleanup: function() {
		this.element = null;
		this.originalData = null;
		this._data = null;
		this.cleanup = null;
		this.config = null;
	},

	/** Internal function resetting and creating the field
	 * @param {Object} data the initial data
	 */
	_create: function(data) { alert("Function _create not implemented in typed_field: "+getObjectClassName(this)); },
	/** Get the element of the field
	 * @returns the HTML element representing the field
	 */
	getHTMLElement: function() { return this.element; },
	/** The field must use the full width of its container
	 * @param {Object} cache_data previously returned value, to optimize performance
	 */
	fillWidth: function(cache_data) {
		this.element.style.width = "100%";
		this._width_filled = true;
		return this._width_filled_cache_data = this._fillWidth(cache_data);
	},
	/** Keep the information if we fill the width or not */
	_width_filled: false,
	/** Keep cache data given by fillWidth */
	_width_filled_cache_data: null,
	/** To be overriden by the implementation
	 * @param {Object} cache_data previously returned value
	 */
	_fillWidth: function(cache_data) { return cache_data; },
	/** Check if the field is editable or read-only
	 * @returns true if this field is editable
	 */
	isEditable: function() { return this.editable; },
	/** Set the field as editable or read-only
	 * @param {Boolean} editable true to edit, false for read-only 
	 */
	setEditable: function(editable) {
		if (this.editable == editable) return;
		var data = this.getCurrentData();
		this.editable = editable;
		this.element.removeAllChildren();
		// reset some functions which may not be overriden
		this._getEditedData = function() { return this._data; };
		this._fillWidth = function(cache_data) { return cache_data; };
		// create
		this._create(data);
		if (this._width_filled) this.fillWidth(this._width_filled_cache_data);
	},
	/** Indicates if we are currently handling a data change */
	_in_change_event: false,
	/** To be called by implementation, when data is edited
	 * @param {Boolean} force if true, force to process the change even if the new data is the same as the previous one
	 */
	_datachange: function(force) {
		var cur = this._getEditedData();
		if (!force && objectEquals(cur, this._data)) return; // no change
		this._in_change_event = true;
		this._data = cur;
		this.validate();
		this.onchange.fire(this);
		if (!force && !this.hasChanged())
			this.ondataunchanged.fire(this);
		else
			this.ondatachanged.fire(this);
		this._in_change_event = false;
	},
	/** Check if data changed since the beginning (compared to its initial data)
	 * @returns {Boolean} true if the data has been changed by the user since the creation of the field
	 */
	hasChanged: function() {
		var cur = this.getCurrentData();
		if (cur != this.originalData) return true;
		return !objectEquals(cur, this.originalData);
	},
	/** Get the current data (edited one)
	 * @returns the current data (the edited one)
	 */
	getCurrentData: function() { return this._data; },
	/** Must be overriden, to export the data, in a simple way for printing
	 * @param {Object} cell cell
	 */
	exportCell: function(cell) {
		cell.value = "Export not implemented in typed_field: "+getObjectClassName(this);
	},
	/** Get the edited value
	 * @returns the data from the edited field
	 */
	_getEditedData: function() { return this._data; },
	/** Get the original value
	 * @returns the original data (at creation time, or set by setOriginalData)
	 */
	getOriginalData: function() { return this.originalData; },
	/**
	 * Change the original data (for example when the value has been saved, and current data should become the original one)
	 * @param {Object} data new data
	 */
	setOriginalData: function(data) { return this.originalData = data; },
	/**
	 * Change data
	 * @param {Object} data new data value
	 * @param {Boolean} same_change if true, does not try to compare with previous data
	 * @param {Boolean} from_input indicates if this comes from the user or an Excel file, and may need additional processing
	 */
	setData: function(data, same_change, from_input) {
		if (!same_change && objectEquals(data, this._data)) return; // no change
		if (this._in_change_event) {
			var t=this;
			setTimeout(function () { t.setData(data, same_change); },1);
			return;
		}
		data = this._setData(data, from_input);
		this._datachange(same_change);
		this._data = data;
		this.validate();
	},
	/** {Function} if specified, returned a list of possible values which can be selected by the user */
	getPossibleValues: undefined,
	/** {Function} if specified, gives the possibility to create a new value: function(value, name, oncreated) */
	createValue: undefined,
	/** Compare two values, for sorting
	 * @param {Object} v1 value 1
	 * @param {Object} v2 value 2
	 * @returns {Number} -1 if v1 is less than v2, 1 if v1 is more than v2, or 0 if they are equal
	 */
	compare: function(v1,v2) {
		return (""+v1).localeCompare(""+v2);
	},
	/**
	 * Function to be overriden, called when data is changed
	 * @param {Object} data new data value
	 * @param {Boolean} from_input indicates if it comes from the user of an Excel file (so it is a string), and may need additional processing
	 * @returns {Object} the data set (may have been converted, and so be different from the data given as parameter)
	 */
	_setData: function(data,from_input) { alert("Function _setData not implemented in typed_field "+getObjectClassName(this)); return data; },
	/** {String} Validation error */
	error: null,
	/**
	 * Highlight the field to signal an error
	 * @param {String} error the error message, or null if no error
	 */
	signalError: function(error) {
		this.error = error;
	},
	/** Validate the field. This function should call signalError at the end */
	validate: function() { this.signalError(null); },
	/** Get the error message
	 * @returns {String} error message, or null if no error
	 */
	getError: function() {
		return this.error;
	},
	/** Check if the field has a validation error
	 * @returns {Boolean} true if error
	 */
	hasError: function() { return this.error != null; },
	/** Indicates if the data can be null. This function must be overriden
	 * @returns {Boolean} true if the data can be null
	 */
	canBeNull: function() {
		alert("Function canBeNull not implemented in typed_field: "+getObjectClassName(this));
	},
	/** Gives the focus to the field */
	focus: function () { this.element.focus(); },
	/** Indicates if the field handle multiple data. If it returns true, the class must inherit from typed_field_multiple
	 * @returns {Boolean} true if this field inherits from typed_field_multiple
	 */
	isMultiple: function() { return false; },
	
	/** {Function} if specified, this function provides a way to fill several typed fields with the same value.
	 * This function must return an object with 3 attributes:<ul>
	 * <li><code>title</code>: a title to display to the user</li>
	 * <li><code>content</code>: an Element which will be displayed to choose the value</li>
	 * <li><code>apply</code>: a function that takes a typed field as parameter, to fill the given field with the choosen value</li>
	 * </ul>
	 */
	helpFillMultipleItems: undefined,
	/** {Function} same as helpFillMultipleItems but for sub-data. The difference is that the function apply takes an array of typed fields instead of a single field. */
	helpFillMultipleItemsForAllSubData: undefined,
	
	/** Register this field as a cell in the datamodel.
	 * Every change in this field will be propagated to other places in the screen where the same data is displayed, as well as every change in another place will be propagated to this field.
	 * @param {String} table table name
	 * @param {String} column column of the cell
	 * @param {Number|Array} row_key key of the cell
	 */
	registerDataModelCell: function(table, column, row_key) {
		var t=this;
		window.top.datamodel.registerCellWidget(window, table, column, row_key, this.element, function(){
			return t._getEditedData();
		},function(data){
			t._data = t._setData(data);
		},function(listener) {
			t.onchange.addListener(listener);
		},function(listener) {
			t.onchange.removeListener(listener);
		});
	},
	/**
	 * Unregister this field as a cell in the datamodel.
	 */
	unregisterDataModelCell: function() {
		window.top.datamodel.unregisterCellWidget(window, this.element);
	},
	/** Register this field as a data in the datamodel.
	 * Every change in this field will be propagated to other places in the screen where the same data is displayed, as well as every change in another place will be propagated to this field.
	 * @param {DataDisplay} data_display the data
	 * @param {Number|Array} data_key key
	 */
	registerDataModelDataDisplay: function(data_display, data_key) {
		var t=this;
		window.top.datamodel.registerDataWidget(window, data_display, data_key, this.element, function(){
			return t._getEditedData();
		},function(data){
			t._data = t._setData(data);
		},function(listener) {
			t.onchange.addListener(listener);
		}, function(listener) {
			t.onchange.removeListener(listener);
		});
	},
	/**
	 * Unregister this field as a data in the datamodel.
	 */
	unregisterDataModelDataDisplay: function() {
		window.top.datamodel.unregisterDataWidget(window, this.element);
	},
	/** Register this field as a data in the datamodel.
	 * Every change in this field will be propagated to other places in the screen where the same data is displayed, as well as every change in another place will be propagated to this field.
	 * @param {DataDisplay} data_display the data
	 * @param {Number|Array} data_key key
	 */
	setDataDisplay: function(data_display, data_key) {
		this.registerDataModelDataDisplay(data_display, data_key);
	}
};
typed_field.prototype.constructor = typed_field;

/**
 * A typed_field_multiple is a typed_field which contain a list of data.
 * It provides additional functions to access data: addData, getNbData, getDataIndex, setDataIndex, resetData
 */
function typed_field_multiple(data, editable, config) {
	typed_field.call(this, data, editable, config);
}
typed_field_multiple.prototype = new typed_field();
typed_field_multiple.prototype.constructor = typed_field_multiple;		
typed_field_multiple.prototype.isMultiple = function() { return true; };
/**
 * Append a new data
 * @param {Object} new_data the data to add
 * @param {Boolean} from_input indicates if the data comes from the user or Excel file (a string) and may need additional processing to convert it
 */
typed_field_multiple.prototype.addData = function(new_data, from_input) { alert("Function addData not implemented in typed_field_multiple: "+getObjectClassName(this)); };
/**
 * Get the number of data in this multiple field
 * @returns {Number} number of data
 */
typed_field_multiple.prototype.getNbData = function() { alert("Function getNbData not implemented in typed_field_multiple: "+getObjectClassName(this)); };
/**
 * Remove all data
 */
typed_field_multiple.prototype.resetData = function() { alert("Function resetData not implemented in typed_field_multiple: "+getObjectClassName(this)); };
/**
 * Get the data at the given index
 * @param {Number} index the index
 * @returns {Object} the data
 */
typed_field_multiple.prototype.getDataIndex = function(index) { alert("Function getDataIndex not implemented in typed_field_multiple: "+getObjectClassName(this)); };
/**
 * Set the data at the given index
 * @param {Number} index index
 * @param {Object} value data
 * @param {Boolean} from_input indicates if the data comes from the user or Excel file (a string) and may need additional processing to convert it
 */
typed_field_multiple.prototype.setDataIndex = function(index, value, from_input) { alert("Function setDataIndex not implemented in typed_field_multiple: "+getObjectClassName(this)); };


if (window == window.top) {
	/** 
	 * Allow to register fields having sub data, so that we can synchronize the sub datas
	 */
	window.top.sub_field_registry = {
		/** List of fields */
		_fields: [],
		/**
		 * Register a field
		 * @param {Window} win the window containing the field
		 * @param {typed_field} field the field
		 */
		register: function(win, field) {
			this._fields.push({field:field,win:win});
		},
		/** Indicates if we are currently handling a change event */
		_in_change: false,
		/** Indicates a field changed. This must be called by the fields. It will propagate the change to all other registered fields
		 * @param {Window} win the window containing the field
		 * @param {typed_field} field the field which changed
		 */
		changed: function(win, field) {
			if (this._in_change) return;
			this._in_change = true;
			for (var i = 0; i < this._fields.length; ++i) {
				var f = this._fields[i];
				if (f.win == win && f.field._data == field._data) {
					// same window, same data
					if (f.field.config.sub_data_index == field.config.sub_data_index) continue; // same
					f.field.setData(field._data, true);
				}
			}
			this._in_change = false;
		},
		/** Cleaning when a window is closed, to avoid memory leaks
		 * @param {Window} win the window which has been closed
		 */
		_clean: function(win) {
			for (var i = 0; i < this._fields.length; ++i)
				if (this._fields[i].win == win) {
					this._fields.splice(i,1);
					i--;
				}
		},
		/**
		 * Retrieve fields having the same data in the given window
		 * @param {Window} win the window
		 * @param {Object} data the data to search
		 * @returns {Array} list of typed_field
		 */
		getFields: function(win, data) {
			var list = [];
			for (var i = 0; i < this._fields.length; ++i) {
				var f = this._fields[i];
				if (f.win == win && f.field._data == data) {
					// same window, same data
					list.push(f.field);
				}
			}
			return list;
		}
	};
	window.top.pnapplication.onwindowclosed.addListener(function(c) {c.top.sub_field_registry._clean(c.win); });
}
window.top.require("typed_field.js");
