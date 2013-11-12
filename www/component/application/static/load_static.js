function load_static_resources(container) {
	if (typeof container == 'string') container = document.getElementById(container);
	if (window.top.pn_application_static_loaded) {
		container.style.visibility = 'hidden';
		return;
	}
	
	var t=this;
	this.stopped = false;
	this.start_time = new Date();
	
	container.appendChild(document.createTextNode("Loading application"));
	this.pc_container = document.createElement("DIV");
	this.pc_container.style.display = "inline-block";
	this.pc_container.style.height = "8px";
	this.pc_container.style.width = "250px";
	this.pc_container.style.border = "1px solid #808080";
	this.pc_container.style.marginLeft = "5px";
	container.appendChild(this.pc_container);
	this.pc = document.createElement("DIV");
	this.pc.style.width = "0px";
	this.pc.style.height = "8px";
	this.pc.style.backgroundColor = "#D0D0FF";
	this.pc_container.appendChild(this.pc);

	this.loaded = function(size) {
		if (t.stopped) return;
		window.top.pn_application_static.loaded_size += size;
		var pc = Math.floor(window.top.pn_application_static.loaded_size*250/window.top.pn_application_static.total_size);
		t.pc.style.width = pc+"px";
	};
	this.check_end = function() {
		if (!t.stopped && window.top.pn_application_static.scripts.length > 0) return;
		if (!t.stopped && window.top.pn_application_static.images.length > 0) return;
		if (window.top.pn_application_static.scripts.length == 0 && window.top.pn_application_static.images.length == 0)
			window.top.pn_application_static_loaded = true;
		container.innerHTML = "Application loaded in "+Math.floor((new Date().getTime()-t.start_time.getTime())/1000)+"s.";
		require("animation.js",function() {
			animation.fadeOut(container, 1000);
		});
	};
	this.stop = function() {
		this.stopped = true;
		this.check_end();
	};
	
	this.next_script = function() {
		if (t.stopped) return;
		var script = null;
		for (var i = 0; i < window.top.pn_application_static.scripts.length; ++i)
			if (window.top.pn_application_static.scripts[i].dependencies.length == 0) {
				script = window.top.pn_application_static.scripts[i];
				window.top.pn_application_static.loading_scripts.push(script);
				window.top.pn_application_static.scripts.splice(i,1);
				break;
			}
		if (script == null) { t.check_end(); return; }
		add_javascript(script.url,function() {
			t.loaded(script.size);
			for (var i = 0; i < window.top.pn_application_static.scripts.length; ++i)
				window.top.pn_application_static.scripts[i].dependencies.remove(script.url);
			window.top.pn_application_static.loading_scripts.remove(script);
			t.next_script();
		});
	};
	this.next_image = function() {
		if (t.stopped) return;
		if (window.top.pn_application_static.images.length == 0) { t.check_end(); return; }
		var image = window.top.pn_application_static.images[0];
		window.top.pn_application_static.images.splice(0,1);
		window.top.pn_application_static.loading_images.push(image);
		var i = document.createElement("IMG");
		i.data = image;
		i.onload = function() { window.top.pn_application_static.loading_images.remove(this.data); t.loaded(this.data.size); t.next_image(); document.body.removeChild(this); };
		i.onerror = function() { t.loaded(this.data); t.next_image(); document.body.removeChild(this); };
		i.src = image.url;
		i.style.position = "fixed";
		i.style.top = "-10000px";
		document.body.appendChild(i);
	};
	
	if (!window.top.pn_application_static) {
		window.top.pn_application_static = {
			total_size: 10000,
			loaded_size: 0,
			loading_scripts: [],
			loading_images: []
		};
		require("service.js",function(){
			service.json("application","get_static_resources",{},function(res){
				window.top.pn_application_static.scripts = res.scripts;
				window.top.pn_application_static.images = res.images;
				var start_size = window.top.pn_application_static.total_size;
				for (var i = 0; i < res.scripts.length; ++i) window.top.pn_application_static.total_size += res.scripts[i].size;
				for (var i = 0; i < res.images.length; ++i) window.top.pn_application_static.total_size += res.images[i].size;
				t.loaded(start_size);
				for (var i = 0; i < 20; ++i)
					t.next_image();
				for (var i = 0; i < 10; ++i)
					t.next_script();
			});
		});
	} else {
		t.loaded(0);
		for (var i = 0; i < window.top.pn_application_static.loading_scripts.length; ++i)
			window.top.pn_application_static.scripts.push(window.top.pn_application_static.loading_scripts[i]);
		window.top.pn_application_static.loading_scripts = [];
		for (var i = 0; i < window.top.pn_application_static.loading_images.length; ++i)
			window.top.pn_application_static.images.push(window.top.pn_application_static.loading_images[i]);
		window.top.pn_application_static.loading_images = [];
		for (var i = 0; i < 20; ++i)
			t.next_image();
		for (var i = 0; i < 10; ++i)
			t.next_script();
	}
	
}