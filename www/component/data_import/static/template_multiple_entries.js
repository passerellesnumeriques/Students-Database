function template_multiple_entries(container, excel, fields, existing, onready) {
	container.removeAllChildren();
	var t=this;
	
	this.save = function(type_id, name, id, root_table, sub_model, onsaved) {
		var data = {type:type_id,id:id,name:name,root_table:root_table,sub_model:sub_model,to_import:[]};
		for (var i = 0; i < t.table.childNodes.length; ++i) {
			var tr = t.table.childNodes[i];
			if (typeof tr.getToImport == 'undefined') continue;
			var to_import = tr.getToImport();
			if (!to_import) continue;
			if (typeof to_import == 'string') { alert("Please correct the information: "+to_import); if (onsaved) onsaved(null); return; }
			for (var j = 0; j < to_import.length; ++j)
				data.to_import.push(to_import[j]);
		}
		service.json("data_import","save_template_multiple",data,function(res) {
			if (onsaved) onsaved(res);
		});
	};

	this._createSelectColumn = function(container, field, sub_index, found) {
		var div = document.createElement("DIV");
		div.style.whiteSpace = "nowrap";
		var select = document.createElement("SELECT");
		var o;
		o = document.createElement("OPTION");
		o.text = "";
		select.add(o);
		o = document.createElement("OPTION");
		o.text = " Fixed value:";
		select.add(o);
		var sel = 0;
		for (var i = 0; i < excel.sheets.length; ++i) {
			var sheet = excel.sheets[i];
			for (var j = 0; j < sheet.columns.length; ++j) {
				var colname = excel.getExcelColumnName(j);
				o = document.createElement("OPTION");
				o._value = [i,j];
				o.text = (excel.sheets.length > 1 ? sheet.name+" / " : "")+"Column "+colname;
				if (found && found[0] == i && found[1] == j) sel = select.options.length;
				select.add(o);
			}
		}
		select.selectedIndex = sel;
		div.appendChild(select);
		var cfg = objectCopy(field.data.field_config,10);
		if (typeof sub_index != 'undefined') cfg.sub_data_index = sub_index;
		var f = new window[field.data.field_classname](objectCopy(field.data.new_data,10),true,cfg);
		var cont = document.createElement("DIV");
		cont.style.display = "inline-block";
		cont.appendChild(f.getHTMLElement());
		div.appendChild(cont);
		f.getHTMLElement().style.display = "none";
		select._field = f;
		select.validate = function() {
			if (!this._field.isMultiple() && !this._field.canBeNull()) {
				this.style.border = this.selectedIndex == 0 ? "1px solid red" : "";
			}
		};
		select.onchange = function () {
			this.validate();
			if (this.selectedIndex != 1)
				this._field.getHTMLElement().style.display = "none";
			else
				this._field.getHTMLElement().style.display = "";
			this._onchange();
			layout.changed(div);
		};
		container.appendChild(div);
		select.validate();
		return select;
	};
	
	this._createSingleField = function(field, sub_index) {
		if (typeof sub_index == 'undefined' && field.data.sub_data) {
			for (var i = 0; i < field.data.sub_data.names.length; ++i)
				if (field.data.sub_data.editableForNew[i])
					this._createSingleField(field, i);
			return;
		}
		var name = field.data.name+(typeof sub_index != 'undefined' ? " "+field.data.sub_data.names[sub_index] : "");
		var tr = document.createElement("TR");
		t.table.appendChild(tr);
		var td = document.createElement("TD");
		tr.appendChild(td);
		td.appendChild(document.createTextNode(name));
		td = document.createElement("TD");
		tr.appendChild(td);
		var select = this._createSelectColumn(td, field, sub_index, typeof sub_index == 'undefined' ? field._found : field._found[sub_index]);
		var layer = null;
		tr.getToImport = function() {
			if (select.selectedIndex <= 0) {
				if (typeof sub_index != 'undefined')
					return null; // TODO
				if (select._field.canBeNull())
					return null;
				return "Please specify how to import "+field.data.name;
			}
			if (select.selectedIndex == 1) {
				var err = select._field.getError();
				if (err) {
					if (typeof sub_index == 'undefined')
						return field.data.name+": "+err;
					return field.data.name+": "+field.data.sub_data.names[sub_index]+": "+err;
				}
				var val = select._field.getCurrentData();
				var data = {data:field.data.name,path:field.path.path,value:val};
				if (field.data.sub_data) data.sub_index = sub_index;
				return [data];
			}
			var sel = select.options[select.selectedIndex]._value;
			var data = {data:field.data.name,path:field.path.path,sheet_name:excel.sheets[sel[0]].name,column:sel[1],row_start:excel.sheets[sel[0]].__header_rows};
			if (field.data.sub_data) data.sub_index = sub_index;
			return [data];
		};
		select._onchange = function() {
			if (select.selectedIndex < 2) {
				if (layer) {
					layer.sheet.__columns_layers.remove(layer);
					layer.sheet.removeLayer(layer);
					layer = null;
				}
			} else {
				var val = select.options[select.selectedIndex]._value;
				var sheet = excel.sheets[val[0]];
				if (layer) {
					if (layer.sheet != sheet) {
						layer.sheet.__columns_layers.remove(layer);
						layer.sheet.removeLayer(layer);
						layer = null;
					} else {
						layer.setRange(val[1],layer.row_start,val[1],layer.row_end);
					}
				}
				if (!layer) {
					layer = sheet.addLayer(val[1],sheet.__header_rows,val[1],sheet.rows.length-1,0,255,0, name);
					if (!sheet.__columns_layers) sheet.__columns_layers = [];
					sheet.__columns_layers.push(layer);
				}
			}
		};
		if (select.selectedIndex > 0) select.onchange();
		if (existing)
			for (var i = 0; i < existing.length; ++i) {
				if (existing[i].data != field.data.name) continue;
				if (existing[i].path != field.path.path) continue;
				if ((typeof sub_index != 'undefined') && existing[i].sub_index != sub_index) continue;
				if (typeof existing[i].value != 'undefined') {
					// fixed value
					select.selectedIndex = 1;
					select.onchange();
					select._field.setData(existing[i].value);
					break;
				} else {
					// selected sheet and column
					var sheet_index = -1;
					if (excel.sheets.length == 1) sheet_index = 0;
					else for (var j = 0; j < excel.sheets.length; ++j) if (excel.sheets[j].name == existing[i].sheet_name) { sheet_index = j; break; }
					if (sheet_index < 0) continue;
					if (existing[i].column >= excel.sheets[sheet_index].columns.length) continue;
					var found = false;
					for (var j = 2; j < select.options.length; ++j)
						if (select.options[j]._value[0] == sheet_index && select.options[j]._value[1] == existing[i].column) {
							select.selectedIndex = j;
							select.onchange();
							found = true;
							break;
						}
					if (found) break;
				}
			}
	};
	this._updateMultipleFieldSubDataRows = function(field, rows) {
		for (var i = 0; i < rows.length; ++i) {
			var td = rows[i].tr.childNodes[0];
			td.removeAllChildren();
			td.appendChild(document.createTextNode("#"+(i+1)));
			for (var j = 0; j < rows[i].layers.length; ++j)
				if (rows[i].layers[j])
					rows[i].layers[j].setContent(field.data.name+" #"+(i+1)+field.data.sub_data.names[j]);
		}
	};
	this._createMultipleFieldRowSubData = function(field, table, rows) {
		var tr = document.createElement("TR");
		table.appendChild(tr);
		var td = document.createElement("TD");
		tr.appendChild(td);
		td.appendChild(document.createTextNode("#"+(rows.length+1)));
		var row = {tr:tr,selects:[],layers:[]};
		rows.push(row);
		for (var i = 0; i < field.data.sub_data.names.length; ++i) {
			if (!field.data.sub_data.editableForNew[i]) continue;
			td = document.createElement("TD");
			tr.appendChild(td);
			var select = this._createSelectColumn(td, field, i, null);
			row.selects.push(select);
			row.layers.push(null);
			select._sub_index = i;
			select._select_index = row.selects.length;
			select._onchange = function() {
				var sub_index = this._sub_index;
				var select_index = this._select_index;
				var empty_index = -1;
				for (var i = 0; i < rows.length; ++i) {
					if (rows[i] == row) continue;
					var is_empty = true;
					for (var j = 0; j < rows[i].selects.length; ++j)
						if (rows[i].selects[j].selectedIndex > 0) { is_empty = false; break; }
					if (is_empty) {
						empty_index = i;
						break;
					}
				}
				if (this.selectedIndex > 1) {
					var name = field.data.name+" #"+(rows.indexOf(row)+1)+" "+field.data.sub_data.names[sub_index];
					var val = this.options[this.selectedIndex]._value;
					var sheet = excel.sheets[val[0]];
					if (row.layers[select_index]) {
						if (row.layers[select_index].sheet != sheet) {
							row.layers[select_index].sheet.__columns_layers.remove(row.layers[select_index]);
							row.layers[select_index].sheet.removeLayer(row.layers[select_index]);
							row.layers[select_index] = null;
						} else {
							row.layers[select_index].setRange(val[1],row.layers[select_index].row_start,val[1],row.layers[select_index].row_end);
							row.layers[select_index].setContent(name);
						}
					}
					if (!row.layers[select_index]) {
						row.layers[select_index] = sheet.addLayer(val[1],sheet.__header_rows,val[1],sheet.rows.length-1,0,255,0,name);
						if (!sheet.__columns_layers) sheet.__columns_layers = [];
						sheet.__columns_layers.push(row.layers[select_index]);
					}
					if (empty_index == -1)
						t._createMultipleFieldRowSubData(field,table,rows);
				} else {
					if (row.layers[select_index]) {
						row.layers[select_index].sheet.__columns_layers.remove(row.layers[select_index]);
						row.layers[select_index].sheet.removeLayer(row.layers[select_index]);
						row.layers[select_index] = null;
					}
					if (this.selectedIndex == 1) {
						if (empty_index == -1)
							t._createMultipleFieldRowSubData(field,table,rows);
					} else {
						var is_empty = true;
						for (var i = 0; i < row.selects.length; ++i) {
							if (row.selects[i].selectedIndex <= 0) continue;
							is_empty = false;
							break;
						}
						if (is_empty && empty_index >= 0) {
							row.tr.parentNode.removeChild(row.tr);
							rows.remove(row);
							t._updateMultipleFieldSubDataRows(field, rows);
						}
					}
				}
			};
		}
	};
	this._updateMultipleFieldRows = function(field, rows) {
		for (var i = 0; i < rows.length; ++i) {
			var td = rows[i].tr.childNodes[0];
			td.removeAllChildren();
			var name = field.data.name+" #"+(i+1);
			td.appendChild(document.createTextNode(name));
			if (rows[i].layer)
				rows[i].layer.setContent(name);
		}
	};
	this._createMultipleFieldRow = function(field, rows) {
		var tr = document.createElement("TR");
		if (rows.length == 0)
			t.table.appendChild(tr);
		else
			t.table.insertBefore(tr, rows[rows.length-1].tr.nextSibling);
		var td = document.createElement("TD");
		tr.appendChild(td);
		td.appendChild(document.createTextNode(field.data.name+" #"+(rows.length+1)));
		td = document.createElement("TD");
		tr.appendChild(td);
		var select = this._createSelectColumn(td, field, undefined, null);
		var row = {tr:tr,select:select,layer:null};
		rows.push(row);
		tr.getToImport = function() {
			if (select.selectedIndex <= 0) return null;
			if (select.selectedIndex == 1) {
				var err = select._field.getError();
				if (err) return field.data.name+": "+err;
				var val = select._field.getCurrentData();
				return [{data:field.data.name,path:field.path.path,value:val}];
			}
			var sel = select.options[select.selectedIndex]._value;
			return [{data:field.data.name,path:field.path.path,sheet_name:excel.sheets[sel[0]].name,column:sel[1],row_start:excel.sheets[sel[0]].__header_rows}];
		};
		select._onchange = function() {
			var other_empty = false;
			for (var i = 0; i < rows.length; ++i) {
				if (rows[i] == row) continue;
				if (rows[i].select.selectedIndex == 0) other_empty = true;
			}
			if (select.selectedIndex > 0) {
				var name = field.data.name+" #"+(rows.indexOf(row)+1);
				var val = select.options[select.selectedIndex]._value;
				var sheet = excel.sheets[val[0]];
				if (row.layer) {
					if (row.layer.sheet != sheet) {
						row.layer.sheet.__columns_layers.remove(row.layer);
						row.layer.sheet.removeLayer(row.layer);
						row.layer = null;
					} else {
						row.layer.setRange(val[1],row.layer.row_start,val[1],row.layer.row_end);
						row.layer.setContent(name);
					}
				}
				if (!row.layer) {
					row.layer = sheet.addLayer(val[1],sheet.__header_rows,val[1],sheet.rows.length-1,0,255,0,name);
					if (!sheet.__columns_layers) sheet.__columns_layers = [];
					sheet.__columns_layers.push(row.layer);
				}
				if (!other_empty)
					t._createMultipleFieldRow(field,rows);
			} else {
				if (row.layer) {
					row.layer.sheet.__columns_layers.remove(row.layer);
					row.layer.sheet.removeLayer(row.layer);
					row.layer = null;
				}
				if (select.selectedIndex == 1) {
					if (!other_empty)
						t._createMultipleFieldRow(field,rows);
				} else {
					if (other_empty) {
						row.tr.parentNode.removeChild(row.tr);
						rows.remove(row);
						t._updateMultipleFieldRows(field, rows);
					}
				}
			}
		};
	};
	this._createMultipleField = function(field) {
		if (field.data.sub_data) {
			var tr = document.createElement("TR"); t.table.appendChild(tr);
			var td_container = document.createElement("TD"); tr.appendChild(td_container);
			td_container.colSpan = 2;
			var table = document.createElement("TABLE");
			td_container.appendChild(table);
			var tbody = document.createElement("TBODY");
			table.appendChild(tbody);
			var tr_title = document.createElement("TR"); tbody.appendChild(tr_title);
			var td = document.createElement("TD"); tr_title.appendChild(td);
			td.appendChild(document.createTextNode(field.data.name));
			for (var i = 0; i < field.data.sub_data.names.length; ++i) {
				if (!field.data.sub_data.editableForNew[i]) continue;
				td = document.createElement("TD"); tr_title.appendChild(td);
				td.appendChild(document.createTextNode(field.data.sub_data.names[i]));
			}
			var rows = [];
			tr.getToImport = function() {
				var list = [];
				var index = 0;
				for (var i = 0; i < rows.length; ++i) {
					var row_empty = true;
					for (var j = 0; j < rows[i].selects.length; ++j) {
						if (rows[i].selects[j].selectedIndex <= 0) continue;
						if (rows[i].selects[j].selectedIndex == 1) {
							var err = rows[i].selects[j]._field.getError();
							if (err) return field.data.name+": "+field.data.sub_data.names[rows[i].selects[j]._sub_index]+": "+err;
							var val = rows[i].selects[j]._field.getCurrentData();
							var data = {data:field.data.name,path:field.path.path,value:val};
							data.index = index;
							data.sub_index = rows[i].selects[j]._sub_index;
							list.push(data);
						} else {
							var sel = rows[i].selects[j].options[rows[i].selects[j].selectedIndex]._value;
							var data = {data:field.data.name,path:field.path.path,sheet_name:excel.sheets[sel[0]].name,column:sel[1],row_start:excel.sheets[sel[0]].__header_rows};
							data.index = index;
							data.sub_index = rows[i].selects[j]._sub_index;
							row_empty = false;
							list.push(data);
						}
					}
					if (!row_empty) index++;
				}
				return list;
			};
			this._createMultipleFieldRowSubData(field, tbody, rows);
			if (existing) {
				var list = [];
				for (var i = 0; i < existing.length; ++i) {
					if (existing[i].data != field.data.name) continue;
					if (existing[i].path != field.path.path) continue;
					while (list.length <= existing[i].index) list.push([]);
					while (list[existing[i].index].length <= existing[i].sub_index) list[existing[i].index].push(null);
					list[existing[i].index][existing[i].sub_index] = existing[i];
				}
				while (rows.length < list.length) this._createMultipleFieldRowSubData(field, tbody, rows);
				for (var i = 0; i < list.length; ++i) {
					var row = rows[i];
					for (var j = 0; j < list[i].length; ++j) {
						var e = list[i][j];
						if (!e) continue;
						var select = null;
						for (var k = 0; k < row.selects.length; ++k) if (row.selects[k]._sub_index == j) { select = row.selects[k]; break; }
						if (!select) continue;
						if (typeof e.value != 'undefined') {
							// fixed value
							select.selectedIndex = 1;
							select.onchange();
							select._field.setData(e.value);
						} else {
							// selected sheet and column
							var sheet_index = -1;
							if (excel.sheets.length == 1) sheet_index = 0;
							else for (var k = 0; k < excel.sheets.length; ++k) if (excel.sheets[k].name == e.sheet_name) { sheet_index = k; break; }
							if (sheet_index < 0) continue;
							if (e.column >= excel.sheets[sheet_index].columns.length) continue;
							for (var k = 2; k < select.options.length; ++k)
								if (select.options[k]._value[0] == sheet_index && select.options[k]._value[1] == e.column) {
									select.selectedIndex = k;
									select.onchange();
									break;
								}
						}
					}
				}
			} else if (field._found)
				for (var sub_index = 0; sub_index < field._found.length; ++sub_index) {
					if (!field._found[sub_index]) continue;
					for (var i = 0; i < field._found[sub_index].length; ++i) {
						var found = field._found[sub_index][i];
						for (var j = 0; j < rows.length; ++j) {
							var select = null;
							for (var k = 0; k < rows[j].selects.length; ++k) if (rows[j].selects[k]._sub_index == sub_index) { select = rows[j].selects[k]; break; }
							if (!select) continue;
							if (select.selectedIndex > 0) continue;
							var si = -1;
							for (var k = 2; k < select.options.length; ++k)
								if (select.options[k]._value[0] == found[0] && select.options[k]._value[1] == found[1]) { si = k; break; }
							if (si > 0) {
								select.selectedIndex = si;
								select.onchange();
								break;
							}
						}
					}
				}
		} else {
			var rows = [];
			this._createMultipleFieldRow(field, rows);
			if (existing) {
				for (var i = 0; i < existing.length; ++i) {
					if (existing[i].data != field.data.name) continue;
					if (existing[i].path != field.path.path) continue;
					var row = rows[rows.length-1];
					if (typeof existing[i].value != 'undefined') {
						// fixed value
						row.select.selectedIndex = 1;
						row.select.onchange();
						row.select._field.setData(existing[i].value);
					} else {
						// selected sheet and column
						var sheet_index = -1;
						if (excel.sheets.length == 1) sheet_index = 0;
						else for (var j = 0; j < excel.sheets.length; ++j) if (excel.sheets[j].name == existing[i].sheet_name) { sheet_index = j; break; }
						if (sheet_index < 0) continue;
						if (existing[i].column >= excel.sheets[sheet_index].columns.length) continue;
						for (var j = 2; j < row.select.options.length; ++j)
							if (row.select.options[j]._value[0] == sheet_index && row.select.options[j]._value[1] == existing[i].column) {
								row.select.selectedIndex = j;
								row.select.onchange();
								break;
							}
					}
				}
			} else if (field._found)
				for (var i = 0; i < field._found.length; ++i) {
					var s = rows[rows.length-1].select;
					var si = -1;
					for (var j = 2; j < s.options.length; ++j)
						if (s.options[j]._value[0] == field._found[i][0] && s.options[j]._value[1] == field._found[i][1]) { si = j; break; }
					if (si > 0) {
						s.selectedIndex = si;
						s.onchange();
					}
				}
		}
	};
	
	this._createHeaderRowsField = function() {
		var div = document.createElement("DIV");
		div.innerHTML = "How many rows contain title/header ? ";
		container.appendChild(div);
		require([["typed_field.js","field_integer.js"]], function() {
			var f = new field_integer(excel.getActiveSheet().__header_rows,true,{min:0,can_be_null:false});
			div.appendChild(f.getHTMLElement());
			var update_layer = function(nb_rows) {
				var sheet = excel.getActiveSheet();
				if (nb_rows == 0) {
					if (sheet.__header_layer) {
						sheet.removeLayer(sheet.__header_layer);
						sheet.__header_layer = null;
					}
				} else {
					if (!sheet.__header_layer)
						sheet.__header_layer = sheet.addLayer(0, 0, sheet.columns.length-1, nb_rows-1, 255,100,0, "Title / Header");
					else
						sheet.__header_layer.setRange(0, 0, sheet.columns.length-1, nb_rows-1);
				}
				if (sheet.__columns_layers)
					for (var i = 0; i < sheet.__columns_layers.length; ++i)
						sheet.__columns_layers[i].setRange(sheet.__columns_layers[i].col_start, nb_rows, sheet.__columns_layers[i].col_start, sheet.__columns_layers[i].row_end);
			};
			excel.onactivesheetchanged.add_listener(function() {
				var nb_rows = excel.getActiveSheet().__header_rows;
				f.setData(nb_rows);
				update_layer(nb_rows);
			});
			f.onchange.add_listener(function() {
				var nb_rows = f.hasError() ? 0 : f.getCurrentData();
				excel.getActiveSheet().__header_rows = nb_rows;
				update_layer(nb_rows);
			});
			update_layer(excel.getActiveSheet().__header_rows);
		});
	};
	
	this._buildCategory = function(cat, list) {
		var title = document.createElement("DIV");
		title.className = "page_section_title3";
		title.appendChild(document.createTextNode(cat));
		var tr = document.createElement("TR"); t.table.appendChild(tr);
		var td = document.createElement("TD"); tr.appendChild(td);
		td.colSpan = 2;
		td.appendChild(title);
		for (var i = 0; i < list.length; ++i)
			if (window[list[i].data.field_classname].prototype.isMultiple())
				this._createMultipleField(list[i]);
			else
				this._createSingleField(list[i]);
	};
	
	this._columnFound = function(i,j,field,sub_index) {
		if (sub_index == -1) {
			if (window[field.data.field_classname].prototype.isMultiple()) {
				if (typeof field._found == 'undefined')
					field._found = [[i,j]];
				else
					field._found.push([i,j]);
			} else {
				if (typeof field._found == 'undefined')
					field._found = [i,j];
				else
					field._found = null;
			}
			return;
		}
		if (window[field.data.field_classname].prototype.isMultiple()) {
			if (typeof field._found[sub_index] == 'undefined')
				field._found[sub_index] = [[i,j]];
			else
				field._found[sub_index].push([i,j]);
		} else {
			if (typeof field._found[sub_index] == 'undefined')
				field._found[sub_index] = [i,j];
			else
				field._found[sub_index] = null;
		}
	};
	this._init = function() {
		require("typed_field.js");
		var cats = [];
		var js = [];
		for (var i = 0; i < fields.length; ++i) {
			if (!cats.contains(fields[i].data.category))
				cats.push(fields[i].data.category);
			if (!js.contains(fields[i].data.field_classname+".js"))
				js.push(fields[i].data.field_classname+".js");
		}
		require([["typed_field.js",js]]);
		for (var k = 0; k < fields.length; ++k)
			if (fields[k].data.sub_data) {
				fields[k]._found = [];
				for (var i = 0; i < fields[k].data.sub_data.names.length; ++i)
					fields[k]._found.push(undefined);
			}
		require([["typed_field.js",js]],function() {
			if (!existing) {
				// try to detect columns
				for (var i = 0; i < excel.sheets.length; ++i) {
					excel.sheets[i].__header_rows = 0;
					if (excel.sheets[i].rows.length == 0) continue;
					var found = false;
					for (var j = 0; j < excel.sheets[i].columns.length; ++j) {
						var value = excel.sheets[i].getCell(j, 0).getValue();
						// first, test exact match
						var exact_found = false;
						for (var k = 0; k < fields.length; ++k) {
							if (fields[k].data.sub_data == null) {
								if (fields[k].data.name.isSame(value)) {
									t._columnFound(i,j,fields[k],-1);
									found = true;
									exact_found = true;
								}
							} else {
								for (var l = 0; l < fields[k].data.sub_data.names.length; ++l) {
									if (!fields[k].data.sub_data.editableForNew[l]) continue;
									var name = fields[k].data.name+" "+fields[k].data.sub_data.names[l];
									if (name.isSame(value)) {
										t._columnFound(i,j,fields[k],l);
										found = true;
										exact_found = true;
									}
								}
							}
						}
						if (exact_found) continue;
						// then, try with word matching
						var best_match = null;
						var best_field = null;
						for (var k = 0; k < fields.length; ++k) {
							if (fields[k].data.sub_data == null) {
								var match = wordsMatch(value, fields[k].data.name, true);
								if (match.nb_words1_in_words2 == 0) continue;
								if (best_match === null) {
									best_match = match;
									best_field = [k,-1];
									continue;
								}
								if (match.nb_words1_in_words2 < best_match.nb_words1_in_words2) continue;
								if (match.nb_words1_in_words2 > best_match.nb_words1_in_words2) {
									best_match = match;
									best_field = [k,-1];
									continue;
								}
								best_field = undefined;
							} else {
								for (var l = 0; l < fields[k].data.sub_data.names.length; ++l) {
									if (!fields[k].data.sub_data.editableForNew[l]) continue;
									var match = wordsMatch(value, fields[k].data.name+" "+fields[k].data.sub_data.names[l], true);
									if (match.nb_words1_in_words2 == 0) continue;
									if (best_match === null) {
										best_match = match;
										best_field = [k,l];
										continue;
									}
									if (match.nb_words1_in_words2 < best_match.nb_words1_in_words2) continue;
									if (match.nb_words1_in_words2 > best_match.nb_words1_in_words2) {
										best_match = match;
										best_field = [k,l];
										continue;
									}
									best_field = undefined;
								}
							}
						}
						if (!best_match || !best_field) continue;
						t._columnFound(i,j,fields[best_field[0]],best_field[1]);
						found = true;
					}
					if (found)
						excel.sheets[i].__header_rows = 1;
				}
			} else {
				for (var i = 0; i < excel.sheets.length; ++i) {
					var min = -1;
					for (var j = 0; j < existing.length; ++j) {
						if (excel.sheets.length > 1 && excel.sheets[i].name != existing[j].sheet_name) continue;
						if (typeof existing[j].row_start == 'undefined') continue;
						if (min == -1 || existing[j].row_start < min) min = existing[j].row_start;
					}
					excel.sheets[i].__header_rows = min <= 0 ? 0 : min;
				}
			}
			
			t._createHeaderRowsField();
			var table = document.createElement("TABLE");
			container.appendChild(table);
			t.table = document.createElement("TBODY");
			table.appendChild(t.table);
			for (var i = 0; i < cats.length; ++i) {
				var list = [];
				for (var j = 0; j < fields.length; ++j)
					if (fields[j].data.category == cats[i]) list.push(fields[j]);
				t._buildCategory(cats[i], list);
			}
			onready(t);
		});
	};
	this._init();
}