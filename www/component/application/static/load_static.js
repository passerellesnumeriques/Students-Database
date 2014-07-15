/**
 * Launch the background loading of all static resources of the application.
 * @param {Element|String} container the html element where to insert the progress bar, or its ID
 * @param {String} bgcolor color of the progress bar
 * @param {String} border_color color of the border of the progress bar
 * @param {String} active_color color of text when the loading is active
 * @param {String} inactive_color color of the text when the loading is going slowly
 */
function load_static_resources(container, bgcolor, border_color, active_color, inactive_color) {
	if (typeof container == 'string') container = document.getElementById(container);
	if (!bgcolor) bgcolor = "#22bbea";
	if (!border_color) border_color = "#8080A0";
	if (!active_color) active_color = "#000000";
	if (!inactive_color) inactive_color = "#808080";
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
	this._pc_container.style.border = "1px solid "+border_color;
	setBorderRadius(this._pc_container,3,3,3,3,3,3,3,3);
	this._pc_container.style.marginLeft = "5px";
	container.appendChild(this._pc_container);
	/** HTML Element, inside <code>_pc_container</code> representing the progress in the progress bar */
	this._pc = document.createElement("DIV");
	this._pc.style.width = "0px";
	this._pc.style.height = "8px";
	this._pc.style.backgroundColor = bgcolor;
	this._pc_container.appendChild(this._pc);
	setBorderRadius(this._pc,3,3,3,3,3,3,3,3);

	/** Called when a resources has been loaded.
	 * @param {Number} size size of the resources loaded, in bytes 
	 **/
	this.loaded = function(size) {
		if (t._stopped) return;
		window.top.pn_application_static.loaded_size += size+512;
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

	/** {Number} number of scripts currently loading */
	this._scripts_loading = 0;
	/** {Number} maximum number of scripts that can be loaded at the same time */
	this._max_scripts_loading = 10;
	/** {Number} maximum number of scripts to be loaded at the same time when we load resources slowly */
	this._max_scripts_loading_slow = 2;
	/** {Number} number of images currently loading */
	this._images_loading = 0;
	/** {Number} maximum number of images that can be loaded at the same time */
	this._max_images_loading = 20;
	/** {Number} maximum number of images to be loaded at the same time when we load resources slowly */
	this._max_images_loading_slow = 2;
	
	/** {Boolean} indicates we should load slowly due to user activity */
	this._slow_when_user_active = false;
	/** {Boolean} indicates we should load slowly due to application activity (calls to services) */
	this._slow_when_services_active = false;
	
	/** Start to load another script */
	this._nextScript = function() {
		var slow = false;
		if (t._slow_when_user_active && window.top.pnapplication && window.top.pnapplication.last_activity > new Date().getTime()-3000) {
			slow = true;
		}
		if (!slow && t._slow_when_services_active && window.top._last_service_call && window.top._last_service_call > new Date().getTime()-3000) {
			slow = true;
		}
		if (slow) {
			container.style.color = inactive_color;
			setOpacity(t._pc, 0.25);
			if (t._scripts_loading >= t._max_scripts_loading_slow) {
				setTimeout(t._nextScript, 250);
				return;
			}
		} else {
			container.style.color = active_color;
			setOpacity(t._pc, 1);
		}
		if (t._stopped) return;
		if (!window.top.pn_application_static) return;
		var script = null;
		for (var i = 0; i < window.top.pn_application_static.scripts.length; ++i)
			if (window.top.pn_application_static.scripts[i].dependencies.length == 0) {
				script = window.top.pn_application_static.scripts[i];
				window.top.pn_application_static.loading_scripts.push(script);
				window.top.pn_application_static.scripts.splice(i,1);
				break;
			}
		if (script == null) {
			// check we don't have anymore scripts waiting for a dependency
			if (window.top.pn_application_static.scripts.length > 0) {
				for (var i = 0; i < window.top.pn_application_static.scripts.length; ++i) {
					var s = window.top.pn_application_static.scripts[i];
					var msg = "JavaScript file "+s.url+" cannot be loaded because it is still waiting for the following dependencies:\r\n";
					for (var j = 0; j < s.dependencies.length; ++j)
						msg += " - "+s.dependencies[j]+"\r\n";
					alert(msg);
				}
				// finalize
				window.top.pn_application_static.scripts = [];
			}
			t._checkEnd();
			return;
		}
		t._scripts_loading++;
		addJavascript(script.url,function() {
			t._scripts_loading--;
			t.loaded(script.size);
			for (var i = 0; i < window.top.pn_application_static.scripts.length; ++i)
				window.top.pn_application_static.scripts[i].dependencies.remove(script.url);
			window.top.pn_application_static._loaded_scripts.push(script.url);
			window.top.pn_application_static.loading_scripts.remove(script);
			t._nextScript();
		});
	};
	/** Start to load another image */
	this._nextImage = function() {
		var slow = false;
		if (t._slow_when_user_active && window.top.pnapplication && window.top.pnapplication.last_activity > new Date().getTime()-3000) {
			slow = true;
		}
		if (!slow && t._slow_when_services_active && window.top._last_service_call && window.top._last_service_call > new Date().getTime()-3000) {
			slow = true;
		}
		if (slow) {
			container.style.color = inactive_color;
			setOpacity(t._pc, 0.25);
			if (t._images_loading >= t._max_images_loading_slow) {
				setTimeout(t._nextImage, 250);
				return;
			}
		} else {
			container.style.color = active_color;
			setOpacity(t._pc, 1);
		}
		if (t._stopped) return;
		t._images_loading++;
		if (!window.top.pn_application_static) return;
		if (window.top.pn_application_static.images.length == 0) { t._checkEnd(); return; }
		var image = window.top.pn_application_static.images[0];
		window.top.pn_application_static.images.splice(0,1);
		window.top.pn_application_static.loading_images.push(image);
		var i = document.createElement("IMG");
		i.data = image;
		i.onload = function() { window.top.pn_application_static.loading_images.remove(this.data); t._images_loading--; t.loaded(this.data.size); t._nextImage(); try { document.body.removeChild(this); } catch (e){} };
		i.onerror = function() { t._images_loading--; t.loaded(this.data); t._nextImage(); document.body.removeChild(this); };
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
			service_done: false,
			_loaded_scripts: []
		};
		require("service.js",function(){
			service.json("application","get_static_resources",{},function(res){
				if (!window.top.pn_application_static) return;
				if (!res) return;
				window.top.pn_application_static.service_done = true;
				window.top.pn_application_static.scripts = res.scripts;
				window.top.pn_application_static.images = res.images;
				var start_size = window.top.pn_application_static.total_size;
				for (var i = 0; i < res.scripts.length; ++i) window.top.pn_application_static.total_size += res.scripts[i].size+512;
				for (var i = 0; i < res.images.length; ++i) window.top.pn_application_static.total_size += res.images[i].size+512;
				t.loaded(start_size);
				for (var i = 0; i < t._max_images_loading; ++i)
					t._nextImage();
				for (var i = 0; i < t._max_scripts_loading; ++i)
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
		for (var i = 0; i < window.top.pn_application_static._loaded_scripts.length; ++i)
			addJavascript(window.top.pn_application_static._loaded_scripts[i]);
		for (var i = 0; i < t._max_images_loading; ++i)
			t._nextImage();
		for (var i = 0; i < t._max_scripts_loading; ++i)
			t._nextScript();
	}
	
}