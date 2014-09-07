if (typeof require != 'undefined') require(["upload.js","header_bar.js","progress_bar.js"]);

function import_with_match_provider() {}
import_with_match_provider.prototype = {
	addDataChangedListener: function(listener) {},
	getColumnsCanBeMatched: function() {},
	getColumnsCanBeImported: function() {},
	getGrid: function() {},
	isDataLoading: function() { return false; },
	onDataLoaded: function(listener) { listener(); },
	makeEditable: function(col) { if (!col.editable) col.toggleEditable(); return true; }
};

function import_with_match_provider_data_list(data_list) {
	this.addDataChangedListener = function(listener) { data_list.ondataloaded.add_listener(listener); };
	this.getColumnsCanBeMatched = this.getColumnsCanBeImported = function() {
		var cols = [];
		for (var i = 0; i < data_list.show_fields.length; ++i) {
			var col = {};
			col.id = data_list.getColumnIdFromField(data_list.show_fields[i]);
			if (data_list.show_fields[i].sub_index != -1)
				col.name = data_list.show_fields[i].field.sub_data.names[data_list.show_fields[i].sub_index];
			else
				col.name = data_list.show_fields[i].field.name;
			cols.push(col);
		}
		return cols;
	};
	this.getGrid = function() { return data_list.grid; };
	this.isDataLoading = function() { return data_list.isLoading(); };
	this.onDataLoaded = function(listener) { data_list.onNotLoading(listener); };
	this.makeEditable = function(col) {
		var edit_action = col.getAction('edit');
		if (!edit_action) return false;
		if (!col.editable)
			edit_action.onclick(createEvent('click'),edit_action,col);
		return true;
	};
}
import_with_match_provider_data_list.prototype = new import_with_match_provider;
import_with_match_provider_data_list.prototype.constructor = import_with_match_provider_data_list;

function import_with_match_provider_custom_data_grid(custom_grid) {
	this.addDataChangedListener = function(listener) {
		custom_grid.object_added.add_listener(listener);
		custom_grid.object_removed.add_listener(listener);
		custom_grid.column_shown.add_listener(listener);
		custom_grid.column_hidden.add_listener(listener);
	};
	this.getColumnsCanBeMatched = this.getColumnsCanBeImported = function() {
		var cols = [];
		var gcols = custom_grid.getAllFinalColumns();
		for (var i = 0; i < gcols.length; ++i)
			if (gcols[i].shown)
				cols.push({ id: gcols[i].grid_column.id, name: gcols[i].select_menu_name ? gcols[i].select_menu_name : gcols[i].grid_column.title });
		return cols;
	};
	this.getGrid = function() { return custom_grid.grid; };
}
import_with_match_provider_custom_data_grid.prototype = new import_with_match_provider;
import_with_match_provider_custom_data_grid.prototype.constructor = import_with_match_provider_custom_data_grid;

