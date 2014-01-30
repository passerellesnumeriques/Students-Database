if (typeof require != 'undefined') require("color.js");

function color_choice(container, current_color) {
	var t=this;
	this._init = function() {
		var page = document.createElement("TABLE"); container.appendChild(page);
		var tr, td;
		page.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		var simple_td = td;
		var simple_choice_table = document.createElement("TABLE");
		simple_choice_table.style.borderCollapse = "collapse";
		simple_choice_table.style.borderSpacing = "0px";
		simple_choice_table.style.backgroundColor = "white";
		td.appendChild(simple_choice_table);
		tr.appendChild(this.custom_choice_container = document.createElement("TD"));
		this.custom_choice_container.style.position = 'absolute';
		this.custom_choice_container.style.visibility = 'hidden';
		this.custom_choice_container.style.top = '-10000px';
		var default_colors = [
		  ["#FFFFFF", "#C0C0C0", "#808080", "#404040", "#000000"],
		  ["#0000FF", "#4040FF", "#8080FF", "#A0A0FF", "#C0C0FF"],
		  ["#00FF00", "#40FF40", "#80FF80", "#A0FFA0", "#C0FFC0"],
		  ["#FF0000", "#FF4040", "#FF8080", "#FFA0A0", "#FFC0C0"],
		  ["#FFFF00", "#FFFF40", "#FFFF80", "#FFFFA0", "#FFFFC0"],
		  ["#FF8000", "#FF8040", "#FF8080", "#FF80A0", "#FF80C0"],
		  ["#80FF00", "#80FF40", "#80FF80", "#80FFA0", "#80FFC0"],
		  ["#FF00FF", "#FF40FF", "#FF80FF", "#FFA0FF", "#FFC0FF"],
		  ["#FF0080", "#FF4080", "#FF8080", "#FFA080", "#FFC080"],
		  ["#8000FF", "#8040FF", "#8080FF", "#80A0FF", "#80C0FF"],
		  ["#00FFFF", "#40FFFF", "#80FFFF", "#A0FFFF", "#C0FFFF"],
		  ["#0080FF", "#4080FF", "#8080FF", "#A080FF", "#C080FF"],
		  ["#00FF80", "#40FF80", "#80FF80", "#A0FF80", "#C0FF80"],
		];
		this.default_boxes = [];
		for (var i = 0; i < default_colors.length; ++i) {
			simple_choice_table.appendChild(tr = document.createElement("TR"));
			this.default_boxes.push([]);
			for (var j = 0; j < default_colors[i].length; ++j) {
				var color = parse_color(default_colors[i][j]);
				tr.appendChild(td = document.createElement("TD"));
				var box = document.createElement("DIV");
				box.color = color;
				box.style.border = "2px solid white";
				box.style.backgroundColor = color_string(color);
				box.style.width = "15px";
				box.style.height = "15px";
				td.appendChild(box);
				this.default_boxes[i].push(box);
				box.onclick = function() { t.setColor(this.color); };
			}
		}

		page.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.colSpan = 2;
		td.style.textAlign = "center";
		var button = document.createElement("DIV");
		button.className = "button";
		button.innerHTML = "Custom Color";
		td.appendChild(button);
		button.onclick = function() {
			if (t.custom_choice_container.childNodes.length == 0) {
				// show
				this.innerHTML = "Default Colors";
				var frame = document.createElement("IFRAME");
				t.frame = frame;
				frame.src = "/static/widgets/color_choice/lib_colorpicker/default.html"+color_string(t.color);
				frame.onload = function() { 
					fireLayoutEventFor(container); 
				};
				frame.style.height = "280px";
				frame.style.width = "450px";
				t.custom_choice_container.appendChild(frame);
				frame.style.border = "0px";
				t.custom_choice_container.style.position = 'static';
				t.custom_choice_container.style.visibility = 'visible';
				simple_td.style.position = 'absolute';
				simple_td.style.visibility = 'hidden';
				simple_td.style.top = '-10000px';
				fireLayoutEventFor(container);
			} else {
				// hide
				this.innerHTML = "Custom Color";
				var col = getIFrameDocument(t.frame).getElementById('cp1_Hex').value;
				while (t.custom_choice_container.childNodes.length > 0) t.custom_choice_container.removeChild(t.custom_choice_container.childNodes[0]);
				simple_td.style.position = 'static';
				simple_td.style.visibility = 'visible';
				t.custom_choice_container.style.position = 'absolute';
				t.custom_choice_container.style.visibility = 'hidden';
				fireLayoutEventFor(container);
				t.setColor('#'+col);
			}
		};
	};
	this.setColor = function(color) {
		if (typeof color == 'string') color = parse_color(color);
		for (var i = 0; i < this.default_boxes.length; ++i) {
			for (var j = 0; j < this.default_boxes[i].length; ++j) {
				var box = this.default_boxes[i][j];
				if (color_equals(box.color, color))
					box.style.border = "2px solid #FF8000";
				else
					box.style.border = "2px solid white";
			}
		}
		this.color = color;
	};
	
	require("color.js", function() {
		t._init();
		if (!current_color) current_color = "#000000";
		t.setColor(current_color);
	});
}