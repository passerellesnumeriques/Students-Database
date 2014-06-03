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
	
	this.createBounds = function(south, west, north, east) {
		return new window.top.google.maps.LatLngBounds(
			new window.top.google.maps.LatLng(south, west),
			new window.top.google.maps.LatLng(north, east)
		);
	};
	
	this.fitToBounds = function(south, west, north, east) {
		this.map.fitBounds(this.createBounds(south, west, north, east));
	};
	
	this.fitToShapes = function() {
		if (this.shapes.length == 0) return;
		var bounds = this.shapes[0].getBounds();
		for (var i = 1; i < this.shapes.length; ++i)
			bounds = maxBounds(bounds, this.shapes[i].getBounds());
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
	
	this._init = function() {
		loadGoogleMaps(function() {
			t.map = new window.top.google.maps.Map(container, { 
				center: new window.top.google.maps.LatLng(0, 0), 
				zoom: 0 
			});
			onready(t);
		});
	};
	this._init();
}

function maxBounds(b1,b2) {
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