function load_static_resources(container) {
	if (typeof container == 'string') container = document.getElementById(container);
	
	var t=this;
	this.total_size = 10000;
	this.loaded_size = 0;
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
		t.loaded_size += size;
		var pc = Math.floor(t.loaded_size*250/t.total_size);
		t.pc.style.width = pc+"px";
	};
	this.check_end = function() {
		if (t.scripts.length > 0) return;
		if (t.images.length > 0) return;
		container.innerHTML = "Application loaded in "+Math.floor((new Date().getTime()-t.start_time.getTime())/1000)+"s.";
		require("animation.js",function() {
			animation.fadeOut(container, 1000);
		});
	};
	this.stop = function() {
		this.stopped = true;
		this.scripts = [];
		this.images = [];
		this.check_end();
	};
	
	this.next_script = function() {
		if (t.stopped) return;
		var script = null;
		for (var i = 0; i < t.scripts.length; ++i)
			if (t.scripts[i].dependencies.length == 0) {
				script = t.scripts[i];
				t.scripts.splice(i,1);
				break;
			}
		if (script == null) { t.check_end(); return; }
		add_javascript(script.url,function() {
			t.loaded(script.size);
			for (var i = 0; i < t.scripts.length; ++i)
				t.scripts[i].dependencies.remove(script.url);
			t.next_script();
		});
	};
	this.next_image = function() {
		if (t.stopped) return;
		if (t.images.length == 0) { t.check_end(); return; }
		var image = t.images[0];
		t.images.splice(0,1);
		var i = document.createElement("IMG");
		i.data = image.size;
		i.onload = function() { t.loaded(this.data); t.next_image(); document.body.removeChild(this); };
		i.onerror = function() { t.loaded(this.data); t.next_image(); document.body.removeChild(this); };
		i.src = image.url;
		i.style.position = "fixed";
		i.style.top = "-10000px";
		document.body.appendChild(i);
	};
	
	require("service.js",function(){
		service.json("application","get_static_resources",{},function(res){
			t.scripts = res.scripts;
			t.images = res.images;
			var start_size = t.total_size;
			for (var i = 0; i < t.scripts.length; ++i) t.total_size += t.scripts[i].size;
			for (var i = 0; i < t.images.length; ++i) t.total_size += t.images[i].size;
			t.loaded(start_size);
			for (var i = 0; i < 10; ++i) {
				t.next_script();
				t.next_image();
			}
		});
	});
	
	
}