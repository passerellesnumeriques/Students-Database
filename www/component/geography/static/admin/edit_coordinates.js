function EditCoordinatesWithMap(container, coordinates, onready) {
	this.element = document.createElement("DIV");
	container.appendChild(this.element);
	var t=this;
	
	// we need a map, and edit coordinates
	this.map = null;
	this.coord = null;
	var check_ready = function() {
		if (t.map && t.coord) {
			t.linkECWithMap = new linkEditCoordinatesWithMap(t.coord, t.map);
			onready(t);
		}
	};
	new EditCoordinates(this.element, coordinates, function(ec) {
		t.coord = ec;
		check_ready();
	});
	var map_container = document.createElement("DIV");
	map_container.style.width = "400px";
	map_container.style.height = "300px";
	this.element.appendChild(map_container);
	window.top.google.loadGoogleMap(map_container, function(m) {
		t.map = m;
		check_ready();
	});
}

function EditCoordinates(container, coordinates, onready) {
	
	this.field_north = null;
	this.field_south = null;
	this.field_west = null;
	this.field_east = null;
	
	this.element = document.createElement("DIV");
	container.appendChild(this.element);
	
	this.setCoordinates = function(north, east, south, west) {
		coordinates.north = north;
		coordinates.east = east;
		coordinates.south = south;
		coordinates.west = west;
		if (this.field_north) {
			this.field_north.setData(north);
			this.field_east.setData(east);
			this.field_south.setData(south);
			this.field_west.setData(west);
		}
	};
	
	var t=this;
	require([["typed_field.js","field_decimal.js"]], function() {
		t.element.appendChild(document.createTextNode("North "));
		t.field_north = new field_decimal(coordinates.north, true, {can_be_null:true,integer_digits:3,decimal_digits:6,min:-90,max:90});
		t.element.appendChild(t.field_north.getHTMLElement());
		t.field_north.onchange.addListener(function() { coordinates.north = t.field_north.getCurrentData(); });
		
		t.element.appendChild(document.createTextNode("East "));
		t.field_east = new field_decimal(coordinates.east, true, {can_be_null:true,integer_digits:3,decimal_digits:6,min:-180,max:180});
		t.element.appendChild(t.field_east.getHTMLElement());
		t.field_east.onchange.addListener(function() { coordinates.east = t.field_east.getCurrentData(); });

		t.element.appendChild(document.createTextNode("South "));
		t.field_south = new field_decimal(coordinates.south, true, {can_be_null:true,integer_digits:3,decimal_digits:6,min:-90,max:90});
		t.element.appendChild(t.field_south.getHTMLElement());
		t.field_south.onchange.addListener(function() { coordinates.south = t.field_south.getCurrentData(); });

		t.element.appendChild(document.createTextNode("West "));
		t.field_west = new field_decimal(coordinates.west, true, {can_be_null:true,integer_digits:3,decimal_digits:6,min:-180,max:180});
		t.element.appendChild(t.field_west.getHTMLElement());
		t.field_west.onchange.addListener(function() { coordinates.west = t.field_west.getCurrentData(); });
		
		onready(t);
	});
}

function linkEditCoordinatesWithMap(edit_coordinates, map) {
	this.map_rect = null;
	this.updateMap = function() {
		var north = edit_coordinates.field_north.getCurrentData();
		var east = edit_coordinates.field_east.getCurrentData();
		var south = edit_coordinates.field_south.getCurrentData();
		var west = edit_coordinates.field_west.getCurrentData();
		if (north && south && west && east) {
			// it is set
			if (this.map_rect == null) {
				// first time
				this.map_rect = new window.top.google.maps.Rectangle({
					bounds: map.createBounds(south, west, north, east),
					strokeColor: "#6060F0",
					strokeWeight: 2,
					strokeOpacity: 0.8,
					fillColor: "#D0D0F0",
					fillOpacity: 0.3,
					editable: true,
				});
				var t=this;
				window.top.google.maps.event.addListener(this.map_rect, 'bounds_changed', function() {
					var bounds = t.map_rect.getBounds();
					edit_coordinates.setCoordinates(bounds.getNorthEast().lat(), bounds.getNorthEast().lng(), bounds.getSouthWest().lat(), bounds.getSouthWest().lng());
				});
				map.addShape(this.map_rect);
				map.fitToShapes();
			} else {
				var bounds = this.map_rect.getBounds();
				if (bounds.getNorthEast().lat() != north ||
					bounds.getNorthEast().lng() != east ||
					bounds.getSouthWest().lat() != south ||
					bounds.getSouthWest().lng() != west) {
					// it changed
					this.map_rect.setBounds(map.createBounds(south, west, north, east));
					map.fitToShapes();
				}
			}
		} else {
			// not set
			if (this.map_rect != null) {
				map.removeShape(this.map_rect);
				this.map_rect = null;
			}
		}
	};
	this.addResetOriginalButton = function(original) {
		var original_button = document.createElement("BUTTON");
		original_button.className = "action";
		original_button.innerHTML = "Back to original";
		original_button.onclick = function() { edit_coordinates.setCoordinates(original.north,original.east,original.south,original.west); };
		this._buttons.appendChild(original_button);
	};
	this._init = function() {
		this._buttons = document.createElement("DIV");
		var reset_button = document.createElement("BUTTON");
		reset_button.className = "action red";
		reset_button.innerHTML = "Reset";
		reset_button.onclick = function() { edit_coordinates.setCoordinates(null,null,null,null); };
		this._buttons.appendChild(reset_button);
		var map_button = document.createElement("BUTTON");
		map_button.className = "action";
		map_button.innerHTML = "Use map bounds";
		map_button.onclick = function() {
			var bounds = map.map.getBounds();
			edit_coordinates.setCoordinates(bounds.getNorthEast().lat(), bounds.getNorthEast().lng(), bounds.getSouthWest().lat(), bounds.getSouthWest().lng());
		};
		this._buttons.appendChild(map_button);
		var encompassing_button = document.createElement("BUTTON");
		encompassing_button.className = "action";
		encompassing_button.innerHTML = "Encompassing areas";
		encompassing_button.onclick = function() {
			if (map.shapes.length == 0) return;
			var bounds = map.shapes[0].getBounds();
			for (var i = 1; i < map.shapes.length; ++i)
				bounds = window.top.maxGoogleBounds(bounds, map.shapes[i].getBounds());
			edit_coordinates.setCoordinates(bounds.getNorthEast().lat(), bounds.getNorthEast().lng(), bounds.getSouthWest().lat(), bounds.getSouthWest().lng());
		};
		this._buttons.appendChild(encompassing_button);
		edit_coordinates.element.appendChild(this._buttons);
	};
	this._init();
	this.updateMap();
	var t=this;
	edit_coordinates.field_north.onchange.addListener(function() { t.updateMap(); });
	edit_coordinates.field_east.onchange.addListener(function() { t.updateMap(); });
	edit_coordinates.field_south.onchange.addListener(function() { t.updateMap(); });
	edit_coordinates.field_west.onchange.addListener(function() { t.updateMap(); });
}
