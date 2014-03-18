// #depends[/static/images_tool/images_tool.js]

function images_tool_crop() {
	this.getTitle = function() { return "Crop"; };
	this.getIcon = function() { return "/static/images_tool/crop.png"; };
	this.general_crop = null;
	this.general_crop_editable = true;
	this.general_ratio = null;
	this.general_ratio_editable = true;
	this.setValue = function(pic, value, editable) {
		if (pic) {
			if (!this.general_crop && value.rect) {
				this.general_crop = null;
				pic.crop_rect = value.rect;
				pic.crop_rect_editable = editable;
			}
			if (!this.general_ratio && value.aspect_ratio) {
				this.general_ratio = null;
				pic.crop_ratio = value.aspect_ratio;
				pic.crop_ratio_editable = editable;
			}
		} else {
			if (value.rect) {
				this.general_crop = value.rect;
				this.general_crop_editable = editable;
			}
			if (value.aspect_ratio) {
				this.general_ratio = value.aspect_ratio;
				this.general_ratio_editable = editable;
				if (this.general_ratio_input) {
					this.general_ratio_input.value = this.general_ratio ? this.general_ratio : "";
					this.general_ratio_input.disable = this.general_ratio_editable ? "" : "disabled";
				}
			}
		}
	};
	this._apply_ratio = function(rect, ratio, max_width, max_height) {
		var current = rect.width/rect.height;
		if (current == ratio) return;
		if (current < ratio) {
			// height is bigger => enlarge the width
			// ratio = w/h => w = ratio*h
			var w = ratio*rect.height;
			w -= rect.width;
			rect.x -= Math.floor(w/2);
			rect.width += Math.floor(w);
		} else {
			// width is bigger => enlarge the height
			// ratio = w/h => ratio*h = w*h/h => h = w*h/h/ratio => h = w/ratio
			var h = rect.width/ratio;
			h -= rect.height;
			rect.y -= Math.floor(h/2);
			rect.height += Math.floor(h);
		}
		if (rect.x < 0) {
			if (rect.width-rect.x <= max_width)
				rect.x = 0;
			else {
				// we need to reduce width
				rect.x = 0;
				rect.width = max_width;
				var h = max_width/ratio;
				if (h > max_height) {
					rect.y = 0;
					rect.height = max_height;
				} else {
					var add = h-rect.height;
					rect.y -= Math.floor(add/2);
					if (rect.y < 0) rect.y = 0;
					rect.height += add;
				}
			}
		}
		if (rect.width > max_width) {
			rect.x = 0;
			rect.width = max_width;
			var h = max_width/ratio;
			if (h > max_height) {
				rect.y = 0;
				rect.height = max_height;
			} else {
				var add = h-rect.height;
				rect.y -= Math.floor(add/2);
				if (rect.y < 0) rect.y = 0;
				rect.height += add;
			}
		}
		if (rect.y < 0) {
			if (rect.height-rect.y <= max_height)
				rect.y = 0;
			else {
				// we need to reduce height
				rect.y = 0;
				rect.height = max_height;
				var w = ratio/max_height;
				if (w > max_width) {
					rect.x = 0;
					rect.width = max_width;
				} else {
					var add = w-rect.width;
					rect.x -= Math.floor(add/2);
					if (rect.x < 0) rect.x = 0;
					rect.width += add;
				}
			}
		}
		if (rect.width > max_width) {
			rect.y = 0;
			rect.height = max_height;
			var w = ratio/max_height;
			if (w > max_width) {
				rect.x = 0;
				rect.width = max_width;
			} else {
				var add = w-rect.width;
				rect.x -= Math.floor(add/2);
				if (rect.x < 0) rect.x = 0;
				rect.width += add;
			}
		}
	};
	
	this.update = function(pic, canvas) {
		var rect = null;
		if (this.general_crop) rect = this.general_crop; else if (pic.crop_rect) rect = pic.crop_rect;
		if (rect == null) rect = {x:0,y:0,width:pic.original.naturalWidth,height:pic.original.naturalHeight};
		if (this.general_ratio)
			this._apply_ratio(rect, this.general_ratio, pic.original.naturalWidth, pic.original.naturalHeight);
		else if (pic.crop_ratio)
			this._apply_ratio(rect, pic.crop_ratio, pic.original.naturalWidth, pic.original.naturalHeight);
		pic.crop_rect = rect;
		// apply crop
		var ctx = canvas.getContext("2d");
		var data = ctx.getImageData(pic.crop_rect.x, pic.crop_rect.y, pic.crop_rect.width, pic.crop_rect.height);
		canvas.width = pic.crop_rect.width;
		canvas.height = pic.crop_rect.height;
		ctx.putImageData(data, 0, 0);
		
		// update fields
		if (pic.crop_top_input) {
			pic.crop_top_input.value = pic.crop_rect.y;
			pic.crop_bottom_input.value = pic.crop_rect.y+pic.crop_rect.height-1;
			pic.crop_left_input.value = pic.crop_rect.x;
			pic.crop_right_input.value = pic.crop_rect.x+pic.crop_rect.width-1;
			pic.crop_size_container.innerHTML = pic.crop_rect.width+"x"+pic.crop_rect.height;
			pic.crop_ratio_input.value = pic.crop_rect.width/pic.crop_rect.height;
		}
		
		// draw the crop
		var w = pic.original_canvas.width;
		var h = pic.original_canvas.height;
		if (pic.crop_divs) {
			pic.td_original.removeChild(pic.crop_divs[0]);
			pic.td_original.removeChild(pic.crop_divs[1]);
			pic.td_original.removeChild(pic.crop_divs[2]);
			pic.td_original.removeChild(pic.crop_divs[3]);
			pic.crop_divs = null;
		}
		if (pic.crop_rect) {
			var x = Math.floor(pic.crop_rect.x/(pic.original.naturalWidth/w));
			var width = Math.floor(pic.crop_rect.width/(pic.original.naturalWidth/w));
			var y = Math.floor(pic.crop_rect.y/(pic.original.naturalHeight/h));
			var height = Math.floor(pic.crop_rect.height/(pic.original.naturalHeight/h));

			var div_top = document.createElement("DIV");
			div_top.style.position = "absolute";
			div_top.style.top = y+"px";
			div_top.style.left = x+"px";
			div_top.style.height = "2px";
			div_top.style.width = width+"px";
			div_top.style.backgroundColor = "#C04000";
			pic.td_original.appendChild(div_top);

			var div_bottom = document.createElement("DIV");
			div_bottom.style.position = "absolute";
			div_bottom.style.top = (y+height-2)+"px";
			div_bottom.style.left = x+"px";
			div_bottom.style.height = "2px";
			div_bottom.style.width = width+"px";
			div_bottom.style.backgroundColor = "#C04000";
			pic.td_original.appendChild(div_bottom);
			
			var div_left = document.createElement("DIV");
			div_left.style.position = "absolute";
			div_left.style.top = y+"px";
			div_left.style.left = x+"px";
			div_left.style.height = height+"px";
			div_left.style.width = "2px";
			div_left.style.backgroundColor = "#C04000";
			pic.td_original.appendChild(div_left);
			
			var div_right = document.createElement("DIV");
			div_right.style.position = "absolute";
			div_right.style.top = y+"px";
			div_right.style.left = (x+width-2)+"px";
			div_right.style.height = height+"px";
			div_right.style.width = "2px";
			div_right.style.backgroundColor = "#C04000";
			pic.td_original.appendChild(div_right);
			
			pic.crop_divs = [div_top,div_bottom,div_left,div_right];
		};
	};
	var createInput = function() {
		var input = document.createElement("INPUT");
		input.size = 4;
		input.style.fontSize = "8pt";
		input.style.textAlign = "center";
		return input;
	};
	var createTD = function() {
		var td = document.createElement("TD");
		td.style.fontSize = "8pt";
		td.style.textAlign = "center";
		return td;
	};
	this.createContent = function(pic) {
		var t=this;
		var table = document.createElement("TABLE");
		var tr,td;
		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = createTD());
		tr.appendChild(td = createTD());
		td.appendChild(document.createTextNode("Top"));
		td.appendChild(document.createElement("BR"));
		pic.crop_top_input = createInput();
		if (this.general_crop) {
			pic.crop_top_input.value = this.general_crop.y;
			pic.crop_top_input.disabled = "disabled";
		} else if (pic.crop_rect) {
			pic.crop_top_input.value = pic.crop_rect.y;
			pic.crop_top_input.disabled = pic.crop_rect_editable ? "" : "disabled";
		}
		pic.crop_top_input.onchange = function() {
			var value = parseInt(this.value);
			if (isNaN(value) || value <= 0) return;
			if (value >= pic.original.naturalHeight) {
				this.value = value = pic.original.naturalHeight-1;
			}
			var rect = pic.crop_rect;
			if (!rect) rect = {x:0,y:0,width:pic.original.naturalWidth,height:pic.original.naturalHeight};
			rect.height -= value-rect.y;
			if (rect.height <= 0) rect.height = 1;
			rect.y = value;
			if (rect.y+rect.height > pic.original.naturalHeight)
				rect.height = pic.original.naturalHeight-rect.y;
			t.setValue(pic, {rect:rect}, true);
			pic.update();
			if (value+rect.height <= pic.original.naturalHeight) {
				rect.y = value;
				t.setValue(pic, {rect:rect}, true);
				pic.update();
			}
		};
		td.appendChild(pic.crop_top_input);
		tr.appendChild(td = createTD());

		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = createTD());
		td.appendChild(document.createTextNode("Left"));
		pic.crop_left_input = createInput();
		if (this.general_crop) {
			pic.crop_left_input.value = this.general_crop.x;
			pic.crop_left_input.disabled = "disabled";
		} else if (pic.crop_rect) {
			pic.crop_left_input.value = pic.crop_rect.x;
			pic.crop_left_input.disabled = pic.crop_rect_editable ? "" : "disabled";
		}
		pic.crop_left_input.onchange = function() {
			var value = parseInt(this.value);
			if (isNaN(value) || value <= 0) return;
			if (value >= pic.original.naturalWidth) {
				this.value = value = pic.original.naturalWidth-1;
			}
			var rect = pic.crop_rect;
			if (!rect) rect = {x:0,y:0,width:pic.original.naturalWidth,height:pic.original.naturalHeight};
			rect.width -= value-rect.x;
			if (rect.width <= 0) rect.width = 1;
			rect.x = value;
			if (rect.x+rect.width > pic.original.naturalWidth)
				rect.width = pic.original.naturalWidth-rect.x;
			t.setValue(pic, {rect:rect}, true);
			pic.update();
			if (value+rect.width <= pic.original.naturalWidth) {
				rect.x = value;
				t.setValue(pic, {rect:rect}, true);
				pic.update();
			}
		};
		td.appendChild(pic.crop_left_input);
		tr.appendChild(td = createTD());
		pic.crop_size_container = td;
		if (this.general_crop) td.innerHTML = this.general_crop.width+"x"+this.general_crop.height;
		else if (pic.crop_rect) td.innerHTML = pic.crop_rect.width+"x"+pic.crop_rect.height;
		tr.appendChild(td = createTD());
		pic.crop_right_input = createInput();
		if (this.general_crop) {
			pic.crop_right_input.value = this.general_crop.x+this.general_crop.width-1;
			pic.crop_right_input.disabled = "disabled";
		} else if (pic.crop_rect) {
			pic.crop_right_input.value = pic.crop_rect.x+pic.crop_rect.width-1;
			pic.crop_right_input.disabled = pic.crop_rect_editable ? "" : "disabled";
		}
		pic.crop_right_input.onchange = function() {
			var value = parseInt(this.value);
			if (isNaN(value) || value <= 0) return;
			if (value >= pic.original.naturalWidth) {
				this.value = value = pic.original.naturalWidth;
			}
			var rect = pic.crop_rect;
			if (!rect) rect = {x:0,y:0,width:pic.original.naturalWidth,height:pic.original.naturalHeight};
			rect.width = value-rect.x;
			if (rect.x+rect.width > pic.original.naturalWidth)
				rect.x = pic.original.naturalWidth-rect.width;
			t.setValue(pic, {rect:rect}, true);
			pic.update();
			if (value-rect.width >= 0) {
				rect.x = value-rect.width;
				t.setValue(pic, {rect:rect}, true);
				pic.update();
			}
		};
		td.appendChild(pic.crop_right_input);
		td.appendChild(document.createTextNode("Right"));
		
		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = createTD());
		tr.appendChild(td = createTD());
		pic.crop_bottom_input = createInput();
		if (this.general_crop) {
			pic.crop_bottom_input.value = this.general_crop.y+this.general_crop.height-1;
			pic.crop_bottom_input.disabled = "disabled";
		} else if (pic.crop_rect) {
			pic.crop_bottom_input.value = pic.crop_rect.y+pic.crop_rect.height-1;
			pic.crop_bottom_input.disabled = pic.crop_rect_editable ? "" : "disabled";
		}
		pic.crop_bottom_input.onchange = function() {
			var value = parseInt(this.value);
			if (isNaN(value) || value <= 0) return;
			if (value >= pic.original.naturalHeight) {
				this.value = value = pic.original.naturalHeight;
			}
			var rect = pic.crop_rect;
			if (!rect) rect = {x:0,y:0,width:pic.original.naturalWidth,height:pic.original.naturalHeight};
			rect.height = value-rect.y;
			if (rect.y+rect.height > pic.original.naturalHeight)
				rect.y = pic.original.naturalHeight-rect.height;
			t.setValue(pic, {rect:rect}, true);
			pic.update();
			if (value-rect.height >= 0) {
				rect.y = value-rect.height;
				t.setValue(pic, {rect:rect}, true);
				pic.update();
			}
		};
		td.appendChild(pic.crop_bottom_input);
		td.appendChild(document.createElement("BR"));
		td.appendChild(document.createTextNode("Bottom"));
		tr.appendChild(td = createTD());

		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = createTD());
		td.colSpan = 3;
		td.appendChild(document.createTextNode("Aspect ratio"));
		pic.crop_ratio_input = createInput();
		if (this.general_ratio) {
			pic.crop_ratio_input.value = this.general_ratio;
			pic.crop_ratio_input.disabled = "disabled";
		} else if (pic.crop_ratio) {
			pic.crop_ratio_input.value = pic.crop_ratio;
			pic.crop_ratio_input.disabled = pic.crop_ratio_editable ? "" : "disabled";
		}
		pic.crop_ratio_input.onchange = function() {
			if (this.value == "")
				t.setValue(pic, {aspect_ratio:null}, true);
			else {
				var value = parseFloat(this.value);
				if (isNaN(value) || value <= 0)
					this.value = pic.crop_ratio ? pic.crop_ratio : "";
				else
					t.setValue(pic, {aspect_ratio:value}, true);
			}
			pic.update();
		};
		td.appendChild(pic.crop_ratio_input);
		
		return table;
	};
	this.createGeneralContent = function() {
		var div = document.createElement("DIV");
		div.style.fontSize = "8pt";
		div.appendChild(document.createTextNode("Aspect ratio "));
		this.general_ratio_input = createInput();
		if (this.general_ratio) this.general_ratio_input.value = this.general_ratio;
		if (!this.general_ratio_editable) this.general_ratio_input.disabled = "disabled";
		div.appendChild(this.general_ratio_input);
		var t=this;
		this.general_ratio.onchange = function() {
			if (this.value == "")
				t.setValue(null, {aspect_ratio:null}, true);
			else {
				var value = parseFloat(this.value);
				if (isNaN(value) || value <= 0)
					this.value = t.general_ratio ? t.general_ratio : "";
				else
					t.setValue(null, {aspect_ratio:value}, true);
			}
			var pictures = t.images_tool.getPictures();
			for (var i = 0; i < pictures.length; ++i) pictures[i].update();
		};
		return div;
	};
};
images_tool_crop.prototype = new ImageTool;
images_tool_crop.prototype.constructor = images_tool_crop;
