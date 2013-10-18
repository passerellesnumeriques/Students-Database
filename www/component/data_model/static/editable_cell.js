if (typeof require != 'undefined')
	require("typed_field.js");
	
	/**
	 * @param container
	 * @param table the table to which the data belongs to
	 * @param column the column to which the data belongs to
	 * @param row_key
	 * @param field_classname the typed filed of the data
	 * @param field_arguments (optional) in case this typed_filed needs arguments
	 * @param data the data that initiates the editable_cell
	 * @param style_unedit (optional) an object containing all the style attributes to set on the editable_cell(when it is uneditable). For instance style = {verticalAlign:"bottom"}
	 * @param style_edit (optional)
	 */
function editable_cell(container, table, column, row_key, field_classname, field_arguments, data, style_unedit, style_edit) {
	var t=this;
	if (typeof container == 'string') container = document.getElementById(container);
	if (typeof field_arguments == 'string') field_arguments = eval('('+field_arguments+')');
	container.editable_cell = this;
	t.data = data;
	t.field = null;
	t.save_button = null;
	t.unedit_button = null;
	t.lock = null;

	t.unedit = function() {
		if (t.lock) {
			service.json("data_model", "unlock", {lock:t.lock}, function(result) {
				window.database_locks.remove_lock(t.lock);
				t.lock = null;
			});	
		}
		if (t.save_button) { container.removeChild(t.save_button); t.save_button = null; }
		if (t.unedit_button) { container.removeChild(t.unedit_button); t.unedit_button = null; }
		if (t.field) { container.removeChild(t.field.getHTMLElement()); t.field = null; }
		require("typed_field.js",function() { 
			require(field_classname+".js",function(){
				t.field = new window[field_classname](t.data,false,null,null,field_arguments);
				t.elem = t.field.getHTMLElement();
				container.appendChild(t.elem);
				if (t.elem.nodeType != 1) t.elem = container;
				t.elem.title = "Click to edit";
				t.elem.onmouseover = function(ev) { this.style.outline = '1px solid #C0C0F0'; stopEventPropagation(ev); return false; };
				t.elem.onmouseout = function(ev) { this.style.outline = 'none'; stopEventPropagation(ev); return false; };
				t.elem.onclick = function(ev) { t.edit(); stopEventPropagation(ev); return false; };
				if(style_unedit != null && typeof(style_unedit) == "object"){
					for(p in style_unedit){
						t.elem.style[p] = style_unedit[p];
					}
				}
			}); 
		});
	};
	t.edit = function() {
		t.elem.title = "";
		t.elem.style.outline = 'none';
		t.elem.onmouseover = null;
		t.elem.onmouseout = null;
		t.elem.onclick = null;
		container.removeChild(t.field.getHTMLElement());
		var loading = document.createElement("IMG");
		loading.src = theme.icons_16.loading;
		container.appendChild(loading);
		service.json("data_model", "lock_cell", {table:table,row_key:row_key,column:column}, function(result) {
			container.removeChild(loading);
			if (result == null) {
				t.unedit();
				return;
			}
			t.lock = result.lock;
			t.data = result.value;
			window.database_locks.add_lock(parseInt(result.lock));
			t.field = new window[field_classname](t.data,true,null,null,field_arguments);
			if(style_edit != null && typeof(style_edit) == "object"){
				for(p in style_edit){
					t.field.getHTMLElement().style[p] = style_edit[p];
				}
			}
			container.appendChild(t.field.getHTMLElement());
			t.field.getHTMLElement().focus();
			if (t.field.getHTMLElement().onfocus) t.field.getHTMLElement().onfocus();
			var prev_click = t.field.getHTMLElement().onclick; 
			t.field.getHTMLElement().onclick = function (ev) { stopEventPropagation(ev); if (prev_click) prev_click(ev); };
			t.save_button = document.createElement("IMG");
			t.save_button.src = theme.icons_16.save;
			t.save_button.style.verticalAlign = 'bottom';
			t.save_button.style.cursor = 'pointer';
			t.save_button.onclick = function(ev) { t.save(); stopEventPropagation(ev); return false; };
			container.appendChild(t.save_button);
			t.unedit_button = document.createElement("IMG");
			t.unedit_button.src = theme.icons_16.no_edit;
			t.unedit_button.style.verticalAlign = 'bottom';
			t.unedit_button.style.cursor = 'pointer';
			t.unedit_button.onclick = function(ev) { t.unedit(); stopEventPropagation(ev); return false; };
			container.appendChild(t.unedit_button);
		});
	};
	t.save = function() {
		t.field.save();
		var new_data = t.field.getCurrentData();
		if (t.onsave) {
			var value = t.onsave(new_data);
			if (value != new_data) {
				t.field.setData(value);
				new_data = value;
			}
		}
		if (!t.field.hasChanged()) { t.unedit(); return; }
		container.removeChild(t.field.getHTMLElement()); t.field = null;
		container.removeChild(t.save_button); t.save_button = null;
		container.removeChild(t.unedit_button); t.unedit_button = null;
		var loading = document.createElement("IMG");
		loading.src = theme.icons_16.loading;
		container.appendChild(loading);
		service.json("data_model", "save_cell", {lock:t.lock,table:table,row_key:row_key,column:column,value:new_data},function(result) {
			container.removeChild(loading);
			if (result) t.data = new_data;
			t.unedit();
		});
	};
	
	t.onsave = null;
	
	t.unedit();
}
