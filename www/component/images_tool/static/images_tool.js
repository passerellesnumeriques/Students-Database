function images_tool() {
	var t=this;

	this.usePopup = function() {
		this.container = document.createElement("DIV");
		require("popup_window.js", function() {
			t.popup = new popup_window("Pictures", "/static/images_tool/people_picture.png", t.container);
		});
		this.container.style.overflow = "auto";
		this.container.style.width = "100%";
		this.container.style.height = "100%";
	};
	this.useContainer = function(container) {
		if (typeof container == 'string') container = document.getElementById(container);
		this.container = container;
	};
	
	this.useUpload = function(multiple) {
		require("upload.js", function() {
			t.upl = createUploadTempFile(multiple);
			t.upl.onstart = function(files, callback) {
				t.init();
				for (var i = 0; i < files.length; ++i) {
					var pic = t.addPicture();
					pic.file = files[i];
					pic.td_original.innerHTML = "Waiting: "+files[i].name;
				}
				callback();
			};
			t.upl.onstartfile = function(file) {
				var pic = null;
				for (var i = 0; i < t.pictures.length; ++i)
					if (t.pictures[i].file == file) { pic = t.pictures[i]; break; }
				if (pic) {
					pic.td_original.innerHTML = "Uploading "+file.name+"...<br/>";
					pic.progress_container = document.createElement("DIV");
					pic.progress_container.style.width = '200px';
					pic.progress_container.style.height = '20px';
					pic.progress_container.style.border = '1px solid black';
					pic.progress_container.style.backgroundColor = '#FFFFFF';
					pic.progress_container.style.display = "inline-block";
					pic.progress_container.style.overflow = 'hidden';
					setBorderRadius(pic.progress_container, 3, 3, 3, 3, 3, 3, 3, 3);
					pic.td_original.appendChild(pic.progress_container);
					pic.progress_bar = document.createElement("DIV");
					pic.progress_bar.style.height = '100%';
					pic.progress_bar.style.width = '0px';
					pic.progress_bar.style.display = 'inline-block';
					pic.progress_bar.style.backgroundColor = "#C0C0FF";
					pic.progress_container.appendChild(pic.progress_bar);
					layout.invalidate(t.container);
				}
			};
			t.upl.onprogressfile = function (file, uploaded, total) {
				var pic = null;
				for (var i = 0; i < t.pictures.length; ++i)
					if (t.pictures[i].file == file) { pic = t.pictures[i]; break; }
				if (pic) {
					pic.progress_bar.style.width =  Math.round(uploaded*200/total)+"px";
				}
			};
			t.upl.ondonefile = function(file, output, errors) {
				var pic = null;
				for (var i = 0; i < t.pictures.length; ++i)
					if (t.pictures[i].file == file) { pic = t.pictures[i]; break; }
				if (pic) {
					if (errors.length > 0)
						pic.td_original.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> Error uploading file "+pic.file.name;
					else {
						pic.td_original.innerHTML = "<img src='"+theme.icons_16.ok+"' style='vertical-align:bottom'/> "+pic.file.name+" successfully uploaded.";
						pic._storage_id = output.id;
						pic.loadImage("/dynamic/storage/service/get_temp?id="+output.id);
					}
				}
			};
		});
	};
	this.launchUpload = function(click_event) {
		this.upl.openDialog(click_event, "image/*");
	};
	
	this._faceDetection = false;
	this.useFaceDetection = function() {
		add_javascript("/static/images_tool/lib_ccv/ccv.js");
		add_javascript("/static/images_tool/lib_ccv/face.js");
		this._faceDetection = true;
	};
	
	this.init = function() {
		while (t.container.childNodes.length > 0) t.container.removeChild(t.container.childNodes[0]);
		t.container.appendChild(t.table = document.createElement("TABLE"));
		if (t.popup) t.popup.showPercent(95, 95);
	};
	this.pictures = [];
	this.addPicture = function() {
		var picture = new images_tool_picture(this);
		t.table.appendChild(picture.tr);
		this.pictures.push(picture);
		layout.invalidate(t.container);
		return picture;
	};
}

