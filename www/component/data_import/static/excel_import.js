function excel_import(popup, container, onready) {
	var t=this;
	if (typeof container == 'string') container = document.getElementById(container);
	
	this.loadImportDataScreen = function(root_table_name, sub_model) {
		// TODO
	};
	this.loadImportDataURL = function(url, post_data) {
		postData(url, post_data, getIFrameWindow(t.frame_import));
	};
	
	this.uploadFile = function(click_event) {
		var pb = null;
		t._upl.onstart = function(files, onready) {
			popup.freeze_progress("Uploading file...", files[0].size, function(span, prog) {
				pb = prog;
				onready();
			});
		};
		t._upl.onprogressfile = function(file, uploaded, total) {
			pb.setTotal(total);
			pb.setPosition(uploaded);
		};
		t._upl.ondonefile = function(file, output, errors) {
			if (errors.length > 0) {
				pb.error();
				popup.enableClose();
				return;
			}
			pb.done();
			popup.set_freeze_content("<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Reading File...");
			// TODO extend expiration time of temporary storage
			t.frame_excel.onload = function() {
				var check_view = function() {
					var win = getIFrameWindow(t.frame_excel);
					if (!win.excel || !win.excel.tabs) {
						if (win.page_errors) {
							popup.unfreeze();
							return;
						}
						setTimeout(check_view, 100);
						return;
					}
					t._prepareExcel();
					popup.unfreeze();
				};
				var check_loaded = function() {
					var win = getIFrameWindow(t.frame_excel);
					if (!win.excel_uploaded) {
						setTimeout(check_loaded, 100);
						return;
					}
					popup.set_freeze_content("<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Building Excel View...");
					check_view();
				};
				check_loaded();
			};
			t.frame_excel.src = "/dynamic/data_import/page/excel_upload?id="+output.id;
		};
		t._upl.openDialog(click_event);
	};
	
	this._prepareExcel = function() {
		t.excel_info.className = "help_header";
		t.excel_info.innerHTML = "<img src='"+theme.icons_16.question+"' style='vertical-align:top'/> How many rows are title ? ";
		t.header_rows_field = new field_integer(0,true,{min:0});
		t.excel_info.appendChild(t.header_rows_field.getHTMLElement());
		layout.invalidate(t.left);
		var w = getIFrameWindow(t.frame_excel);
		var xl = w.excel;
		for (var i = 0; i < xl.sheets.length; ++i) {
			var sheet = xl.sheets[i];
			for (var j = 0; j < sheet.columns.length; ++j) {
				var col = sheet.columns[j];
				col.header.innerHTML = "";
				var link = document.createElement("A");
				link.innerHTML = "Import Column";
				link.href = "#";
				link.col = col;
				link.onclick = function(ev) {
					var col = this.col;
					var col_index = col.sheet.columns.indexOf(col);
					var row = t.header_rows_field.getCurrentData();
					if (row == null) row = 0;
					var range = {start_col:col_index,start_row:row,end_col:col_index,end_row:col.sheet.rows.length-1};
					t._askImport(this, col.sheet, range);
					stopEventPropagation(ev);
					return false;
				};
				col.header.appendChild(link);
			}
		}
	};
	this._askImport = function(element, sheet, range) {
		var win = getWindowFromElement(element);
		win.require("context_menu.js", function() {
			var menu = new win.context_menu();
			var fields = getIFrameWindow(t.frame_import).fields;
			var div = document.createElement("DIV");
			var hr = t.header_rows_field.getCurrentData();
			var radios = [];
			for (var i = 0; i < fields.length; ++i) {
				var d = document.createElement("DIV"); div.appendChild(d);
				d.style.whiteSpace = "nowrap";
				var radio = document.createElement("INPUT"); d.appendChild(radio);
				radio.type = "radio";
				radio.name = "select_field_col_"+range.start_col;
				var found = false;
				if (range.start_row == hr && range.end_row == sheet.rows.length-1)
					for (var j = 0; j < hr; ++j)
						if (sheet.getCell(range.start_col, j).getValue().toLowerCase() == fields[i].name.toLowerCase()) {
							found = true;
							break;
						}
				if (found) radio.checked = "checked";
				var name = document.createElement("SPAN"); d.appendChild(name);
				name.radio = radio;
				name.appendChild(document.createTextNode(fields[i].name));
				name.style.cursor = "pointer";
				name.onclick = function() {
					this.radio.checked = "checked";
				};
				radios.push(radio);
			}
			menu.addItem(div, true);
			var button = document.createElement("BUTTON");
			button.innerHTML = "<img src='"+theme.icons_16.ok+"' style='vertical-align:bottom'/> Import";
			button.onclick = function() {
				var index = -1;
				for (var i = 0; i < radios.length; ++i) if (radios[i].checked) { index = i; break; }
				if (index == -1) return;
				t._importData(sheet, range, index);
			};
			menu.addItem(button);
			menu.showBelowElement(element);
		});
	};
	
	t._importData = function(sheet, range, field_index) {
		var win = getIFrameWindow(t.frame_import);
		var grid = win.grid;
		var row = 0;
		var nb_rows = grid.getNbRows();
		while (row < nb_rows) {
			var field = grid.getCellField(row, field_index+1);
			if (field.getCurrentData() == field.originalData) break;
			row++;
		}
		for (var ci = range.start_col; ci <= range.end_col; ci++)
			for (var ri = range.start_row; ri <= range.end_row; ri++) {
				var value = sheet.getCell(ci,ri).getValue();
				value = value.trim();
				grid.getCellField(row++, field_index+1).setData(value);
			}
	};

	/* prepare */
	require(["upload.js","splitter_vertical.js","header_bar.js","vertical_layout.js",["typed_field.js","field_integer.js"]], function() {
		onready(t);
	});
	
	this.init = function() {
		if (!t.frame_excel) {
			getWindowFromElement(container).theme.css("header_bar.css");

			/* upload excel file */
			t._upl = createUploadTempFile(false, true);
	
			/* layout */
			t.left = document.createElement("DIV");
			t.right = document.createElement("DIV");
			
			t.excel_header = document.createElement("DIV");
			t.excel_info = document.createElement("DIV");
			t.frame_excel = document.createElement("IFRAME");
			t.frame_excel.style.border = "0px";
			t.left.appendChild(t.excel_header);
			t.left.appendChild(t.excel_info);
			t.left.appendChild(t.frame_excel);
			t.frame_excel.setAttribute("layout", "fill");
			
			t.data_header = document.createElement("DIV");
			t.frame_import = document.createElement("IFRAME");
			t.frame_import.style.border = "0px";
			t.right.appendChild(t.data_header);
			t.right.appendChild(t.frame_import);
			t.frame_import.setAttribute("layout", "fill");

			container.appendChild(t.left);
			container.appendChild(t.right);
			new splitter_vertical(container, 0.5);
			t.excel_bar = new header_bar(t.excel_header, 'toolbar');
			t.data_bar = new header_bar(t.data_header, 'toolbar');
			new vertical_layout(t.left);
			new vertical_layout(t.right);
			t.excel_bar.setTitle("/static/excel/excel_16.png", "Excel File");
			t.data_bar.setTitle(theme.icons_16._import, "Data to Import");
			
			t.excel_bar.addMenuButton("/static/data_import/import_excel_16.png", "Open another file", function(ev) {
				t.uploadFile(ev);
			});
		} else {		
			t.frame_excel.src = "about:blank";
			t.frame_import.src = "about:blank";
		}
	};
}