if (typeof require != 'undefined') {
	require("vertical_layout.js");
}
function data_import(container, root_table, import_fields, preset_fields, title) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	
	this.root_table = root_table;
	this.import_fields = import_fields;
	this.preset_fields = preset_fields ? preset_fields : [];
	
	this.step1_upload_file = function() {
		t.unfreeze();
		while (t.page_container.childNodes.length > 0) t.page_container.removeChild(t.page_container.childNodes[0]);
		var div = document.createElement("DIV");
		t.page_container.appendChild(div);
		div.style.padding = "5px";
		div. innerHTML = "Upload the file to import:";
		var form = document.createElement("FORM");
		form.method = "POST";
		form.enctype = "multipart/form-data";
		form.target = "_import_data_excel_frame";
		form.action = "/dynamic/data_import/page/excel_upload";
		var input = document.createElement("INPUT");
		input.type = 'file';
		input.name = 'excel';
		form.appendChild(input);
		div.appendChild(form);
		div.appendChild(document.createElement("BR"));
		div.appendChild(document.createTextNode("Supported formats are: Excel (xls, xlsx), OpenOffice (odf), Sylk (slk), Gnumeric, CSV"));
		
		t.previousButton.disabled = 'disabled';
		t.finishButton.disabled = 'disabled';
		t.nextButton.disabled = 'disabled';
		input.onchange = function() {
			if (input.value.length > 0) {
				t.nextButton.disabled = '';
				t.onnext = function() {
					while (t.page_container.childNodes.length > 0) t.page_container.removeChild(t.page_container.childNodes[0]);
					t.freeze();
					t.freeze_message("<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Uploading file...");
					t.frame = document.createElement("IFRAME");
					t.frame.style.border = 'none';
					t.frame.frameBorder = 0;
					t.frame.name = "_import_data_excel_frame";
					t.page_container.appendChild(t.frame);
					t.right_div = document.createElement("DIV");
					t.right_div.style.overflow = 'auto';
					t.right_div.style.padding = "5px";
					t.page_container.appendChild(t.right_div);
					t.frame.onload = function() { this.onload = null; t.step2_select_data(); };
					require("splitter_vertical.js",function() {
						new splitter_vertical(t.page_container, 0.5);
						setTimeout(function(){form.submit();},1);
					});
					return true;
				};
			} else {
				t.nextButton.disabled = 'disabled';
			}
		};
	};
	
	this.step2_select_data = function() {
		var w = getIFrameWindow(t.frame);
		if (!w.excel || !w.excel.tabs) {
			t.freeze_message("<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Preparing Excel...");
			setTimeout(function() {t.step2_select_data();},25);
			return;
		}
		t.freeze_message("<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Preparing Import...");
		
		// initialize data
		for (var i = 0; i < w.excel.sheets.length; ++i) {
			w.excel.sheets[i]._data_import_layer = null;
		}
		
		while (t.right_div.childNodes.length > 0) t.right_div.removeChild(t.right_div.childNodes[0]);
		
		// header row
		var header_row_div = document.createElement("DIV"); t.right_div.appendChild(header_row_div);
		var header_row = document.createElement("INPUT");
		header_row.type = 'checkbox';
		header_row_div.appendChild(header_row);
		header_row_div.appendChild(document.createTextNode("Table contains headers "));
		var header_row_range = document.createElement("INPUT");
		header_row_div.appendChild(header_row_range);
		var icon = document.createElement("IMG");
		icon.src = "/static/excel/select_range.png";
		icon.className = "button";
		icon.style.verticalAlign = 'bottom';
		header_row_div.appendChild(icon);
		header_row.onchange = function() {
			if (header_row.checked) {
				header_row_range.disabled = '';
				header_row_range.onchange = function() {
					t.setRangeLayer(this, "Headers", 192,192,255);
					w.excel.sheets[w.excel.tabs.selected]._data_import_layer = this.layer;
				};
				icon.className = "button";
				icon.onclick = function() {
					t.getExcelRange(header_row_range);
					header_row_range.onchange();
				};
			} else {
				header_row_range.disabled = 'disabled';
				header_row_range.value = "";
				if (header_row_range.layer) header_row_range.layer.sheet.removeLayer(header_row_range.layer);
				w.excel.sheets[w.excel.tabs.selected]._data_import_layer = null;
				header_row_range.onchange = null;
				icon.className = "button disabled";
				icon.onclick = null;
			}
		};
		header_row.onchange();
		w.excel.tabs.onselect = function() {
			if (w.excel.sheets[w.excel.tabs.selected]._data_import_layer) {
				header_row.checked = 'checked';
				header_row_range.value = w.getExcelRangeString(w.excel.sheets[w.excel.tabs.selected], w.excel.sheets[w.excel.tabs.selected]._data_import_layer.getRange());
			} else {
				header_row.checked = '';
				header_row_range.value = "";
			}
			header_row.onchange();
			if (header_row_range.onchange) header_row_range.onchange();
		};
		
		// Fields
		require(["DataPath.js","collapsable_section.js","typed_field.js"]);
		service.json("data_model","get_available_fields",{table:t.root_table},function(fields){
			var categories = [];
			var javascripts = [];
			var avail_fields = fields;
			require("DataPath.js",function() {
				// analyze fields
				for (var i = 0; i < fields.length; ++i)
					fields[i].p = new DataPath(fields[i].path);
				
				// filter according to import_fields
				var final_fields = [];
				for (var i = 0; i < import_fields.length; ++i) {
					var path = import_fields[i].split(">");
					for (var j = 0; j < path.length; ++j) {
						var k = path[j].indexOf('.');
						path[j] = {table:path[j].substring(0,k), column:path[j].substring(k+1)};
					}
					if (path[0].table != root_table) {
						alert("Invalid field to import '"+import_fields[i]+"': starting table must be "+root_table);
						continue;
					}
					for (var j = 0; j < fields.length; ++j) {
						if (path[path.length-1].table != fields[j].p.table) continue; // not the same final table
						if (path[path.length-1].column != '*' && fields[j].p.column != path[path.length-1].column) continue; // not the same column
						// TODO check we have the same path
						if (!final_fields.contains(fields[j]))
							final_fields.push(fields[j]);
					}
				}
				fields = final_fields;

				// gather categories and javascripts needed
				for (var i = 0; i < fields.length; ++i) {
					if (!categories.contains(fields[i].cat)) categories.push(fields[i].cat);
					if (!javascripts.contains(fields[i].field_classname)) javascripts.push(fields[i].field_classname+".js");
				}
				require(["typed_field.js",javascripts]);
				// create sections for each category
				require("collapsable_section.js",function() {
					for (var i = 0; i < categories.length; ++i) {
						var section = new collapsable_section();
						section.header.appendChild(document.createTextNode(categories[i]));
						section.element.style.display = 'block';
						t.right_div.appendChild(section.element);
						var table = document.createElement("TABLE");
						section.content.appendChild(table);
						for (var j = 0; j < fields.length; ++j) {
							if (fields[j].cat != categories[i]) continue;
							var tr = document.createElement("TR"); table.appendChild(tr);
							
							var td = document.createElement("TD"); tr.appendChild(td);
							td.style.whiteSpace = 'nowrap';
							td.appendChild(document.createTextNode(fields[j].name));
							if (fields[j].p.is_mandatory()) {
								var span = document.createElement("SUP");
								span.style.color = 'red';
								span.innerHTML = "*";
								td.appendChild(span);
							}
							td = document.createElement("TD"); tr.appendChild(td);
							td.style.whiteSpace = 'nowrap';
							
							td = document.createElement("TD"); tr.appendChild(td);
							fields[j].select = document.createElement("SELECT");
							fields[j].select.field = fields[j];
							td.appendChild(fields[j].select);
							var o;
							o = document.createElement("OPTION");
							o.value = 'na';
							o.text = "Not Available";
							fields[j].select.add(o);
							o = document.createElement("OPTION");
							o.value = 'set';
							o.text = "Set all to...";
							fields[j].select.add(o);
							for (var sheet_i = 0; sheet_i < w.excel.sheets.length; ++sheet_i) {
								var sheet = w.excel.sheets[sheet_i];
								for (var col_i = 0; col_i < sheet.columns.length; ++col_i) {
									o = document.createElement("OPTION");
									o.value = 'column';
									o.sheet = sheet_i;
									o.column = col_i;
									o.text = sheet.name+" Column "+w.getExcelColumnName(col_i);
									fields[j].select.add(o);
								}
							}
							fields[j].select.onchange = function() {
								if (this.layer) this.layer.sheet.removeLayer(this.layer);
								this.layer = null;
								var o = this.options[this.selectedIndex];
								try { this.parentNode.removeChild(this.field.span_selected); } catch (ex) {}
								try { this.parentNode.removeChild(this.field.typed_field.getHTMLElement()); } catch (ex) {}
								if (o.value == 'na') {
								} else if (o.value == 'set') {
									this.parentNode.appendChild(this.field.typed_field.getHTMLElement());
								} else if (o.value == 'column') {
									var row_start = 0;
									if (w.excel.sheets[o.sheet]._data_import_layer) {
										if (o.column >= w.excel.sheets[o.sheet]._data_import_layer.col_start && o.column <= w.excel.sheets[o.sheet]._data_import_layer.col_end)
											row_start = w.excel.sheets[o.sheet]._data_import_layer.row_end+1;
									}
									this.layer = w.excel.sheets[o.sheet].addLayer(o.column,row_start,o.column,w.excel.sheets[o.sheet].rows.length-1,192,255,192,this.field.name);
									this.parentNode.appendChild(this.field.span_selected);
									this.field.span_selected.innerHTML = (w.excel.sheets[o.sheet].rows.length-row_start)+" value(s) selected";
								}
							};
							
							fields[j].span_selected = document.createElement("SPAN");
							fields[j].typed_field = new window[fields[j].field_classname](null,true,null,null,fields[j].field_args);
						}
						section.element.style.marginBottom = "5px";
					}
					
					t.unfreeze();
					t.previousButton.disabled = '';
					t.onprevious = function() {
						while (t.page_container.childNodes.length > 0) t.page_container.removeChild(t.page_container.childNodes[0]);
						t.freeze();
						t.step1_upload_file();
					};
					t.nextButton.disabled = '';
					t.onnext = function() {
						var nb_rows = 0;
						for (var i = 0; i < fields.length; ++i) {
							if (fields[i].select.value == 'column') {
								var layer = fields[i].select.layer;
								var nb = layer.row_end-layer.row_start+1;
								if (nb > nb_rows) nb_rows = nb;
							}
						}
						if (nb_rows == 0) {
							alert("Nothing to import. Please select data to import.");
							return false;
						}
						var data = [];
						var columns = [];
						for (var i = 0; i < nb_rows; ++i) data.push([]);
						for (var i = 0; i < fields.length; ++i) {
							if (fields[i].select.value == 'na' || (fields[i].select.value == 'set' && fields[i].typed_field.getCurrentData() == null)) {								if (fields[i].p.is_mandatory()) {
									alert(fields[i].name+" is mandatory.");
									return false;
								}
								continue;
							}
							if (fields[i].select.value == 'set') {
								columns.push(fields[i]);
								for (var row = 0; row < nb_rows; ++row)
									data[row][columns.length-1] = fields[i].typed_field.getCurrentData();
							} else {
								columns.push(fields[i]);
								var layer = fields[i].select.layer;
								for (var row = 0; row < nb_rows; ++ row) {
									var excel_row = row+layer.row_start;
									if (excel_row > layer.row_end) {
										if (fields[i].p.is_mandatory()) {
											alert(fields[i].name+" is mandatory, but some values are missing.");
											return false;
										}
										data[row][columns.length-1] = null;
									} else
										data[row][columns.length-1] = layer.sheet.getCell(layer.col_start, excel_row).getValue();
								}
							}
						}
						t.step3_show_imported_data(columns, data, avail_fields);
						return true;
					};
				});
			});
		});
	};
	
	this.step3_show_imported_data = function(fields, data, avail_fields) {
		var prev_content = t.page_container.childNodes.length;
		for (var i = 0; i < t.page_container.childNodes.length; ++i) {
			var e = t.page_container.childNodes[i];
			e.style.visibility = 'hidden';
			e.style.position = 'absolute';
			e.style.top = '-10000px';
		}
		
		var preset = [];
		for (var i = 0; i < t.preset_fields.length; ++i) {
			for (var j = 0; j < avail_fields.length; ++j) {
				if (avail_fields[j].edit) {
					if (avail_fields[j].edit.table == t.preset_fields[i].table && avail_fields[j].edit.column == t.preset_fields[i].column) {
						preset.push({field:avail_fields[j],preset:t.preset_fields[i]});
					}
				} else {
					if (avail_fields[j].p.table == t.preset_fields[i].table && avail_fields[j].p.column == t.preset_fields[i].column) {
						preset.push({field:avail_fields[j],preset:t.preset_fields[i]});
					}
				}
			}
		}
		
		var div = document.createElement("DIV");
		div.style.width = '100%';
		div.style.height = '100%';
		div.style.overflow = 'auto';
		t.page_container.appendChild(div);
		var div2 = document.createElement("DIV");
		div2.style.padding = '3px';
		div.appendChild(div2);
		div2.appendChild(document.createTextNode("Please check the data which is about to be imported. You can still modify some values. When ready, click on Finish."));
		div2.appendChild(document.createElement("BR"));
		var table = document.createElement("TABLE");
		div2.appendChild(table);
		table.style.border = '1px solid black';
		table.style.borderCollapse = 'collapse';
		table.style.borderSpacing = '0px';
		var tr, td;
		table.appendChild(tr = document.createElement("TR"));
		for (var i = 0; i < fields.length; ++i) {
			tr.appendChild(td = document.createElement("TH"));
			td.style.backgroundColor = '#C0C0F0';
			td.style.border = '1px solid black';
			td.appendChild(document.createTextNode(fields[i].name));
		}
		for (var i = 0; i < preset.length; ++i) {
			tr.appendChild(td = document.createElement("TH"));
			td.style.backgroundColor = '#C0C0F0';
			td.style.border = '1px solid black';
			td.appendChild(document.createTextNode(preset[i].field.name));
		}
		for (var row = 0; row < data.length; ++row) {
			table.appendChild(tr = document.createElement("TR"));
			for (var col = 0; col < fields.length; ++col) {
				tr.appendChild(td = document.createElement("TD"));
				td.style.border = '1px solid black';
				td.style.padding = '1px';
				var f = new window[fields[col].field_classname](data[row][col],true,null,null,fields[col].field_args);
				td.appendChild(f.getHTMLElement());
				td.field = f;
			}
			for (var col = 0; col < preset.length; ++col) {
				tr.appendChild(td = document.createElement("TD"));
				td.style.border = '1px solid black';
				td.style.padding = '1px';
				var f = new window[preset[col].field.field_classname](preset[col].preset.value,false,null,null,preset[col].field.field_args);
				td.appendChild(f.getHTMLElement());
				td.field = f;
			}
		}
		
		t.previousButton.disabled = '';
		t.onprevious = function() {
			while (t.page_container.childNodes.length > prev_content)
				t.page_container.removeChild(t.page_container.childNodes[prev_content]);
			for (var i = 0; i < t.page_container.childNodes.length; ++i) {
				var e = t.page_container.childNodes[i];
				e.style.visibility = 'visible';
				e.style.position = 'static';
			}
			t.unfreeze();
		};
		t.finishButton.disabled = '';
		t.onfinish = function() {
			var req = {};
			req.root_table = t.root_table;
			req.fields = [];
			for (var i = 0; i < fields.length; ++i) req.fields.push(fields[i]);
			for (var i = 0; i < preset.length; ++i) req.fields.push(preset[i].field);
			req.data = data;
			for (var col = 0; col < preset.length; ++col) {
				for (var row = 0; row < req.data.length; ++row)
					data[row].push(preset[col].preset.value);
			}
			// remove duplicate columns
			for (var i = 0; i < req.fields.length; ++i) {
				for (var j = i+1; j < req.fields.length; ++j) {
					if (req.fields[i].edit.table != req.fields[j].edit.table) continue;
					if (req.fields[i].edit.column != req.fields[j].edit.column) continue;
					if (req.fields[i].edit.sub_model != req.fields[j].edit.sub_model) continue;
					// duplicate
					req.fields.splice(j,1);
					for (var r = 0; r < req.data.length; ++r)
						req.data[r].splice(j,1);
					j--;
				}
			}
			var json = service.generate_input(req);
			var form = document.createElement("FORM");
			form.method = 'POST';
			form.action = "/dynamic/data_import/page/data_import";
			var input = document.createElement("INPUT");
			input.type = 'hidden';
			input.name = "data_import_check";
			input.value = json;
			form.appendChild(input);
			form.submit();
		};
		t.unfreeze();
	};
	
	this.setRangeLayer = function(input, title, r,g,b) {
		if (input.layer) input.layer.sheet.removeLayer(input.layer);
		input.layer = null;
		var w = getIFrameWindow(t.frame);
		var r = w.parseExcelRangeString(input.value);
		if (!r.sheet) return;
		var xl = w.excel;
		var sheet = xl.getSheet(r.sheet);
		input.layer = sheet.addLayer(r.range.start_col,r.range.start_row,r.range.end_col,r.range.end_row,r,g,b,title);
	};
	this.getExcelRange = function(input) {
		var w = getIFrameWindow(t.frame);
		var xl = w.excel;
		var sheet = xl.getActiveSheet();
		if (!sheet) return;
		var sel = sheet.getSelection();
		if (!sel) return;
		input.value = w.getExcelRangeString(sheet, sel);
		if (input.onchange) input.onchange();
	};
	
	
	this.freezer = null;
	this.freezer_message_id = generate_id();
	this.freeze = function() {
		if (this.freezer) return;
		this.freezer = document.createElement("DIV");
		this.freezer.style.position = "absolute";
		this.freezer.style.backgroundColor = "rgba(192,192,192,0.5)";
		this.freezer.style.top = this.page_container.offsetTop+"px";
		this.freezer.style.left = this.page_container.offsetLeft+"px";
		this.freezer.style.width = this.page_container.offsetWidth+"px";
		this.freezer.style.height = this.page_container.offsetHeight+"px";
		this.freezer.innerHTML = "<table style='width:100%;height:100%'><tr><td id='"+this.freezer_message_id+"' align=center valign=middle></td></tr></table>";
		container.appendChild(this.freezer);
	};
	this.unfreeze = function() {
		if (!this.freezer) return;
		container.removeChild(this.freezer);
		this.freezer = null;
	};
	this.freeze_message = function(msg) {
		var td = document.getElementById(this.freezer_message_id);
		if (td) td.innerHTML = msg;
	};
	this._create_wizard = function() {
		while (container.childNodes.length > 0) container.removeChild(container.childNodes[0]);
		t.header = document.createElement("DIV"); container.appendChild(t.header);
		t.header.setAttribute("layout","35");
		t.header.className = "wizard_header";
		t.header.innerHTML = "<img src='/static/data_import/import_excel_32.png'/> Import Excel - "+title;
		t.page_container = document.createElement("DIV"); container.appendChild(t.page_container);
		t.page_container.setAttribute("layout", "fill");
		t.buttons = document.createElement("DIV"); container.appendChild(t.buttons);
		t.buttons.className = "wizard_buttons";
		t.previousButton = document.createElement("BUTTON"); t.buttons.appendChild(t.previousButton);
		t.previousButton.innerHTML = "<img src='"+theme.icons_16.back+"'/> Previous";
		t.previousButton.onclick = function() { 
			t.freeze();
			t.previousButton.disabled = 'disabled';
			t.finishButton.disabled = 'disabled';
			t.nextButton.disabled = 'disabled';
			t.onprevious(); 
		};
		t.nextButton = document.createElement("BUTTON"); t.buttons.appendChild(t.nextButton);
		t.nextButton.innerHTML = "<img src='"+theme.icons_16.forward+"'/> Next";
		t.nextButton.onclick = function() { 
			t.freeze();
			var p = t.previousButton.disabled;
			var f = t.finishButton.disabled;
			var n = t.nextButton.disabled;
			t.previousButton.disabled = 'disabled';
			t.finishButton.disabled = 'disabled';
			t.nextButton.disabled = 'disabled';
			if (!t.onnext()) {
				t.previousButton.disabled = p;
				t.finishButton.disabled = f;
				t.nextButton.disabled = n;
				t.unfreeze();
			} 
		};
		t.finishButton = document.createElement("BUTTON"); t.buttons.appendChild(t.finishButton);
		t.finishButton.innerHTML = "<img src='"+theme.icons_16.ok+"'/> Finish";
		t.finishButton.onclick = function() { 
			t.freeze();
			t.previousButton.disabled = 'disabled';
			t.finishButton.disabled = 'disabled';
			t.nextButton.disabled = 'disabled';
			t.onfinish(); 
			t.unfreeze();
		};
		require("vertical_layout.js",function() {
			new vertical_layout(container);
		});
		t.step1_upload_file();
	};
	this._create_wizard();
}