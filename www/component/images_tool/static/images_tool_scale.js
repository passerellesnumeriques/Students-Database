function images_tool_scale() {
	this.getTitle = function() { return "Scale"; };
	this.getIcon = function() { return "/static/images_tool/scale.png"; };
	this.general_scale = null;
	this.general_editable = true;
	this.setValue = function(pic, value, editable) {
		if (pic) {
			pic.scale = value;
			pic.scale_editable = editable;
			pic.update();
		} else {
			this.general_scale = value;
			this.general_editable = editable;
		}
	};
	this.update = function(pic, canvas) {
		var max_width = null;
		var max_height = null;
		var editable = true;
		if (this.general_scale) {
			max_width = this.general_scale.max_width;
			max_height = this.general_scale.max_height;
			editable = this.general_editable;
		} else if (pic.scale) {
			max_width = pic.scale.max_width;
			max_height = pic.scale.max_height;
			editable = pic.scale_editable;
		}
		// apply scale
		var w = canvas.width;
		var h = canvas.height;
		if (max_width && w > max_width) {
			h = Math.floor(h*(max_width/w));
			w = max_width;
		}
		if (max_height && h > max_height) {
			w = Math.floor(w*(max_height/h));
			h = max_height;
		}
		if (w != canvas.width || h != canvas.height) {
			var new_canvas = document.createElement("CANVAS");
			new_canvas.width = w;
			new_canvas.height = h;
			var ctx = new_canvas.getContext("2d");
			ctx.drawImage(canvas, 0, 0, w, h);
			canvas.width = w;
			canvas.height = h;
			ctx = canvas.getContext("2d");
			ctx.drawImage(new_canvas, 0, 0);
		}
		
		// update fields
		if (pic.scale_width_input) {
			pic.scale_width_input.value = max_width ? max_width : "";
			pic.scale_width_input.disabled = editable ? "" : "disabled";
			pic.scale_height_input.value = max_height ? max_height : "";
			pic.scale_height_input.disabled = editable ? "" : "disabled";
		}
	};
	this.createContent = function(pic) {
		var createInput = function() {
			var input = document.createElement("INPUT");
			input.size = 4;
			input.style.fontSize = "8pt";
			input.style.textAlign = "right";
			return input;
		};
		var createTD = function() {
			var td = document.createElement("TD");
			td.style.fontSize = "8pt";
			return td;
		};
		
		var table = document.createElement("TABLE");
		var tr,td;
		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = createTD());
		td.innerHTML = "Maximum Width";
		tr.appendChild(td = createTD());
		pic.scale_width_input = createInput();
		if (this.general_scale) {
			pic.scale_width_input.value = this.general_scale.max_width;
			pic.scale_width_input.disabled = this.general_editable ? "" : "disabled";
		} else if (pic.scale) {
			pic.scale_width_input.value = pic.scale.max_width;
			pic.scale_width_input.disabled = pic.scale_editable ? "" : "disabled";
		}
		td.appendChild(pic.scale_width_input);
		
		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = createTD());
		td.innerHTML = "Maximum Height";
		tr.appendChild(td = createTD());
		pic.scale_height_input = createInput();
		if (this.general_scale) {
			pic.scale_height_input.value = this.general_scale.max_height;
			pic.scale_height_input.disabled = this.general_editable ? "" : "disabled";
		} else if (pic.scale) {
			pic.scale_height_input.value = pic.scale.max_height;
			pic.scale_height_input.disabled = pic.scale_editable ? "" : "disabled";
		}
		td.appendChild(pic.scale_height_input);
		
		return table;
	};
};
images_tool_scale.prototype = new ImageTool;
images_tool_scale.prototype.constructor = images_tool_scale;
