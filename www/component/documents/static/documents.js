if (!window.top.pndocuments) {
window.top.pndocuments = {
	_possible_ports: [127,128,129,130,131,132,133,134,270,271,272,273,274,275,466,467,468,469,470],
	_opener_latest_version: "0.0.4",
	connect: function(listener) {
		if (window.top.pndocuments._connected_port > 0) {
			if (listener) listener(window.top.pndocuments._connected_port);
			return;
		}
		var head = window.top.document.getElementsByTagName("HEAD")[0];
		var scripts = [];
		var onload = function() {
			window.top.pndocuments._connected_port = this._port;
			window.top.pndocuments._opener_script = this;
			for (var j = 0; j < scripts.length; ++j)
				if (scripts[j]._port != this._port) head.removeChild(scripts[j]);
			window.top.pndocuments.opener = new window.top.PNDocumentOpener(this._port, window.top.php_server, window.top.php_server_port, window.top.php_session_cookie_name, window.top.php_session_id, window.top.pn_version);
			if (window.top.pndocuments.opener.version != window.top.pndocuments._opener_latest_version) {
				window.top.pndocuments._connected_port = -1;
				if (listener) listener(-1);
				window.top.pndocuments.update_status = window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_INFO,"Your version of PN Document Opener is outdated.<br/>Please <a href='#' onclick='window.top.pndocuments.updateOpener();'>update it</a>.",[{action:"close"}]));
				return;
			}
			this._loaded = true;
			if (listener) listener(this._port);
		};
		var onerror = function() {
			if (this.parentNode == head)
				head.removeChild(this);
			scripts.remove(this);
			if (scripts.length == 0) {
				if (listener) listener(-1);
			}
		};
		var onreadystatechanged = function() { 
			if (this.readyState == 'loaded') { 
				this.onreadystatechange = null; 
				window.top.pndocuments._connected_port = this._port;
				window.top.pndocuments._opener_script = this;
				for (var j = 0; j < scripts.length; ++j)
					if (scripts[j]._port != this._port) head.removeChild(scripts[j]);
				window.top.pndocuments.opener = new window.top.PNDocumentOpener(this._port, window.top.php_server, window.top.php_server_port, window.top.php_session_cookie_name, window.top.php_session_id, window.top.pn_version);
				if (window.top.pndocuments.opener.version != window.top.pndocuments._opener_latest_version) {
					window.top.pndocuments._connected_port = -1;
					if (listener) listener(-1);
					window.top.pndocuments.update_status = window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_INFO,"Your version of PN Document Opener is outdated.<br/>Please <a href='#' onclick='window.top.pndocuments.updateOpener();'>update it</a>.",[{action:"close"}]));
					return;
				}
				this._loaded = true; 
				if (listener) listener(this._port);
			} 
		};
		// first try with first port
		var prev_listener = listener;
		listener = function(port) {
			listener = prev_listener;
			if (port == -1) {
				// try with other ports
				for (var i = 1; i < window.top.pndocuments._possible_ports.length; ++i) {
					var s = window.top.document.createElement("SCRIPT");
					s.type = "text/javascript";
					s._port = window.top.pndocuments._possible_ports[i];
					s.onload = onload;
					s.onerror = onerror;
					s.onreadystatechange = onreadystatechanged;
					scripts.push(s);
					head.appendChild(s);
					s.src = "http://localhost:"+window.top.pndocuments._possible_ports[i]+"/javascript";
				}
				return;
			}
			if (listener) listener(port);
		};
		var s = window.top.document.createElement("SCRIPT");
		s.type = "text/javascript";
		s._port = window.top.pndocuments._possible_ports[0];
		s.onload = onload;
		s.onerror = onerror;
		s.onreadystatechange = onreadystatechanged;
		scripts.push(s);
		head.appendChild(s);
		s.src = "http://localhost:"+window.top.pndocuments._possible_ports[0]+"/javascript";
	},
	connected_port: -1,
	opener: null,
	_opener_script: null,
	updateOpener: function() {
		window.top.status_manager.remove_status(window.top.pndocuments.update_status);
		window.top.pndocuments.opener.update(function() {
			window.top.pndocuments.opener = null;
			if (window.top.pndocuments._opener_script != null)
				window.top.pndocuments._opener_script.parentNode.removeChild(window.top.pndocuments._opener_script);
			var trial = 0;
			var check = function() {
				window.top.pndocuments.connect(function(port) {
					if (port == -1) {
						if (++trial < 100)
							setTimeout(check, 2000);
						return;
					}
					window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_INFO,"PN Document Opener successfully updated !",[{action:"close"}],10000));
				});
			};
			setTimeout(check,2000);
		});
	},
	attachFiles: function(click_event, table, sub_model, key, type, onfileadded) {
		var upl = new upload('/dynamic/documents/service/add_files?table='+table+(sub_model ? "&sub_model="+sub_model : "")+"&key="+encodeURIComponent(key)+"&type="+type, true, true);
		upl.ondonefile = function(file, output, errors) {
			if (output && output.length > 0)
				for (var i = 0; i < output.length; ++i) {
					output[i].versions[0].people = window.top.my_people;
					onfileadded(output[i]);
				}
		};
		upl.addUploadPopup();
		upl.openDialog(click_event);
	},
	uploadNewVersion: function(click_event, doc, ondone) {
		var upl = new upload("/dynamic/documents/service/save_file?id="+doc.id, false, true);
		upl.onstart = function(files, callback) {
			var lock_and_start = function() {
				service.json("documents","lock?id="+doc.id,null,function(res) {
					if (!res) { callback(true); return; }
					if (typeof res.locked != 'undefined') {
						error_dialog("This file is currently edited by "+res.locked+".");
						callback(true);
						return;
					}
					callback(false);
				});
			};
			if (files[0].name == doc.name) { lock_and_start(); return; }
			confirm_dialog("The file <i>"+files[0].name+"</i> does not match with current name <i>"+doc.name+"</i>. Are you sure this is the correct file ?", function(yes){
				if (!yes) { callback(true); return; }
				lock_and_start();
			});
		};
		upl.ondonefile = function(file, output, errors) {
			if (output != "OK") {
				window.top.status_manager.add_status(new window.top.StatusMessageError(null, output, 10000));
				return;
			}
			service.json("documents","unlock?id="+doc.id,null,function(res){
				if (ondone) ondone();
			});
		};
		upl.addUploadPopup();
		upl.openDialog(click_event);
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
				error_dialog("You need the software <b>PN Document Opener</b> to open or edit files.<br/>You can download this software <a href='/dynamic/documents/service/download_document_opener' target='_blank'>here</a><br/><br/>If you already installed it, please launch it.<br/><br/>Without this software, you can still download and upload files.");
				return;
			}
			window.top.pndocuments.opener.openDocument(document_id, version_id, storage_id, storage_revision, filename, readonly);
			unlock_screen(locker);
		});
	}
};
window.top.require("upload.js");
}

