if (!window.top.pndocuments) {
window.top.pndocuments = {
	_possible_ports: [127,128,129,130,131,132,133,134,270,271,272,273,274,275,466,467,468,469,470],
	_opener_latest_version: "0.0.1",
	connect: function(listener) {
		if (window.top.pndocuments._connected_port > 0) {
			if (listener) listener(window.top.pndocuments._connected_port);
			return;
		}
		var head = window.top.document.getElementsByTagName("HEAD")[0];
		var scripts = [];
		for (var i = 0; i < window.top.pndocuments._possible_ports.length; ++i) {
			var s = window.top.document.createElement("SCRIPT");
			s.type = "text/javascript";
			s._port = window.top.pndocuments._possible_ports[i];
			s.onload = function() {
				window.top.pndocuments._connected_port = this._port;
				for (var j = 0; j < scripts.length; ++j)
					if (scripts[j]._port != this._port) head.removeChild(scripts[j]);
				window.top.pndocuments.opener = new window.top.PNDocumentOpener(this._port, window.top.php_server, window.top.php_server_port, window.top.php_session_cookie_name, window.top.php_session_id, window.top.pn_version);
				this._loaded = true;
				if (listener) listener(this._port);
			};
			s.onerror = function() {
				if (this.parentNode == head)
					head.removeChild(this);
				scripts.remove(this);
				if (scripts.length == 0) {
					if (listener) listener(-1);
				}
			};
			s.onreadystatechange = function() { 
				if (this.readyState == 'loaded') { 
					window.top.pndocuments._connected_port = this._port;
					for (var j = 0; j < scripts.length; ++j)
						if (scripts[j]._port != this._port) head.removeChild(scripts[j]);
					this._loaded = true; 
					this.onreadystatechange = null; 
					if (listener) listener(this._port);
				} 
			};
			scripts.push(s);
			head.appendChild(s);
			s.src = "http://localhost:"+window.top.pndocuments._possible_ports[i]+"/javascript";
		}
	},
	connected_port: -1,
	opener: null,
	attachFiles: function(click_event, table, sub_model, key, type, docs_table) {
		var upl = new upload('/dynamic/documents/service/add_files?table='+table+(sub_model ? "&sub_model="+sub_model : "")+"&key="+encodeURIComponent(key)+"&type="+type, true, true);
		upl.ondonefile = function(file, output, errors) {
			if (output && output.length > 0)
				for (var i = 0; i < output.length; ++i)
					window.top.pndocuments.addDocumentToTable(docs_table, output[i].id, output[i].type, output[i].name, [output[i].version],true,true);
		};
		upl.addUploadPopup();
		upl.openDialog(click_event);
	},
	addDocumentToTable: function(table, doc_id, doc_type, doc_name, doc_versions, can_write, can_remove) {
		var tr = document.createElement("TR");
		table.appendChild(tr);
		var td;
		tr.appendChild(td = document.createElement("TD"));
		var icon = "file.png";
		if (doc_type.startsWith("image/"))
			icon = "picture.png";
		else if (doc_type.startsWith("audio/"))
			icon = "audio.png";
		else if (doc_type.startsWith("video/"))
			icon = "video.png";
		else if (doc_type.startsWith("text/"))
			icon = "text.png";
		else {
			var ext = doc_name;
			var i = ext.lastIndexOf('.');
			if (i > 0) ext = ext.substring(i+1);
			ext = ext.toLowerCase();
			if (ext == "doc" || ext == "docx" || ext == "dot" || ext == "dotx")
				icon = "word.png";
			else if (ext == "xls" || ext == "xlsx")
				icon = "excel.png";
			else if (ext == "csv")
				icon = "csv.gif";
			else if (ext == "ppt" || ext == "pptx" || ext == "pot" || ext == "potx")
				icon = "powerpoint.png";
			else if (ext == "jpg" || ext == "jpeg" || ext == "bmp" || ext == "gif" || ext == "png" || ext == "dcx" || ext == "dib" || ext == "pcx" || ext == "tif" || ext == "tiff" || ext == "xpg" || ext == "xbm" || ext == "xif")
				icon = "picture.png";
			else if (ext == "au" || ext == "ra" || ext == "ram" || ext == "wav" || ext == "mid" || ext == "mp3" || ext == "rmi")
				icon = "audio.png";
			else if (ext == "avi" || ext == "mov" || ext == "mp4" || ext == "asf" || ext == "idf" || ext == "asd")
				icon = "video.png";
			else if (ext == "7z")
				icon = "7z.png";
			else if (ext == "zip")
				icon = "zip.png";
			else if (ext == "rar")
				icon = "rar.gif";
			else if (ext == "pdf")
				icon = "pdf.png";
			else if (ext == "bat")
				icon = "bat.gif";
			else if (ext == "exe")
				icon = "exe.gif";
		}
		td.innerHTML = "<img src='/static/documents/files/"+icon+"'/>";
		tr.appendChild(td = document.createElement("TD"));
		var link = document.createElement("A");
		link.className = "black_link";
		link.href = "#";
		link.appendChild(document.createTextNode(doc_name));
		link.onclick = function() {
			require("context_menu.js",function() {
				var menu = new context_menu();
				menu.addIconItem(theme.icons_16.see, "Open file (read-only)", function() {
					window.top.pndocuments.open(doc_id, doc_versions[0].id, doc_versions[0].file,doc_versions[0].revision,doc_name,true);
				});
				menu.addIconItem("/static/storage/download.png", "Download file", function() {
					window.top.pndocuments.download(doc_versions[0].file,doc_versions[0].revision,doc_name);
				});
				if (can_write)
					menu.addIconItem(theme.icons_16.edit, "Edit file", function() {
						window.top.pndocuments.open(doc_id, doc_versions[0].id, doc_versions[0].file,doc_versions[0].revision,doc_name,false);
					});
				if (can_remove)
					menu.addIconItem(theme.icons_16.remove, "Remove file", function() {
						// TODO
					});
				// TODO history/versions
				menu.showBelowElement(link);
			});
		};
		td.appendChild(link);
		var elem = table.ownerDocument.getElementById(table.getAttribute("elem_id"));
		if (elem.resize)
			elem.resize();
	},
	download: function(storage_id, storage_revision, filename) {
		var form = document.createElement("FORM");
		var input;
		form.action = "/dynamic/storage/service/get?id="+storage_id+(storage_revision?"&revision="+storage_revision:"");
		form.method = 'POST';
		form.appendChild(input = document.createElement("INPUT"));
		input.type = 'hidden';
		input.name = 'download';
		input.value = filename;
		if (window.top._download_form) window.top.document.body.removeChild(window.top._download_form);
		if (window.top._download_frame) window.top.document.body.removeChild(window.top._download_frame);
		var frame = window.top.document.createElement("IFRAME");
		frame.style.position = "absolute";
		frame.style.top = "-10000px";
		frame.style.visibility = "hidden";
		frame.name = "_download";
		form.target = "_download";
		window.top._download_frame = frame;
		window.top._download_form = form;
		window.top.document.body.appendChild(frame);
		window.top.document.body.appendChild(form);
		form.submit();
		window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_INFO,"The download will start soon...",[{action:"close"}],5000));
	},
	open: function(document_id, version_id, storage_id, storage_revision, filename, readonly) {
		var locker = lock_screen(null, "Opening document...");
		window.top.pndocuments.connect(function(port) {
			if (port == -1) {
				unlock_screen(locker);
				error_dialog("You need the software <b>PN Document Opener</b> to open or edit files.<br/>You can download it <a href='/dynamic/documents/page/download_opener' target='_blank'>here</a><br/>If you already installed it, it is not running, so please launch it.");
				return;
			}
			window.top.pndocuments.opener.openDocument(document_id, version_id, storage_id, storage_revision, filename, readonly);
			unlock_screen(locker);
		});
	}
};
window.top.require("upload.js");
}