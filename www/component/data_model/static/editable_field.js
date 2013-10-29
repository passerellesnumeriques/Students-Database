if (typeof require != 'undefined')
	require("typed_field.js");
	
/**
 * @param container
 * @param field_classname the typed filed of the data
 * @param field_arguments (optional) in case this typed_filed needs arguments
 * @param data the data that initiates the editable_cell
 */
function editable_field(container, field_classname, field_arguments, data, lock_data, save_data) {
	var t=this;
	if (typeof container == 'string') container = document.getElementById(container);
	if (typeof field_arguments == 'string') field_arguments = eval('('+field_arguments+')');
	container.editable_cell = this;
	t.field = null;
	t.save_button = null;
	t.unedit_button = null;
	t.locks = null;

	t.unedit = function() {
		if (t.locks) {
			var locks = t.locks;
			t.locks = null;
			for (var i = 0; i < locks.length; ++i)
				service.json("data_model", "unlock", {lock:locks[i]}, function(result) {
					window.database_locks.remove_lock(locks[i]);
				});	
		}
		if (t.save_button) { container.removeChild(t.save_button); t.save_button = null; }
		if (t.unedit_button) { container.removeChild(t.unedit_button); t.unedit_button = null; }
		var config_field = function() {
			t.field.getHTMLElement().title = "Click to edit";
			t.field.getHTMLElement().onmouseover = function(ev) { this.style.outline = '1px solid #C0C0F0'; stopEventPropagation(ev); return false; };
			t.field.getHTMLElement().onmouseout = function(ev) { this.style.outline = 'none'; stopEventPropagation(ev); return false; };
			t.field.getHTMLElement().onclick = function(ev) { t.edit(); stopEventPropagation(ev); return false; };
		};
		if (t.field) {
			t.field.setData(t.field.getOriginalData());
			t.field.setEditable(false);
			config_field();
		} else {
			require("typed_field.js",function() { 
				require(field_classname+".js",function(){
					t.field = new window[field_classname](data,false,field_arguments);
					container.appendChild(t.field.getHTMLElement());
					t.field.onchange.add_listener(function() {
						t._changed();
					});
					config_field();
				}); 
			});
		}
	};
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
				window.database_locks.add_lock(parseInt(locks[i]));
			t.field.setEditable(true);
			t.field.getHTMLElement().focus();
			if (t.field.getHTMLElement().onfocus) t.field.getHTMLElement().onfocus();
			var prev_click = t.field.getHTMLElement().onclick; 
			t.field.getHTMLElement().onclick = function (ev) { stopEventPropagation(ev); if (prev_click) prev_click(ev); };
			t.save_button = document.createElement("IMG");
			t.save_button.src = theme.icons_16.save;
			t.save_button.style.verticalAlign = 'bottom';
			t.save_button.style.cursor = 'pointer';
			t.save_button.onclick = function(ev) { t.field.getHTMLElement().onclick = prev_click; t.save(); stopEventPropagation(ev); return false; };
			container.appendChild(t.save_button);
			t.unedit_button = document.createElement("IMG");
			t.unedit_button.src = theme.icons_16.no_edit;
			t.unedit_button.style.verticalAlign = 'bottom';
			t.unedit_button.style.cursor = 'pointer';
			t.unedit_button.onclick = function(ev) { t.field.getHTMLElement().onclick = prev_click; t.unedit(); stopEventPropagation(ev); return false; };
			container.appendChild(t.unedit_button);
		});
	};
	t.save = function() {
		var data = t.field.getCurrentData();
		container.removeChild(t.field.getHTMLElement());
		container.removeChild(t.save_button); t.save_button = null;
		container.removeChild(t.unedit_button); t.unedit_button = null;
		var loading = document.createElement("IMG");
		loading.src = theme.icons_16.loading;
		container.appendChild(loading);
		save_data(data, function(data) {
			container.removeChild(loading);
			container.appendChild(t.field.getHTMLElement());
			t.field.setData(data);
			t.field.setOriginalData(data);
			t.unedit();
		});
	};
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
	
	t.unedit();
}
