function loadGoogleMaps(callback) {
	var loadMaps = function() {
		if (!window.top.googleMapsLoaded) {
			if (!window.top.initGoogleMaps) {
				if (!window.top.initGoogleMapsEvent)
					window.top.initGoogleMapsEvent = new Custom_Event();
				window.top.initGoogleMapsEvent.addListener(callback);
				window.top.googleMapsLoaded = false;
				window.top.initGoogleMaps = function() {
					window.top.googleMapsLoaded = true;
					window.top.initGoogleMapsEvent.fire();
				};
				setTimeout(function() {
					if (window.top.googleMapsLoaded) return;
					window.top.removeJavascript("http://maps.googleapis.com/maps/api/js?v=3.exp&libraries=places&sensor=false&callback=initGoogleMaps&key="+window.top.google._api_key);
					if (window.top.googleMapJS.parentNode)
						window.top.googleMapJS.parentNode.removeChild(window.top.googleMapJS);
					window.top.googleMapJS = null;
					window.top.initGoogleMaps = null;
					window.top.loadGoogleMaps(function(){});
				},10000);
				window.top.googleMapJS = window.top.addJavascript("http://maps.googleapis.com/maps/api/js?v=3.exp&libraries=places&sensor=false&callback=initGoogleMaps&key="+window.top.google._api_key);
			} else
				window.top.initGoogleMapsEvent.addListener(callback);
		} else
			callback();
	};
	if (!window.top.google)
		window.top.addJavascript("/static/google/google.js", function() { loadMaps(); });
	else
		loadMaps();
}

