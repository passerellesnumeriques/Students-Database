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
		add_javascript("/static/people/pictures_list_"+type+".js", function() {
			for (var i = 0; i < peoples.length; ++i)
				t.pictures.push(new window["pictures_list_"+type](t.content, peoples[i], t.width, t.height));
		});
	};
	this.setSize = function(width, height) {
		this.width = width;
		this.height = height;
		for (var i = 0; i < this.pictures.length; ++i)
			this.pictures[i].setSize(width, height);
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
		o = document.createElement("OPTION"); o.text = "50x60"; this.select_size.add(o);
		o = document.createElement("OPTION"); o.text = "100x120"; this.select_size.add(o);
		o = document.createElement("OPTION"); o.text = "150x180"; this.select_size.add(o);
		o = document.createElement("OPTION"); o.text = "200x240"; this.select_size.add(o);
		o = document.createElement("OPTION"); o.text = "300x360"; this.select_size.add(o);
		o = document.createElement("OPTION"); o.text = "400x480"; this.select_size.add(o);
		this.select_size.selectedIndex = 4;
		this.select_size.onchange = function() {
			switch (t.select_size.selectedIndex) {
			case 0: t.setSize(35,35); break;
			case 1: t.setSize(50,60); break;
			case 2: t.setSize(100,120); break;
			case 3: t.setSize(150,180); break;
			case 4: t.setSize(200,240); break;
			case 5: t.setSize(300,360); break;
			case 6: t.setSize(400,480); break;
			};
		};
		var button;
		button = document.createElement("DIV");
		button.className = "button_verysoft disabled";
		button.innerHTML = "<img src='/static/images_tool/people_picture.png'/> Import Pictures";
		button.style.marginLeft = "5px";
		require("images_tool.js",function() {
			var tool = new images_tool();
			tool.usePopup(true);
			tool.useUpload();
			tool.useFaceDetection();
			tool.addTool("crop",function() {
				tool.setToolValue("crop", null, {aspect_ratio:0.75}, false);
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