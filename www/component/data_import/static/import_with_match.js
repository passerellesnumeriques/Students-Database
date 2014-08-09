if (typeof require != 'undefined') require(["upload.js","header_bar.js","progress_bar.js"]);

function import_with_match(data_list, ev) {
	var t=this;
	
	this.uploadFile = function(ev) {
		var locker = lock_screen(null,"Initializing...");
		require("upload.js", function() {
			unlock_screen(locker);
			var pb = null;
			var upl = createUploadTempFile(false, true);
			upl.onstart = function(files, onready) {
				locker = lock_screen(null,"Uploading file...");
				t.import_wizard.removeAllChildren();
				layout.invalidate(t.import_wizard);
				set_lock_screen_content_progress(locker, files[0].size, "Uploading file...", false, function(span,prog) {
					pb = prog;
					onready();
				});
			};
			upl.onprogressfile = function(file, uploaded, total) {
				pb.setTotal(total);
				pb.setPosition(uploaded);
			};
			upl.ondonefile = function(file, output, errors) {
				if (errors.length > 0) {
					pb.error();
					unlock_screen(locker);
					return;
				}
				pb.done();
				set_lock_screen_content(locker, "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Loading Excel page...");
				// TODO extend expiration time of temporary storage
				waitFrameContentReady(t.excel_frame, function(win) {
					return win._page_ready && win.is_excel_upload_button;
				}, function(win) {
					set_lock_screen_content(locker, "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Reading File...");
					t.excel_frame.onload = function() {
						var check_view = function() {
							var win = getIFrameWindow(t.excel_frame);
							if (!win.excel || !win.excel.tabs) {
								if (win.page_errors) {
									unlock_screen(locker);
									return;
								}
								setTimeout(check_view, 100);
								return;
							}
							t._prepareExcel();
							unlock_screen(locker);
						};
						var check_loaded = function() {
							var win = getIFrameWindow(t.excel_frame);
							if (!win) {
								setTimeout(check_loaded, 100);
								return;
							}
							if (win.page_errors) {
								unlock_screen(locker);
								return;
							}
							if (!win.excel_uploaded) {
								setTimeout(check_loaded, 100);
								return;
							}
							set_lock_screen_content(locker, "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Building Excel View...");
							check_view();
						};
						check_loaded();
					};
					t.excel_frame.src = "/dynamic/data_import/page/excel_upload?id="+output.id;
				});
			};
			upl.openDialog(ev);
		});
	};
	
	this._prepareExcel = function() {
		var win = getIFrameWindow(t.excel_frame);
		win.excel.onactivesheetchanged.add_listener(function() {
			t._importWizardMatch();
		});
		data_list.ondataloaded.add_listener(function() {
			t._importWizardMatch();
		});
		this._importWizardMatch();
	};
	this._importWizardMatch = function () {
		this._matching = [];
		this._performMatching();
		this._import = [];
		var win = getIFrameWindow(t.excel_frame);
		this.import_wizard.removeAllChildren();
		require([["typed_field.js","field_integer.js"]], function() {
			var table = document.createElement("TABLE");
			table.innerHTML = "<tr id='import_wizard_match_header'><th></th><th>Excel Column</th><th>Data Column</th></tr>";
			t.import_wizard.appendChild(table);
			var table2 = document.createElement("TABLE");
			table2.innerHTML = "<tr id='import_wizard_import_header'><th></th><th>Excel Column</th><th>Data Column</th></tr>";
			t.import_wizard.appendChild(table2);
			t.import_wizard.appendChild(document.createTextNode("How many rows are containing the titles ? "));
			t._header_rows = new field_integer(0,true,{min:0,max:win.excel.getActiveSheet().rows.length});
			t.import_wizard.appendChild(t._header_rows.getHTMLElement());
			t._header_rows.onchange.add_listener(function() {
				if (t._matching.length > 0)
					t._performMatching();
			});
			var button = document.createElement("BUTTON");
			button.innerHTML = "<img src='"+theme.icons_16._import+"'/> Import data";
			button.disabled = "disabled";
			button.style.marginLeft = "5px";
			button.onclick = function() { t._doImport(); };
			t.import_wizard.appendChild(button);
			var first_tr = document.getElementById('import_wizard_match_header');
			var first_tr2 = document.getElementById('import_wizard_import_header');
			var addRow = function() {
				var tr = document.createElement("TR");
				var td = document.createElement("TD");
				td.style.textAlign = "right";
				td.style.fontSize = "8pt";
				td.innerHTML = "Match";
				tr.appendChild(td);
				td = document.createElement("TD");
				var select_excel_column = document.createElement("SELECT");
				select_excel_column.style.fontSize = "8pt";
				var o = document.createElement("OPTION");
				o.value = ""; o.text = "";
				select_excel_column.add(o);
				var sheet = win.excel.getActiveSheet();
				for (var i = 0; i < sheet.columns.length; ++i) {
					o = document.createElement("OPTION");
					o.value = i;
					o.text = sheet.columns[i].name;
					select_excel_column.add(o);
				}
				td.appendChild(select_excel_column);
				tr.appendChild(td);
				td = document.createElement("TD");
				var select_data = document.createElement("SELECT");
				select_data.style.fontSize = "8pt";
				o = document.createElement("OPTION");
				o.value = ""; o.text = "";
				select_data.add(o);
				for (var i = 0; i < data_list.show_fields.length; ++i) {
					o = document.createElement("OPTION");
					o.value = data_list.getColumnIdFromField(data_list.show_fields[i]);
					if (data_list.show_fields[i].sub_index != -1)
						o.text = data_list.show_fields[i].field.sub_data.names[data_list.show_fields[i].sub_index];
					else
						o.text = data_list.show_fields[i].field.name;
					select_data.add(o);
				}
				td.appendChild(select_data);
				tr.appendChild(td);
				first_tr.parentNode.appendChild(tr);
				select_data.other_selects = [];
				var check_complete = function() {
					var blank = -1;
					if (select_data.selectedIndex > 0) {
						for (var i = 0; i < select_data.other_selects.length; ++i) {
							if (select_data.other_selects[i].selectedIndex == 0) {
								if (blank >= 0) {
									var select = select_data.other_selects[blank];
									select.parentNode.removeChild(select.previousSibling);
									select.parentNode.removeChild(select);
									select_data.other_selects.splice(blank,1);
									i--;
								}
								blank = i;
							}
						}
						var last_select = select_data.other_selects.length > 0 ? select_data.other_selects[select_data.other_selects.length-1] : select_data;
						if (!last_select.nextSibling && blank == -1) {
							var button = document.createElement("BUTTON");
							button.className = "flat";
							button.innerHTML = "+";
							last_select.parentNode.appendChild(button);
							button.onclick = function() {
								if (button.nextSibling) return;
								var select = document.createElement("SELECT");
								select.style.fontSize = "8pt";
								o = document.createElement("OPTION");
								o.value = ""; o.text = "";
								select.add(o);
								for (var i = 0; i < data_list.show_fields.length; ++i) {
									o = document.createElement("OPTION");
									o.value = data_list.getColumnIdFromField(data_list.show_fields[i]);
									if (data_list.show_fields[i].sub_index != -1)
										o.text = data_list.show_fields[i].field.sub_data.names[data_list.show_fields[i].sub_index];
									else
										o.text = data_list.show_fields[i].field.name;
									select.add(o);
								}
								button.parentNode.appendChild(select);
								select_data.other_selects.push(select);
								select.onchange = check_complete;
							};
						}
					}
					for (var i = 0; i < t._matching.length; ++i)
						if (t._matching[i].tr == tr) { t._matching.splice(i,1); break;}
					var last_tr = first_tr.parentNode.childNodes[first_tr.parentNode.childNodes.length-1];
					if (select_excel_column.selectedIndex > 0 && select_data.selectedIndex > 0) {
						var data_columns = [select_data.value];
						for (var i = 0; i < select_data.other_selects.length; ++i)
							if (select_data.other_selects[i].selectedIndex > 0 && !data_columns.contains(select_data.other_selects[i].value))
								data_columns.push(select_data.other_selects[i].value);
						t._matching.push({tr:tr,excel_column:select_excel_column.value,data_columns:data_columns});
						if (last_tr == tr)
							addRow();
					} else {
						if (last_tr != tr)
							last_tr.parentNode.removeChild(last_tr);
					}
					t._performMatching();
				};
				select_excel_column.onchange = check_complete;
				select_data.onchange = check_complete;
			};
			addRow();
			var addRow2 = function() {
				var tr = document.createElement("TR");
				var td = document.createElement("TD");
				td.style.textAlign = "right";
				td.style.fontSize = "8pt";
				td.innerHTML = "Import";
				tr.appendChild(td);
				td = document.createElement("TD");
				var select_excel_column = document.createElement("SELECT");
				select_excel_column.style.fontSize = "8pt";
				var o = document.createElement("OPTION");
				o.value = ""; o.text = "";
				select_excel_column.add(o);
				var sheet = win.excel.getActiveSheet();
				for (var i = 0; i < sheet.columns.length; ++i) {
					o = document.createElement("OPTION");
					o.value = i;
					o.text = sheet.columns[i].name;
					select_excel_column.add(o);
				}
				td.appendChild(select_excel_column);
				tr.appendChild(td);
				td = document.createElement("TD");
				var select_data = document.createElement("SELECT");
				select_data.style.fontSize = "8pt";
				o = document.createElement("OPTION");
				o.value = ""; o.text = "";
				select_data.add(o);
				for (var i = 0; i < data_list.show_fields.length; ++i) {
					if (!data_list.show_fields[i].field.editable) continue;
					o = document.createElement("OPTION");
					o.value = data_list.getColumnIdFromField(data_list.show_fields[i]);
					o.text = data_list.show_fields[i].field.name;
					select_data.add(o);
				}
				td.appendChild(select_data);
				tr.appendChild(td);
				first_tr2.parentNode.appendChild(tr);
				var check_complete = function() {
					for (var i = 0; i < t._import.length; ++i)
						if (t._import[i].tr == tr) { t._import.splice(i,1); break;}
					var last_tr = first_tr2.parentNode.childNodes[first_tr2.parentNode.childNodes.length-1];
					if (select_excel_column.selectedIndex > 0 && select_data.selectedIndex > 0) {
						t._import.push({tr:tr,excel_column:select_excel_column.value,data_column:select_data.value});
						if (last_tr == tr)
							addRow2();
					} else {
						if (last_tr != tr)
							last_tr.parentNode.removeChild(last_tr);
					}
					t._checkImport(button);
				};
				select_excel_column.onchange = check_complete;
				select_data.onchange = check_complete;
			};
			addRow2();
			layout.invalidate(t.import_wizard);
		});
	};
	this._performMatching = function() {
		// reset
		this._resetExcel();
		this._resetDataList();
		// match
		if (t._matching.length == 0) return;
		var win = getIFrameWindow(t.excel_frame);
		var sheet = win.excel.getActiveSheet();
		var start = t._header_rows ? t._header_rows.getCurrentData() : 0;
		if (!start) start = 0;
		var matches = [];
		for (var excel_row = start; excel_row < sheet.rows.length; ++excel_row) {
			matches.push([]);
			for (var data_row = 0; data_row < data_list.grid.getNbRows(); ++data_row) {
				var matching = true;
				for (var i = 0; i < t._matching.length && matching; ++i) {
					var excel_value = sheet.getCell(t._matching[i].excel_column, excel_row).getValue();
					var data_str = "";
					for (var j = 0; j < t._matching[i].data_columns.length; ++j) {
						var data_col = data_list.grid.getColumnIndexById(t._matching[i].data_columns[j]);
						if (data_col < 0) continue;
						var field = data_list.grid.getCellField(data_row, data_col);
						if (!field) continue;
						var data_value = field.getCurrentData(); // TODO current data display ?
						if (data_str != "") data_str += " ";
						data_str += data_value;
					}
					if (!excel_value.isSame(data_str)) {
						if (t._matching[i].data_columns.length > 1) {
							var res = wordsMatch(excel_value, data_str, true);
							if (res.nb_words2_in_words1 != res.nb_words_2)
								matching = false;
						} else
							matching = false;
					}
				}
				if (matching)
					matches[excel_row-start].push(data_row);
			}
		}
		for (var excel_row = start; excel_row < sheet.rows.length; ++excel_row) {
			if (matches[excel_row-start].length == 1) {
				var data_match = matches[excel_row-start][0];
				var found = false;
				for (var i = start; i < sheet.rows.length; ++i) {
					if (i == excel_row) continue;
					if (matches[i-start].contains(data_match)) { found = true; break; }
				}
				if (found) {
					sheet.rows[excel_row]._data_matching = {};
					sheet.rows[excel_row]._data_matching.layer = sheet.addLayer(0, excel_row, sheet.columns.length-1, excel_row, 192,192,0, "Ambiguous: another row has the same matching");
				} else {
					sheet.rows[excel_row]._data_matching = {};
					sheet.rows[excel_row]._data_matching.layer = sheet.addLayer(0, excel_row, sheet.columns.length-1, excel_row, 0,255,0, "Match!");
					data_list.grid.getRow(data_match).style.backgroundColor = "#00FF00";
					sheet.rows[excel_row]._data_matching.row = data_match;
				}
			} else if (matches[excel_row-start].length > 1) {
				sheet.rows[excel_row]._data_matching = {};
				sheet.rows[excel_row]._data_matching.layer = sheet.addLayer(0, excel_row, sheet.columns.length-1, excel_row, 192,192,0, "Ambiguous: several matches");
			}
		}
	};
	
	this._checkImport = function(button) {
		button.disabled = "disabled";
		if (this._import.length == 0) return;
		var win = getIFrameWindow(t.excel_frame);
		var sheet = win.excel.getActiveSheet();
		// check excel columns are not used to match and to import at the same time
		for (var i = 0; i < t._matching.length; ++i) {
			for (var j = 0; j < t._import.length; ++j)
				if (t._matching[i].excel_column == t._import[j].excel_column) {
					alert("Column "+sheet.columns[t._matching[i].excel_column].name+" in Excel cannot be used to match and to import at the same time");
					return;
				}
		}
		// check data columns are not used to match and to import at the same time
		for (var i = 0; i < t._matching.length; ++i) {
			for (var j = 0; j < t._import.length; ++j)
				for (var k = 0; k < t._matching[i].data_columns.length; ++k)
					if (t._matching[i].data_columns[k] == t._import[j].data_column) {
						var col = data_list.grid.getColumnById(t._matching[i].data_columns[k]);
						if (!col) continue;
						alert("Column "+col.title+" cannot be used to match and to import at the same time");
						return;
					}
		}
		button.disabled = "";
	};
	
	this._doImport = function() {
		// 1-enable edition on columns to import
		for (var i = 0; i < t._import.length; ++i) {
			var col = data_list.grid.getColumnById(t._import[i].data_column);
			if (!col) continue;
			var edit_action = col.getAction('edit');
			if (!edit_action) continue;
			if (col.editable)
				edit_action.onclick(createEvent('click'),edit_action,col);
		}
		var listener = function() {
			if (data_list.isLoading()) {
				data_list.onNotLoading(listener);
				return;
			}
			// 2- import data
			var win = getIFrameWindow(t.excel_frame);
			var sheet = win.excel.getActiveSheet();
			for (var row = 0; row < sheet.rows.length; ++row) {
				if (!sheet.rows[row]._data_matching) continue;
				if (typeof sheet.rows[row]._data_matching.row == 'undefined') continue;
				for (var i = 0; i < t._import.length; ++i) {
					var col = data_list.grid.getColumnById(t._import[i].data_column);
					if (!col) continue;
					var edit_action = col.getAction('edit');
					if (!edit_action) continue;
					var col_index = data_list.grid.getColumnIndex(col);
					var field = data_list.grid.getCellField(sheet.rows[row]._data_matching.row,col_index);
					if (!field) continue;
					if (!field.editable) continue;
					var excel_value = sheet.getCell(t._import[i].excel_column, row).getValue();
					if (!excel_value) continue;
					if (field.addData)
						field.addData(excel_value);
					else
						field.setData(excel_value);
				}
			}
		};
		data_list.onNotLoading(listener);
	};

	this._resetExcel = function() {
		var win = getIFrameWindow(t.excel_frame);
		var sheet = win.excel.getActiveSheet();
		for (var i = 0; i < sheet.rows.length; ++i) {
			if (sheet.rows[i]._data_matching) {
				sheet.removeLayer(sheet.rows[i]._data_matching.layer);
				sheet.rows[i]._data_matching = null;
			}
		}
	};
	this._resetDataList = function() {
		for (var i = 0; i < data_list.grid.getNbRows(); ++i)
			data_list.grid.getRow(i).style.backgroundColor = "";
	};
	
	this.close = function() {
		this._resetDataList();
		this.container.parentNode.insertBefore(data_list.grid_container, this.container);
		this.container.parentNode.removeChild(this.container);
		data_list._import_with_match = null;
		layout.invalidate(data_list.grid_container);
	};
	this._init = function() {
		data_list._import_with_match = this;
		this.container = document.createElement("DIV");
		this.container.style.flex = "1 1 auto";
		this.container.style.display = "flex";
		this.container.style.flexDirection = "row";
		this.import_container = document.createElement("DIV");
		this.import_container.style.display = "flex";
		this.import_container.style.flexDirection = "column";
		this.import_container.style.flex = "1 1 auto";
		this.import_container.style.borderRight = "2px solid black";
		this.excel_header = document.createElement("DIV");
		this.excel_header.style.flex = "none";
		require("header_bar.js",function() {
			t.excel_bar = new header_bar(t.excel_header, 'toolbar');
			t.excel_bar.setTitle("/static/excel/excel_16.png", "Excel File");
			t.excel_bar.addMenuButton("/static/data_import/import_excel_16.png", "Open another file", function(ev) {
				t.uploadFile(ev);
			});
			t.excel_bar.addMenuButton(theme.icons_16.close, "Close", function() { t.close(); });
			layout.invalidate(t.container);
		});
		this.import_container.appendChild(this.excel_header);
		this.import_wizard = document.createElement("DIV");
		this.import_wizard.style.flex = "none";
		this.import_wizard.style.backgroundColor = "#FFFFC0";
		this.import_wizard.style.borderBottom = "1px solid #A0A080";
		this.import_container.appendChild(this.import_wizard);
		this.excel_frame = document.createElement("IFRAME");
		this.excel_frame.style.flex = "1 1 auto";
		this.excel_frame.style.border = "none";
		this.excel_frame._upload = function(ev) { t.uploadFile(ev); };
		this.excel_frame._no_loading = true;
		listenEvent(this.excel_frame, 'load', function() { layout.invalidate(t.container); });
		this.excel_frame.src = "/dynamic/data_import/page/excel_upload?button=_upload";
		this.import_container.appendChild(this.excel_frame);
		this.container.appendChild(this.import_container);

		data_list.grid_container.style.flex = "1 1 auto"; 
		data_list.grid_container.parentNode.insertBefore(this.container, data_list.grid_container);
		this.container.appendChild(data_list.grid_container);
		layout.invalidate(this.container);
		this.uploadFile(ev);
	};
	this._init();
}