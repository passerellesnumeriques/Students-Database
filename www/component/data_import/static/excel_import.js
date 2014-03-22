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

	/* prepare */
	require(["upload.js","splitter_vertical.js"], function() {
		onready(t);
	});
	
	this.init = function() {
		if (!t.frame_excel) {
			/* upload excel file */
			t._upl = createUploadTempFile(false, true);
	
			/* layout */
			t.frame_excel = document.createElement("IFRAME");
			t.frame_excel.style.border = "0px";
			t.frame_import = document.createElement("IFRAME");
			t.frame_import.style.border = "0px";
			container.appendChild(t.frame_excel);
			container.appendChild(t.frame_import);
			new splitter_vertical(container, 0.5);
		} else {		
			t.frame_excel.src = "about:blank";
			t.frame_import.src = "about:blank";
		}
	};
}