function GoogleMap(container, onready) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	this.map = null;
	this.shapes = [];
	
	this.getShapeBounds = function(shape) {
		if (typeof shape.getBounds != 'undefined') return shape.getBounds();
		if (typeof shape.getPosition != 'undefined') {
			var pos = shape.getPosition();
			return this.createBounds(pos.lat(), pos.lng(), pos.lat(), pos.lng());
		}
		return null;
	};
	
	this.createBounds = function(south, west, north, east) {
		return new window.top.google.maps.LatLngBounds(
			new window.top.google.maps.LatLng(south, west),
			new window.top.google.maps.LatLng(north, east)
		);
	};
	
	this._current_move = null;
	this._moving = [];
	this.fitToBounds = function(south, west, north, east, trial) {
		if (this._current_move) clearTimeout(this._current_move);
		if (!t) return;
		for (var i = 0; i < this._moving.length; ++i) this._moving[i].stop = true;
		this._current_move = null;
		var cur_bounds = this.map.getBounds();
		var cur_center = this.map.getCenter();
		if (cur_center.lat() == 0 || cur_center.lng() == 0) {
			this.map.fitBounds(this.createBounds(south, west, north, east));
			this.map.setCenter(new window.top.google.maps.LatLng(south+(north-south)/2, west+(east-west)/2));
			return;
		}
		if (cur_bounds) {
			var cur_south = cur_bounds.getSouthWest().lat();
			var cur_west = cur_bounds.getSouthWest().lng();
			var cur_north = cur_bounds.getNorthEast().lat();
			var cur_east = cur_bounds.getNorthEast().lng();
			var cur_width = cur_north - cur_south;
			var cur_height = cur_east - cur_west;
			if (cur_width < 0 || cur_height < 0) {
				if (!trial) trial = 0;
				if (trial < 3)
					this._current_move = setTimeout(function() { t.fitToBounds(south, west, north, east, trial+1);}, 250);
				else {
					this.map.fitBounds(this.createBounds(south, west, north, east));
					this.map.setCenter(new window.top.google.maps.LatLng(south+(north-south)/2, west+(east-west)/2));
				}
				return;
			} 
			var cur_zoom = this.map.getZoom();
			var new_center = new window.top.google.maps.LatLng(south+(north-south)/2, west+(east-west)/2);
			if (new_center.lat() < cur_south || new_center.lat() > cur_north || new_center.lng() < cur_west || new_center.lng() > cur_east) {
				// we are going outside, let's do it step by step
				var lat = new_center.lat() < cur_south ? cur_south+(cur_north-cur_south)/10 : new_center.lat() > cur_north ? cur_north-(cur_north-cur_south)/10 : new_center.lat();
				var moving = {stop:false};
				this._moving.push(moving);
				var lng = new_center.lng() < cur_west ? cur_west+(cur_east-cur_west)/10 : new_center.lng() > cur_east ? cur_east-(cur_east-cur_west)/10 : new_center.lng();
				t.onNextIdle(function() {
					if (t) {
						t._moving.removeUnique(moving);
						if (moving.stop) return;
						t.fitToBounds(south, west, north, east);
					}
				},1000);
				this.map.panTo(new window.top.google.maps.LatLng(lat,lng));
				if (cur_zoom > 1 && (new_center.lat() < lat-cur_width/2 || new_center.lat() > lat+cur_width/2 || new_center.lng() < lng-cur_height/2 || new_center.lng() > lng+(cur_height/2)))
					this.map.setZoom(cur_zoom-1);
				return;
			}
			var new_width = north-south;
			var new_height = east-west;
			if (new_width < cur_width && new_height < cur_height) {
				// we are zooming in
				if (new_width < cur_width*0.1 || new_height < cur_height*0.1) {
					// we are zooming a lot, let's do it in several step
					var moving = {stop:false};
					this._moving.push(moving);
					t.onNextIdle(function() {
						if (t) {
							t._moving.removeUnique(moving);
							if (moving.stop) return;
							t.fitToBounds(south,west,north,east);
						}
					},1000);
					t.map.setZoom(cur_zoom+1);
					t.map.setCenter(new_center);
					return;
				} else {
					//t.onNextIdle(function() {
					//	t.map.fitBounds(t.createBounds(south, west, north, east));
					//},300);
					//t.map.panToBounds(t.createBounds(south, west, north, east));
					//return;
				}
			} else if (new_width > cur_width && new_height > cur_height) {
				// we are zooming out
				if (new_width > cur_width*2 || new_height > cur_height*2) {
					// we are zooming a lot, let's do it in several step
					var moving = {stop:false};
					this._moving.push(moving);
					t.onNextIdle(function() {
						if (t) {
							t._moving.removeUnique(moving);
							if (moving.stop) return;
							t.fitToBounds(south,west,north,east);
						}
					},1000);
					t.map.setZoom(cur_zoom-1);
					t.map.setCenter(new_center);
					return;
				} else {
					//t.onNextIdle(function() {
					//	if (t) t.map.fitBounds(t.createBounds(south, west, north, east));
					//},300);
					//t.map.panToBounds(t.createBounds(south, west, north, east));
					//return;
				}
			} else {
				//t.onNextIdle(function() {
				//	if (t) t.map.fitBounds(t.createBounds(south, west, north, east));
				//},300);
				//t.map.panToBounds(t.createBounds(south, west, north, east));
				//return;
			}
		}
		this.map.fitBounds(this.createBounds(south, west, north, east));
		this.map.setCenter(new window.top.google.maps.LatLng(south+(north-south)/2, west+(east-west)/2));
	};
	
	this.fitToShapes = function() {
		if (this.shapes.length == 0) return;
		var bounds = this.getShapeBounds(this.shapes[0]);
		for (var i = 1; i < this.shapes.length; ++i)
			bounds = maxGoogleBounds(bounds, this.getShapeBounds(this.shapes[i]));
		var diff = bounds.getNorthEast().lat() - bounds.getSouthWest().lat();
		if (diff < 0.01) {
			bounds = new window.top.google.maps.LatLngBounds(
				new window.top.google.maps.LatLng(bounds.getSouthWest().lat()-(0.01-diff)/2, bounds.getSouthWest().lng()),
				new window.top.google.maps.LatLng(bounds.getNorthEast().lat()+(0.01-diff)/2, bounds.getNorthEast().lng())
			);
		}
		diff = bounds.getNorthEast().lng() - bounds.getSouthWest().lng();
		if (diff < 0.01) {
			bounds = new window.top.google.maps.LatLngBounds(
				new window.top.google.maps.LatLng(bounds.getSouthWest().lat(), bounds.getSouthWest().lng()-(0.01-diff)/2),
				new window.top.google.maps.LatLng(bounds.getNorthEast().lat(), bounds.getNorthEast().lng()+(0.01-diff)/2)
			);
		}
		this.fitToBounds(bounds.getSouthWest().lat(), bounds.getSouthWest().lng(), bounds.getNorthEast().lat(), bounds.getNorthEast().lng());
	};
	
	this.zoomOnShape = function(shape) {
		var bounds = this.getShapeBounds(shape);
		var diff = bounds.getNorthEast().lat() - bounds.getSouthWest().lat();
		if (diff < 0.01) {
			bounds = new window.top.google.maps.LatLngBounds(
				new window.top.google.maps.LatLng(bounds.getSouthWest().lat()-(0.01-diff)/2, bounds.getSouthWest().lng()),
				new window.top.google.maps.LatLng(bounds.getNorthEast().lat()+(0.01-diff)/2, bounds.getNorthEast().lng())
			);
		}
		diff = bounds.getNorthEast().lng() - bounds.getSouthWest().lng();
		if (diff < 0.01) {
			bounds = new window.top.google.maps.LatLngBounds(
				new window.top.google.maps.LatLng(bounds.getSouthWest().lat(), bounds.getSouthWest().lng()-(0.01-diff)/2),
				new window.top.google.maps.LatLng(bounds.getNorthEast().lat(), bounds.getNorthEast().lng()+(0.01-diff)/2)
			);
		}
		this.fitToBounds(bounds.getSouthWest().lat(), bounds.getSouthWest().lng(), bounds.getNorthEast().lat(), bounds.getNorthEast().lng());
	};
	
	this.addShape = function(shape) {
		this.shapes.push(shape);
		shape.setMap(this.map);
	};
	this.addShapes = function(shapes) {
		for (var i = 0; i < shapes.length; ++i) {
			this.shapes.push(shapes[i]);
			shapes[i].setMap(this.map);
		}
	};
	this.removeShape = function(shape) {
		this.shapes.remove(shape);
		shape.setMap(null);
	};
	this.removeShapes = function(shapes) {
		for (var i = 0; i < shapes.length; ++i) {
			this.shapes.remove(shapes[i]);
			shapes[i].setMap(null);
		}
	};
	
	this.addRect = function(south, west, north, east, borderColor, fillColor, fillOpacity) {
		var rect = new window.top.google.maps.Rectangle({
			bounds: this.createBounds(south, west, north, east),
			strokeColor: borderColor,
			strokeWeight: 2,
			strokeOpacity: 0.8,
			fillColor: fillColor,
			fillOpacity: fillOpacity,
			editable: false,
			clickable: false
		});
		this.addShape(rect);
		return rect;
	};
	this.addMarker = function(lat, lng, opacity, title) {
		var m = new window.top.google.maps.Marker({
			//clickable: false,
			//crossOnDrag: false,
			opacity: opacity,
			position: new window.top.google.maps.LatLng(lat, lng),
			//visible: true,
			title: title
		});
		this.addShape(m);
		return m;
	};
	this.addPNMarker = function(lat, lng, color, content) {
		var m = new PNMapMarker(lat, lng, color, content);
		this.addShape(m);
		return m;
	};
	
	this.onNextIdle = function(listener, timeout) {
		window.top.google.maps.event.addListenerOnce(this.map, 'idle', function() {
			if (!t) return;
			var done = false;
			window.top.google.maps.event.addListenerOnce(t.map, 'tilesloaded', function() {
				if (!t) return;
				if (done) return;
				done = true;
				listener();
			});
			if (timeout) setTimeout(function() {
				if (!t) return;
				if (done) return;
				done = true;
				listener();
			},timeout);
		});
	};
	
	this._init = function() {
		loadGoogleMaps(function() {
			t.map = new window.top.google.maps.Map(container, { 
				center: new window.top.google.maps.LatLng(0, 0), 
				zoom: 0 
			});
			if (window.top.default_country_bounds)
				t.map.fitBounds(t.createBounds(window.top.default_country_bounds.south, window.top.default_country_bounds.west, window.top.default_country_bounds.north, window.top.default_country_bounds.east));
			var div = document.createElement("DIV");
			var button = document.createElement("BUTTON");
			button.className = "flat";
			button.innerHTML = "<img src='"+theme.icons_16.window_popup+"'/>";
			button.title = "Open map in new window";
			div.appendChild(button);
			div.style.marginRight = "2px";
			button.onclick = function() {
				var init_map = function(win) {
					if (!win.document || !win.document.body || !win.document.getElementById('map_container')) {
						setTimeout(function() { init_map(win); }, 100);
						return;
					}
					win.map = new window.top.google.maps.Map(win.document.getElementById('map_container'), {
						center: t.map.getCenter(),
						zoom: t.map.getZoom()
					});
					for (var i = 0; i < t.shapes.length; ++i) {
						var shape = t.shapes[i];
						if (shape instanceof window.top.google.maps.Marker) {
							new window.top.google.maps.Marker({
								opacity: shape.getOpacity(),
								position: shape.getPosition(),
								map:win.map
							});
						}
						// TODO add other shapes
					}
				};
				init_map(window.open("/static/google/google_big_map.html"));
			};
			t.map.controls[window.top.google.maps.ControlPosition.RIGHT_TOP].push(div);
			layout.listenElementSizeChanged(container, function() {
				if (!t) return;
				window.top.google.maps.event.trigger(t.map, 'resize');
			});
			window.top.google.maps.event.trigger(t.map, 'resize');
			window.top.google.maps.event.addListenerOnce(t.map, "tilesloaded", function() {
				if (!t) return;
				window.top.google.maps.event.trigger(t.map, 'resize');
				onready(t);
			});
			container.ondomremoved(function() {
				t.map = null;
				t.shapes = null;
				t = null;
			});
		});
	};
	this._init();
}

