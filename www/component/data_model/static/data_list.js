if (typeof require != 'undefined') {
	require("grid.js");
	require("vertical_layout.js");
	require("horizontal_layout.js");
	require("typed_field.js");
	require("field_text.js");
}
function data_list(container, root_table, show_fields) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	
	t._init_list = function() {
		// analyze and remove container content
		while (container.childNodes.length > 0) {
			var e = container.childNodes[0];
			// TODO get headers
			container.removeChild(e);
		}
		// init header
		t.header = document.createElement("DIV");
		t.header.setAttribute("layout","fixed");
		t.header.className = "data_list_header";
		t.header_left = document.createElement("DIV");
		t.header_left.setAttribute("layout","fixed");
		t.header.appendChild(t.header_left);
		t.header_center = document.createElement("DIV");
		t.header_center.setAttribute("layout","fill");
		t.header.appendChild(t.header_center);
		t.header_right = document.createElement("DIV");
		t.header_right.setAttribute("layout","fixed");
		var div = document.createElement("DIV");
		div.className = "button";
		var img = document.createElement("IMG");
		img.onload = function() { fireLayoutEventFor(t.header); };
		img.src = get_script_path("data_list.js")+"/table_column.png";
		div.appendChild(img);
		t.header_right.appendChild(div);
		t.header.appendChild(t.header_right);
		container.appendChild(t.header);
		// init grid
		t.grid_container = document.createElement("DIV");
		t.grid_container.setAttribute("layout","fill");
		container.appendChild(t.grid_container);
		require("grid.js",function(){
			t.grid = new grid(t.grid_container);
			t._ready();
		});
		// layout
		require("vertical_layout.js",function(){
			new vertical_layout(container);
		});
		require("horizontal_layout.js",function(){
			new horizontal_layout(t.header);
		});
	};
	t._load_fields = function() {
		service.json("data_model","get_available_fields",{table:root_table},function(result){
			if (result) {
				t.available_fields = result;
				t._ready();
			}
		});
	};
	t._ready = function() {
		if (t.grid == null) return;
		if (t.available_fields == null) return;
		// compute visible fields
		t.show_fields = [];
		var add_field = function(field) {
			for (var i = 0; i < t.show_fields.length; ++i)
				if (t.show_fields[i].field == field.field)
					return;
			t.show_fields.push(field);
		};
		for (var i = 0; i < show_fields.length; ++i)
			for (var j = 0; j < t.available_fields.length; ++j)
				if (show_fields[i] == t.available_fields[j].field) {
					add_field(t.available_fields[j]);
					show_fields.splice(i,1);
					i--;
					break;
				}
		for (var i = 0; i < show_fields.length; ++i) {
			for (var j = 0; j < t.available_fields.length; ++j) {
				var f = t.available_fields[j].field;
				// remove parenthesis and brackets
				do {
					var a = f.indexOf('(');
					if (a < 0) break;
					var b = f.indexOf(')', a);
					if (b < 0) break;
					f = f.substring(0,a)+f.substring(b+1);
				} while (true);
				do {
					var a = f.indexOf('[');
					if (a < 0) break;
					var b = f.indexOf(']', a);
					if (b < 0) break;
					f = f.substring(0,a)+f.substring(b+1);
				} while (true);
				if (show_fields[i] == f) {
					add_field(t.available_fields[j]);
					show_fields.splice(i,1);
					i--;
					break;
				}
				if (f.substring(f.length-show_fields[i].length) == show_fields[i]) {
					var c = f.charAt(f.length-show_fields[i].length-1);
					if (c == '.' || c == '>') {
						add_field(t.available_fields[j]);
						show_fields.splice(i,1);
						i--;
						break;
					}
				}
			}
		}
		// initialize grid
		t._load_typed_fields(function(){
			for (var i = 0; i < t.show_fields.length; ++i) {
				var f = t.show_fields[i];
				// TODO typed
				var col = new GridColumn(f.field, f.name, null, "field_text", false, null, null, {}, f);
				col.addSorting(function (v1,v2){
					return v1.localeCompare(v2);
				}); // TODO external sorting if paged
				col.addFiltering(); // TODO better + if paged need external filtering
				t.grid.addColumn(col);
			}
			// get data
			t.grid.startLoading();
			var fields = [];
			for (var i = 0; i < t.show_fields.length; ++i)
				fields.push(t.show_fields[i].field);
			service.json("data_model","get_data_list",{table:root_table,fields:fields},function(result){
				if (!result) {
					t.grid.endLoading();
					return;
				}
				t.grid.setData(result.data);
				t.grid.endLoading();
			});
		});
	};
	t._load_typed_fields = function(handler) {
		require("typed_field.js",function() {
			var fields = ["field_text.js"];
			var nb = fields.length;
			for (var i = 0; i < fields.length; ++i)
				require(fields[i],function(){if (--nb == 0) handler(); });
		});
	};
	t.grid = null;
	t.available_fields = null;
	
	t._init_list();
	t._load_fields();
}
