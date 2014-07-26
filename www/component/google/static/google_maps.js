function loadGoogleMaps(callback) {
	if (!window.top.googleMapsLoaded) {
		if (!window.top.initGoogleMaps) {
			window.top.initGoogleMapsEvent = new Custom_Event();
			window.top.initGoogleMapsEvent.add_listener(callback);
			window.top.googleMapsLoaded = false;
			window.top.initGoogleMaps = function() {
				window.top.googleMapsLoaded = true;
				window.top.initGoogleMapsEvent.fire();
			};
			window.top.addJavascript("http://maps.googleapis.com/maps/api/js?v=3.exp&libraries=places&sensor=false&callback=initGoogleMaps&key=AIzaSyBy-4f3HsbxvXJ6sULM87k35JrsGSGs3q8");
		} else
			window.top.initGoogleMapsEvent.add_listener(callback);
	} else
		callback();
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
	this.fitToBounds = function(south, west, north, east) {
		if (this._current_move) clearTimeout(this._current_move);
		this._current_move = null;
		var cur_bounds = this.map.getBounds();
		var cur_center = this.map.getCenter();
		if (cur_center.lat() == 0 || cur_center.lng() == 0) {
			var t=this;
			this.map.fitBounds(this.createBounds(south, west, north, east));
			this._current_move = setTimeout(function() {
				var cur_center = t.map.getCenter();
				if (cur_center.lat() == 0 || cur_center.lng() == 0)
					t.map.fitBounds(t.createBounds(south, west, north, east));
			},1000);
			return;
		}
		if (cur_bounds) {
			var t=this;
			var cur_south = cur_bounds.getSouthWest().lat();
			var cur_west = cur_bounds.getSouthWest().lng();
			var cur_north = cur_bounds.getNorthEast().lat();
			var cur_east = cur_bounds.getNorthEast().lng();
			var cur_width = cur_north - cur_south;
			var cur_height = cur_east - cur_west;
			var cur_zoom = this.map.getZoom();
			var new_center = new window.top.google.maps.LatLng(south+(north-south)/2, west+(east-west)/2);
			if (new_center.lat() < cur_south || new_center.lat() > cur_north) {
				var lat = new_center.lat() < cur_south ? cur_south : cur_north;
				var lng;
				if (new_center.lng() < cur_west) lng = cur_west;
				else if (new_center.lng() > cur_east) lng = cur_east;
				else lng = new_center.lng();
				this.map.setCenter(new window.top.google.maps.LatLng(lat,lng));
				if (cur_zoom > 1)
					this.map.setZoom(cur_zoom-1);
				this._current_move = setTimeout(function() {
					t.fitToBounds(south, west, north, east);
				},400);
				return;
			} else if (new_center.lng() < cur_west || new_center.lng() > cur_east) {
				var lng = new_center.lng() < cur_west ? cur_west : cur_east;
				this.map.setCenter(new window.top.google.maps.LatLng(new_center.lat(),lng));
				if (cur_zoom > 1)
					this.map.setZoom(cur_zoom-1);
				this._current_move = setTimeout(function() {
					t.fitToBounds(south, west, north, east);
				},400);
				return;
			}
			var new_width = north-south;
			var new_height = east-west;
			if (new_width < cur_width && new_height < cur_height) {
				// we are zooming in
				if (new_width < cur_width*0.1 || new_height < cur_height*0.1) {
					// we are zooming a lot, let's do it in several step
					this.map.setCenter(new_center);
					this._current_move = setTimeout(function() {
						t.map.setZoom(cur_zoom+1);
						this._current_move = setTimeout(function() {
							t.fitToBounds(south,west,north,east);
						},300);
					},50);
					return;
				}
			} else {
				// we are zooming out
				if (new_width > cur_width*2 || new_height > cur_height*2) {
					// we are zooming a lot, let's do it in several step
					this.map.setCenter(new_center);
					this._current_move = setTimeout(function() {
						t.map.setZoom(cur_zoom-1);
						this._current_move = setTimeout(function() {
							t.fitToBounds(south,west,north,east);
						},300);
					},50);
					return;
				}
			}
		}
		this.map.fitBounds(this.createBounds(south, west, north, east));
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
		this.map.fitBounds(bounds);
	};
	
	this.addShape = function(shape) {
		this.shapes.push(shape);
		shape.setMap(this.map);
		this.fitToShapes();
	};
	this.addShapes = function(shapes) {
		for (var i = 0; i < shapes.length; ++i) {
			this.shapes.push(shapes[i]);
			shapes[i].setMap(this.map);
		}
		this.fitToShapes();
	};
	this.removeShape = function(shape) {
		this.shapes.remove(shape);
		shape.setMap(null);
		this.fitToShapes();
	};
	this.removeShapes = function(shapes) {
		for (var i = 0; i < shapes.length; ++i) {
			this.shapes.remove(shapes[i]);
			shapes[i].setMap(null);
		}
		this.fitToShapes();
	};
	
	this.addRect = function(south, west, north, east, borderColor, fillColor, fillOpacity) {
		var rect = new window.top.google.maps.Rectangle({
			bounds: this.createBounds(south, west, north, east),
			strokeColor: borderColor,
			strokeWeight: 2,
			strokeOpacity: 0.8,
			fillColor: fillColor,
			fillOpacity: fillOpacity,
			editable: false
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
	
	this._init = function() {
		loadGoogleMaps(function() {
			t.map = new window.top.google.maps.Map(container, { 
				center: new window.top.google.maps.LatLng(0, 0), 
				zoom: 0 
			});
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
			onready(t);
		});
	};
	this._init();
}

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