function images_tool_picture(tool) {
	var t=this;
	this.tool = tool;
	this.tr = document.createElement("TR");
	this.tr.appendChild(this.td_original = document.createElement("TD"));
	this.tr.appendChild(this.td_tools = document.createElement("TD"));
	this.tr.appendChild(this.td_current = document.createElement("TD"));
	this.loadImage = function(url,ondone) {
		t.original = document.createElement("IMG");
		t.original.style.width = "50px";
		t.original.style.height = "50px";
		t.original.src = url;
		t.td_original.appendChild(t.original);
		/*
		t.original_canvas = document.createElement("CANVAS");
		t.original_canvas.width = 10;
		t.original_canvas.height = 10;
		t.original_canvas.style.width = 10+"px";
		t.original_canvas.style.height = 10+"px";
		t.td_original.appendChild(t.original_canvas);
		this.original = document.createElement("IMG");
		this.original.style.position = "absolute";
		this.original.style.top = "0px";
		this.original.style.width = "5px";
		this.original.style.height = "5px";
		this.original.onload = function() {
			t.original._loaded = true;
			t.originalWidth = this.offsetWidth;
			t.originalHeight = this.offsetHeight;
			t._loading.innerHTML = "Image loaded.";
			if (ondone) ondone();
		};
		this.original.onerror = function() {
			t.original._loaded = true;
			document.body.removeChild(t.original);
			t.td_original.innerHTML = "Invalid image";
			t.original = null;
			if (ondone) ondone();
		};
		this.original.src = url;
		document.body.appendChild(this.original);
		*/
	};
	this.drawImage = function(ondone) {
		if (!t.original) {
			if (ondone) ondone();
			return;
		}
		t.td_original.removeChild(t._loading);
		var w = t.originalWidth;
		var h = t.originalHeight;
		if (w > h) {
			if (w > 250) {
				h = Math.floor(h*(250/w));
				w = 250;
			}
		} else {
			if (h > 250) {
				w = Math.floor(w*(250/h));
				h = 250;
			}
		}
		t.original_canvas.width = w;
		t.original_canvas.height = h;
		t.original_canvas.style.width = w+"px";
		t.original_canvas.style.height = h+"px";
		var draw_image = function() {
			var ctx = t.original_canvas.getContext("2d");
			try { ctx.drawImage(t.original, 0, 0, w, h); } catch (e) {
				// handle bug of browser https://bugzilla.mozilla.org/show_bug.cgi?id=574330
				setTimeout(draw_image, 25);
				return;
			}
			document.body.removeChild(t.original);
			if (tool._faceDetection)
				t.detectFace(ondone);
			else
				if (ondone) ondone();
		};
		setTimeout(draw_image, 2000);
	};
	
	this.detectFace = function(ondone) {
		t.td_tools.innerHTML = "Detecting face...";
		var detected = ccv.detect_objects({ 
			"canvas" : t.original_canvas,
			"cascade" : cascade,
			"interval" : 5,
			"min_neighbors" : 1
		});
		t.td_tools.innerHTML = "Face detected: "+detected.length;
		if (detected.length == 0) {
			if (ondone) ondone();
			return;
		}
		var face = detected[0];
		/*
		var enlarge = Math.floor(face.width/3);
		if (enlarge > face.x) enlarge = face.x;
		if (face.x+face.width+enlarge >= t.original_canvas.width) enlarge = t.original_canvas.width-1-face.x-face.width;
		face.x -= enlarge;
		face.width += enlarge*2;
		*/
		var detect = {x:face.x,y:face.y,width:face.width,height:face.height};
		var ctx = t.original_canvas.getContext("2d");
		var data = ctx.getImageData(0,0,t.original_canvas.width,t.original_canvas.height);
		var getPixel = function(data,x,y) {
			var i = y*data.width+x;
			return [data.data[i*4],data.data[i*4+1],data.data[i*4+2]];
		};
		var black_limit = 80;
		var isBlack = function(pix) {
			return pix[0]<black_limit && pix[1]<black_limit && pix[2]<black_limit;
		};
		var detectHeadX2 = function(y) {
			var start_x = Math.floor(face.x+face.width-face.width/5);
			var black_found = false;
			var new_x = -1;
			for (var x = start_x; x < t.original_canvas.width; ++x) {
				if (isBlack(getPixel(data,x,y))) {
					if (black_found) continue;
					black_found = true;
					continue;
				} else {
					if (!black_found) continue;
					new_x = x;
					break;
				}
			}
			ctx.strokeStyle = 'rgba(0,230,0,0.8)';
			ctx.beginPath();
			ctx.rect(start_x, y, new_x > 0 ? new_x-start_x : 3, 3);
			ctx.stroke();
			if (new_x != -1 && new_x-face.x-face.width > face.width/3) new_x = -1;
			return new_x;
		};
		var detectHeadX1 = function(y) {
			var start_x = Math.floor(face.x+face.width/5);
			var black_found = false;
			var new_x = -1;
			for (var x = start_x; x > 0; --x) {
				if (isBlack(getPixel(data,x,y))) {
					if (black_found) continue;
					if (x > 3 && isBlack(getPixel(data,x-1,y)) && isBlack(getPixel(data,x-2,y)) && isBlack(getPixel(data,x-3,y)))
						black_found = true;
				} else {
					if (!black_found) continue;
					if (x > 3 && !isBlack(getPixel(data,x-1,y)) && !isBlack(getPixel(data,x-2,y)) && !isBlack(getPixel(data,x-3,y))) {
						new_x = x;
						break;
					}
				}
			}
			ctx.strokeStyle = 'rgba(0,230,0,0.8)';
			ctx.beginPath();
			ctx.rect(new_x > 0 ? new_x : start_x, y, new_x > 0 ? start_x-new_x : 3, 3);
			ctx.stroke();
			if (new_x != -1 && face.x-new_x > face.width/2) new_x = -1;
			return new_x == -1 ? face.width*2 : new_x;
		};
		var detectHeadY1 = function(x) {
			var start_y = Math.floor(face.y+face.height/5);
			var black_found = false;
			var new_y = -1;
			for (var y = start_y; y > 0; --y) {
				if (isBlack(getPixel(data,x,y))) {
					if (black_found) continue;
					if (y > 3 && isBlack(getPixel(data,x,y-1)) && isBlack(getPixel(data,x,y-2)) && isBlack(getPixel(data,x,y-3)))
						black_found = true;
				} else {
					if (!black_found) continue;
					if (y > 3 && !isBlack(getPixel(data,x,y-1)) && !isBlack(getPixel(data,x,y-2)) && !isBlack(getPixel(data,x,y-3))) {
						new_y = y;
						break;
					}
				}
			}
			ctx.strokeStyle = 'rgba(0,230,0,0.8)';
			ctx.beginPath();
			ctx.rect(x, new_y > 0 ? new_y : start_y, 3, new_y > 0 ? start_y-new_y : 3, 3);
			ctx.stroke();
			if (new_y != -1 && face.y-new_y > face.height-face.height/5) new_y = -1;
			return new_y == -1 ? face.height*2 : new_y;
		};
		
		var newx2_1 = detectHeadX2(face.y);
		var newx2_2 = detectHeadX2(Math.floor(face.y+face.height/8));
		var newx2_3 = detectHeadX2(Math.floor(face.y+face.height/4));
		var newx2_4 = detectHeadX2(Math.floor(face.y+face.height/3));
		var newx2_5 = detectHeadX2(Math.floor(face.y+face.height/2));
		var new_x = Math.max(newx2_1, newx2_2, newx2_3, newx2_4, newx2_5);
		if (new_x != -1 && new_x >= face.x+face.width) face.width = new_x-face.x+1;
		
		var newx1_1 = detectHeadX1(face.y);
		var newx1_2 = detectHeadX1(Math.floor(face.y+face.height/8));
		var newx1_3 = detectHeadX1(Math.floor(face.y+face.height/4));
		var newx1_4 = detectHeadX1(Math.floor(face.y+face.height/3));
		var newx1_5 = detectHeadX1(Math.floor(face.y+face.height/2));
		var new_x = Math.min(newx1_1, newx1_2, newx1_3, newx1_4, newx1_5);
		if (new_x < face.x) {
			face.width += face.x-new_x;
			face.x = new_x;
		}
		
		var newy1_1 = detectHeadY1(face.x);
		var newy1_2 = detectHeadY1(Math.floor(face.x+face.width/8));
		var newy1_3 = detectHeadY1(Math.floor(face.x+face.width/4));
		var newy1_4 = detectHeadY1(Math.floor(face.x+face.width/3));
		var newy1_5 = detectHeadY1(Math.floor(face.x+face.width/2));
		var new_y = Math.min(newy1_1, newy1_2, newy1_3, newy1_4, newy1_5);
		if (new_y < face.y) {
			face.height += face.y-new_y;
			face.y = new_y;
		}
		
		// add 5% on each side
		face.x -= face.width/20;
		face.width += face.width/10;
		face.y -= face.height/20;
		// add half at the bottom
		face.height += detect.height/2;

		ctx.strokeStyle = 'rgba(0,87,230,0.8)';
		ctx.beginPath();
		ctx.rect(detect.x, detect.y, detect.width, detect.height);
		ctx.stroke();
		ctx.lineWidth = 2;
		ctx.strokeStyle = 'rgba(230,87,0,0.8)';
		ctx.beginPath();
		ctx.rect(face.x, face.y, face.width, face.height);
		ctx.stroke();
		
		if (ondone) ondone();
	};
}