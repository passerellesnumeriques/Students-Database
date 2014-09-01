function createUploadTempFile(multiple, async) {
	return new upload("/dynamic/storage/service/store_temp", multiple, async);
}

function upload(target, multiple, async) {
	var t=this;
	t.onstart = null;
	t.onstartfile = null;
	t.onprogressfile = null;
	t.ondonefile = null;
	t.ondone = null;
	t.oncancelled = null;
	
	t.openDialog = function(click_event, accept) {
		document.body.appendChild(t.form = document.createElement("FORM"));
		t.form.method = "POST";
		t.form.enctype = "multipart/form-data";
		t.form.action = target;
		t.form.appendChild(t.input = document.createElement("INPUT"));
		t.input.type = "file";
		t.input.name = "storage_upload";
		if (accept) t.input.accept = accept;
		if (multiple) t.input.multiple = "multiple";
		t.form.style.position = 'absolute';
		t.form.style.top = '-10000px';
		t.input.addEventListener("change", function(e){t.FileSelectHandler(e);}, false);
		if (t.input.click)
			t.input.click();
		else
			triggerEvent(t.input, 'click', {});
	};
	
	t.appendDropArea = function(container) {
		if (typeof container == 'string') container = document.getElementById(container);
		container.appendChild(t.area = document.createElement("DIV"));
		t.area.style.color = "#505050";
		t.area.style.border = "2px dashed #505050";
		setBorderRadius(t.area, 7, 7, 7, 7, 7, 7, 7, 7);
		t.area.style.textAlign = "center";
		t.area.style.verticalAlign = "middle";
		t.area.innerHTML = multiple ? "Drop files here" : "Drop file here";
		t.area.addEventListener("dragover", function(e){t.FileDragHover(e);return false;}, false);
		t.area.addEventListener("dragleave", function(e){t.FileDragHover(e);return false;}, false);
		t.area.addEventListener("drop", function(e){t.FileSelectHandler(e);return false;}, false);
	};
	t.addHiddenDropArea = function(where) {
		if (!t.droppable) return;
		if (typeof where == 'string') where = document.getElementById(where);
		document.addEventListener("dragover", function(e){
			if (t.area) return;
			t.area = document.createElement("DIV");
			t.area.style.position = "absolute";
			t.area.style.top = absoluteTop(where)+"px";
			t.area.style.left = absoluteLeft(where)+"px";
			t.area.style.width = where.offsetWidth+"px";
			t.area.style.height = where.offsetHeight+"px";
			t.area.style.zIndex = 10;
			t.area.style.padding = "1em 0";
			t.area.style.color = "#505050";
			t.area.style.backgroundColor = "rgba(255,255,255,0.5)";
			t.area.style.border = "2px dashed #505050";
			setBorderRadius(t.area, 7, 7, 7, 7, 7, 7, 7, 7);
			t.area.style.textAlign = "center";
			t.area.style.verticalAlign = "middle";
			t.area.innerHTML = multiple ? "Drop files here" : "Drop file here";
			document.body.appendChild(t.area);
			t.area.addEventListener("dragover", function(e){t.FileDragHover(e);}, false);
			t.area.addEventListener("dragleave", function(e){t.FileDragHover(e);}, false);
			t.area.addEventListener("drop", function(e){t.FileSelectHandler(e);}, false);
			t.area_hidden = true;
		},false);
		document.addEventListener("dragleave", function(e){
			t.area_hidden = false;
			if (t.area) {
				t.area.parentNode.removeChild(t.area);
				t.area = null;
			}
		},false);
		document.addEventListener("drop", function(e){
			t.area_hidden = false;
			if (t.area) {
				t.area.parentNode.removeChild(t.area);
				t.area = null;
			}
		},false);
	};
	
	t.addUploadPopup = function(icon, title, callback_with_popup) {
		if (!icon) icon = "/static/storage/upload.png";
		if (!title) title = "Uploading files";
		require("popup_window.js");
		var popup = null;
		var prev_oncancelled = t.oncancelled;
		t.oncancelled = function() {
			if (popup) popup.close();
			if (prev_oncancelled) prev_oncancelled();
		};
		var prev_onstart = t.onstart;
		var progress_table = document.createElement("TABLE");
		var progress_bars = [];
		t.onstart = function(files, callback) {
			require("popup_window.js",function() {
				popup = new popup_window(title, icon, progress_table);
				popup.show();
				popup.disableClose();
				if (prev_onstart) {
					var tr = document.createElement("TR");
					progress_table.appendChild(tr);
					var td = document.createElement("TD");
					td.innerHTML = "Initializing upload...";
					tr.appendChild(td);
					layout.invalidate(progress_table);
					progress_bars = [];
					prev_onstart(files, function(cancel) {
						progress_table.removeChild(tr);
						callback(cancel);
					});
				} else
					callback();
			});			
		};
		var prev_onstartfile = t.onstartfile;
		t.onstartfile = function(file) {
			var tr = document.createElement("TR");
			progress_table.appendChild(tr);
			var td = document.createElement("TD");
			td.innerHTML = file.name;
			tr.appendChild(td);
			td = document.createElement("TD"); tr.appendChild(td);
			var progress = document.createElement("DIV");
			progress.style.width="200px";
			progress.style.height="15px";
			progress.style.border="1px solid black";
			progress.style.position="relative";
			var progress_bar = document.createElement("DIV");
			progress_bar.style.position="absolute";
			progress_bar.style.top="0px";
			progress_bar.style.left="0px";
			progress_bar.style.width="0px";
			progress_bar.style.height="15px";
			progress_bar.style.backgroundColor="#A0A0FF";
			progress.appendChild(progress_bar);
			td.appendChild(progress);
			progress_bars.push({file:file,bar:progress_bar,td:td});
			layout.invalidate(progress_table);
			if (prev_onstartfile) prev_onstartfile(file);
		};
		var prev_onprogressfile = t.onprogressfile;
		t.onprogressfile = function(file, uploaded, total) {
			var progress_bar = null;
			for (var i = 0; i < progress_bars.length; ++i)
				if (progress_bars[i].file == file) { progress_bar = progress_bars[i].bar; break; }
			if (progress_bar)
				progress_bar.style.width = Math.round(uploaded*200/total)+"px";
			if (prev_onprogressfile) prev_onprogressfile(file, uploaded, total);
		};
		var prev_ondonefile = t.ondonefile;
		t.ondonefile = function(file, output, errors) {
			var td = null;
			for (var i = 0; i < progress_bars.length; ++i)
				if (progress_bars[i].file == file) { td = progress_bars[i].td; break; }
			if (td) {
				if (errors.length == 0)
					td.innerHTML = "<img src='"+theme.icons_16.ok+"' style='vertical-align:bottom'/> OK";
				else
					td.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> Error";
			}
			layout.invalidate(progress_table);
			if (prev_ondonefile) prev_ondonefile(file, output, errors);
		};
		var prev_ondone = t.ondone;
		t.ondone = function(outputs) {
			popup.enableClose();
			if (callback_with_popup)
				callback_with_popup(popup);
			else
				popup.close();
			if (prev_ondone) prev_ondone(outputs);
		};
	};
	
	t.FileDragHover = function(e) {
		e.stopPropagation();
		e.preventDefault();
		if (t.area) {
			if (e.type == "dragover") {
				t.area.style.color = "#000000";
				t.area.style.border = "2px solid black";
			} else {
				t.area.style.color = "#505050";
				t.area.style.border = "2px dashed #505050";
			}
		}
		return false;
	};
	t.FileSelectHandler = function(e) {
		// cancel event and hover styling
		t.FileDragHover(e);
		// fetch FileList object
		var files = e.target.files || e.dataTransfer.files;
		if (files.length == 0) return;
		if (t.area_hidden) {
			t.area_hidden = false;
			if (t.area) {
				t.area.parentNode.removeChild(t.area);
				t.area = null;
			}
		}
		if (t.onstart)
			t.onstart(files, function(cancel) {
				if (cancel) {
					if (t.oncancelled) t.oncancelled();
					return;
				}
				t.sendFiles(files); 
			});
		else
			t.sendFiles(files);
	};
	
	t.sendFiles = function(files) {
		var received = [];
		var send = function(f,ondone) {
			if (t.onstartfile) t.onstartfile(f);
			t.uploadFile(f,function(xhr,file){
				var errors = [];
				var output = null;
				if (xhr.status != 200)
					errors.push("Error returned by the server: "+xhr.status+" "+xhr.statusText);
				else if (xhr.getResponseHeader("Content-Type").startsWith("application/json")) {
					try {
						var json = eval("("+xhr.responseText+")");
						if (json.errors)
							for (var j = 0; j < json.errors.length; ++j)
								errors.push(json.errors[j]);
						if (json.result) output = json.result;
					} catch (e) {
						errors.push("Invalid response: "+e+"<br/>"+xhr.responseText);
					}
				} else
					output = xhr.responseText;
				for (var j = 0; j < errors.length; ++j)
					window.top.status_manager.add_status(new window.top.StatusMessageError(null, errors[j], 10000));
				received.push(output);
				if (t.ondonefile) t.ondonefile(file, output, errors);
				ondone();
			});
		};
		var parallel = async ? 5 : 1;
		var uploading = 0;
		var todo = [];
		var next = function() {
			if (todo.length == 0) return;
			var f = todo[0];
			todo.splice(0,1);
			uploading++;
			send(f,function() {
				uploading--;
				if (todo.length == 0 && uploading == 0) {
					if (t.ondone) t.ondone(received);
					return;
				}
				setTimeout(next, 10);
			});
		};
		for (var i = 0; i < files.length; ++i) todo.push(files[i]);
		for (var i = 0; i < parallel && i < files.length; i++)
			setTimeout(next, 1+i*15);
	};
	
	t.uploadFile = function(file,ondone) {
		var xhr = new XMLHttpRequest();
		xhr.open("POST", target, true);
		xhr.setRequestHeader("X_FILENAME", file.name);
		xhr.setRequestHeader("X_FILETYPE", file.type);
		xhr.setRequestHeader("X_FILESIZE", file.size);
		xhr.upload.addEventListener("progress", function(e) {
			if (t.onprogressfile) t.onprogressfile(file, e.loaded, e.total);
		}, false);
		xhr.onreadystatechange = function(e) {
			if (xhr.readyState == 4) {
				if (ondone) ondone(xhr, file);
			}
		};
		xhr.send(file);
	};
	
}