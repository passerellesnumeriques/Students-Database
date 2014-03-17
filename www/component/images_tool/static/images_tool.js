function images_tool() {
	var t=this;

	this._use_popup = false;
	this._use_popup_on_top = false;
	this._use_upload = false;
	this._face_detection = false;
	this._tools = [];
	this._pictures = [];
	
	this.usePopup = function(on_top) {
		this.container = document.createElement("DIV");
		this.container.style.overflow = "auto";
		this.container.style.width = "100%";
		this.container.style.height = "100%";
		this._use_popup = true;
		this._use_popup_on_top = on_top;
		if (on_top) window.top.require("popup_window.js"); else require("popup_window.js");
	};
	this.useContainer = function(container) {
		if (typeof container == 'string') container = document.getElementById(container);
		this.container = container;
	};
	
	this.useUpload = function() {
		require(["upload.js","progress_bar.js"]);
		theme.css("progress_bar.css");
		window.top.theme.css("progress_bar.css");
		this._use_upload = true;
	};
	
	this.useFaceDetection = function() {
		require("images_tool_face_detection.js");
		this._face_detection = true;
		this.addTool("crop");
	};
	
	this.addTool = function(id, onready) {
		for (var i = 0; i < this._tools.length; ++i)
			if (this._tools[i].id == id) {
				if (onready) this._tools[i].onready.add_listener(onready);
				return;
			}
		var tool = {id:id,tool:null,onready:new Custom_Event()};
		if (onready) tool.onready.add_listener(onready);
		this._tools.push(tool);
		require("images_tool_"+id+".js", function() {
			tool.tool = new window["images_tool_"+id]();
			tool.onready.fire();
		});
		require("section.js");
	};
	
	this.setToolValue = function(tool_id, pic, value, editable) {
		for (var i = 0; i < this._tools.length; ++i)
			if (this._tools[i].id == tool_id) {
				this._tools[i].tool.setValue(pic, value, editable);
				return;
			}
	};
	
	
	this.init = function(onready) {
		var ready_count = 0;
		if (this._use_popup) ready_count++;
		if (this._use_upload) ready_count++;
		if (this._face_detection) ready_count++;
		if (this._tools.length > 0) ready_count++;
		var ready = function() {
			if (--ready_count == 0)
				onready(t);
		};
		if (this._use_popup_on_top)
			window.top.theme.css("section.css");
		else
			theme.css("section.css");
		for (var i = 0; i < this._tools.length; ++i) {
			var tool = this._tools[i];
			if (tool.tool) continue;
			ready_count++;
			tool.onready.add_listener(ready);
		}
		if (this._use_popup) {
			if (this._use_popup_on_top)
				window.top.require("popup_window.js", function() {
					t.popup = new window.top.popup_window("Pictures", "/static/images_tool/people_picture.png", t.container);
					ready();
				});
			else
				top.require("popup_window.js", function() {
					t.popup = new popup_window("Pictures", "/static/images_tool/people_picture.png", t.container);
					ready();
				});
		}
		if (this._use_upload)
			require(["upload.js","progress_bar.js"], ready);

		if (this._face_detection) {
			require("images_tool_face_detection.js", function() {
				images_tool_face_detection_init(function() {
					ready();
				});
			});
		}
		if (this._tools.length > 0)
			require("section.js", ready);
	};
	
	this.reset = function() {
		this._pictures = [];
		this.container.innerHTML = "";
		this.table = null;
	};
	
	this.launchUpload = function(click_event, multiple) {
		t.upl = new upload("/dynamic/storage/service/store_temp_image?max_size=800", multiple, true);
		var table, tr, td;
		table = document.createElement("TABLE");
		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TH"));
		td.innerHTML = "File";
		tr.appendChild(td = document.createElement("TH"));
		td.innerHTML = "Upload file";
		tr.appendChild(td = document.createElement("TH"));
		td.innerHTML = "Loading image";
		if (t._faceDetection) {
			tr.appendChild(td = document.createElement("TH"));
			td.innerHTML = "Analyzing image";
		}
		table.appendChild(tr = document.createElement("TR"));
		var td_nb_files;
		tr.appendChild(td_nb_files = document.createElement("TD"));
		tr.appendChild(td = document.createElement("TD"));
		var global_upload_progress = new progress_bar(200, 20);
		td.appendChild(global_upload_progress.element);
		tr.appendChild(td = document.createElement("TD"));
		var global_loading_progress = new progress_bar(200, 20);
		td.appendChild(global_loading_progress.element);
		var global_analyzing_progress = null;
		if (t._face_detection) {
			global_analyzing_progress = new progress_bar(200, 20);
			tr.appendChild(td = document.createElement("TD"));
			td.appendChild(global_analyzing_progress.element);
		}
		var todo = [];
		t.upl.onstart = function(files, callback) {
			t.container.appendChild(table);
			if (t.popup) t.popup.showPercent(95, 95);
			todo = [];
			var total_size = 0;
			for (var i = 0; i < files.length; ++i) {
				var f = {};
				f.file = files[i];
				table.appendChild(tr = document.createElement("TR"));
				tr.appendChild(td = document.createElement("TD"));
				td.style.fontSize = "9pt";
				td.appendChild(document.createTextNode(files[i].name));
				tr.appendChild(td = document.createElement("TD"));
				td.style.fontSize = "9pt";
				f.upload_progress = new progress_bar(200, 14);
				td.appendChild(f.upload_progress.element);
				tr.appendChild(f.td_loading = document.createElement("TD"));
				f.td_loading.style.fontSize = "9pt";
				if (t._face_detection) {
					tr.appendChild(f.td_analyzing = document.createElement("TD"));
					f.td_analyzing.style.fontSize = "9pt";
				}
				todo.push(f);
				total_size += files[i].size;
			}
			td_nb_files.innerHTML = files.length+" file(s)";
			global_upload_progress.setTotal(total_size);
			global_loading_progress.setTotal(files.length);
			if (t._face_detection)
				global_analyzing_progress.setTotal(files.length);
			callback();
		};
		t.upl.onstartfile = function(file) {
			var o = null;
			for (var i = 0; i < todo.length; ++i)
				if (todo[i].file == file) { o = todo[i]; break; }
			if (!o) return;
			o.last_update_global = 0;
			o.upload_progress.setTotal(file.size);
		};
		t.upl.onprogressfile = function (file, uploaded, total) {
			var o = null;
			for (var i = 0; i < todo.length; ++i)
				if (todo[i].file == file) { o = todo[i]; break; }
			if (!o) return;
			global_upload_progress.addAmount(uploaded-o.last_update_global);
			o.last_update_global = uploaded;
			o.upload_progress.setPosition(uploaded);
		};
		var ready = function() {
			t.container.removeChild(table);
			for (var i = 0; i < todo.length; ++i) {
				var pic = t.addPicture();
				pic.setName(todo[i].file.name);
				if (todo[i].error) {
					pic.setError();
					continue;
				}
				pic.setImage(todo[i].image);
				if (todo[i].face)
					t.setToolValue("crop", pic, {rect:{x:todo[i].face.x, y:todo[i].face.y, width:todo[i].face.width, height:todo[i].face.height}});
			}
		};
		var face_detection = function(o) {
			if (o.error) {
				global_analyzing_progress.addAmount(1);
				if (global_analyzing_progress.position == global_analyzing_progress.total) {
					global_analyzing_progress.done();
					ready();
				}
				return;
			}
			o.td_analyzing.innerHTML = "Analyzing...";
			setTimeout(function() {
				images_tool_face_detection(o.image, function(nb, face) {
					o.nb_faces = nb;
					o.face = face;
					o.td_analyzing.innerHTML = nb+" face(s) detected.";
					global_analyzing_progress.addAmount(1);
					if (global_analyzing_progress.position == global_analyzing_progress.total) {
						global_analyzing_progress.done();
						ready();
					}
				});
			},5);
		};
		t.upl.ondonefile = function(file, output, errors) {
			var o = null;
			for (var i = 0; i < todo.length; ++i)
				if (todo[i].file == file) { o = todo[i]; break; }
			if (!o) return;
			if (!output) {
				o.error = true;
				o.upload_progress.error();
				global_loading_progress.addAmount(1);
				o.td_loading.innerHTML = "";
				if (global_loading_progress.position == global_loading_progress.total) {
					global_loading_progress.done();
					if (!t._face_detection)
						ready();
				}
				return;
			}
			o.upload_progress.done();
			o.image = new Image();
			o.td_loading.innerHTML = "Loading...";
			o.image.onload = function() {
				o.td_loading.innerHTML = "Loaded.";
				global_loading_progress.addAmount(1);
				if (global_loading_progress.position == global_loading_progress.total) {
					global_loading_progress.done();
					if (!t._face_detection)
						ready();
				}
				if (t._face_detection)
					face_detection(o);
			};
			o.image.src = "/dynamic/storage/service/get_temp?id="+output.id;
		};
		t.upl.ondone = function() {
			global_upload_progress.done();
		};
		t.upl.openDialog(click_event, "image/*");
	};
	
	
	this.addPicture = function() {
		var picture = new images_tool_picture();
		for (var i = 0; i < this._tools.length; ++i)
			picture.registerTool(this._tools[i].tool);
		if (!t.table) {
			t.container.appendChild(t.table = document.createElement("TABLE"));
			t.table.className = "all_borders";
			var tr, td;
			t.table.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TH"));
			td.innerHTML = "File";
			tr.appendChild(td = document.createElement("TH"));
			td.innerHTML = "Original Image";
			tr.appendChild(td = document.createElement("TH"));
			td.innerHTML = "Modifications";
			tr.appendChild(td = document.createElement("TH"));
			td.innerHTML = "Result Image";
		}
		t.table.appendChild(picture.tr);
		t._pictures.push(picture);
		layout.invalidate(t.container);
		return picture;
	};
}

