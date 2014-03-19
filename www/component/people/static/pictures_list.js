function pictures_list(container, peoples, default_view) {
	if (typeof container == 'string') container = document.getElementById(container);
	if (!default_view) default_view = 'thumbnail';
	var t=this;

	this.view = default_view;
	this.width = 200; 
	this.height = 240;
	this.pictures = [];
	
	this.setView = function(type) {
		this.view = type;
		while (this.content.childNodes.length > 0) this.content.removeChild(this.content.childNodes[0]);
		this.pictures = [];
		var ready_count = peoples.length;
		var ready = function() {
			if (--ready_count == 0)
				t.adjustSizes();
		};
		add_javascript("/static/people/pictures_list_"+type+".js", function() {
			for (var i = 0; i < peoples.length; ++i) {
				var pic = new window["pictures_list_"+type](t.content, peoples[i], t.width, t.height, ready);
				pic.element.title = "Click to see profile of "+peoples[i].first_name+" "+peoples[i].last_name;
				pic.element.style.cursor = "pointer";
				pic.element._people_id = peoples[i].id;
				pic.element.onclick = function() {
					var id = this._people_id;
					window.top.require("popup_window.js", function() {
						var p = new window.top.popup_window("Profile", null, "");
						p.setContentFrame("/dynamic/people/page/profile?people="+id);
						p.showPercent(95,95);
					});
				};
				t.pictures.push(pic);
			}
		});
	};
	this.setSize = function(width, height) {
		this.width = width;
		this.height = height;
		for (var i = 0; i < this.pictures.length; ++i)
			this.pictures[i].setSize(width, height);
		this.adjustSizes();
	};
	
	this.adjustSizes = function() {
		for (var i = 0; i < this.pictures.length; ++i) {
			this.pictures[i].element.style.width = "";
			this.pictures[i].element.style.height = "";
		}
		var max_width = 0;
		var max_height = 0;
		for (var i = 0; i < this.pictures.length; ++i) {
			var w = getWidth(this.pictures[i].element);
			if (w > max_width)
				max_width = w;
			var h = getHeight(this.pictures[i].element);
			if (h > max_height)
				max_height = h;
		}
		for (var i = 0; i < this.pictures.length; ++i) {
			setWidth(this.pictures[i].element, max_width);
			setHeight(this.pictures[i].element, max_height);
			this.pictures[i].adjustPicture();
		}
	};
	
	this._init = function() {
		this.header = document.createElement("DIV");
		this.content = document.createElement("DIV");
		this.content.style.overflowY = "auto";
		container.appendChild(this.header);
		container.appendChild(this.content);
		require("vertical_layout.js", function() {
			t.content.setAttribute("layout", "fill");
			new vertical_layout(container);
		});
		theme.css('header_bar.css');
		this.header.className = "header_bar_toolbar_style";
		
		require("horizontal_layout.js",function() {
			new horizontal_layout(t.header, true, "middle");
		});
		
		var div;
		this.header.appendChild(div = document.createElement("DIV"));
		div.appendChild(document.createTextNode("View"));
		div.style.marginLeft = "3px";
		var view_chooser_container = document.createElement("DIV"); this.header.appendChild(view_chooser_container);
		view_chooser_container.style.marginLeft = "3px";
		view_chooser_container.style.marginRight = "3px";
		require("mac_tabs.js",function() {
			t.view_chooser = new mac_tabs("compressed");
			t.view_chooser.addItem("<img src='/static/people/list_detail_16.png'/>", 'detail');
			t.view_chooser.addItem("<img src='/static/people/list_thumb_16.png'/>", 'thumbnail');
			t.view_chooser.select(t.view);
			view_chooser_container.appendChild(t.view_chooser.element);
			t.view_chooser.onselect = function(type) { t.setView(type); };
			layout.invalidate(t.header);
		});
		this.header.appendChild(div = document.createElement("DIV"));
		div.appendChild(document.createTextNode("Picture size"));
		div.style.marginRight = "3px";
		this.select_size = document.createElement("SELECT"); this.header.appendChild(this.select_size);
		var o;
		o = document.createElement("OPTION"); o.text = "35x35"; this.select_size.add(o);
		o = document.createElement("OPTION"); o.text = "38x50"; this.select_size.add(o);
		o = document.createElement("OPTION"); o.text = "75x100"; this.select_size.add(o);
		o = document.createElement("OPTION"); o.text = "150x150"; this.select_size.add(o);
		o = document.createElement("OPTION"); o.text = "150x200"; this.select_size.add(o);
		o = document.createElement("OPTION"); o.text = "225x300"; this.select_size.add(o);
		o = document.createElement("OPTION"); o.text = "300x300"; this.select_size.add(o);
		this.select_size.selectedIndex = 5;
		this.select_size.onchange = function() {
			switch (t.select_size.selectedIndex) {
			case 0: t.setSize(35,35); break;
			case 1: t.setSize(38,50); break;
			case 2: t.setSize(75,100); break;
			case 3: t.setSize(150,150); break;
			case 4: t.setSize(150,200); break;
			case 5: t.setSize(225,300); break;
			case 6: t.setSize(300,300); break;
			};
		};
		var button;
		button = document.createElement("DIV");
		button.className = "button_verysoft disabled";
		button.innerHTML = "<img src='/static/images_tool/people_picture.png'/> Import Pictures";
		button.style.marginLeft = "5px";
		require("images_tool.js",function() {
			var tool = new images_tool();
			tool.usePopup(true, function() {
				var pictures = [];
				for (var i = 0; i < tool.getPictures().length; ++i) pictures.push(tool.getPictures()[i]);
				var nb = 0;
				for (var i = 0; i < pictures.length; ++i)
					if (tool.getTool("people").getPeople(pictures[i]))
						nb++;
				if (nb == 0) return;
				tool.popup.freeze_progress("Saving pictures...", nb, function(span_message, progress_bar) {
					var next = function(index) {
						if (index == pictures.length) {
							if (tool.getPictures().length > 0) {
								tool.popup.unfreeze();
								return;
							}
							tool.popup.close();
							return;
						}
						var people = tool.getTool("people").getPeople(pictures[index]);
						if (!people) {
							next(index+1);
							return;
						}
						span_message.innerHTML = "";
						span_message.appendChild(document.createTextNode("Saving picture for "+people.first_name+" "+people.last_name));
						var data = pictures[index].getResultData();
						service.json("people", "save_picture", {id:people.id,picture:data}, function(res) {
							if (res) {
								tool.removePicture(pictures[index]);
								for (var i = 0; i < t.pictures.length; ++i)
									if (t.pictures[i].people.id == people.id) {
										t.pictures[i].reload();
										break;
									}
							}
							progress_bar.addAmount(1);
							next(index+1);
						});
					};
					next(0);
				});
			});
			tool.useUpload();
			tool.useFaceDetection();
			tool.addTool("crop",function() {
				tool.setToolValue("crop", null, {aspect_ratio:0.75}, true);
			});
			tool.addTool("scale", function() {
				tool.setToolValue("scale", null, {max_width:300,max_height:300}, false);
			});
			tool.addTool("people", function() {
				tool.setToolValue("people", null, peoples, false);
			});
			tool.init(function() {
				button.className = "button_verysoft";
				button.onclick = function(ev) {
					tool.reset();
					tool.launchUpload(ev, true);
				};
			});
		});
		this.header.appendChild(button);
	};
	this._init();
	this.setView(default_view);
}