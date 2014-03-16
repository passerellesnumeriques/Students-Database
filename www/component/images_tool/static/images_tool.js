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
			t.upl = createUploadTempFile(multiple, true);
			var nb_files = 0;
			t.upl.onstart = function(files, callback) {
				nb_files = files.length;
				t.init();
				for (var i = 0; i < files.length; ++i) {
					var pic = t.addPicture();
					pic.file = files[i];
					pic.td_tools.innerHTML = "Waiting for upload: "+files[i].name;
				}
				callback();
			};
			t.upl.onstartfile = function(file) {
				var pic = null;
				for (var i = 0; i < t.pictures.length; ++i)
					if (t.pictures[i].file == file) { pic = t.pictures[i]; break; }
				if (pic) {
					pic.td_tools.innerHTML = "Uploading "+file.name+"...<br/>";
					pic.progress_container = document.createElement("DIV");
					pic.progress_container.style.width = '200px';
					pic.progress_container.style.height = '20px';
					pic.progress_container.style.border = '1px solid black';
					pic.progress_container.style.backgroundColor = '#FFFFFF';
					pic.progress_container.style.display = "inline-block";
					pic.progress_container.style.overflow = 'hidden';
					setBorderRadius(pic.progress_container, 3, 3, 3, 3, 3, 3, 3, 3);
					pic.td_tools.appendChild(pic.progress_container);
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
			var all_loaded = function() {
				if (t._faceDetection) {
					var next = function(index) {
						if (index == t.pictures.length) {
							return;
						}
						var pic = t.pictures[index];
						var face_detection_status = document.createElement("DIV");
						pic.td_tools.appendChild(face_detection_status);
						face_detection_status.innerHTML = "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Analyzing image...";
						pic.detectFace(function(nb_faces) {
							if (nb_faces == 1)
								face_detection_status.innerHTML = "<img src='"+theme.icons_16.ok+"' style='vertical-align:bottom'/> Face detected.";
							else
								face_detection_status.innerHTML = "<img src='"+theme.icons_16.warning+"' style='vertical-align:bottom'/> "+nb_faces+" face(s) detected.";
							setTimeout(function() { next(index+1); },5);
						});
					};
					setTimeout(function() { next(0); },1);
				}
			};
			t.upl.ondonefile = function(file, output, errors) {
				var pic = null;
				for (var i = 0; i < t.pictures.length; ++i)
					if (t.pictures[i].file == file) { pic = t.pictures[i]; break; }
				if (pic) {
					if (errors.length > 0)
						pic.td_tools.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> Error uploading file "+pic.file.name;
					else {
						pic.td_tools.innerHTML = "";
						var upload_status = document.createElement("DIV");
						pic.td_tools.appendChild(upload_status);
						upload_status.innerHTML = "<img src='"+theme.icons_16.ok+"' style='vertical-align:bottom'/> "+pic.file.name+" successfully uploaded.";
						var image_status = document.createElement("DIV");
						pic.td_tools.appendChild(image_status);
						image_status.innerHTML = "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Loading image...";
						pic._storage_id = output.id;
						pic.loadImage("/dynamic/storage/service/get_temp?id="+output.id, function(ok) {
							if (!ok) {
								image_status.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> Invalid image.";
								return;
							}
							image_status.innerHTML = "<img src='"+theme.icons_16.ok+"' style='vertical-align:bottom'/> Image loaded.";
							pic.drawImage(function(error) {
								if (error) {
									var status = document.createElement("DIV");
									pic.td_tools.appendChild(status);
									status.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> Error drawing image: "+e;
								}
								if (--nb_files == 0)
									all_loaded();
							});
						});
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
		t.table.className = "all_borders";
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

function images_tool_picture() {
	var t=this;
	this.tr = document.createElement("TR");
	this.tr.appendChild(this.td_original = document.createElement("TD"));
	this.tr.appendChild(this.td_tools = document.createElement("TD"));
	this.tr.appendChild(this.td_current = document.createElement("TD"));
	this.loadImage = function(url,ondone) {
		t.original = new Image();
		t.original.onload = function() {
			t.originalWidth = t.original.naturalWidth;
			t.originalHeight = t.original.naturalHeight;
			if (ondone) ondone(true);
		};
		t.original.src = url;
	};
	this.drawImage = function(ondone) {
		var w = t.originalWidth;
		var h = t.originalHeight;
		var max_width = 400;
		var max_height = 250;
		if (w > max_width) {
			h = Math.floor(h*(max_width/w));
			w = max_width;
		}
		if (h > max_height) {
			w = Math.floor(w*(max_height/h));
			h = max_height;
		}
		t.original_canvas = document.createElement("CANVAS");
		t.original_canvas.width = w;
		t.original_canvas.height = h;
		t.original_canvas.style.width = w+"px";
		t.original_canvas.style.height = h+"px";
		t.td_original.appendChild(t.original_canvas);
		setTimeout(function() {
			var ctx = t.original_canvas.getContext("2d");
			try { 
				ctx.drawImage(t.original, 0, 0, w, h); 
				if (ondone) ondone(null);
			}
			catch (e) {
				if (ondone) ondone(e);
				else log_exception(e); 
			}
		},5);
	};
	
	this.detectFace = function(ondone) {
		setTimeout(function() {
			t._detectFace(function(nb) {
				if (ondone) ondone(nb);
			}, 300, 300);
		},1);
	};
	
	this._detectFace = function(ondone, max_width, max_height) {
		var canvas = document.createElement("CANVAS");
		var w = t.originalWidth;
		var h = t.originalHeight;
		if (w > max_width) {
			h = Math.floor(h*(max_width/w));
			w = max_width;
		}
		if (h > max_height) {
			w = Math.floor(w*(max_height/h));
			h = max_height;
		}
		canvas.width = w;
		canvas.height = h;
		var ctx = canvas.getContext("2d");
		ctx.drawImage(t.original, 0, 0, w, h);
		var detected = ccv.detect_objects({ 
			"canvas" : ccv.grayscale(canvas),
			"cascade" : cascade,
			"interval" : 5,
			"min_neighbors" : 1,
		});
		if (detected.length == 0) {
			if (ondone) ondone(0);
			return;
		}
		var face;
//		for (var i = 0; i < detected.length; ++i) {
//			face = {x:detected[i].x,y:detected[i].y,width:detected[i].width,height:detected[i].height};
//			face.x /= w/t.original_canvas.width;
//			face.width /= w/t.original_canvas.width;
//			face.y /= h/t.original_canvas.height;
//			face.height /= h/t.original_canvas.height;
//			
//			ctx = t.original_canvas.getContext("2d");
//			ctx.lineWidth = 2;
//			ctx.strokeStyle = 'rgba(0,87,230,0.8)';
//			ctx.beginPath();
//			ctx.rect(face.x, face.y, face.width, face.height);
//			ctx.stroke();
//		}
		if (detected.length == 1)
			face = detected[0];
		else {
			face = detected[0];
			for (var i = 1; i < detected.length; ++i) {
				if (detected[i].width+detected[i].height > face.width+face.height)
					face = detected[i];
			}
		}
		var detect = {x:face.x,y:face.y,width:face.width,height:face.height};
		var data = ctx.getImageData(0,0,w,h);
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
			for (var x = start_x; x <w; ++x) {
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

		// put back with aspect ratio;
		face.x /= w/t.original_canvas.width;
		face.width /= w/t.original_canvas.width;
		face.y /= h/t.original_canvas.height;
		face.height /= h/t.original_canvas.height;
		
		ctx = t.original_canvas.getContext("2d");
		ctx.lineWidth = 2;
		ctx.strokeStyle = 'rgba(230,87,0,0.8)';
		ctx.beginPath();
		ctx.rect(face.x, face.y, face.width, face.height);
		ctx.stroke();
		
		if (ondone) ondone(detected.length);
	};
}