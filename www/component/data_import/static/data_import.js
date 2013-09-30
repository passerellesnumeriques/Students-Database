if (typeof require != 'undefined') {
	require("vertical_layout.js");
}
function data_import(container, root_table, preset_fields, import_type) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	
	this.root_table = root_table;
	this.preset_fields = preset_fields ? preset_fields : [];
	this.import_type = import_type ? import_type : "file";
	
	this.step1_select_root_table = function() {
		t.unfreeze();
		if (root_table) { this.step2_upload_file(); return; } // already given in constructor
		while (t.page_container.childNodes.length > 0) t.page_container.removeChild(t.page_container.childNodes[0]);
		var div = document.createElement("DIV");
		t.page_container.appendChild(div);
		div.style.padding = "5px";
		div.innerHTML = "Select the type of data you want to import: ";
		var select = document.createElement("SELECT");
		div.appendChild(select);
		t.previousButton.disabled = 'disabled';
		t.finishButton.disabled = 'disabled';
		t.nextButton.disabled = 'disabled';
		service.json("data_import", "get_root_tables", {}, function(r) {
			if (r) {
				for (var i = 0; i < r.length; ++i) {
					var o = document.createElement("OPTION");
					o.value = r[i].table;
					o.text = r[i].display;
					select.add(o);
				}
				t.nextButton.disabled = '';
				t.onnext = function() { t.root_table = select.value; t.step2_upload_file(); };
			}
		});
	};
	
	this.step2_upload_file = function() {
		t.unfreeze();
		while (t.page_container.childNodes.length > 0) t.page_container.removeChild(t.page_container.childNodes[0]);
		var div = document.createElement("DIV");
		t.page_container.appendChild(div);
		div.style.padding = "5px";
		if (import_type == "create_template")
			div.innerHTML = "Upload an example of file to define the template:";
		else
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
		
		if (!root_table) {
			t.previousButton.disabled = '';
			t.onprevious = function() { t.step1_select_root_table(); };
		} else {
			t.previousButton.disabled = 'disabled';
		}
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
					t.frame.onload = function() { this.onload = null; t.step3_select_how_are_data(); };
					require("splitter_vertical.js",function() {
						new splitter_vertical(t.page_container, 0.7);
						setTimeout(function(){form.submit();},1);
					});
				};
			} else {
				t.nextButton.disabled = 'disabled';
			}
		};
	};
	
	this.step3_select_how_are_data = function() {
		var w = getIFrameWindow(t.frame);
		if (!w.excel) {
			t.freeze_message("<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Preparing Excel...");
			setTimeout(function() {t.step3_select_how_are_data();},25);
			return;
		}
		t.unfreeze();
		
		while (t.right_div.childNodes.length > 0) t.right_div.removeChild(t.right_div.childNodes[0]);
		t.right_div.appendChild(document.createTextNode("How data are organized in the Excel ?"));
		t.right_div.appendChild(document.createElement("BR"));
		var radio_by_column = document.createElement("INPUT");
		var radio_by_range = document.createElement("INPUT");
		var f = function() {
			if (radio_by_column.checked) {
				t.nextButton.disabled = '';
				t.onnext = function() { t.step4_by_column(); };
			} else if (radio_by_range.checked) {
				t.nextButton.disabled = '';
				t.onnext = function() { t.step4_by_range(); };
			} else
				t.nextButton.disabled = 'disabled';
		};
		radio_by_column.onchange = f;
		radio_by_range.onchange = f;
		radio_by_column.type = "radio";
		radio_by_column.name = "how_are_data";
		radio_by_column.value = 'by_column';
		t.right_div.appendChild(radio_by_column);
		t.right_div.appendChild(document.createTextNode("Multiple entries, one per row (specify only column for each information)"));
		t.right_div.appendChild(document.createElement("BR"));
		radio_by_range.type = "radio";
		radio_by_range.name = "how_are_data";
		radio_by_range.value = 'by_range';
		t.right_div.appendChild(radio_by_range);
		t.right_div.appendChild(document.createTextNode("Multiple entries, not per row (specify ranges where to find information)"));
		t.right_div.appendChild(document.createElement("BR"));
		
		t.previousButton.disabled = '';
		t.onprevious = function() {
			t.step2_upload_file();
		};
	};
	
	this.step4_init_multiple = function(handler) {
		while (t.right_div.childNodes.length > 0) t.right_div.removeChild(t.right_div.childNodes[0]);
		
		require("DataPath.js");
		require("collapsable_section.js");
		service.json("data_model","get_available_fields",{table:t.root_table},function(fields){
			var categories = [];
			require("DataPath.js",function() {
				for (var i = 0; i < fields.length; ++i) {
					fields[i].p = new DataPath(fields[i].path);
					if (!categories.contains(fields[i].cat)) categories.push(fields[i].cat);
				}
				require("collapsable_section.js",function() {
					for (var i = 0; i < categories.length; ++i) {
						var section = new collapsable_section();
						section.header.appendChild(document.createTextNode(categories[i]));
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
							fields[j].td = td;
						}
						section.element.style.marginBottom = "5px";
					}
					handler(fields,function() {
						t.unfreeze();
						t.previousButton.disabled = '';
						t.onprevious = function() { t.step3_select_how_are_data(); };
					});
				});
			});
		});
	};
	this.step4_by_column = function() {
		this.step4_init_multiple(function(fields,onready) {
			onready();
		});
	};
	this.step4_by_range = function() {
		this.step4_init_multiple(function(fields,onready){
			var to_be_ready_2 = 1;
			var ready2 = function() {
				if (--to_be_ready_2 > 0) return;
				onready();
			};
			var preset_value = function(field, value) {
				field.select_type.selectedIndex = 2;
				to_be_ready_2++;
				field.select_type.onchange(null,function(){
					field.set_field.setData(value);
					field.set_field.getHTMLElement().disabled = 'disabled';
					ready2();
				});
				field.select_type.disabled = 'disabled';
			};
			var to_be_ready = 1;
			var ready = function() {
				if (--to_be_ready > 0) return;
				for (var i = 0; i < t.preset_fields.length; ++i) {
					for (var j = 0; j < fields.length; ++j) {
						if (fields[j].edit &&
							fields[j].edit.table == t.preset_fields[i].table &&
							fields[j].edit.column == t.preset_fields[i].column) {
							preset_value(fields[j], t.preset_fields[i].value);
							for (var k = 0; k < fields.length; ++k) {
								if (k == j) continue;
								if (fields[k].edit && fields[k].edit.table == fields[j].edit.table && fields[k].edit.column == fields[j].edit.column)
									preset_value(fields[k], t.preset_fields[i].value);
							}
							break;
						}
					}
				}
				ready2();
			};
			for (var i = 0; i < fields.length; ++i) {
				fields[i].select_type = document.createElement("SELECT"); fields[i].td.appendChild(fields[i].select_type);
				var o;
				o = document.createElement("OPTION");
				o.value = 'na'; o.text = 'Not Available';
				fields[i].select_type.add(o);
				o = document.createElement("OPTION");
				o.value = 'range'; o.text = 'From Range';
				fields[i].select_type.add(o);
				o = document.createElement("OPTION");
				o.value = 'set'; o.text = 'All set to value';
				fields[i].select_type.add(o);
				fields[i].select_type.field = fields[i];
				fields[i].select_type.onchange = function(ev,onloaded) {
					var field = this.field;
					if (field.range && field.range.layer) field.range.layer.sheet.removeLayer(field.range.layer);
					while (field.select_type.nextSibling) field.td.removeChild(field.select_type.nextSibling);
					if (field.select_type.value == 'range') {
						field.range = document.createElement("INPUT");
						field.range.type = "text";
						field.range.onblur = function() {
							if (this.layer) this.layer.sheet.removeLayer(this.layer);
							this.layer = null;
							var w = getIFrameWindow(t.frame);
							var r = w.parseExcelRangeString(this.value);
							if (!r.sheet) return;
							var xl = w.excel;
							var sheet = xl.getSheet(r.sheet);
							this.layer = sheet.addLayer(r.range.start_col,r.range.start_row,r.range.end_col,r.range.end_row,192,255,192,field.name);
						};
						field.td.appendChild(field.range);
						var button = document.createElement("IMG");
						button.className = "button";
						button.style.verticalAlign = 'bottom';
						button.src = "/static/excel/select_range.png";
						button.onclick = function() {
							var w = getIFrameWindow(t.frame);
							var xl = w.excel;
							var sheet = xl.getActiveSheet();
							if (!sheet) return;
							var sel = sheet.getSelection();
							if (!sel) return;
							field.range.value = w.getExcelRangeString(sheet, sel);
							field.range.onblur();
						};
						field.td.appendChild(button);
						fireLayoutEventFor(t.right_div);
					} else if (field.select_type.value == 'set') {
						require("typed_field.js",function(){
							require(field.field_classname+".js",function(){
								field.set_field = new window[field.field_classname](null,true,null,null,field.field_args);
								field.td.appendChild(field.set_field.getHTMLElement());
								if (onloaded) onloaded();
								fireLayoutEventFor(t.right_div);
							});
						});
					}
				};
			}
			ready();
		});
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
		t.header.innerHTML = "<img src='/static/data_import/import_excel_32.png'/> "+(import_type == "create_template" ? "Create Excel Import Template" : "Import Excel");
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
			t.previousButton.disabled = 'disabled';
			t.finishButton.disabled = 'disabled';
			t.nextButton.disabled = 'disabled';
			t.onnext(); 
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
		t.step1_select_root_table();
	};
	this._create_wizard();
}