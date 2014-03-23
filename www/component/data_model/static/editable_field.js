if (typeof require != 'undefined')
	require("typed_field.js");
	
/** Generic control to display a field, allow the user to switch between editable and non-editable mode. When the user is in editable mode, the user can save the value or cancel modifications. This is used by editable_cell and editable_datadisplay.
 * @param {DOMNode} container where to put it
 * @param {String} field_classname the typed filed of the data
 * @param {Object} field_arguments (optional) in case this typed_filed needs arguments
 * @param {Object} data the initial data for the typed field
 * @param {Function} lock_data ask to lock the data. Parameters are: (data, function(locks,data)) the given data is the current data, before locking, then it must call the given function with a list of locks, and the new data coming from the server at the time of the lock
 * @param {Function} save_data ask to save the data. Parameters are: (data, function(data)) the given data is the data saved, then the given function must be called with the data really saved as parameter
 * @param {Function} onready called when this editable field is ready to use
 */
function editable_field(container, field_classname, field_arguments, data, lock_data, save_data, onready) {
	var t=this;
	if (typeof container == 'string') container = document.getElementById(container);
	if (typeof field_arguments == 'string') field_arguments = eval('('+field_arguments+')');
	container.editable_cell = this;
	/** {typed_field} the typed field */
	t.field = null;
	/** {DOMNode} the save button when in editable mode */ 
	t.save_button = null;
	/** {DOMNode} the cancel edit when in editable mode */
	t.unedit_button = null;
	/** {Array} list of locks when in editable mode */
	t.locks = null;
	/** indicates if we are currently in editabled mode */
	t.editable = true;
	t._fill_container = false;
	
	t.fillContainer = function() {
		t._fill_container = true;
		t.field.getHTMLElement().style.width = "100%";
		//t.field.getHTMLElement().style.height = "100%";
	};

	/** Goes to non-editable mode
	 * @param {Function} onready called when non-editable mode is ready
	 */
	t.unedit = function(onready) {
		if (t.locks) {
			var locks = t.locks;
			t.locks = null;
			for (var i = 0; i < locks.length; ++i) {
				service.json("data_model", "unlock", {lock:locks[i]}, function(result) {});
				window.databaselock.removeLock(locks[i]);
			}
		}
		if (t.save_button) { t.field.getHTMLElement().removeChild(t.save_button); t.save_button = null; }
		if (t.unedit_button) { t.field.getHTMLElement().removeChild(t.unedit_button); t.unedit_button = null; }
		var config_field = function() {
			if (t.editable) {
				t.field.getHTMLElement().title = "Click to edit";
				t.field.getHTMLElement().onmouseover = function(ev) { this.style.outline = '1px solid #C0C0F0'; stopEventPropagation(ev); return false; };
				t.field.getHTMLElement().onmouseout = function(ev) { this.style.outline = 'none'; stopEventPropagation(ev); return false; };
				t.field.getHTMLElement().onclick = function(ev) { t.edit(); stopEventPropagation(ev); return false; };
			}
			if (t._fill_container) {
				t.field.getHTMLElement().style.width = "100%";
				//t.field.getHTMLElement().style.height = "100%";
			}
		};
		if (t.field) {
			t.field.setData(t.field.getOriginalData());
			t.field.setEditable(false);
			config_field();
			layout.invalidate(container);
			if (onready) onready(t);
		} else {
			require("typed_field.js",function() { 
				require(field_classname+".js",function(){
					t.field = new window[field_classname](data,false,field_arguments);
					container.appendChild(t.field.getHTMLElement());
					t.field.getHTMLElement().style.verticalAlign = "top";
					t.field.onchange.add_listener(function() {
						t._changed();
					});
					config_field();
					layout.invalidate(container);
					if (onready) onready(t);
				}); 
			});
		}
	};
	/** Goes to editable mode */
	t.edit = function() {
		t.field.getHTMLElement().title = "";
		t.field.getHTMLElement().style.outline = 'none';
		t.field.getHTMLElement().onmouseover = null;
		t.field.getHTMLElement().onmouseout = null;
		t.field.getHTMLElement().onclick = null;
		var data = t.field.getCurrentData();
		container.removeChild(t.field.getHTMLElement());
		var loading = document.createElement("IMG");
		loading.src = theme.icons_16.loading;
		container.appendChild(loading);
		layout.invalidate(container);
		lock_data(data, function(locks, data){
			container.removeChild(loading);
			container.appendChild(t.field.getHTMLElement());
			if (locks == null) {
				t.unedit();
				return;
			}
			t.locks = locks;
			t.field.setData(data);
			t.field.setOriginalData(data);
			for (var i = 0; i < locks.length; ++i)
				window.databaselock.addLock(parseInt(locks[i]));
			t.field.setEditable(true);
			t.field.getHTMLElement().focus();
			if (t.field.getHTMLElement().onfocus) t.field.getHTMLElement().onfocus();
			var prev_click = t.field.getHTMLElement().onclick; 
			t.field.getHTMLElement().onclick = function (ev) { stopEventPropagation(ev); if (prev_click) prev_click(ev); };
			t.save_button = document.createElement("IMG");
			t.save_button.src = theme.icons_16.save;
			t.save_button.style.verticalAlign = 'top';
			t.save_button.style.cursor = 'pointer';
			t.save_button.onclick = function(ev) { t.field.getHTMLElement().onclick = prev_click; t.save(); stopEventPropagation(ev); return false; };
			t.field.getHTMLElement().appendChild(t.save_button);
			t.unedit_button = document.createElement("IMG");
			t.unedit_button.src = theme.icons_16.no_edit;
			t.unedit_button.style.verticalAlign = 'top';
			t.unedit_button.style.cursor = 'pointer';
			t.unedit_button.onclick = function(ev) { t.field.getHTMLElement().onclick = prev_click; t.unedit(); stopEventPropagation(ev); return false; };
			t.field.getHTMLElement().appendChild(t.unedit_button);
			layout.invalidate(container);
		});
	};
	/** Save current data */
	t.save = function() {
		var data = t.field.getCurrentData();
		container.removeChild(t.field.getHTMLElement());
		t.field.getHTMLElement().removeChild(t.save_button); t.save_button = null;
		t.field.getHTMLElement().removeChild(t.unedit_button); t.unedit_button = null;
		var loading = document.createElement("IMG");
		loading.src = theme.icons_16.loading;
		container.appendChild(loading);
		layout.invalidate(container);
		save_data(data, function(data) {
			container.removeChild(loading);
			container.appendChild(t.field.getHTMLElement());
			t.field.setData(data);
			t.field.setOriginalData(data);
			t.unedit();
		});
	};
	/** Called when the user is editing the value, to display or not the save button */
	t._changed = function() {
		if (t.save_button) {
			if (t.field.getError() == null) {
				t.save_button.style.visibility = 'visible';
				t.save_button.style.position = 'static';
			} else {
				t.save_button.style.visibility = 'hidden';
				t.save_button.style.position = 'absolute';
			}
		}
	};
	
	/** Stop editing */
	t.cancelEditable = function() {
		t.editable = false;
		t.unedit();
	};
	
	t.unedit(onready);
}
