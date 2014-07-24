function dialog_coordinates(country, country_data, division_index, area_index) {
	
	var t=this;
	this.popup = null;
	this.content = document.createElement("DIV");
	this.map = null;
	this.coordinates = null;
	this.division_index = division_index;
	this.area_index = area_index;
	
	this.reset = function(division_index, area_index) {
		this.division_index = division_index;
		this.area_index = area_index;
		var area;
		if (typeof division_index != 'undefined')
			area = country_data[division_index].areas[area_index];
		else
			area = country;
		if (!area.north) this.coordinates = { north: null, south: null, west: null, east: null };
		else this.coordinates = { north: area.north, south: area.south, west: area.west, east: area.east };
		this.content.removeAllChildren();
		// title: path of the area
		new AreaPathTitle(this, this.content, country, country_data, division_index, area_index);
		// split into 3 parts: coordinates and children on the left, the map in the center, and the searches on the right
		var table = document.createElement("TABLE"); this.content.appendChild(table);
		var tr = document.createElement("TR"); table.appendChild(tr);
		var td_left = document.createElement("TD"); tr.appendChild(td_left); td_left.style.verticalAlign = "top";
		var td_middle = document.createElement("TD"); tr.appendChild(td_middle); td_middle.style.verticalAlign = "top";
		var td_right = document.createElement("TD"); tr.appendChild(td_right); td_right.style.verticalAlign = "top";
		// first, create the map
		var map_container = document.createElement("DIV");
		map_container.style.width = "400px";
		map_container.style.height = "300px";
		td_middle.appendChild(map_container);
		require("google_maps.js", function() {
			t.map = new GoogleMap(map_container, function(map) {
				// left side
				new CoordinatesEdit(td_left, t.coordinates, map);
				new DisplayAreas(td_left, country, country_data, division_index, area_index, map);
				new ChildrenAreas(td_left, country, country_data, division_index, area_index, map);
				// right side
				// TODO
				// initialize the map
				if (area.north) {
					// already defined: let's set the map
					map.fitToBounds(area.south, area.west, area.north, area.east);
				} else if (typeof division_index != 'undefined'){
					// try to get first parent
					var p = area;
					do {
						p = window.top.geography.getParentArea(country_data, p);
						if (!p) break;
						if (p.north) {
							map.fitToBounds(p.south, p.west, p.north, p.east);
							break;
						}
					} while (true);
					if (!p && country.north) {
						// get the country
						map.fitToBounds(country.south, country.west, country.north, country.east);
					}
				}
			});
		});
	};
	require("popup_window.js", function() {
		t.popup = new popup_window("Geographic Coordinates", "/static/geography/geography_16.png", t.content);
		t.popup.show();
	});
	this.reset(division_index, area_index);
}

function createAreaLink(dialog, name, division_index, area_index, indicate_if_coordinates) {
	var link = document.createElement("A");
	link.href = "#";
	link.className = "black_link";
	link.appendChild(document.createTextNode(name));
	if (indicate_if_coordinates)
		link.appendChild(document.createTextNode(" ("+(t[division_index].areas[area_index].north ? "has" : "no")+" coord.)"));
	link.onclick = function() {
		dialog.reset(division_index,area_index);
		return false;
	};
	return link;
};

function AreaPathTitle(dialog, container, country, country_data, division_index, area_index) {
	var path = [];
	if (typeof division_index != 'undefined') {
		var area = country_data[division_index].areas[area_index];
		path.push({area:area,division_index:division_index,area_index:area_index});
		while (division_index > 0) {
			var parent = window.top.geography.getParentArea(country_data, area);
			path.push({area:parent,division_index:division_index-1,area_index:country_data[division_index-1].areas.indexOf(parent)});
			division_index--;
		}
		path.push({area:country});
	} else {
		path.push({area:country});
	}
	var title = document.createElement("DIV");
	title.style.textAlign = "center";
	title.style.fontWeight = "bold";
	title.style.fontSize = "14pt";
	for (var i = path.length-1; i >= 0; --i) {
		if (i != path.length-1) title.appendChild(document.createTextNode(" > "));
		if (path[i].area.area_name) {
			title.appendChild(createAreaLink(dialog, path[i].area.area_name, path[i].division_index, path[i].area_index));
		} else {
			title.appendChild(createAreaLink(dialog, path[i].area.country_name));
		}
	}
	container.appendChild(title);
}