function import_with_match(provider, ev, show_after_grid) {
	var t=this;
	
	this.uploadFile = function(ev) {
		var locker = lock_screen(null,"Initializing...");
		t.excel_frame.src = "/dynamic/data_import/page/excel_upload?button=_upload";
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
								if (win.page_errors && !win.excel_uploaded) {
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
							if (win.page_errors && !win.excel_uploaded) {
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
					t.excel_frame.src = "/dynamic/data_import/page/excel_upload?id="+output.id+"&remove_empty_sheets=true";
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
		provider.addDataChangedListener(function() {
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
			t.import_wizard.style.verticalAlign = "top";
			var table = document.createElement("TABLE");
			table.style.display = "inline-block";
			table.style.verticalAlign = "top";
			table.style.border = "1px solid #A0A0C0";
			table.style.marginRight = "1px";
			table.style.marginTop = "1px";
			setBorderRadius(table,3,3,3,3,3,3,3,3);
			table.innerHTML = "<tr id='import_wizard_match_header'><th></th><th colspan=2>Excel Column</th><th>Data Column</th></tr>";
			t.import_wizard.appendChild(table);
			var table2 = document.createElement("TABLE");
			table2.style.display = "inline-block";
			table2.style.verticalAlign = "top";
			table2.style.border = "1px solid #A0A0C0";
			table2.style.marginTop = "1px";
			setBorderRadius(table2,3,3,3,3,3,3,3,3);
			table2.innerHTML = "<tr id='import_wizard_import_header'><th></th><th colspan=2>Excel Column</th><th>Data Column</th></tr>";
			t.import_wizard.appendChild(table2);
			var span = document.createElement("SPAN");
			span.style.whiteSpace = "nowrap";
			t.import_wizard.appendChild(span);
			span.style.fontSize = "8pt";
			span.appendChild(document.createTextNode("How many rows to skip at the beginning (before the data to match/import) ? "));
			t._header_rows = new field_integer(0,true,{min:0,max:win.excel.getActiveSheet().rows.length});
			span.appendChild(t._header_rows.getHTMLElement());
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
				td.style.textAlign = "right";
				td.style.fontSize = "8pt";
				td.innerHTML = " with ";
				tr.appendChild(td);
				td = document.createElement("TD");
				var select_data = document.createElement("SELECT");
				select_data.style.fontSize = "8pt";
				o = document.createElement("OPTION");
				o.value = ""; o.text = "";
				select_data.add(o);
				var cols = provider.getColumnsCanBeMatched();
				for (var i = 0; i < cols.length; ++i) {
					o = document.createElement("OPTION");
					o.value = cols[i].id;
					o.text = cols[i].name;
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
								for (var i = 0; i < cols.length; ++i) {
									o = document.createElement("OPTION");
									o.value = cols[i].id;
									o.text = cols[i].name;
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
					var empty_index = -1;
					for (var i = 1; i < first_tr.parentNode.childNodes.length; ++i) {
						var otr = first_tr.parentNode.childNodes[i];
						if (otr == tr) continue;
						var sel_xl = otr.childNodes[1].childNodes[0];
						if (sel_xl.selectedIndex <= 0) { empty_index = i; break; }
						var sel_data = otr.childNodes[3].childNodes[0];
						if (sel_data.selectedIndex <= 0) { empty_index = i; break; }
					}
					if (select_excel_column.selectedIndex > 0 && select_data.selectedIndex > 0) {
						var data_columns = [select_data.value];
						for (var i = 0; i < select_data.other_selects.length; ++i)
							if (select_data.other_selects[i].selectedIndex > 0 && !data_columns.contains(select_data.other_selects[i].value))
								data_columns.push(select_data.other_selects[i].value);
						t._matching.push({tr:tr,excel_column:select_excel_column.value,data_columns:data_columns});
						if (empty_index == -1)
							addRow();
					} else {
						if (empty_index != -1)
							tr.parentNode.removeChild(tr);
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
				td.style.textAlign = "right";
				td.style.fontSize = "8pt";
				td.innerHTML = " into ";
				tr.appendChild(td);
				td = document.createElement("TD");
				var select_data = document.createElement("SELECT");
				select_data.style.fontSize = "8pt";
				o = document.createElement("OPTION");
				o.value = ""; o.text = "";
				select_data.add(o);
				var cols = provider.getColumnsCanBeImported();
				for (var i = 0; i < cols.length; ++i) {
					o = document.createElement("OPTION");
					o.value = cols[i].id;
					o.text = cols[i].name;
					select_data.add(o);
				}
				td.appendChild(select_data);
				tr.appendChild(td);
				first_tr2.parentNode.appendChild(tr);
				var check_complete = function() {
					for (var i = 0; i < t._import.length; ++i)
						if (t._import[i].tr == tr) { t._import.splice(i,1); break;}
					var empty_index = -1;
					for (var i = 1; i < first_tr2.parentNode.childNodes.length; ++i) {
						var otr = first_tr2.parentNode.childNodes[i];
						if (otr == tr) continue;
						var sel_xl = otr.childNodes[1].childNodes[0];
						if (sel_xl.selectedIndex <= 0) { empty_index = i; break; }
						var sel_data = otr.childNodes[3].childNodes[0];
						if (sel_data.selectedIndex <= 0) { empty_index = i; break; }
					}
					if (select_excel_column.selectedIndex > 0 && select_data.selectedIndex > 0) {
						t._import.push({tr:tr,excel_column:select_excel_column.value,data_column:select_data.value});
						if (empty_index == -1)
							addRow2();
					} else {
						if (empty_index != -1)
							tr.parentNode.removeChild(tr);
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
		this._resetGrid();
		// match
		if (t._matching.length == 0) return;
		var win = getIFrameWindow(t.excel_frame);
		var sheet = win.excel.getActiveSheet();
		var start = t._header_rows ? t._header_rows.getCurrentData() : 0;
		if (!start) start = 0;
		var grid = provider.getGrid();
		var matches = [];
		for (var excel_row = start; excel_row < sheet.rows.length; ++excel_row) {
			matches.push([]);
			for (var data_row = 0; data_row < grid.getNbRows(); ++data_row) {
				var matching = true;
				for (var i = 0; i < t._matching.length && matching; ++i) {
					var excel_value = sheet.getCell(t._matching[i].excel_column, excel_row).getValue();
					var data_str = "";
					for (var j = 0; j < t._matching[i].data_columns.length; ++j) {
						var data_col = grid.getColumnIndexById(t._matching[i].data_columns[j]);
						if (data_col < 0) continue;
						var field = grid.getCellField(data_row, data_col);
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
					grid.getRow(data_match).style.backgroundColor = "#00FF00";
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
		var grid = provider.getGrid();
		for (var i = 0; i < t._matching.length; ++i) {
			for (var j = 0; j < t._import.length; ++j)
				for (var k = 0; k < t._matching[i].data_columns.length; ++k)
					if (t._matching[i].data_columns[k] == t._import[j].data_column) {
						var col = grid.getColumnById(t._matching[i].data_columns[k]);
						if (!col) continue;
						alert("Column "+col.title+" cannot be used to match and to import at the same time");
						return;
					}
		}
		button.disabled = "";
	};
	
	this._doImport = function() {
		var grid = provider.getGrid();
		// 1-enable edition on columns to import
		for (var i = 0; i < t._import.length; ++i) {
			var col = grid.getColumnById(t._import[i].data_column);
			if (!col) continue;
			if (!provider.makeEditable(col)) continue;
		}
		var listener = function() {
			if (provider.isDataLoading()) {
				provider.onDataLoaded(listener);
				return;
			}
			// 2- import data
			var win = getIFrameWindow(t.excel_frame);
			var sheet = win.excel.getActiveSheet();
			for (var row = 0; row < sheet.rows.length; ++row) {
				if (!sheet.rows[row]._data_matching) continue;
				if (typeof sheet.rows[row]._data_matching.row == 'undefined') continue;
				for (var i = 0; i < t._import.length; ++i) {
					var col = grid.getColumnById(t._import[i].data_column);
					if (!col) continue;
					if (!col.editable) continue;
					var col_index = grid.getColumnIndex(col);
					var field = grid.getCellField(sheet.rows[row]._data_matching.row,col_index);
					if (!field) continue;
					if (!field.editable) continue;
					var excel_value = sheet.getCell(t._import[i].excel_column, row).getValue();
					if (!excel_value) continue;
					if (field.addData)
						field.addData(excel_value,true);
					else
						field.setData(excel_value,false,true);
				}
			}
		};
		provider.onDataLoaded(listener);
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
	this._resetGrid = function() {
		var grid = provider.getGrid();
		for (var i = 0; i < grid.getNbRows(); ++i)
			grid.getRow(i).style.backgroundColor = "";
	};
	
	this.close = function() {
		var grid = provider.getGrid();
		this._resetGrid();
		this.container.parentNode.insertBefore(grid.element, this.container);
		this.container.parentNode.removeChild(this.container);
		grid._import_with_match = null;
		layout.invalidate(grid.element);
	};
	this._init = function() {
		var grid = provider.getGrid();
		grid._import_with_match = this;
		this.container = document.createElement("DIV");
		this.container.style.flex = "1 1 auto";
		this.container.style.display = "flex";
		this.container.style.flexDirection = "row";
		this.import_container = document.createElement("DIV");
		this.import_container.style.display = "flex";
		this.import_container.style.flexDirection = "column";
		this.import_container.style.flex = "1 1 auto";
		if (show_after_grid)
			this.import_container.style.borderLeft = "2px solid black";
		else
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

		grid.element.style.flex = "1 1 auto";
		grid.element.parentNode.insertBefore(this.container, show_after_grid ? grid.element.nextSibling : grid.element);
		if (show_after_grid)
			this.container.insertBefore(grid.element, this.container.firstChild);
		else
			this.container.appendChild(grid.element);
		layout.invalidate(this.container);
		this.uploadFile(ev);
	};
	this._init();
}