function AttachedDocuments(container, table, sub_model, key, type, can_add_remove, can_edit, title_size, orientation) {
	if (typeof container == 'string') container = document.getElementById(container);
	this.table = table;
	this.sub_model = sub_model;
	this.key = key;
	this.type = type;
	this.can_add_remove = can_add_remove;
	this.can_edit = can_edit;
	this._init(container);
	switch (title_size) {
	case "small":
		this.title_icon.src = "/static/documents/documents_16.png";
		this.title_text.style.fontSize = "10pt";
		this.title_div.style.padding = "2px";
		break;
	case "medium":
		this.title_icon.src = "/static/documents/documents_24.png";
		this.title_text.style.fontSize = "14pt";
		this.title_div.style.padding = "5px";
		break;
	case "large":
		this.title_icon.src = "/static/documents/documents_32.png";
		this.title_text.style.fontSize = "16pt";
		this.title_div.style.padding = "5px";
		break;
	}
	switch (orientation) {
	case "top":
		setBorderRadius(this.title_div,5,5,5,5,0,0,0,0);
		break;
	case "bottom":
		setBorderRadius(this.title_div,0,0,0,0,5,5,5,5);
		break;
	case "full":
		setBorderRadius(this.title_div,5,5,5,5,0,0,0,0);
		break;
	}
	container.appendChild(this.title_div);
	if (orientation != "full") {
		addClassName(this.title_div, "overview");
		this.docs_div.style.display = "none";
		document.body.appendChild(this.docs_div);
		var t=this;
		this.title_div.onclick = function() {
			if (this._anim1) animation.stop(this._anim1);
			if (this._anim2) animation.stop(this._anim2);
			this._anim1 = null;
			this._anim2 = null;
			if (!this._opened) {
				t.docs_div.style.width = "";
				t.docs_div.style.height = "";
				t.docs_div.style.position = "absolute";
				t.docs_div.style.display = "block";
				t.docs_div.style.overflow = "";
				if (getWidth(t.docs_div) < getWidth(this)) setWidth(t.docs_div, getWidth(this));
				var h = t.docs_div.offsetHeight;
				t.docs_div.style.height = "0px";
				t.docs_div.style.overflow = "hidden";
				t.docs_div.style.top = (absoluteTop(this)+this.offsetHeight-1)+"px";
				t.docs_div.style.left = (absoluteLeft(this)+this.offsetWidth-getWidth(t.docs_div))+"px";
				this._anim1 = animation.create(t.docs_div,0,h,500,function(value,element){
					element.style.height = Math.floor(value)+"px";
				});
				this._anim2 = animation.fadeIn(t.docs_div,500);
				this._opened = true;
			} else {
				this._anim1 = animation.create(t.docs_div,getHeight(t.docs_div),0,500,function(value,element){
					element.style.height = Math.floor(value)+"px";
				});
				this._anim2 = animation.fadeOut(t.docs_div,500);
				this._opened = false;
			}
		};
		this._resizeDocList = function() {
			if (!this.title_div._opened) return;
			this.docs_div.style.width = "";
			this.docs_div.style.height = "";
			if (getWidth(this.docs_div) < getWidth(this.title_div)) setWidth(this.docs_div, getWidth(this.title_div));
			this.docs_div.style.top = (absoluteTop(this.title_div)+this.title_div.offsetHeight-1)+"px";
			this.docs_div.style.left = (absoluteLeft(this.title_div)+this.title_div.offsetWidth-getWidth(this.docs_div))+"px";
		};
	} else {
		container.appendChild(this.docs_div);
	}
}
AttachedDocuments.prototype = {
	table: null, sub_model: null, key: null, type: null,
	can_add_remove: false, can_edit: false,
	title_div: null, title_icon: null, title_text: null,
	docs_div: null,
	docs_table: null, docs_tbody: null,
	documents: [],
	addDocument: function(doc) {
		this.documents.push(doc);
		this.title_text.innerHTML = (this.documents.length)+" document"+(this.documents.length>1?"s":"");
		var tr,td,tr2;
		this.docs_tbody.appendChild(tr = document.createElement("TR"));
		this.docs_tbody.appendChild(tr2 = document.createElement("TR"));
		tr._doc = doc;
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "<img src='/static/documents/files/"+this.getDocumentIcon(doc.versions[0].type,doc.name)+"'/>";
		tr.appendChild(td = document.createElement("TD"));
		td.className = "document_name";
		var link = document.createElement("A");
		link.className = "black_link";
		link.href = "#";
		link.appendChild(document.createTextNode(doc.name));
		var t=this;
		link.onclick = function() {
			require("context_menu.js",function() {
				var menu = new context_menu();
				menu.addIconItem(theme.icons_16.see, "Open file (read-only)", function() {
					window.top.pndocuments.open(doc.id, doc.versions[0].id, doc.versions[0].storage_id,doc.versions[0].revision,doc.name,true);
				});
				if (t.can_edit)
					menu.addIconItem(theme.icons_16.edit, "Edit file", function() {
						window.top.pndocuments.open(doc.id, doc.versions[0].id, doc.versions[0].storage_id,doc.versions[0].revision,doc.name,false);
					});
				menu.addIconItem("/static/storage/download.png", "Download file", function() {
					window.top.pndocuments.download(doc.versions[0].storage_id,doc.versions[0].revision,doc.name);
				});
				if (t.can_edit)
					menu.addIconItem("/static/storage/upload.png", "Upload new version", function(ev) {
						window.top.pndocuments.uploadNewVersion(ev, doc, function() {
							t._updateDocuments(true);
						});
					});
				if (t.can_add_remove)
					menu.addIconItem(theme.icons_16.remove, "Remove file", function() {
						confirm_dialog("Are you sure to remove file "+doc.name+" ?", function(yes) {
							if (!yes) return;
							service.json("documents","remove",{id:doc.id},function(res) {
								if (res) t.removeDocument(doc);
							});
						});
					});
				// TODO history/versions
				menu.showBelowElement(link);
			});
		};
		td.appendChild(link);
		tr2.appendChild(td = document.createElement("TD"));
		tr2.appendChild(td = document.createElement("TD"));
		td.className = "document_info";
		this._fillDocInfo(doc,td);
		this._resizeDocList();
	},
	removeDocument: function(doc) {
		this.documents.remove(doc);
		for (var i = 0; i < this.docs_tbody.childNodes.length; i += 2) {
			var tr = this.docs_tbody.childNodes[i];
			if (tr._doc.id == doc.id) {
				var tr2 = this.docs_tbody.childNodes[i+1];
				this.docs_tbody.removeChild(tr);
				this.docs_tbody.removeChild(tr2);
				tr._doc = null;
			}
		}
		// update title
		if (this.documents.length == 0)
			this.title_text.innerHTML = "No document";
		else
			this.title_text.innerHTML = this.documents.length+" document"+(this.documents.length>1?"s":"");
		this._resizeDocList();
	},
	_fillDocInfo: function(doc,td) {
		td.removeAllChildren();
		if (doc.lock == null) {
			removeClassName(td, "editing");
			td.appendChild(document.createTextNode("Latest version by "+doc.versions[0].people.first_name+" "+doc.versions[0].people.last_name+" on "+new Date(doc.versions[0].time*1000).toLocaleString()));
		} else {
			addClassName(td, "editing");
			td.appendChild(document.createTextNode("Currently edited by "+doc.lock));
		}
	},
	_loadDocuments: function() {
		setOpacity(this.title_div, 0.75);
		this.title_text.innerHTML = "<span style='color:#808080;font-style:italic'>Loading...</span>";
		var t=this;
		service.json("documents","get_documents_list",{table:this.table,sub_model:this.sub_model,key:this.key,type:this.type},function(res) {
			setOpacity(t.title_div,1);
			if (!res || res.length == 0)
				t.title_text.innerHTML = "No document";
			else
				for (var i = 0; i < res.length; ++i)
					t.addDocument(res[i]);
			setTimeout(function(){t._updateDocuments();},15000);
		});
	},
	_updateDocuments: function(custom_update) {
		if (window.closing || !this.docs_table || !this.docs_table.parentNode) return;
		var t=this;
		service.json("documents","get_documents_list",{table:this.table,sub_model:this.sub_model,key:this.key,type:this.type},function(res) {
			var removed = [];
			for (var i = 0; i < t.docs_tbody.childNodes.length; i += 2) {
				var tr = t.docs_tbody.childNodes[i];
				var tr2 = t.docs_tbody.childNodes[i+1];
				var doc = null;
				for (var j = 0; j < res.length; ++j)
					if (res[j].id == tr._doc.id) { doc = res[j]; res.splice(j,1); break; }
				if (doc == null) {
					// document was removed
					removed.push(tr._doc);
					continue;
				}
				if (doc.versions[0].time != tr._doc.versions[0].time || doc.lock != tr._doc.lock) {
					// last version changed
					var td = tr2.childNodes[1];
					t._fillDocInfo(doc,td);
				}
				tr._doc.versions = doc.versions;
				tr._doc.lock = doc.lock;
			}
			// add new documents
			for (var i = 0; i < res.length; ++i)
				t.addDocument(res[i]);
			// update title
			if (t.documents.length == 0)
				t.title_text.innerHTML = "No document";
			else
				t.title_text.innerHTML = t.documents.length+" document"+(t.documents.length>1?"s":"");
			for (var i = 0; i < removed.length; ++i)
				t.removeDocument(removed[i]);
			if (!custom_update)
				setTimeout(function(){t._updateDocuments();},15000);
		});
	},
	_resizeDocList: function() {},
	_init: function() {
		theme.css("documents.css");
		this.title_div = document.createElement("DIV");
		this.title_div.className = "documents_title";
		this.title_icon = document.createElement("IMG");
		this.title_text = document.createElement("DIV");
		this.title_div.appendChild(this.title_icon);
		this.title_div.appendChild(this.title_text);
		this.docs_div = document.createElement("DIV");
		this.docs_div.className = "documents_details";
		this.docs_table = document.createElement("TABLE");
		this.docs_tbody = document.createElement("TBODY");
		this.docs_table.appendChild(this.docs_tbody);
		this.docs_table.className = "documents_table";
		this.docs_div.appendChild(this.docs_table);
		if (this.can_add_remove) {
			var button = document.createElement("BUTTON");
			button.className = "action green";
			button.innerHTML = "Add Files...";
			var t=this;
			button.onclick = function(ev) {
				window.top.pndocuments.attachFiles(ev,t.table,t.sub_model,t.key,t.type,function(doc){ t.addDocument(doc); });
			};
			this.docs_div.appendChild(button);
		}
		this._loadDocuments();
	},
	getDocumentIcon: function(doc_type, doc_name) {
		if (doc_type == null) doc_type = "";
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
		return icon;
	}
};
