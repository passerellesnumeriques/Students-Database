/**
 * Launch the background loading of all static resources of the application.
 * @param {DOMNode|string} container the html element where to insert the progress bar, or its ID
 */
function load_static_resources(container) {
	if (typeof container == 'string') container = document.getElementById(container);
	if (window.top.pn_application_static_loaded) {
		container.style.visibility = 'hidden';
		return;
	}
	
	var t=this;
	/** indicates if the background loading must be _stopped, meaning nothing will be loaded anymore */
	this._stopped = false;
	/** time when the process started. Used to calculate the time spent on loading resources. */
	this._start_time = new Date();
	
	container.appendChild(document.createTextNode("Loading application"));
	/** HTML Element representing the progress bar */
	this._pc_container = document.createElement("DIV");
	this._pc_container.style.display = "inline-block";
	this._pc_container.style.height = "8px";
	this._pc_container.style.width = "250px";
	this._pc_container.style.border = "1px solid #808080";
	this._pc_container.style.marginLeft = "5px";
	container.appendChild(this._pc_container);
	/** HTML Element, inside <code>_pc_container</code> representing the progress in the progress bar */
	this._pc = document.createElement("DIV");
	this._pc.style.width = "0px";
	this._pc.style.height = "8px";
	this._pc.style.backgroundColor = "#D0D0FF";
	this._pc_container.appendChild(this._pc);

	/** Called when a resources has been loaded.
	 * @param {Number} size size of the resources loaded, in bytes 
	 **/
	this.loaded = function(size) {
		if (t._stopped) return;
		window.top.pn_application_static.loaded_size += size;
		var pc = Math.floor(window.top.pn_application_static.loaded_size*250/window.top.pn_application_static.total_size);
		t._pc.style.width = pc+"px";
	};
	/** Called each time something has been done, to check if this is the end. */
	this._checkEnd = function() {
		if (!t._stopped && window.top.pn_application_static.scripts.length > 0) return;
		if (!t._stopped && window.top.pn_application_static.images.length > 0) return;
		if (window.top.pn_application_static && window.top.pn_application_static.scripts && window.top.pn_application_static.scripts.length == 0 && window.top.pn_application_static.images.length == 0)
			window.top.pn_application_static_loaded = true;
		container.innerHTML = "Application loaded.";
		require("animation.js",function() {
			animation.fadeOut(container, 1000);
		});
	};
	/** Stop to load resources. */
	this.stop = function() {
		this._stopped = true;
		this._checkEnd();
	};
	
	/** Start to load another script */
	this._nextScript = function() {
		if (window.top.pnapplication && window.top.pnapplication.last_activity > new Date().getTime()-3000) {
			container.style.color = "#808080";
			setTimeout(t._nextScript);
			return;
		}
		if (t._stopped) return;
		container.style.color = "#000000";
		var script = null;
		for (var i = 0; i < window.top.pn_application_static.scripts.length; ++i)
			if (window.top.pn_application_static.scripts[i].dependencies.length == 0) {
				script = window.top.pn_application_static.scripts[i];
				window.top.pn_application_static.loading_scripts.push(script);
				window.top.pn_application_static.scripts.splice(i,1);
				break;
			}
		if (script == null) { t._checkEnd(); return; }
		add_javascript(script.url,function() {
			t.loaded(script.size);
			for (var i = 0; i < window.top.pn_application_static.scripts.length; ++i)
				window.top.pn_application_static.scripts[i].dependencies.remove(script.url);
			window.top.pn_application_static.loading_scripts.remove(script);
			t._nextScript();
		});
	};
	/** Start to load another image */
	this._nextImage = function() {
		if (window.top.pnapplication && window.top.pnapplication.last_activity > new Date().getTime()-3000) {
			container.style.color = "#808080";
			setTimeout(t._nextImage);
			return;
		}
		if (t._stopped) return;
		container.style.color = "#000000";
		if (window.top.pn_application_static.images.length == 0) { t._checkEnd(); return; }
		var image = window.top.pn_application_static.images[0];
		window.top.pn_application_static.images.splice(0,1);
		window.top.pn_application_static.loading_images.push(image);
		var i = document.createElement("IMG");
		i.data = image;
		i.onload = function() { window.top.pn_application_static.loading_images.remove(this.data); t.loaded(this.data.size); t._nextImage(); document.body.removeChild(this); };
		i.onerror = function() { t.loaded(this.data); t._nextImage(); document.body.removeChild(this); };
		i.src = image.url;
		i.style.position = "fixed";
		i.style.top = "-10000px";
		document.body.appendChild(i);
	};
	
	if (!window.top.pn_application_static || !window.top.pn_application_static.service_done) {
		window.top.pn_application_static = {
			total_size: 10000,
			loaded_size: 0,
			loading_scripts: [],
			loading_images: [],
			service_done: false
		};
		require("service.js",function(){
			service.json("application","get_static_resources",{},function(res){
				if (!window.top.pn_application_static) return;
				if (!res) return;
				window.top.pn_application_static.service_done = true;
				window.top.pn_application_static.scripts = res.scripts;
				window.top.pn_application_static.images = res.images;
				var start_size = window.top.pn_application_static.total_size;
				for (var i = 0; i < res.scripts.length; ++i) window.top.pn_application_static.total_size += res.scripts[i].size;
				for (var i = 0; i < res.images.length; ++i) window.top.pn_application_static.total_size += res.images[i].size;
				t.loaded(start_size);
				for (var i = 0; i < 20; ++i)
					t._nextImage();
				for (var i = 0; i < 10; ++i)
					t._nextScript();
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
			t._nextImage();
		for (var i = 0; i < 10; ++i)
			t._nextScript();
	}
	
}