function CoordinatesEdit(container, coordinates, map) {
	
	this.field_north = null;
	this.field_south = null;
	this.field_west = null;
	this.field_east = null;
	
	this.map_rect = null;
	
	this.updateMap = function() {
		if (coordinates.north && coordinates.south && coordinates.west && coordinates.east) {
			// it is set
			if (this.map_rect == null) {
				// first time
				this.map_rect = new window.top.google.maps.Rectangle({
					bounds: map.createBounds(coordinates.south, coordinates.west, coordinates.north, coordinates.east),
					strokeColor: "#6060F0",
					strokeWeight: 2,
					strokeOpacity: 0.8,
					fillColor: "#D0D0F0",
					fillOpacity: 0.3,
					editable: true,
				});
				var t=this;
				window.top.google.maps.event.addListener(this.map_rect, 'bounds_changed', function() {
					t.updateFromMap();
				});
				map.addShape(this.map_rect);
			} else {
				var bounds = this.map_rect.getBounds();
				if (bounds.getNorthEast().lat() != coordinates.north ||
					bounds.getNorthEast().lng() != coordinates.east ||
					bounds.getSouthWest().lat() != coordinates.south ||
					bounds.getSouthWest().lng() != coordinates.west) {
					// it changed
					this.map_rect.setBounds(map.createBounds(coordinates.south, coordinates.west, coordinates.north, coordinates.east));
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
	this.updateFromMap = function() {
		var bounds = this.map_rect.getBounds();
		this.setCoordinates(bounds.getNorthEast().lat(), bounds.getNorthEast().lng(), bounds.getSouthWest().lat(), bounds.getSouthWest().lng());
	};
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
		this.updateMap();
	};
	
	this._init = function() {
		var table = document.createElement("TABLE");
		container.appendChild(table);
		
		var t=this;
		require([["typed_field.js","field_decimal.js"]], function() {
			var tr,td;
			table.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TD"));
			td.style.whiteSpace = "nowrap";
			td.appendChild(document.createTextNode("Latitude North"));
			tr.appendChild(td = document.createElement("TD"));
			t.field_north = new field_decimal(coordinates.north, true, {can_be_null:true,integer_digits:3,decimal_digits:6,min:-90,max:90});
			td.appendChild(t.field_north.getHTMLElement());
			t.field_north.fillWidth();
			t.field_north.onchange.add_listener(function() { coordinates.north = t.field_north.getCurrentData(); t.updateMap(); });

			table.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TD"));
			td.appendChild(document.createTextNode("Longitude West"));
			td.style.whiteSpace = "nowrap";
			tr.appendChild(td = document.createElement("TD"));
			t.field_west = new field_decimal(coordinates.west, true, {can_be_null:true,integer_digits:3,decimal_digits:6,min:-180,max:180});
			td.appendChild(t.field_west.getHTMLElement());
			t.field_west.fillWidth();
			t.field_west.onchange.add_listener(function() { coordinates.west = t.field_west.getCurrentData(); t.updateMap(); });
			
			table.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TD"));
			td.appendChild(document.createTextNode("Latitude South"));
			td.style.whiteSpace = "nowrap";
			tr.appendChild(td = document.createElement("TD"));
			t.field_south = new field_decimal(coordinates.south, true, {can_be_null:true,integer_digits:3,decimal_digits:6,min:-90,max:90});
			td.appendChild(t.field_south.getHTMLElement());
			t.field_south.fillWidth();
			t.field_south.onchange.add_listener(function() { coordinates.south = t.field_south.getCurrentData(); t.updateMap(); });
			
			table.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TD"));
			td.appendChild(document.createTextNode("Longitude East"));
			td.style.whiteSpace = "nowrap";
			tr.appendChild(td = document.createElement("TD"));
			t.field_east = new field_decimal(coordinates.east, true, {can_be_null:true,integer_digits:3,decimal_digits:6,min:-180,max:180});
			td.appendChild(t.field_east.getHTMLElement());
			t.field_east.fillWidth();
			t.field_east.onchange.add_listener(function() { coordinates.east = t.field_east.getCurrentData(); t.updateMap(); });
		});
		// buttons
		var reset_button = document.createElement("BUTTON");
		reset_button.className = "action important";
		reset_button.innerHTML = "Reset";
		reset_button.onclick = function() { t.setCoordinates(null,null,null,null); };
		container.appendChild(reset_button);
		var map_button = document.createElement("BUTTON");
		map_button.className = "action";
		map_button.innerHTML = "Use map bounds";
		map_button.onclick = function() {
			var bounds = map.map.getBounds();
			t.setCoordinates(bounds.getNorthEast().lat(), bounds.getNorthEast().lng(), bounds.getSouthWest().lat(), bounds.getSouthWest().lng());
		};
		container.appendChild(map_button);
		container.appendChild(document.createElement("BR"));
		this.updateMap();
	};
	this._init();
}

function DisplayAreas(container, country, country_data, division_index, area_index, map) {
	this.parent_rect = null;
	this.siblings_rects = [];
	this.children_rects = [];
	
	var t=this;
	
	if ((typeof division_index == 'undefined' && country_data.length > 0) || division_index < country_data.length-1) {
		var check_children = document.createElement("INPUT");
		check_children.type = "checkbox";
		container.appendChild(check_children);
		container.appendChild(document.createTextNode(" Display sub-areas (green)"));
		container.appendChild(document.createElement("BR"));
		check_children.onchange = function() {
			if (this.checked) {
				var children;
				if (typeof division_index == 'undefined') children = country_data[0].areas;
				else children = window.top.geography.getAreaChildren(country_data, division_index+1, country_data[division_index].areas[area_index].area_id);
				this.children_rects = [];
				for (var i = 0; i < children.length; ++i) {
					if (!children[i].north) continue;
					var rect = new window.top.google.maps.Rectangle({
						bounds: map.createBounds(children[i].south, children[i].west, children[i].north, children[i].east),
						strokeColor: "#00F000",
						strokeWeight: 2,
						strokeOpacity: 0.7,
						fillColor: "#D0F0D0",
						fillOpacity: 0.2,
						editable: false,
					});
					t.children_rects.push(rect);
				}
				map.addShapes(t.children_rects);
			} else {
				map.removeShapes(t.children_rects);
				t.children_rects = [];
			}
		};
	}
	
	if (typeof division_index != 'undefined') {
		var check_siblings = document.createElement("INPUT");
		check_siblings.type = "checkbox";
		container.appendChild(check_siblings);
		container.appendChild(document.createTextNode(" Display siblings areas (purple)"));
		container.appendChild(document.createElement("BR"));
		check_siblings.onchange = function() {
			if (this.checked) {
				var siblings = [];
				var parent_id = country_data[division_index].areas[area_index].area_parent_id;
				for (var i = 0; i < country_data[division_index].areas.length; ++i)
					if (i != area_index && country_data[division_index].areas[i].area_parent_id == parent_id)
						siblings.push(country_data[division_index].areas[i]);
				this.siblings_rects = [];
				for (var i = 0; i < siblings.length; ++i) {
					if (!siblings[i].north) continue;
					var rect = new window.top.google.maps.Rectangle({
						bounds: map.createBounds(siblings[i].south, siblings[i].west, siblings[i].north, siblings[i].east),
						strokeColor: "#800080",
						strokeWeight: 2,
						strokeOpacity: 0.7,
						fillColor: "#F0D0F0",
						fillOpacity: 0.2,
						editable: false,
					});
					t.siblings_rects.push(rect);
				}
				map.addShapes(t.siblings_rects);
			} else {
				map.removeShapes(t.siblings_rects);
				t.siblings_rects = [];
			}
		};
	}
	
	if (typeof division_index != 'undefined' && division_index > 0) {
		var check_parent = document.createElement("INPUT");
		check_parent.type = "checkbox";
		container.appendChild(check_parent);
		container.appendChild(document.createTextNode(" Display parent area (yellow)"));
		container.appendChild(document.createElement("BR"));
		check_parent.onchange = function() {
			if (this.checked) {
				var area = country_data[division_index].areas[area_index];
				var parent = window.top.geography.getParentArea(country_data, area);
				if (parent && parent.north) {
					t.parent_rect = new window.top.google.maps.Rectangle({
						bounds: map.createBounds(parent[i].south, parent[i].west, parent[i].north, parent[i].east),
						strokeColor: "#F0F000",
						strokeWeight: 2,
						strokeOpacity: 0.7,
						fillColor: "#F0F0D0",
						fillOpacity: 0.2,
						editable: false,
					});
					map.addShape(t.parent_rect);
				}
			} else {
				if (t.parent_rect) {
					map.removeShape(t.parent_rect);
					t.parent_rect = null;
				}
			}
		};
	}
}

function ChildrenAreas(container, country, country_data, division_index, area_index, map) {
	// TODO
}
