if (typeof require != 'undefined')
	require(["vertical_layout.js","horizontal_layout.js","header_bar.js","grid.js","upload.js","context_menu.js",["typed_field.js","field_integer.js"]]);

/**
 * Custom import allows to import manually data from an Excel file.
 * This can be used with import_data.inc, in PHP, which will prepare this import according to given parameters.
 * @param {DOMNode} container where to put it
 * @param {Function} onready called when everything is ready and the object can be used
 */
function custom_import(container, icon, title, import_button_text, import_button_function, onready) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	
	/** vertical or horizontal layout */
	this.container_layout = null;
	/** Change the orientation
	 * @param {String} ori either "horizontal" or "vertical"
	 */
	this.setOrientation = function(ori) {
		this.orientation = ori;
		if (t.container_layout)
			t.container_layout.removeLayout();
		require(ori+"_layout.js",function() {
			t.container_layout = new window[ori+'_layout'](t.main_container);
		});
		if (ori == "horizontal") {
			if (this.manual_import_button) this.manual_import_button.src = theme.icons_16.right;
			this.icon_toggle_layout.src = "/static/widgets/vertical_layout.gif";
		} else {
			if (this.manual_import_button) this.manual_import_button.src = theme.icons_16.down;
			this.icon_toggle_layout.src = "/static/widgets/horizontal_layout.gif";
		}
	};
	/** Switch between horizontal and vertical layout */
	this.toggleOrientation = function() {
		if (this.orientation == "horizontal")
			this.setOrientation("vertical");
		else
			this.setOrientation("horizontal");
	};
	
	this.on_excel_uploading = new Custom_Event();
	this.on_excel_uploaded = new Custom_Event();
	
	this.openFile = function() {
		this.launchUpload(function(async) {
			t.on_excel_uploading.fire();
			var win = getIFrameWindow(t.excel_frame);
			if (win.excel) win.excel = null;
			t.excel_lock = lock_screen(null,"<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Uploading File...");
		}, function(async, pc) {
			if (async && pc != -1) {
				set_lock_screen_content(t.excel_lock,"<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Uploading File... "+pc+"%");
				return;
			}
			var status = 1;
			if (async)
				set_lock_screen_content(t.excel_lock,"<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Opening File... ");
			var check = function() {
				var win = getIFrameWindow(t.excel_frame);
				if (!win.excel || !win.excel.tabs) {
					if (win.page_errors) {
						unlock_screen(t.excel_lock);
						t.on_excel_uploaded.fire();
						return;
					}
					if (win.excel_uploaded) {
						if (status == 1) {
							status = 2;
							set_lock_screen_content(t.excel_lock, "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Building Excel view...");
						}
					}
					setTimeout(check,25);
					return;
				}
				unlock_screen(t.excel_lock);
				t.on_excel_uploaded.fire();
			};
			check();
		});
	};
	
	/**
	 * Ask for uploading an Excel file
	 * @param {Function} onbeforeupload called before the upload operation is started, with a boolean parameter: true if the upload will be asynchronous with HTML5, or false if this is done the old way with form submission
	 * @param {Function} onupload called when the upload is done (or in progress), with first parameter same as onbeforeupload, if it is true, a second parameter is the percentage of progress between 0 and 100, or -1 for the final call.
	 */
	this.launchUpload = function(onbeforeupload, onupload) {
		var d = getIFrameDocument(this.excel_frame);
		var form = d.createElement("FORM");
		form.action = "/dynamic/data_import/page/excel_upload";
		form.method = "POST";
		form.enctype = "multipart/form-data";
		var input = d.createElement("INPUT");
		input.type = 'file';
		input.name = 'excel';
		input.onchange = function(e) { 
			if (window.File && window.FileList && window.FileReader) {
				var file = e.target.files || e.dataTransfer.files;
				if (file.length == 0) return;
				file = file[0];
				if(onbeforeupload) onbeforeupload(true);
				var xhr = new XMLHttpRequest();
				xhr.open("POST", "/dynamic/storage/service/store_temp", true);
				xhr.setRequestHeader("X_FILENAME", file.name);
				xhr.setRequestHeader("X_FILETYPE", file.type);
				xhr.setRequestHeader("X_FILESIZE", file.size);
				xhr.upload.addEventListener("progress", function(e) {
					var pc = Math.round(e.loaded*100/e.total);
					if(onupload) onupload(true, pc);
				}, false);
				xhr.onreadystatechange = function(e) {
					if (xhr.readyState == 4) {
						if(onupload) onupload(true, 100);
						var errors = [];
						var output = null;
						if (xhr.status != 200)
							errors.push("Error returned by the server: "+xhr.status+" "+xhr.statusText);
						else {
							try {
								var json = eval("("+xhr.responseText+")");
								if (json.errors)
									for (var j = 0; j < json.errors.length; ++j)
										errors.push(json.errors[j]);
								if (json.result) output = json.result;
							} catch (e) {
								errors.push("Invalid response: "+e+"<br/>"+xhr.responseText);
							}
						}
						for (var j = 0; j < errors.length; ++j)
							window.top.status_manager.add_status(new window.top.StatusMessageError(null, errors[j], 10000));
						if (output && output.id)
							t.excel_frame.src = "/dynamic/data_import/page/excel_upload?id="+output.id;
						if(onupload) onupload(true, -1);
					}
				};
				xhr.send(file);
				
			} else {
				if(onbeforeupload) onbeforeupload(false);
				form.submit();
				if(onupload) onupload(false);
			}
		};
		form.appendChild(input);
		form.style.visibility = 'hidden';
		d.body.appendChild(form);
		if (input.click)
			input.click();
		else
			triggerEvent(input, 'click', {});
	};
	
	/** List of DataDisplay which can be imported */
	this.data_display = [];
	/** List of values which will be imported and cannot be changed by the user */
	this.fixed_values = [];
	/** List of values pre-filled, but which can be changed by the user */
	this.prefilled_values = [];
	/** Add a new DataDisplay that can be imported
	 * @param {DataDisplay} data_display the data
	 * @param {Object} fixed_value value which cannot be changed by the user, or null
	 * @param {Object} prefilled_value value by default, but which can be changed by the user, or null
	 */
	this.addData = function(data_display, fixed_value, prefilled_value) {
		t.data_display.push(data_display);
		t.fixed_values.push(fixed_value);
		t.prefilled_values.push(prefilled_value);
		t.grid.addColumn(new GridColumn(data_display.category+'@'+data_display.name,data_display.name,null,data_display.field_classname,true,null,null,data_display.field_config, data_display), t.grid.getNbColumns()-1);
	};
	
	/**
	 * Get values from the Excel frame
	 * @param {Number} data_display_index index
	 * @param {Number} row_start first row in the imported data where to insert the values from Excel
	 */
	this.importFromExcel = function(data_display_index, row_start) {
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
			if (cell.isMultiple())
				cell.addData(value);
			else
				cell.setData(value);
			t.grid.setCellDataId(row_start, data_display_index+1, 1);
			row_start++;
		}
		while (values.length > 0) {
			var value = values[0];
			values.splice(0,1);
			data = [{col_id:'#',data_id:0,data:""+(t.grid.getNbRows()+1)}];
			for (var i = 0; i < t.data_display.length; ++i) {
				var new_data;
				if (t.fixed_values[i] != null) new_data = t.fixed_values[i];
				else if (t.prefilled_values[i] != null) new_data = t.prefilled_values[i];
				else new_data = t.data_display[i].new_data;
				new_data = objectCopy(new_data, 100);
				var d = {col_id:t.data_display[i].category+'@'+t.data_display[i].name,data_id:0,data:new_data};
				if (i == data_display_index)
					d.data_id = 1;
				data.push(d);
			}
			var remove = document.createElement("IMG");
			remove.src = theme.icons_16.remove;
			remove.onmouseover = function() { this.src = theme.icons_16.remove_black; };
			remove.onmouseout = function() { this.src = theme.icons_16.remove; };
			remove.onclick = function() {
				t.grid.removeRow(t.grid.getContainingRow(this));
			};
			remove.style.cursor = 'pointer';
			data.push({
				col_id:'##',
				data_id:0,
				data:remove
			});
			t.grid.addRow(generateID(),data);
			var cell = t.grid.getCellField(t.grid.getNbRows()-1, data_display_index+1);
			if (cell.isMultiple())
				cell.addData(value);
			else
				cell.setData(value);
			// check fixed values
			for (var i = 0; i < t.fixed_values.length; ++i)
				if (t.fixed_values[i] != null)
					t.grid.getCellField(t.grid.getNbRows()-1, i+1).setEditable(false);
		}
	};
	
	this.getExcel = function() {
		var win = getIFrameWindow(this.excel_frame);
		return win.excel;
	};
	
	/**
	 * Remove all rows/data to import
	 */
	this.removeAll = function() {
		t.grid.removeAllRows();
	};
	
	this._importSelection = function(element) {
		var xl = this.getExcel();
		var sheet = xl.getActiveSheet();
		if (sheet == null) { return; } // should not happen...
		var sel = sheet.getSelection();
		if (!sel) { alert('Please select data to import in the Excel sheet'); return; }
		require(["context_menu.js",["typed_field.js","field_integer.js"]],function(){
			var nb_rows = t.grid.getNbRows();

			var menu = new context_menu();
			var table = document.createElement("TABLE");
			table.style.borderCollapse = 'collapse';
			table.style.borderSpacing = "0px";
			var tr, td;
			table.appendChild(tr = document.createElement("TR"));
			tr.style.backgroundColor = "#C0C0FF";
			tr.appendChild(td = document.createElement("TH"));
			td.colSpan = 2;
			td.innerHTML = "Select where to import the data";
			if (nb_rows > 0) {
				table.appendChild(tr = document.createElement("TR"));
				tr.style.backgroundColor = "#C0C0FF";
				tr.appendChild(td = document.createElement("TH"));
				td.innerHTML = "Select which kind of data";
				td.style.borderRight = "1px solid #808080";
				tr.appendChild(td = document.createElement("TH"));
				td.innerHTML = "Where in the imported data table";
			}

			table.appendChild(tr = document.createElement("TR"));
			// field list
			var fields_radios = [];
			tr.appendChild(td = document.createElement("TD"));
			td.style.verticalAlign = "top";
			for (var i = 0; i < t.data_display.length; ++i) {
				if (t.fixed_values[i] != null) continue; // cannot import it, as this is a fixed value
				var dd = t.data_display[i];
				var radio = document.createElement("INPUT");
				radio.type = 'radio';
				radio._data_display_index = i;
				td.appendChild(radio);
				fields_radios.push(radio);
				td.appendChild(document.createTextNode(dd.name));
				td.appendChild(document.createElement("BR"));
			}
			var where_radios = [];
			var input_row;
			if (nb_rows > 0) {
				// where
				td.style.borderRight = "1px solid #808080";
				tr.appendChild(td = document.createElement("TD"));
				td.style.verticalAlign = "top";
				var radio;
				radio = document.createElement("INPUT");
				radio.type = 'radio';
				radio.value = 'end';
				td.appendChild(radio);
				where_radios.push(radio);
				td.appendChild(document.createTextNode("At the end of the list (new rows)"));
				td.appendChild(document.createElement("BR"));
				radio = document.createElement("INPUT");
				radio.type = 'radio';
				radio.value = 'empty';
				radio.checked = 'checked';
				td.appendChild(radio);
				where_radios.push(radio);
				td.appendChild(document.createTextNode("At first empty row"));
				td.appendChild(document.createElement("BR"));
				radio = document.createElement("INPUT");
				radio.type = 'radio';
				radio.value = 'specific';
				td.appendChild(radio);
				where_radios.push(radio);
				td.appendChild(document.createTextNode("At row "));
				input_row = new field_integer(1,true,{min:1,max:nb_rows});
				td.appendChild(input_row.getHTMLElement());
				td.appendChild(document.createElement("BR"));
			}

			table.appendChild(tr = document.createElement("TR"));
			tr.style.backgroundColor = "#C0C0C0";
			tr.appendChild(td = document.createElement("TH"));
			td.colSpan = 2;
			td.style.textAlign = "right";
			var button = document.createElement("DIV");
			button.className = "button";
			button.innerHTML = "<img src='"+theme.icons_16.ok+"'/> Ok";
			button.onclick = function() {
				var index = -1;
				for (var i = 0; i < fields_radios.length; ++i)
					if (fields_radios[i].checked) { index = fields_radios[i]._data_display_index; break; }
				if (index < 0) { alert('Please select which kind of data'); return; }
				var row = 0;
				if (nb_rows > 0) {
					var type = null;
					for (var i = 0; i < where_radios.length; ++i)
						if (where_radios[i].checked) { type = where_radios[i].value; break; }
					if (type == 'end') row = nb_rows;
					else if (type == 'specific') {
						row = input_row.getCurrentData();
						if (row < 0) row = 0;
						if (row > nb_rows) row = nb_rows;
					} else {
						// search first available
						while (row < nb_rows && t.grid.getCellDataId(row, index+1) == 1)
							row++;
					}
				}
				menu.close();
				t.importFromExcel(index, row);
			};
			td.appendChild(button);
			button = document.createElement("DIV");
			button.className = "button";
			button.innerHTML = "<img src='"+theme.icons_16.cancel+"'/> Cancel";
			button.onclick = function() {
				menu.close();
			};
			td.appendChild(button);
			
			menu.addItem(table, true);
			menu.showBelowElement(element);
		});
	};
	
	this.activateManualImport = function() {
		if (this.manual_import_button) return;
		this.manual_import_button = document.createElement("IMG");
		this.manual_import_button.className = "button";
		this.manual_import_button.src = this.orientation == "horizontal" ? theme.icons_16.right : theme.icons_16.down;
		this.manual_import_button.onclick = function() {
			t._importSelection(t.manual_import_button);
		};
		this.middle_td.appendChild(this.manual_import_button);
	};
	this.deactivateManualImport = function() {
		if (!this.manual_import_button) return;
		this.middle_td.removeChild(this.manual_import_button);
		this.manual_import_button = null;
	};
	
	this.activateColumnImport = function() {
		var xl = this.getExcel();
		for (var i = 0; i < xl.sheets.length; ++i) {
			for (var j = 0; j < xl.sheets[i].columns.length; ++j) {
				var col = xl.sheets[i].columns[j];
				var link = document.createElement("A");
				link.innerHTML = "Import";
				link.href = '#';
				link.sheet = xl.sheets[i];
				link.col = j;
				link.onclick = function(ev) {
					var layer = this.sheet._import_header_layer;
					var header_rows = 0;
					if (layer) header_rows = layer.row_end+1;
					this.sheet.setSelection(this.col, header_rows, this.col, this.sheet.rows.length-1);
					t._importSelection(this);
					stopEventPropagation(ev);
					return false;
				};
				col.header.innerHTML = "";
				col.header.appendChild(link);
			}
		}
	};
	this.deactivateColumnImport = function() {
		var xl = this.getExcel();
		for (var i = 0; i < xl.sheets.length; ++i) {
			for (var j = 0; j < xl.sheets[i].columns.length; ++j) {
				var col = xl.sheets[i].columns[j];
				col.header.innerHTML = col.name;
			}
		}
	};
	
	this.launchImport = function () {
		var lock = lock_screen();
		// check there is something to import
		var nb_rows = t.grid.getNbRows();
		if (nb_rows == 0) {
			unlock_screen(lock);
			error_dialog("You didn't import any data");
			return;
		}
		// check there is no error in any field, and retrieve data
		var data = [];
		for (var row = 0; row < nb_rows; row++) {
			var row_data = [];
			for (var col = 0; col < imp.grid.getNbColumns(); ++col) {
				var field = t.grid.getCellField(row, col);
				if (typeof t.grid.columns[col].attached_data == 'string') continue;
				if (field.hasError()) {
					unlock_screen(lock);
					error_dialog("You have an error on row "+(row+1)+": "+t.grid.columns[col].attached_data.name+": "+field.getError());
					field.getHTMLElement().focus();
					return;
				}
				var cell = {data:t.grid.columns[col].attached_data,value:field.getCurrentData()};
				row_data.push(cell);
			}
			data.push(row_data);
		}
		// ok: call the import
		import_button_function(data, lock);
	};
	
	this._assistant_please_open_file = function() {
		var id = generateID();
		t.assistant_container.innerHTML = "First step is to open a file using the button <div class='button' id='"+id+"'>Open File...</div><br/><i>Note: After opening a file, you will be able to open other files, and data already imported from previous files will remain.</i>";
		document.getElementById(id).onclick = function() { t.openFile(); };
		layout.invalidate(container);
	};
	this._assistant_opening_file = function () {
		t.assistant_container.innerHTML = "Opening file... Please wait...";
		layout.invalidate(container);
	};
	this._assistant_file_ready = function() {
		var id_radio1 = generateID();
		var id_radio2 = generateID();
		var id_info1 = generateID();
		var id_info2 = generateID();
		var nb_rows_id = generateID();
		t.assistant_container.innerHTML = 
			"How data are organized in the file ?<br/>"+
			"<input type='radio' name='how_import_data' id='"+id_radio1+"'/> Each column corresponds to a type of data, each row is a data to import<br/>"+
			"<div id='"+id_info1+"' style='margin-left:20px;visibility:hidden;position:absolute'>How many rows at the beginning are titles (and should not be imported) ? <span id='"+nb_rows_id+"'></span></div>"+
			"<input type='radio' name='how_import_data' id='"+id_radio2+"'/> No specific organization<br/>"+
			"<div id='"+id_info2+"' style='margin-left:20px;visibility:hidden;position:absolute'>Select cells in the file, then click on the arrow button to specify where the selected cells should be imported</div>"
			;
		t.assistant_container.style.border = "2px solid red";
		var radio1 = document.getElementById(id_radio1);
		var radio2 = document.getElementById(id_radio2);
		var info1 = document.getElementById(id_info1);
		var info2 = document.getElementById(id_info2);
		var nb_rows_container = document.getElementById(nb_rows_id);
		require([["typed_field.js","field_integer.js"]], function() {
			var tf = new field_integer(0, true, {min:0});
			nb_rows_container.appendChild(tf.getHTMLElement());
			tf.onchange.add_listener(function(tf) {
				var rows = tf.getCurrentData();
				var xl = t.getExcel();
				for (var i = 0; i < xl.sheets.length; ++i) {
					if (xl.sheets[i]._import_header_layer)
						xl.sheets[i].removeLayer(xl.sheets[i]._import_header_layer);
					xl.sheets[i]._import_header_layer = null;
					var r = rows;
					if (r >= xl.sheets[i].rows.length) r = xl.sheets[i].rows.length; 
					if (r > 0)
						xl.sheets[i]._import_header_layer = xl.sheets[i].addLayer(0,0,xl.sheets[i].columns.length-1,r-1,128,128,128);
				}
			});
		});
		var changed = function() {
			if (radio1.checked) {
				info1.style.visibility = 'visible';
				info1.style.position = 'static';
				info2.style.visibility = 'hidden';
				info2.style.position = 'absolute';
				var xl = t.getExcel();
				for (var i = 0; i < xl.sheets.length; ++i) {
					if (xl.sheets[i]._import_header_layer)
						xl.sheets[i].removeLayer(xl.sheets[i]._import_header_layer);
					xl.sheets[i]._import_header_layer = null;
				}
				t.deactivateManualImport();
				t.activateColumnImport();
				layout.invalidate(container);
			} else {
				info2.style.visibility = 'visible';
				info2.style.position = 'static';
				info1.style.visibility = 'hidden';
				info1.style.position = 'absolute';
				var xl = t.getExcel();
				for (var i = 0; i < xl.sheets.length; ++i) {
					if (xl.sheets[i]._import_header_layer)
						xl.sheets[i].removeLayer(xl.sheets[i]._import_header_layer);
					xl.sheets[i]._import_header_layer = null;
				}
				t.activateManualImport();
				t.deactivateColumnImport();
				layout.invalidate(container);
			}
			t.assistant_container.style.border = "0px";
		};
		radio1.onchange = changed;
		radio2.onchange = changed;
		layout.invalidate(container);
	};
		
	/** Initialize the display */
	this._create = function() {
		var table,tr,td;
		// header
		this.header = document.createElement("DIV");
		this.header.className = "header_bar_toolbar_big_style";
		this.header_title = document.createElement("DIV");
		this.header_title.className = "header_bar_title";
		if (icon) {
			var img = document.createElement("IMG");
			img.src = icon;
			this.header_title.appendChild(img);
		}
		this.header_title.appendChild(document.createTextNode(title));
		this.header.appendChild(this.header_title);
		this.header_assistant = document.createElement("DIV");
		this.header_assistant.setAttribute("layout", "fill");
		this.header_assistant.appendChild(table = document.createElement("TABLE"));
		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "<img src='"+theme.icons_32.help+"'/>";
		tr.appendChild(this.assistant_container = document.createElement("TD"));
		this.header.appendChild(this.header_assistant);
		container.appendChild(this.header);
		
		// split between excel, button, and imported data
		this.main_container = document.createElement("DIV");
		container.appendChild(this.main_container);
		this.main_container.setAttribute("layout", "fill");
		this.excel_container = document.createElement("DIV"); this.main_container.appendChild(this.excel_container);
		this.middle_container = document.createElement("DIV"); this.main_container.appendChild(this.middle_container);
		this.import_container = document.createElement("DIV"); this.main_container.appendChild(this.import_container);
		this.excel_container.setAttribute("layout", "fill");
		this.middle_container.setAttribute("layout", "35");
		this.import_container.setAttribute("layout", "fill");
		
		// put headers on both side
		this.excel_header_div = document.createElement("DIV"); this.excel_container.appendChild(this.excel_header_div);
		this.import_header_div = document.createElement("DIV"); this.import_container.appendChild(this.import_header_div);

		// excel frame
		this.excel_frame = document.createElement("IFRAME");
		this.excel_frame.excel_uploaded = function() { t.openFile(); };
		this.excel_frame.src = "/dynamic/data_import/page/excel_upload?button=excel_uploaded";
		this.excel_frame.style.border = "none";
		this.excel_frame.setAttribute("layout", "fill");
		this.excel_container.appendChild(this.excel_frame);
		
		// import part
		this.grid_container = document.createElement("DIV");
		this.grid_container.style.overflow = "auto";
		this.grid_container.setAttribute("layout", "fill");
		this.import_container.appendChild(this.grid_container);
		
		this.middle_container.appendChild(table = document.createElement("TABLE"));
		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		table.style.width = "100%";
		table.style.height = "100%";
		table.style.border = '1px solid black';
		td.style.verticalAlign = "middle";
		td.style.textAlign = "center";
		this.icon_toggle_layout = document.createElement("IMG");
		this.icon_toggle_layout.className = "button";
		this.icon_toggle_layout.title = "Switch between horizontal and vertical view";
		td.appendChild(this.icon_toggle_layout);
		this.middle_td = td;
		this.icon_toggle_layout.onclick = function() {
			t.toggleOrientation();
		};
		
		// layout
		require("vertical_layout.js", function() {
			new vertical_layout(container);
			new vertical_layout(t.excel_container);
			new vertical_layout(t.import_container);
		});
		require("horizontal_layout.js", function() {
			new horizontal_layout(t.header);
		});
		require("header_bar.js", function() {
			t.excel_header = new header_bar(t.excel_header_div, 'small');
			t.import_header = new header_bar(t.import_header_div, 'small');
			t.excel_header.setTitle("/static/excel/excel_16.png", "File");
			t.import_header.setTitle(theme.icons_16._import, "Data to import");
			t.import_header.addMenuButton(theme.icons_16.remove, "Remove all", function() { t.removeAll(); });
			var button = document.createElement("DIV");
			button.className = "button";
			button.innerHTML = import_button_text;
			button.onclick = function() { t.launchImport(); };
			t.import_header.addMenuItem(button);
		});
		this.setOrientation("horizontal");
		
		require("grid.js",function() {
			t.grid = new grid(t.grid_container);
			t.grid.addColumn(new GridColumn('#','#',null,'field_text',false,null,null,{},'#'));
			t.grid.addColumn(new GridColumn('##','',null,'field_html',false,null,null,{},'##'));
			onready(t);
		});
		
		// handle assistant
		t._assistant_please_open_file();
		t.on_excel_uploading.add_listener(function() { 
			t._assistant_opening_file();
			t.excel_header.resetMenu();
		});
		t.on_excel_uploaded.add_listener(function() {
			t.excel_header.addMenuButton(null,"Open another file...",function() { t.openFile(); });
			var xl = t.getExcel();
			if (xl != null)
				t._assistant_file_ready();
			else
				t._assistant_please_open_file();
		});
	};
	this._create();
}