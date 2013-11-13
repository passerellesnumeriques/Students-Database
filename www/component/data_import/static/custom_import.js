if (typeof require != 'undefined')
	require(["vertical_layout.js","horizontal_layout.js","grid.js","context_menu.js"]);

function custom_import(container, onready) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	
	this.container_layout = null;
	this.setOrientation = function(ori) {
		this.orientation = ori;
		if (this.container_layout)
			this.container_layout.removeLayout();
		require(ori+"_layout.js",function() {
			this.container_layout = new window[ori+'_layout'](container);
		});
		if (ori == "horizontal") {
			this.icon_import.src = theme.icons_16.right;
			this.icon_toggle_layout.src = "/static/widgets/vertical_layout.gif";
		} else {
			this.icon_import.src = theme.icons_16.down;
			this.icon_toggle_layout.src = "/static/widgets/horizontal_layout.gif";
		}
	};
	this.toggleOrientation = function() {
		if (this.orientation == "horizontal")
			this.setOrientation("vertical");
		else
			this.setOrientation("horizontal");
	};
	
	this.upload_excel = function(onbeforeupload, onupload) {
		var d = getIFrameDocument(this.excel_frame);
		var form = d.createElement("FORM");
		form.action = "/dynamic/data_import/page/excel_upload";
		form.method = "POST";
		form.enctype = "multipart/form-data";
		var input = d.createElement("INPUT");
		input.type = 'file';
		input.name = 'excel';
		input.onchange = function() { if(onbeforeupload) onbeforeupload(); form.submit(); if(onupload) onupload(); };
		form.appendChild(input);
		form.style.visibility = 'hidden';
		d.body.appendChild(form);
		triggerEvent(input, 'click', {});
	};
	
	this.data_display = [];
	this.add_data = function(data_display) {
		t.data_display.push(data_display);
		t.grid.addColumn(new GridColumn(data_display.category+'@'+data_display.name,data_display.name,null,data_display.field_classname,true,null,null,data_display.field_config, data_display));
	};
	
	this.import_from_excel = function(data_display_index, row_start) {
		var win = getIFrameWindow(this.excel_frame);
		if (!win.excel) { alert('Please upload an Excel file first'); return; }
		var xl = win.excel;
		var sheet = xl.getActiveSheet();
		var sel = sheet.getSelection();
		if (!sel) { alert('Please select data to import in the Excel sheet'); return; }
		var values = [];
		for (var row = sel.start_row; row <= sel.end_row; row++) {
			for (var col = sel.start_col; col <= sel.end_col; ++col) {
				values.push(sheet.getCell(col,row).getValue());
			}
		}
		
		var nb_rows = t.grid.getNbRows();
		while (row_start < nb_rows && values.length > 0) {
			var value = values[0];
			values.splice(0,1);
			var cell = t.grid.getCellField(row_start, data_display_index+1);
			cell.setData(value);
			row_start++;
		}
		while (values.length > 0) {
			var value = values[0];
			values.splice(0,1);
			data = [{col_id:'#',data_id:0,data:""+(t.grid.getNbRows()+1)}];
			for (var i = 0; i < t.data_display.length; ++i) {
				var d = {col_id:t.data_display[i].category+'@'+t.data_display[i].name,data_id:0,data:t.data_display[i].new_data};
				if (i == data_display_index) {
					d.data = value;
					d.data_id = 1;
				}
				data.push(d);
			}
			t.grid.addRow(generate_id(),data);
			row_start++;
		}
	};
	
	this._create = function() {
		this.excel_container = document.createElement("DIV");
		this.middle_container = document.createElement("DIV");
		this.import_container = document.createElement("DIV");
		this.excel_container.setAttribute("layout", "fill");
		this.middle_container.setAttribute("layout", "35");
		this.import_container.setAttribute("layout", "fill");
		this.import_container.style.overflow = "auto";
		container.appendChild(this.excel_container);
		container.appendChild(this.middle_container);
		container.appendChild(this.import_container);
		this.excel_frame = document.createElement("IFRAME");
		this.excel_frame.src = "/dynamic/data_import/page/excel_upload?new=true";
		this.excel_frame.style.border = "none";
		this.excel_frame.style.width = "100%";
		this.excel_frame.style.height = "100%";
		this.excel_container.appendChild(this.excel_frame);
		var table,tr,td;
		this.middle_container.appendChild(table = document.createElement("TABLE"));
		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		table.style.width = "100%";
		table.style.height = "100%";
		table.style.borderRight = '1px solid black';
		table.style.borderLeft = '1px solid black';
		td.style.verticalAlign = "middle";
		td.style.textAlign = "center";
		this.icon_import = document.createElement("IMG");
		this.icon_import.className = "button";
		this.icon_import.onclick = function() {
			require("context_menu.js",function(){
				var menu = new context_menu();
				for (var i = 0; i < t.data_display.length; ++i) {
					var dd = t.data_display[i];
					var item = document.createElement("DIV");
					item.className = 'context_menu_item';
					item.appendChild(document.createTextNode(dd.name));
					item.dd_index = i;
					item.onclick = function() {
						var nb_rows = t.grid.getNbRows();
						if (nb_rows == 0)
							t.import_from_excel(this.dd_index, 0);
						else {
							var menu = new context_menu();
							var item;
							item = document.createElement("DIV"); item.className = 'context_menu_item';
							item.appendChild(document.createTextNode("At the end of the list (new rows)"));
							item.dd_index = this.dd_index;
							item.onclick = function() {
								t.import_from_excel(this.dd_index, t.grid.getNbRows());
							};
							menu.addItem(item);
							var first_empty = 0;
							while (first_empty < nb_rows && t.grid.getCellDataId(first_empty, this.dd_index+1) == 1)
								first_empty++;
							if (first_empty < nb_rows) {
								item = document.createElement("DIV"); item.className = 'context_menu_item';
								item.appendChild(document.createTextNode("At first empty row ("+(first_empty+1)+")"));
								item.dd_index = this.dd_index;
								item.row_index = first_empty;
								item.onclick = function() {
									t.import_from_excel(this.dd_index, this.row_index);
								};
								menu.addItem(item);
							}
							// TODO custom row
							menu.showBelowElement(t.icon_import);
						}
					};
					menu.addItem(item);
				}
				menu.showBelowElement(t.icon_import);
			});
		};
		td.appendChild(this.icon_import);
		this.icon_toggle_layout = document.createElement("IMG");
		this.icon_toggle_layout.className = "button";
		td.appendChild(this.icon_toggle_layout);
		this.icon_toggle_layout.onclick = function() {
			t.toggleOrientation();
		};
		this.setOrientation("horizontal");
		require("grid.js",function() {
			t.grid = new grid(t.import_container);
			t.grid.addColumn(new GridColumn('#','#',null,'field_text',false,null,null,{},'#'));
			onready(t);
		});
	};
	this._create();
}