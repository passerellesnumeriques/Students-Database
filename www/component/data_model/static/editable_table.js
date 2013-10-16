if (typeof require != 'undefined')
	require("typed_field.js");
	/**
	 * Use this to create an editable cell linked to an entire row of a given table
	 * @parameter container
	 * @parameter table the table to which the data belongs to
	 * @parameter row_key
	 * @parameter field_classname the typed filed of the data
	 * @parameter field_arguments (optional) in case this typed_filed needs arguments
	 * @parameter style (optional) an object containing all the style attributes to set on the editable_cell(when it is uneditable). For instance style = {verticalAlign:"bottom"}
	 */
function editable_table(container, table, row_key, field_classname, field_arguments, style) {
	var t=this;
	if (typeof container == 'string') container = document.getElementById(container);
	if (typeof field_arguments == 'string') field_arguments = eval('('+field_arguments+')');
	container.editable_table = this;
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
				t.field = new window[field_classname](row_key,false,null,null,field_arguments);
				t.elem = t.field.getHTMLElement(); 
				container.appendChild(t.elem);
				if (t.elem.nodeType != 1) t.elem = container;
				t.elem.onmouseover = function(ev) { this.style.textDecoration = 'underline'; stopEventPropagation(ev); return false; };
				t.elem.onmouseout = function(ev) { this.style.textDecoration = 'none'; stopEventPropagation(ev); return false; };
				t.elem.onclick = function(ev) { t.edit(); stopEventPropagation(ev); return false; };
				if(style != null && typeof(style) == "object"){
					for(p in style){
						t.elem.style[p] = style[p];
					}
				}
			}); 
		});
	};
	t.edit = function() {
		t.elem.style.textDecoration = 'none';
		t.elem.onmouseover = null;
		t.elem.onmouseout = null;
		t.elem.onclick = null;
		container.removeChild(t.field.getHTMLElement());
		var loading = document.createElement("IMG");
		loading.src = theme.icons_16.loading;
		container.appendChild(loading);
		service.json("data_model", "lock_row", {table:table,row_key:row_key}, function(result) {
			container.removeChild(loading);
			if (result == null) {
				t.unedit();
				return;
			}
			t.lock = result.lock;
			window.database_locks.add_lock(parseInt(result.lock));
			t.field = new window[field_classname](row_key,true,null,null,field_arguments);
			container.appendChild(t.field.getHTMLElement());
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
	
	/**
	 * This method calls the field.save method
	 * 
	 */
	t.save = function() {
		var new_data = t.field.getCurrentData();
		if (t.onsave) {
			var value = t.onsave(new_data);
			if (value != new_data) {
				t.field.setData(value);
				new_data = value;
			}
		}
		container.removeChild(t.field.getHTMLElement());
		container.removeChild(t.save_button);
		container.removeChild(t.unedit_button);
		var loading = document.createElement("IMG");
		loading.src = theme.icons_16.loading;
		container.appendChild(loading);
		/*(!go_next) means the data has not changed*/
		var go_next = t.field.save(function(){container.removeChild(loading); t.unedit();});
		if(!go_next) {t.unedit(); return;}
		t.field = null;
		t.save_button = null;
		t.unedit_button = null;
		
		// service.json("data_model", "save_cell", {lock:t.lock,table:table,row_key:row_key,column:column,value:new_data},function(result) {
			// container.removeChild(loading);
			// if (result) t.data = new_data;
			// t.unedit();
		// });
	};
	
	t.onsave = null;
	
	t.unedit();
}
