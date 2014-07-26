function excel_import(popup, container, onready) {
	var t=this;
	if (typeof container == 'string') container = document.getElementById(container);
	
	this.loadImportDataScreen = function(root_table_name, sub_model) {
		// TODO
	};
	this.loadImportDataURL = function(url, post_data) {
		t.frame_import.onload = function() {
			t.frame_import.onload = null;
			getIFrameWindow(t.frame_import).pnapplication.onclose.add_listener(function() {
				t.splitter.hide_left();
			});
		};
		postData(url, post_data, getIFrameWindow(t.frame_import));
	};
	
	this.uploadFile = function(click_event) {
		t.frame_excel._upload = function(ev) {
			t.uploadFile(click_event);
		};
		t.frame_excel.src = "/dynamic/data_import/page/excel_upload?button=_upload";
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
			t.frame_excel.src = "about:blank";
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
					if (!win) {
						setTimeout(check_loaded, 100);
						return;
					}
					if (win.page_errors) {
						popup.unfreeze();
						return;
					}
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
		var w = getIFrameWindow(t.frame_excel);
		var xl = w.excel;
		
		for (var i = 0; i < xl.sheets.length; ++i)
			xl.sheets[i].enableSelection(false);

		t.excel_info.className = "info_header";
		t.excel_info.innerHTML = "<img src='"+theme.icons_16.question+"' style='vertical-align:top'/> Select how you want to import data from the file:<br/>";
		
		var by_column = document.createElement("INPUT");
		by_column.type = "radio";
		by_column.name = "import_type";
		t.excel_info.appendChild(by_column);
		t.excel_info.appendChild(document.createTextNode("By column"));
		t.excel_info.appendChild(document.createElement("BR"));
		var div_by_column = document.createElement("DIV"); t.excel_info.appendChild(div_by_column);
		div_by_column.style.visibility = "hidden";
		div_by_column.style.position = "absolute";
		div_by_column.style.top = "-10000px";
		div_by_column.style.marginLeft = "20px";
		div_by_column.appendChild(document.createTextNode("How many rows are containing titles at the top? "));
		t.header_rows_field = new field_integer(0,true,{min:0});
		div_by_column.appendChild(t.header_rows_field.getHTMLElement());
		
		var manual = document.createElement("INPUT");
		manual.type = "radio";
		manual.name = "import_type";
		t.excel_info.appendChild(manual);
		t.excel_info.appendChild(document.createTextNode("By selecting cells manually"));
		t.excel_info.appendChild(document.createElement("BR"));
		var div_manual = document.createElement("DIV"); t.excel_info.appendChild(div_manual);
		div_manual.style.visibility = "hidden";
		div_manual.style.position = "absolute";
		div_manual.style.top = "-10000px";
		div_manual.style.marginLeft = "20px";
		div_manual.appendChild(document.createTextNode("Select cells in the file, then click on 'Import' which will appear inside the selection"));
		
		layout.invalidate(t.left);
		
		var header_rows_changed = function() {
			var nb = t.header_rows_field.getCurrentData();
			if (!nb) nb = 0;
			for (var i = 0; i < xl.sheets.length; ++i) {
				var sheet = xl.sheets[i];
				if (sheet._header_layer) sheet.removeLayer(sheet._header_layer);
				sheet._header_layer = null;
				var snb = nb >= sheet.rows.length ? sheet.rows.length-1 : nb;
				if (snb > 0)
					sheet._header_layer = sheet.addLayer(0, 0, sheet.columns.length-1, snb-1, 255, 128, 60);
				for (var j = 0; j < sheet.rows.length; ++j) {
					if (j < snb) {
						if (!sheet.rows[j].header._originalContent)
							sheet.rows[j].header._originalContent = sheet.rows[j].header.innerHTML;
						sheet.rows[j].header.innerHTML = "<i>Title</i>";
					} else {
						if (sheet.rows[j].header._originalContent)
							sheet.rows[j].header.innerHTML = sheet.rows[j].header._originalContent;
						sheet.rows[j].header._originalContent = null;
					}
				}
			}			
		};
		var selection_changed = function(sheet) {
			if (!sheet.cursor) return;
			var link = document.createElement("A");
			link.href = "#";
			link.innerHTML = "Import";
			link.onclick = function(ev) {
				stopEventPropagation(ev);
				t._askImport(link, sheet, sheet.getSelection());
				return false;
			};
			sheet.cursor.setContent(link);
		};
		
		by_column.onchange = function() {
			if (this.checked) {
				div_by_column.style.visibility = "visible";
				div_by_column.style.position = "static";
				div_manual.style.visibility = "hidden";
				div_manual.style.position = "absolute";
				for (var i = 0; i < xl.sheets.length; ++i) {
					var sheet = xl.sheets[i];
					sheet.enableSelection(false);
					sheet.selection_changed.remove_listener(selection_changed);
					for (var j = 0; j < sheet.columns.length; ++j) {
						var col = sheet.columns[j];
						if (!col.header._originalContent) {
							col.header._originalContent = col.header.innerHTML; 
							col.header.removeAllChildren();
							var link = document.createElement("A");
							link.innerHTML = "Import";
							link.href = "#";
							link.col = col;
							link.onclick = function(ev) {
								var col = this.col;
								var col_index = col.sheet.columns.indexOf(col);
								var row = t.header_rows_field.getCurrentData();
								if (row == null) row = 0;
								var range = {start_col:col_index,start_row:row,end_col:col_index,end_row:col.sheet.rows.length-1};
								// remove the empty cells at the end
								while (range.end_row > range.start_row && col.sheet.getCell(col_index,range.end_row).getValue() == "")
									range.end_row--;
								// ask the user where to import
								t._askImport(this, col.sheet, range);
								stopEventPropagation(ev);
								return false;
							};
							col.header.appendChild(link);
						}
					}
				}
				t.header_rows_field.onchange.add_listener(header_rows_changed);
				header_rows_changed();
				layout.invalidate(t.left);
			}
		};
		manual.onchange = function() {
			if (this.checked) {
				div_by_column.style.visibility = "hidden";
				div_by_column.style.position = "absolute";
				div_manual.style.visibility = "visible";
				div_manual.style.position = "static";
				for (var i = 0; i < xl.sheets.length; ++i) {
					var sheet = xl.sheets[i];
					sheet.enableSelection(true);
					sheet.selection_changed.add_listener(selection_changed);
					if (sheet._header_layer) sheet.removeLayer(sheet._header_layer);
					sheet._header_layer = null;
					for (var j = 0; j < sheet.columns.length; ++j) {
						var col = sheet.columns[j];
						if (col.header._originalContent)
							col.header.innerHTML = col.header._originalContent;
						col.header._originalContent = null;
					}
					for (var j = 0; j < sheet.rows.length; ++j) {
						if (sheet.rows[j].header._originalContent)
							sheet.rows[j].header.innerHTML = sheet.rows[j].header._originalContent;
						sheet.rows[j].header._originalContent = null;
					}
				}
				t.header_rows_field.onchange.remove_listener(header_rows_changed);
				layout.invalidate(t.left);
			}
		};
		// try to find columns titles
		var fields = getIFrameWindow(t.frame_import).fields;
		for (var i = 0; i < xl.sheets.length; ++i) {
			if (xl.sheets[i].rows.length == 0) continue;
			var nb_found = 0;
			for (var j = 0; j < xl.sheets[i].columns.length; ++j) {
				var value = xl.sheets[i].getCell(j, 0).getValue();
				var found = false;
				for (var k = 0; k < fields.length; ++k)
					if (fields[k].name.toLowerCase() == value.trim().toLowerCase()) { found = true; break; }
				if (found) nb_found++;
			}
			if (nb_found > 0) {
				xl.activateSheet(i);
				by_column.checked = "checked";
				by_column.onchange();
				t.header_rows_field.setData(1);
				break;
			}
		}
	};
	this._askImport = function(element, sheet, range) {
		var content = document.createElement("TABLE");
		var tr = document.createElement("TR"); content.appendChild(tr);
		var fields = getIFrameWindow(t.frame_import).fields;
		var td_fields = document.createElement("TD"); tr.appendChild(td_fields);
		td_fields.style.verticalAlign = "top";
		var td_where = document.createElement("TD");
		td_where.style.verticalAlign = "top";
		var hr = t.header_rows_field.getCurrentData();
		var radios = [];
		var matching_field = -1;
		var matching_sub_data = -1;
		t._where_selected = null;
		// find the best match for the column name
		if (range.start_row == hr && range.start_col == range.end_col) {
			var perfect_match = -1;
			var perfect_match_sub_data = -1;
			var match_words = 0;
			var match_index = -1;
			var match_index_sub_data = -1;
			for (var i = 0; i < fields.length; ++i) {
				if (fields[i].sub_data == null) {
					var field_name = fields[i].name.toLowerCase();
					for (var j = 0; j < hr; ++j) {
						var col_name = sheet.getCell(range.start_col, j).getValue().trim().toLowerCase();
						if (col_name == field_name) {
							perfect_match = i;
							break;
						} else {
							var match = wordsMatch(col_name, field_name);
							if (match.nb_words1_in_words2 == match.nb_words_1) {
								match_words = -1;
								match_index = i;
								match_index_sub_data = -1;
							} else if (match.nb_words2_in_words1 == match.nb_words_2) {
								if (match_words >= 0 && match_words < match.nb_words1_in_words2) {
									match_words = match.nb_words1_in_words2;
									match_index = i;
									match_index_sub_data = -1;
								}
							}
						}
					}
					if (perfect_match >= 0) break;
				} else {
					for (var sf = 0; sf < fields[i].sub_data.names.length; ++sf) {
						var field_name = fields[i].sub_data.names[sf].toLowerCase();
						for (var j = 0; j < hr; ++j) {
							var col_name = sheet.getCell(range.start_col, j).getValue().trim().toLowerCase();
							if (col_name == field_name) {
								perfect_match = i;
								perfect_match_sub_data = sf;
								break;
							} else {
								var match = wordsMatch(col_name, field_name);
								if (match.nb_words1_in_words2 == match.nb_words_1) {
									match_words = -1;
									match_index = i;
									match_index_sub_data = sf;
								} else if (match.nb_words2_in_words1 == match.nb_words_2) {
									if (match_words >= 0 && match_words < match.nb_words1_in_words2) {
										match_words = match.nb_words1_in_words2;
										match_index = i;
										match_index_sub_data = sf;
									}
								}
							}
						}
						if (perfect_match >= 0) break;
					}
				}
			}
			if (perfect_match >= 0) {
				matching_field = perfect_match;
				matching_sub_data = perfect_match_sub_data;
			} else {
				matching_field = match_index;
				matching_sub_data = match_index_sub_data;
			}
		}
		// build list
		for (var i = 0; i < fields.length; ++i) {
			var d = document.createElement("DIV"); td_fields.appendChild(d);
			d.style.whiteSpace = "nowrap";
			if (fields[i].sub_data != null) {
				d.appendChild(document.createTextNode(fields[i].name));
				for (var j = 0; j < fields[i].sub_data.names.length; ++j) {
					d = document.createElement("DIV"); td_fields.appendChild(d);
					d.style.whiteSpace = "nowrap";
					radios.push(t._createImportChoice(d, fields[i], j, range, matching_field == i && matching_sub_data == j, td_where, tr));
				}
			} else {
				radios.push(t._createImportChoice(d, fields[i], -1, range, matching_field == i, td_where, tr));
			}
		}
		popup.freeze();
		var p = new popup_window("Import Data", null, content);
		p.onclose = function() {
			popup.unfreeze();
		};
		p.addIconTextButton(theme.icons_16.ok, "Import", "import", function() {
			var index = -1;
			for (var i = 0; i < radios.length; ++i) if (radios[i].checked) { index = i; break; }
			if (index == -1) {
				alert('Please select which kind of data it is');
				return;
			}
			if (t._where_selected == null) {
				alert('Please select where to add/set values');
				return;
			}
			p.freeze("Importing data...");
			t._importData(sheet, range, index, t._where_selected);
			p.close();
		});
		p.addCancelButton();
		p.show();
	};
	t._where_selected = null;
	t._createImportChoice = function(d, field, sub_data_index, range, selected, td_where, tr) {
		var radio = document.createElement("INPUT"); d.appendChild(radio);
		radio.type = "radio";
		radio.name = "select_field_col_"+range.start_col;
		radio.field = field;
		radio.sub_data_index = sub_data_index;
		if (sub_data_index != -1) radio.style.marginLeft = "20px";
		var name = document.createElement("SPAN"); d.appendChild(name);
		name.appendChild(document.createTextNode(sub_data_index == -1 ? field.name : field.sub_data.names[sub_data_index]));
		name.style.cursor = "pointer";
		name.radio = radio;
		name.onclick = function() {
			this.radio.checked = "checked";
			this.radio.onchange();
		};
		radio.onchange = function() {
			if (!this.checked) return;
			var win = getIFrameWindow(t.frame_import);
			var grid = win.grid;
			var col_index = -1;
			for (var i = 0; i < grid.columns.length; ++i)
				if (grid.columns[i].attached_data != null) {
					if (this.sub_data_index == -1 && typeof grid.columns[i].attached_data.datadisplay == 'undefined' && grid.columns[i].attached_data.category == this.field.category && grid.columns[i].attached_data.name == this.field.name) {
						col_index = i;
						break;
					} else if (this.sub_data_index != -1 && typeof grid.columns[i].attached_data.datadisplay != 'undefined' && grid.columns[i].attached_data.sub_data == this.sub_data_index && grid.columns[i].attached_data.datadisplay.category == this.field.category && grid.columns[i].attached_data.datadisplay.name == this.field.name) {
						col_index = i;
						break;
					}
				}
			var cell = grid.getCellField(0,col_index);

			var is_new = true;
			var first_empty = -1;
			for (var i = 0; i < grid.getNbRows(); ++i) {
				var f = grid.getCellField(i, col_index);
				if (f.isMultiple()) {
					if (f.getNbData() > 0) { is_new = false; } else if (first_empty == -1) first_empty = i;
				} else {
					if (f.hasChanged()) { is_new = false; } else if (first_empty == -1) first_empty = i;
				}
			}
			if (is_new) {
				// nothing yet
				if (td_where.parentNode == tr) {
					tr.removeChild(td_where);
					layout.invalidate(tr);
				}
				t._where_selected = {type:'add',row:0};
				return;
			}
			
			td_where.removeAllChildren();
			t._where_selected = null;
			if (cell.isMultiple()) {
				var r;
				if (sub_data_index != -1) {
					r = document.createElement("INPUT"); r.type = "radio"; r.name = "import_where"; td_where.appendChild(r);
					td_where.appendChild(document.createTextNode("In each first "+field.name+" where "+field.sub_data.names[sub_data_index]+" is not set")); td_where.appendChild(document.createElement("BR"));
					r.onchange = function() {
						if (!this.checked) return;
						t._where_selected = {type:'set_sub_data',row:0};
					};
					r.checked = "checked";
					r.onchange();
				}
				r = document.createElement("INPUT"); r.type = "radio"; r.name = "import_where"; td_where.appendChild(r);
				td_where.appendChild(document.createTextNode("Add from first row")); td_where.appendChild(document.createElement("BR"));
				r.onchange = function() {
					if (!this.checked) return;
					t._where_selected = {type:'add',row:0};
				};
				if (sub_data_index == -1) {
					r.checked = "checked";
					r.onchange();
				}
				r = document.createElement("INPUT"); r.type = "radio"; r.name = "import_where"; td_where.appendChild(r);
				td_where.appendChild(document.createTextNode("Reset previous values from first row")); td_where.appendChild(document.createElement("BR"));
				r.onchange = function() {
					if (!this.checked) return;
					t._where_selected = {type:'reset',row:0};
				};
				if (first_empty < grid.getNbRows()-1) {
					r = document.createElement("INPUT"); r.type = "radio"; r.name = "import_where"; td_where.appendChild(r);
					td_where.appendChild(document.createTextNode("Add from first empty row ("+(first_empty+1)+")")); td_where.appendChild(document.createElement("BR"));
					r.onchange = function() {
						if (!this.checked) return;
						t._where_selected = {type:'reset',row:first_empty};
					};
				}
				r = document.createElement("INPUT"); r.type = "radio"; r.name = "import_where"; td_where.appendChild(r);
				td_where.appendChild(document.createTextNode("Add as new rows")); td_where.appendChild(document.createElement("BR"));
				r.onchange = function() {
					if (!this.checked) return;
					t._where_selected = {type:'reset',row:grid.getNbRows()-1};
				};
				r = document.createElement("INPUT"); r.type = "radio"; r.name = "import_where"; td_where.appendChild(r);
				td_where.appendChild(document.createTextNode("Add from row: "));
				r.field = new field_integer(1,true,{min:1,max:grid.getNbRows()-1});
				td_where.appendChild(r.field.getHTMLElement());
				td_where.appendChild(document.createElement("BR"));
				r.onchange = function() {
					if (!this.checked) return;
					t._where_selected = {type:'reset',row:-1,row_field:this.field,row_getter:function(){return this.row_field.getCurrentData()-1;}};
				};
			} else {
				var r;
				r = document.createElement("INPUT"); r.type = "radio"; r.name = "import_where"; td_where.appendChild(r);
				td_where.appendChild(document.createTextNode("Change values from first row")); td_where.appendChild(document.createElement("BR"));
				r.onchange = function() {
					if (!this.checked) return;
					t._where_selected = {type:'reset',row:0};
				};
				if (first_empty < grid.getNbRows()-1) {
					r = document.createElement("INPUT"); r.type = "radio"; r.name = "import_where"; td_where.appendChild(r);
					td_where.appendChild(document.createTextNode("Add from first empty row ("+(first_empty+1)+")")); td_where.appendChild(document.createElement("BR"));
					r.onchange = function() {
						if (!this.checked) return;
						t._where_selected = {type:'reset',row:first_empty};
					};
					r.checked = "checked";
					r.onchange();
				}
				r = document.createElement("INPUT"); r.type = "radio"; r.name = "import_where"; td_where.appendChild(r);
				td_where.appendChild(document.createTextNode("Add as new rows")); td_where.appendChild(document.createElement("BR"));
				r.onchange = function() {
					if (!this.checked) return;
					t._where_selected = {type:'reset',row:grid.getNbRows()-1};
				};
				if (t._where_selected == null) {
					r.checked = "checked";
					r.onchange();
				}
				r = document.createElement("INPUT"); r.type = "radio"; r.name = "import_where"; td_where.appendChild(r);
				td_where.appendChild(document.createTextNode("Add from row: "));
				r.field = new field_integer(1,true,{min:1,max:grid.getNbRows()-1});
				td_where.appendChild(r.field.getHTMLElement());
				td_where.appendChild(document.createElement("BR"));
				r.onchange = function() {
					if (!this.checked) return;
					t._where_selected = {type:'reset',row:-1,row_field:this.field,row_getter:function(){return this.row_field.getCurrentData()-1;}};
				};
			}
			if (td_where.parentNode != tr) tr.appendChild(td_where);
			layout.invalidate(tr);
		};
		if (selected) {
			radio.checked = "checked";
			radio.onchange();
		}
		return radio;
	};
	
	t._importData = function(sheet, range, field_index, where) {
		var win = getIFrameWindow(t.frame_import);
		var grid = win.grid;
		var row = where.row;
		if (row == -1) row = where.row_getter();
		var ambiguous = [];
		for (var ci = range.start_col; ci <= range.end_col; ci++)
			for (var ri = range.start_row; ri <= range.end_row; ri++) {
				var value = sheet.getCell(ci,ri).getValue();
				value = value.trim();
				while (grid.getNbRows() <= row)
					win.addRow();
				var f = grid.getCellField(row++, field_index+1);
				if (f.isMultiple()) {
					if (where.type == 'reset') f.resetData();
					if (value != "") {
						if (where.type == "set_sub_data") {
							var nb = f.getNbData();
							var set = false;
							for (var i = 0; i < nb; ++i) {
								var d = f.getDataIndex(i);
								if (!d) {
									f.setDataIndex(i, value);
									set = true;
									break;
								}
							}
							if (!set)
								f.addData(value);
						} else
							f.addData(value);
					}
				} else {
					if (typeof f.getPossibleValues != 'undefined') {
						// support for a list of possible value, we can check
						var values = f.getPossibleValues();
						if (!values.contains(value)) {
							ambiguous.push({
								field: f,
								field_index: field_index,
								value: value,
								values: values
							});
						}
					}
					f.setData(value);
				}
			}
		// resolve ambiguous values by field
		var next_ambiguous = function() {
			if (ambiguous.length == 0) return;
			var field_index = ambiguous[0].field_index;
			var list = [ambiguous[0]];
			ambiguous.splice(0,1);
			for (var i = 0; i < ambiguous.length; ++i)
				if (ambiguous[i].field_index == field_index) {
					list.push(ambiguous[i]);
					ambiguous.splice(i,1);
					i--;
				}
			var found_values = [];
			for (var i = 0; i < list.length; ++i)
				if (!found_values.contains(list[i].value))
					found_values.push(list[i].value);
			var div = document.createElement("DIV");
			div.innerHTML = ""+found_values.length+" value(s) are ambiguous for field '"+grid.columns[field_index+1].title+"':";
			var ul = document.createElement("UL");
			div.appendChild(ul);
			var selects = [];
			for (var i = 0; i < found_values.length; ++i) {
				var li = document.createElement("LI");
				li.appendChild(document.createTextNode(found_values[i]));
				var select = document.createElement("SELECT");
				var o = document.createElement("OPTION");
				o.value = "";
				o.text = "";
				select.add(o);
				for (var j = 0; j < list[0].values.length; ++j) {
					o = document.createElement("OPTION");
					o.value = list[0].values[j];
					o.text = list[0].values[j];
					select.add(o);
				}
				li.appendChild(select);
				ul.appendChild(li);
				selects.push(select);
			}
			var pop = new popup_window("Ambiguous data", null, div);
			pop.addOkCancelButtons(function() {
				for (var i = 0; i < selects.length; ++i) {
					var resolved = selects[i].value;
					if (resolved == "") continue;
					for (var j = 0; j < list.length; ++j) {
						if (list[j].value == found_values[i]) {
							list[j].field.setData(resolved);
						}
					}
				}
				pop.close();
				next_ambiguous();
			});
			pop.show();
		};
		if (ambiguous.length > 0)
			require("popup_window.js", function() {
				next_ambiguous();
			});
	};
	
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
			t.frame_excel.style.width = "100%";
			t.left.appendChild(t.excel_header);
			t.left.appendChild(t.excel_info);
			t.left.appendChild(t.frame_excel);
			t.frame_excel.setAttribute("layout", "fill");
			
			t.data_header = document.createElement("DIV");
			t.frame_import = document.createElement("IFRAME");
			t.frame_import.style.border = "0px";
			t.frame_import.style.width = "100%";
			t.right.appendChild(t.data_header);
			t.right.appendChild(t.frame_import);
			t.frame_import.setAttribute("layout", "fill");

			container.appendChild(t.left);
			container.appendChild(t.right);
			t.splitter = new splitter_vertical(container, 0.5);
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
			t.splitter.show_left();
			t.frame_excel.src = "about:blank";
			t.frame_import.src = "about:blank";
		}
	};

	/* prepare */
	require(["upload.js","splitter_vertical.js","header_bar.js","vertical_layout.js",["typed_field.js","field_integer.js"]], function() {
		onready(t);
	});
}