function ImageTool() {
}
ImageTool.prototype = {
	getIcon: function() { return null; },
	getTitle: function() { return ""; },
	setValue: function(pic, value, editable) {},
	update: function(pic, canvas) {},
	createContent: function(pic) {}
};

function images_tool_picture() {
	var t=this;
	this.tr = document.createElement("TR");
	this.tr.appendChild(this.td_name = document.createElement("TD"));
	this.tr.appendChild(this.td_original = document.createElement("TD"));
	this.tr.appendChild(this.td_tools = document.createElement("TD"));
	this.tr.appendChild(this.td_result = document.createElement("TD"));
	
	this.td_original.style.position = "relative";
	
	this.setName = function(name) {
		this.name = name;
		this.td_name.appendChild(document.createTextNode(name));
	};
	this.setError = function() {
		this.tr.removeChild(this.td_tools);
		this.tr.removeChild(this.td_result);
		this.td_original.colSpan = 3;
		this.td_original.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> Error";
	};
	
	this.tools = [];
	this.registerTool = function(tool) {
		this.tools.push(tool);
		var content = tool.createContent(t);
		var sec = new section(tool.getIcon(), tool.getTitle(), content, false, false, 'soft');
		sec.element.style.display = "inline-block";
		sec.element.style.margin = "5px";
		t.td_tools.appendChild(sec.element);
	};
	
	this.setImage = function(image) {
		this.original = image;
		var max_width = 200, max_height = 200;
		var w = image.naturalWidth;
		var h = image.naturalHeight;
		if (w > max_width) {
			h = Math.floor(h*(max_width/w));
			w = max_width;
		}
		if (h > max_height) {
			w = Math.floor(w*(max_height/h));
			h = max_height;
		}
		this.original_canvas = document.createElement("CANVAS");
		this.original_canvas.width = w;
		this.original_canvas.height = h;
		this.original_canvas.style.width = w+'px';
		this.original_canvas.style.height = h+'px';
		this.original_canvas.style.position = "absolute";
		this.original_canvas.style.top = "0px";
		this.original_canvas.style.left = "0px";
		this.td_original.appendChild(this.original_canvas);
		this.td_original.style.width = w+'px';
		this.td_original.style.height = h+'px';
		
		var ctx = this.original_canvas.getContext("2d");
		ctx.drawImage(this.original, 0, 0, w, h);
		
		this.result_canvas = document.createElement("CANVAS");
		this.result_canvas.width = 200;
		this.result_canvas.height = 200;
		this.result_canvas.style.width = "200px";
		this.result_canvas.style.height = "200px";
		this.td_result.appendChild(this.result_canvas);

		ctx = this.result_canvas.getContext("2d");
		ctx.drawImage(this.original, Math.floor((200-w))/2, 0, w, h);
		
		this.update();
	};
	
	this.update = function() {
		if (this.tools.length > 0) {
			var canvas = document.createElement("CANVAS");
			canvas.width = this.original.naturalWidth;
			canvas.height = this.original.naturalHeight;
			var ctx = canvas.getContext("2d");
			ctx.drawImage(this.original, 0, 0, canvas.width, canvas.height);
			for (var i = 0; i < this.tools.length; ++i)
				this.tools[i].update(this, canvas);
			
			var max_width = 200, max_height = 200;
			var w = canvas.width;
			var h = canvas.height;
			if (w > max_width) {
				h = Math.floor(h*(max_width/w));
				w = max_width;
			}
			if (h > max_height) {
				w = Math.floor(w*(max_height/h));
				h = max_height;
			}
			ctx = this.result_canvas.getContext("2d");
			ctx.clearRect(0,0,200,200);
			ctx.drawImage(canvas, Math.floor((200-w))/2, 0, w, h);
		}
	};
}