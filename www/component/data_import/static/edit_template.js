function edit_template(container, type_id, root_table, sub_model, known_columns, template, onready) {
	if (typeof container == 'string') container = document.getElementById('container');
	var t=this;
	var popup = window.parent.get_popup_window_from_frame(window);
	
	this._startEdition = function(type) {
		var win = getIFrameWindow(t.frame_excel);
		var excel = win.excel;
		popup.freeze();
		require("datadisplay.js",function() {
			service.json("data_import","get_importable_data",{root:root_table,sub_model:sub_model,known_columns:known_columns},function(res){
				if (!res) { popup.unfreeze(); return; }
				if (type == "multiple") {
					require("template_multiple_entries.js", function() {
						var temp_name = null;
						var temp_id = template ? template.id : null;
						new template_multiple_entries(t.import_content, excel, res, template ? template.to_import : null, function(temp) {
							var save = function() {
								popup.freeze("Saving...");
								temp.save(type_id,temp_name.value,temp_id,root_table,sub_model,function(res) {
									popup.unfreeze();
									if (res && res.id) temp_id = res.id;
								});
							};
							var div = document.createElement("DIV");
							div.appendChild(document.createTextNode("Template name: "));
							temp_name = document.createElement("INPUT");
							temp_name.type = "text";
							temp_name.style.marginRight = "5px";
							if (template) temp_name.value = template.name;
							div.appendChild(temp_name);
							popup.addFooter(div);
							popup.addSaveButton(function() {
								if (temp_name.value) save();
								else
									inputDialog(null,"New Import Template","Please choose a name for this new template","",100,function(name) {
										name = name.trim();
										if (name.length == 0) return "Please enter a name";
										return null;
									},function(name) {
										if (!name) return;
										temp_name.value = name.trim();
										save();
									});
							});
							popup.addCancelButton();
							popup.unfreeze();
						});
					});
				} else {
					require("template_single_entry.js", function() {
						new template_single_entry(t.import_content, excel, res, function() { popup.unfreeze(); });
					});
				}
			});
		});
	};
	
	this._askTypeOfImport = function() {
		t.import_content.removeAllChildren();
		var div = document.createElement("DIV");
		div.style.padding = "5px";
		t.import_content.appendChild(div);
		div.innerHTML = "Which type of file is it ?<br/>";
		var r1 = document.createElement("INPUT");
		r1.type = "radio";
		r1.name = "import_type";
		div.appendChild(r1);
		div.appendChild(document.createTextNode("Multiple entries, one by row, data organized by columns"));
		div.appendChild(document.createElement("BR"));
		var r2 = document.createElement("INPUT");
		r2.type = "radio";
		r2.name = "import_type";
		div.appendChild(r2);
		div.appendChild(document.createTextNode("Single entry, each data is on a specific cell"));
		div.appendChild(document.createElement("BR"));
		var button = document.createElement("BUTTON");
		button.innerHTML = "Continue <img src='"+theme.icons_16.right+"'/>";
		button.disabled = "disabled";
		div.appendChild(button);
		r1.onchange = function() { button.disabled = ""; };
		r2.onchange = function() { button.disabled = ""; };
		button.onclick = function() {
			if (r1.checked)
				t._startEdition("multiple");
			else
				t._startEdition("single");
		};
	};
	
	this._excelReady = function() {
		t.splitter.show_right();
		t.excel_header.removeAllChildren();
		t.excel_bar = new header_bar(t.excel_header, 'toolbar');
		t.excel_bar.setTitle("/static/excel/excel_16.png", "Example Excel File");
		container.style.width = "100%";
		container.style.height = "100%";
		layout.changed(container);
		popup.showPercent(95,95);
		if (!template)
			this._askTypeOfImport();
		else
			this._startEdition(template.type);
	};
	
	this._startUpload = function(click_event) {
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
			t.frame_excel.onload = function() {
				var check_view = function() {
					var win = getIFrameWindow(t.frame_excel);
					if (!win.excel || !win.excel.tabs) {
						if (win.page_errors && !win.excel_uploaded) {
							popup.unfreeze();
							return;
						}
						setTimeout(check_view, 100);
						return;
					}
					t._excelReady();
					popup.unfreeze();
				};
				var check_loaded = function() {
					var win = getIFrameWindow(t.frame_excel);
					if (!win) {
						setTimeout(check_loaded, 100);
						return;
					}
					if (win.page_errors && !win.excel_uploaded) {
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
			t.frame_excel.src = "/dynamic/data_import/page/excel_upload?id="+output.id+"&remove_empty_sheets=true";
		};
		t._upl.openDialog(click_event);
	};
	
	this._init = function() {
		container.removeAllChildren();
		theme.css("wizard.css");
		
		require(["upload.js","splitter_vertical.js","header_bar.js"], function(){
			t.left = document.createElement("DIV");
			t.right = document.createElement("DIV");
			t.left_container = document.createElement("DIV");
			t.left.appendChild(t.left_container);
			t.right_container = document.createElement("DIV");
			t.right.appendChild(t.right_container);
			t.left_container.style.height = "100%";
			t.left_container.style.display = "flex";
			t.left_container.style.flexDirection = "column";
			t.right_container.style.display = "flex";
			t.right_container.style.flexDirection = "column";
			t.right_container.style.height = "100%";
			
			t.excel_header = document.createElement("DIV");
			t.excel_header.style.flex = "none";
			t.frame_excel = document.createElement("IFRAME");
			t.frame_excel.style.flex = "1 1 auto";
			t.frame_excel.style.border = "0px";
			t.frame_excel.style.width = "100%";
			t.frame_excel._no_loading = true;
			t.left_container.appendChild(t.excel_header);
			t.left_container.appendChild(t.frame_excel);
			
			t.import_header = document.createElement("DIV");
			t.import_header.style.flex = "none";
			t.import_content = document.createElement("DIV");
			t.import_content.style.flex = "1 1 auto";
			t.import_content.style.overflow = "auto";
			t.right_container.appendChild(t.import_header);
			t.right_container.appendChild(t.import_content);

			container.appendChild(t.left);
			container.appendChild(t.right);
			container.style.height = "200px";
			container.style.width = "500px";
			t.splitter = new splitter_vertical(container, 0.5);
			t.splitter.hide_right();
			t.import_bar = new header_bar(t.import_header, 'toolbar');
			t.import_bar.setTitle(theme.icons_16._import, "How to import data");

			t._upl = createUploadTempFile(false, true);
			t.excel_header.className = "wizard_header";
			t.excel_header.innerHTML = "<img src='/static/data_import/import_excel_32.png'/> Upload an Excel file as example to "+(template ? "edit" : "create")+" the template";
			t.frame_excel.style.border = "0px";
			t.frame_excel.style.width = "100%";
			t.frame_excel._upload = function(ev) {
				t._startUpload(ev);
			};
			t.frame_excel.src = "/dynamic/data_import/page/excel_upload?button=_upload";
			waitFrameContentReady(t.frame_excel, function(win) { return win.is_excel_upload_button; }, function(win){
				layout.changed(container);
				if (onready) onready(this);
			});
		});
	};
	this._init();
}