function PNMapMarker(lat, lng, color, content) {
	this._lat = lat;
	this._lng = lng;
	this._color = color;
	if (typeof content == 'string') {
		var div = document.createElement("DIV");
		div.appendChild(document.createTextNode(content));
		content = div;
	}
	this._content = content;
}
loadGoogleMaps(function() {
	PNMapMarker.prototype = new window.top.google.maps.OverlayView();
	PNMapMarker.prototype.onAdd = function() {
		var div = document.createElement("DIV");
		div.style.position = "absolute";
		var content = document.createElement("DIV");
		content.style.backgroundColor = this._color;
		content.style.border = "1px solid black";
		setBorderRadius(content,3,3,3,3,3,3,3,3);
		content.appendChild(this._content);
		div.appendChild(content);
		this._arrow = document.createElement("CANVAS");
		this._arrow.style.position = "absolute";
		this._arrow.style.width = "15px";
		this._arrow.style.height = "7px";
		this._arrow.width = "15";
		this._arrow.height = "7";
		var ctx = this._arrow.getContext("2d");
		ctx.fillStyle = this._color;
		ctx.beginPath();
		ctx.moveTo(0,0);
		ctx.lineTo(7,6);
		ctx.lineTo(14,0);
		ctx.closePath();
		ctx.fill();
		ctx.strokeStyle = "1px solid black";
		ctx.beginPath();
		ctx.moveTo(0,0);
		ctx.lineTo(7,6);
		ctx.lineTo(14,0);
		ctx.stroke();
		/*
		this._arrow = document.createElement("DIV");
		this._arrow.style.position = "absolute";
		this._arrow.style.display = "inline-block";
		this._arrow.style.borderLeft = "7px solid transparent";
		this._arrow.style.borderRight = "7px solid transparent";
		this._arrow.style.borderTop = "7px solid "+this._color;
		*/
		this._arrow.style.bottom = "0px";
		div.appendChild(this._arrow);
		content.style.marginBottom = "6px";
		this._div = div;
		this.getPanes().floatPane.appendChild(div);
		this._div.onmouseover = function() { this.style.zIndex = 10; };
		this._div.onmouseout = function() { this.style.zIndex = ""; };
	};
	PNMapMarker.prototype.draw = function() {
		var overlayProjection = this.getProjection();
		var pos = overlayProjection.fromLatLngToDivPixel(this.getPosition());
		this._div.style.top = (pos.y-this._div.offsetHeight)+"px";
		this._div.style.left = (pos.x-this._div.offsetWidth/2)+"px";
		this._arrow.style.left = (this._div.offsetWidth/2-7)+"px";
	};
	PNMapMarker.prototype.onRemove = function() {
		this._div.parentNode.removeChild(this._div);
		this._div = null;
	};
	PNMapMarker.prototype.getPosition = function() {
		return new window.top.google.maps.LatLng(this._lat, this._lng);
	};
	PNMapMarker.prototype.lat = function() { return this._lat; };
	PNMapMarker.prototype.lng = function() { return this._lng; };
	PNMapMarker.prototype.setPosition = function(lat,lng) {
		this._lat = lat;
		this._lng = lng;
	};
	PNMapMarker.prototype.setColor = function(color) {
		this._color = color;
		if (this._div) {
			this._div.childNodes[0].style.backgroundColor = color;
			var ctx = this._arrow.getContext("2d");
			ctx.fillStyle = color;
			ctx.beginPath();
			ctx.moveTo(0,0);
			ctx.lineTo(7,6);
			ctx.lineTo(14,0);
			ctx.closePath();
			ctx.fill();
			ctx.strokeStyle = "1px solid black";
			ctx.beginPath();
			ctx.moveTo(0,0);
			ctx.lineTo(7,6);
			ctx.lineTo(14,0);
			ctx.stroke();
		}
	};
	PNMapMarker.prototype.bringToFront = function() {
		if (this._div) this._div.style.zIndex = 10;
	};
	PNMapMarker.prototype.cancelBringToFront = function() {
		if (this._div) this._div.style.zIndex = "";
	};
});

function maxGoogleBounds(b1,b2) {
	var north1 = b1.getNorthEast().lat();
	var south1 = b1.getSouthWest().lat();
	var west1 = b1.getSouthWest().lng();
	var east1 = b1.getNorthEast().lng();
	var north2 = b2.getNorthEast().lat();
	var south2 = b2.getSouthWest().lat();
	var west2 = b2.getSouthWest().lng();
	var east2 = b2.getNorthEast().lng();
	return new window.top.google.maps.LatLngBounds(
		new window.top.google.maps.LatLng(Math.min(south1, south2), Math.min(west1, west2)),
		new window.top.google.maps.LatLng(Math.max(north1, north2), Math.max(east1, east2))
	);
}