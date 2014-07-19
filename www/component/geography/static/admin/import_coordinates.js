if (typeof require != 'undefined') {
	require("popup_window.js");
	require("upload.js");
	require("google_maps.js", function() { loadGoogleMaps(function() {}); });
}

function import_coordinates(country, country_data, onclose) {
	require("popup_window.js", function() {
		var content = document.createElement("DIV");
		var popup = new popup_window("Import Coordinates", "/static/geography/geography_16.png", content);
		popup.onclose = onclose;
		popup.show();
		import_country(popup, country, country_data);
	});
}

function getCoordinatesContaining(boxes) {
	var max = { north: boxes[0].north, east: boxes[0].east, south: boxes[0].south, west: boxes[0].west };
	for (var i = 1; i < boxes.length; ++i) {
		if (boxes[i].north > max.north) max.north = boxes[i].north;
		if (boxes[i].east > max.east) max.east = boxes[i].east;
		if (boxes[i].south < max.south) max.south = boxes[i].south;
		if (boxes[i].west < max.west) max.west = boxes[i].west;
	}
	return max;
}

function searchExactMatch(results, name) {
	var list = [];
	name = name.trim().toLowerCase();
	for (var i = 0; i < results.length; ++i)
		if (results[i].name.trim().toLowerCase() == name)
			list.push(results[i]);
	return list;
}


function createTitle(title, num) {
	var div = document.createElement("DIV");
	div.className = "page_section_title"+(num ? num : "");
	div.appendChild(document.createTextNode(title));
	return div;
}

function createTitleAreaElement(country, country_data, division_index, area_index) {
	var area = country_data[division_index].areas[area_index];
	var link = document.createElement("A");
	link.href = "#";
	link.className = "black_link";
	link.appendChild(document.createTextNode(area.area_name));
	link.onclick = function() {
		// TODO open popup
	};
	return link;
}
function createTitleCountry(country) {
	var link = document.createElement("A");
	link.href = "#";
	link.className = "black_link";
	link.appendChild(document.createTextNode(country.country_name));
	link.onclick = function() {
		// TODO open popup
	};
	return link;
}

function createParentAreaTitle(title, country, country_data, division_index, area_index) {
	if (division_index == 0) {
		title.appendChild(createTitleCountry(country));
		title.appendChild(document.createTextNode(" > "));
		return;
	}
	var area = country_data[division_index].areas[area_index];
	var parent_area = window.top.geography.getParentArea(country_data, area);
	createParentAreaTitle(title, country, country_data, division_index-1, country_data[division_index-1].areas.indexOf(parent_area));
	title.appendChild(createTitleAreaElement(country, country_data, division_index, area_index));
	title.appendChild(document.createTextNode(" > "));
}

function createTitleForArea(country, country_data, division_index, area_index) {
	var title = document.createElement("DIV");
	title.className = "page_section_title";
	createParentAreaTitle(title, country, country_data, division_index, area_index);
	var area = country_data[division_index].areas[area_index];
	title.appendChild(document.createTextNode(area.area_name));
	return title;
}

var map_colors = [
  {border:"#00A000",fill:"#D0F0D0"}, // green
  {border:"#F00000",fill:"#F0D0D0"}, // red
  {border:"#800080",fill:"#F0D0F0"}, // purple
  {border:"#F0F000",fill:"#F0F0D0"}, // yellow
  {border:"#00F0F0",fill:"#D0F0F0"}, // cyan
  {border:"#000000",fill:"#D0D0D0"}, // black
];

function import_country(popup, country, country_data) {
	if (country.north) {
		// already set, continue
		import_division_level(popup, country, country_data, 0, -1, function() {
			import_sub_divisions(popup, country, country_data, 0);			
		});
		return;
	}
	popup.content.removeAllChildren();
	popup.removeButtons();
	popup.content.appendChild(createTitle("Import Country "+country.country_name));

	// step 1- search from all sources
	var search_container = document.createElement("DIV");
	popup.content.appendChild(search_container);
	import_country_step1(search_container, country, country_data, function(gadm, geonames, google) {
		var bounds = [];
		if (gadm.length == 1) bounds.push(gadm[0]);
		if (geonames.length == 1) bounds.push(geonames[0]);
		if (google.length == 1) bounds.push(google[0]);
		if (bounds.length > 0) {
			bounds = getCoordinatesContaining(bounds);
			country.north = bounds.north;
			country.east = bounds.east;
			country.south = bounds.south;
			country.west = bounds.west;
		} else bounds = null;
		// step 2- display the results, by default take the largest bounds to contain results
		require("edit_coordinates.js", function() {
			search_container.removeAllChildren();
			var table = document.createElement("TABLE"); search_container.appendChild(table);
			var tr = document.createElement("TR"); table.appendChild(tr);
			var td_results = document.createElement("TD"); tr.appendChild(td_results);
			td_results.style.verticalAlign = "top";
			var td_coord = document.createElement("TD"); tr.appendChild(td_coord);
			td_coord.style.verticalAlign = "top";
			new EditCoordinatesWithMap(td_coord, country, function(ec) {
				var div = document.createElement("DIV"); td_results.appendChild(div);
				div.style.width = "300px";
				div.style.height = "100%";
				div.style.overflow = "auto";
				new ResultsList(div, "gadm.org", gadm, map_colors[0], ec.coord, ec.map);
				new ResultsList(div, "geonames.org", geonames, map_colors[1], ec.coord, ec.map);
				new ResultsList(div, "Google", google, map_colors[2], ec.coord, ec.map);
				if (bounds != null)
					ec.linkECWithMap.addResetOriginalButton(bounds);
				popup.addNextButton(function() {
					popup.freeze("Saving "+country.country+name+" coordinates");
					service.json("data_model", "save_entity", {
						table: "Country",
						key: country.country_id,
						lock: -1,
						field_north: ec.coord.field_north.getCurrentData(),
						field_south: ec.coord.field_south.getCurrentData(),
						field_east: ec.coord.field_east.getCurrentData(),
						field_west: ec.coord.field_west.getCurrentData()
					},function(res) {
						popup.unfreeze();
						import_division_level(popup, country, country_data, 0, -1, function() {
							import_sub_divisions(popup, country, country_data, 0);			
						});
					});
				});
			});
		});
	});
}
function import_country_step1(container, country, country_data, ondone) {
	var search_container = document.createElement("DIV");
	container.appendChild(search_container);
	
	var gadm = undefined;
	var geonames = undefined;
	var google = undefined;
	
	var check_done = function() {
		if (typeof gadm == 'undefined') return;
		if (typeof geonames == 'undefined') return;
		if (typeof google == 'undefined') return;
		ondone(gadm, geonames, google);
	};
	
	var kml_container = document.createElement("DIV");
	kml_container.appendChild(createTitle("Import from gadm.org",2));
	search_container.appendChild(kml_container);
	new ImportKML(kml_container, function(results, div) {
		// we should find the exact match
		var match = searchExactMatch(results, country.country_name);
		if (match.length == 1) gadm = match;
		else gadm = results;
		check_done();
	});
	var geonames_container = document.createElement("DIV");
	geonames_container.appendChild(createTitle("Import from geonames.org",2));
	search_container.appendChild(geonames_container);
	new SearchGeonames(geonames_container, country.country_id, country.country_name, "PCLI", function(results, div) {
		geonames = results;
		check_done();
	}, true);
	var google_container = document.createElement("DIV");
	google_container.appendChild(createTitle("Import from Google",2));
	search_container.appendChild(google_container);
	new SearchGoogle(google_container, country.country_id, country.country_name, "country", function(results, div) {
		google = results;
		check_done();
	}, true);
};

function import_division_level(popup, country, country_data, division_index, parent_area_index, ondone) {
	if (country_data.length <= division_index) {
		// this is the end
		popup.close();
		return;
	}
	popup.content.removeAllChildren();
	popup.removeButtons();
	popup.content.appendChild(createTitle("Import Divisions '"+country_data[division_index].division_name+"' in Country "+country.country_name));

	// step 1- search from all sources
	var container = document.createElement("DIV");
	popup.content.appendChild(container);
	var search_container = document.createElement("DIV");
	container.appendChild(search_container);
	var results_container = document.createElement("DIV");
	container.appendChild(results_container);
	import_division_level_names(search_container, country, country_data, division_index, function(gadm) {
		results_container.removeAllChildren();
		var matches = [];
		if (match_division_level_names(results_container, country, country_data, division_index, parent_area_index, gadm, matches))
			import_division_level_coordinates(popup, country, country_data, division_index, parent_area_index, matches, ondone);
		else
			popup.addNextButton(function() {
				import_division_level_coordinates(popup, country, country_data, division_index, parent_area_index, matches, ondone);
			});
	});
}

function import_division_level_names(container, country, country_data, division_index, ondone) {
	var search_container = document.createElement("DIV");
	container.appendChild(search_container);
	
	var kml_container = document.createElement("DIV");
	kml_container.appendChild(createTitle("Import from gadm.org",2));
	search_container.appendChild(kml_container);
	new ImportKML(kml_container, function(results, div) {
		// we should find the exact match
		ondone(results);
	});
}

function match_division_level_names(container, country, country_data, division_index, parent_area_index, names, matches) {
	var missing_matches = country_data[division_index].areas.length;
	for (var i = 0; i < country_data[division_index].areas.length; ++i) {
		var match = searchExactMatch(names, country_data[division_index].areas[i].area_name);
		if (match.length == 1) {
			matches.push(match[0]);
			names.remove(match[0]);
			missing_matches--;
		} else
			matches.push(null);
	}
	if (names.length == 0 && missing_matches == 0) {
		// everything match, we can continue
		return true;
	}
	var content = document.createElement("DIV");
	content.style.padding = "5px";
	container.appendChild(content);
	content.appendChild(document.createTextNode(""+(matches.length-missing_matches)+" areas are matching between database and gadm."));
	content.appendChild(document.createElement("BR"));
	var table = document.createElement("TABLE"); content.appendChild(table);
	table.style.borderCollapse = "collapse";
	table.style.borderSpacing = "0px";
	var tr = document.createElement("TR"); table.appendChild(tr);
	var td = document.createElement("TH"); tr.appendChild(td);
	td.innerHTML = "Remaining areas in database";
	td.style.borderRight = "1px solid #808080";
	td = document.createElement("TH"); tr.appendChild(td);
	td.innerHTML = "Remaining areas from gadm";
	tr = document.createElement("TR"); table.appendChild(tr);
	td = document.createElement("TD"); tr.appendChild(td);
	td.style.borderRight = "1px solid #808080";
	var radios_db = [];
	for (var i = 0; i < matches.length; ++i) {
		if (matches[i] != null) continue;
		var div = document.createElement("DIV");
		td.appendChild(div);
		var radio = document.createElement("INPUT");
		radio.type = "radio";
		radio.name = "db";
		radio.value = i;
		div.appendChild(radio);
		radios_db.push(radio);
		div.appendChild(document.createTextNode(" "+country_data[division_index].areas[i].area_name));
	}
	td = document.createElement("TD"); tr.appendChild(td);
	var radios_names = [];
	for (var i = 0; i < names.length; ++i) {
		var div = document.createElement("DIV"); td.appendChild(div);
		var radio = document.createElement("INPUT");
		radio.type = "radio";
		radio.name = "names";
		radio.value = i;
		radio._name = names[i];
		div.appendChild(radio);
		radios_names.push(radio);
		div.appendChild(document.createTextNode(" "+names[i].name));
		var add_button = document.createElement("BUTTON");
		add_button.className = "flat small_icon";
		add_button.innerHTML = "<img src='"+theme.icons_10.add+"'/>";
		add_button.style.marginLeft = "5px";
		add_button.title = "Add this area to the database";
		div.appendChild(add_button);
		add_button._name = names[i];
		add_button._radio_index = radios_names.length-1;
		add_button.onclick = function() {
			var add_button = this;
			var div = document.createElement("DIV");
			var area = {
				area_id: -1,
				area_parent_id: division_index == 0 ? null : country_data[division_index-1].areas[parent_area_index].area_id,
				area_name: this._name.name,
				north: null, south: null, east: null, west:null
			};
			var area_popup = new popup_window("Add area", null, div);
			import_area_coordinates(div, area, division_index, country, country_data, this._name, function(ec, input_name) {
				area_popup.addOkCancelButtons(function() {
					// check name
					var name = input_name.value.trim();
					if (name.length == 0) {
						alert("Please enter a name for the new area");
						return;
					}
					for (var i = 0; i < country_data[division_index].areas.length; ++i) {
						if (division_index > 0 && country_data[division_index].areas[i].area_parent_id != area.area_parent_id) continue;
						if (country_data[division_index].areas[i].area_name.toLowerCase() == name.toLowerCase()) {
							alert("An area already exists with the same name");
							return;
						}
					}
					area.north = ec.coord.field_north.getCurrentData();
					area.south = ec.coord.field_south.getCurrentData();
					area.west = ec.coord.field_west.getCurrentData();
					area.east = ec.coord.field_east.getCurrentData();
					if (area.north === null || area.south === null || area.west === null || area.east === null)
						area.north = area.south = area.west = area.east = null;
					area_popup.freeze("Adding the new area");
					service.json("data_model","save_entity",{
						table: "GeographicArea",
						field_country_division: country_data[division_index].division_id,
						field_name: name,
						field_parent: area.area_parent_id,
						field_north: area.north,
						field_south: area.south,
						field_west: area.west,
						field_east: area.east
					},function(res) {
						if (res && res.key) {
							area.id = res.key;
							country[division_index].areas.push(area);
							matches.push(null);
							add_button.parentNode.parentNode.removeChild(add_button.parentNode);
							radios_names.splice(add_button._radio_index, 1);
							area_popup.close();
						} else
							area_popup.unfreeze();
					});
				});
			});
			area_popup.show();
		};
	}
	tr = document.createElement("TR"); table.appendChild(tr);
	td = document.createElement("TD"); tr.appendChild(td);
	td.colSpan = 2;
	td.style.textAlign = "center";
	var button_match = document.createElement("BUTTON");
	button_match.className = "action";
	button_match.innerHTML = "Link selected areas";
	td.appendChild(button_match);
	button_match.onclick = function() {
		var radio_db = null, radio_names = null;
		for (var i = 0; i < radios_db.length; ++i) if (radios_db[i].checked) { radio_db = radios_db[i]; break;}
		for (var i = 0; i < radios_names.length; ++i) if (radios_names[i].checked) { radio_names = radios_names[i]; break;}
		if (radio_db == null || radio_names == null) {
			alert("Please select one area on each side to link them");
			return;
		}
		var db_index = radio_db.value;
		var name = radio_names._name;
		matches[db_index] = name;
		radios_db.remove(radio_db);
		radios_names.remove(radio_names);
		radio_db.parentNode.parentNode.removeChild(radio_db.parentNode);
		radio_names.parentNode.parentNode.removeChild(radio_names.parentNode);
	};
	return false;
}

function import_division_level_coordinates(popup, country, country_data, division_index, parent_area_index, matches, ondone) {
	var next = function(index) {
		if (index == country_data[division_index].areas.length) {
			ondone();
			return;
		}
		if (country_data[division_index].areas[index].north) {
			// coordinates are already present, continue to the next area
			next(index+1);
			return;
		}
		popup.content.removeAllChildren();
		popup.removeButtons();
		//popup.content.appendChild(createTitleForArea(country, country_data, division_index, index));
		import_area_coordinates(popup.content, country_data[division_index].areas[index], division_index, country, country_data, matches[index], function(ec) {
			popup.addNextButton(function() {
				var north = ec.coord.field_north.getCurrentData();
				var south = ec.coord.field_south.getCurrentData();
				var west = ec.coord.field_west.getCurrentData();
				var east = ec.coord.field_east.getCurrentData();
				if (north === null || south === null || west === null || east === null)
					north = south = west = east = null;
				popup.freeze("Saving geographic coordinates");
				service.json("data_model","save_entity",{
					table: "GeographicArea",
					key: country_data[division_index].areas[index].area_id,
					lock: -1,
					field_north: north,
					field_south: south,
					field_west: west,
					field_east: east
				},function(res) {
					popup.unfreeze();
					if (res) next(index+1);
				});
			});
		});
	};
	next(0);
}

function import_sub_divisions(popup, country, country_data, division_index) {
	if (division_index == country_data.length-1) {
		// this is the end !!
		popup.close();
		return;
	}
	var next = function(index) {
		if (index == country_data[division_index+1].areas.length) {
			// all done, go to next division
			import_sub_divisions(popup, country, country_data, division_index+1);
			return;
		}
		import_division_level(popup, country, country_data, division_index+1, index, function() {
			next(index_1);
		});
	};
	next(0);
}

function dialog_coordinates(country, country_data, division_index, area_index) {
	require("popup_window.js",function() {
		var content = document.createElement("DIV");
		var popup = new popup_window("Geographic Coordinates", "/static/geography/geography_16.png", content);
		var area;
		if (typeof division_index == 'undefined')
			area = country;
		else
			area = country_data[division_index].areas[area_index];
		popup.show();
		import_area_coordinates(content, area, division_index, country, country_data, null, function(ec) {
			popup.addSaveButton(function() {
				var north = ec.coord.field_north.getCurrentData();
				var south = ec.coord.field_south.getCurrentData();
				var west = ec.coord.field_west.getCurrentData();
				var east = ec.coord.field_east.getCurrentData();
				if (north === null || south === null || west === null || east === null)
					north = south = west = east = null;
				popup.freeze("Saving geographic coordinates");
				service.json("data_model","save_entity",{
					table: typeof division_index == 'undefined' ? "Country" : "GeographicArea",
					key: typeof division_index == 'undefined' ? country.country_id : area.area_id,
					lock: -1,
					field_north: north,
					field_south: south,
					field_west: west,
					field_east: east
				},function(res) {
					popup.unfreeze();
				});
			});
		});
	});
}

function import_area_coordinates(container, area, division_index, country, country_data, from_kml, onready) {
	require("edit_coordinates.js", function() {
		var table = document.createElement("TABLE"); container.appendChild(table);
		var tr = document.createElement("TR"); table.appendChild(tr);
		var td = document.createElement("TD"); tr.appendChild(td);
		td.colSpan = 2;
		var input_name = null;
		if (typeof division_index != 'undefined' && area.area_id <= 0) {
			td.style.fontSize = "14pt";
			td.innerHTML = "Area name ";
			input_name = document.createElement("INPUT");
			input_name.type = "text";
			input_name.maxLength = 100;
			input_name.size = 50;
			input_name.value = area.area_name;
			td.appendChild(input_name);
		} else {
			if (typeof division_index == 'undefined')
				td.appendChild(createTitle(country.country_name));
			else
				td.appendChild(createTitleForArea(country, country_data, division_index, country_data[division_index].areas.indexOf(area)));
		}
		var tr = document.createElement("TR"); table.appendChild(tr);
		var td_results = document.createElement("TD"); tr.appendChild(td_results);
		td_results.style.verticalAlign = "top";
		var td_coord = document.createElement("TD"); tr.appendChild(td_coord);
		td_coord.style.verticalAlign = "top";
		new EditCoordinatesWithMap(td_coord, area, function(ec) {
			var div = document.createElement("DIV"); td_results.appendChild(div);
			div.style.width = "300px";
			div.style.height = "100%";
			div.style.overflow = "auto";
			var color_index = 0;
			
			var sub_areas = [];
			var all_sub_areas = [];
			var parent_area = null;
			var sibling_areas = [];
			if (typeof division_index != 'undefined') {
				if (area.area_id > 0 && division_index < country_data.length-1)
					sub_areas = window.top.geography.getAreaChildren(country_data, division_index+1, area.area_id);
				if (division_index > 0)
					parent_area = window.top.geography.getParentArea(country_data, area);
				else
					parent_area = country;
				sibling_areas = window.top.geography.getSiblingAreas(country_data, division_index, area);
			} else if (country_data.length > 0) {
				for (var i = 0; i < country_data[0].areas.length; ++i)
					sub_areas.push(country_data[0].areas[i]);
			}
			for (var i = 0; i < sub_areas.length; ++i)
				if (sub_areas[i].north === null) { all_sub_areas.push(sub_areas[i]); sub_areas.splice(i,1); i--; }
			for (var i = 0; i < sibling_areas.length; ++i)
				if (sibling_areas[i].north === null) { sibling_areas.splice(i,1); i--; }
			if (parent_area != null && parent_area.north === null) parent_area = null;

			// if no coordinates, set map to parent area
			if (!area.north && parent_area) ec.map.fitToBounds(parent_area.south, parent_area.west, parent_area.north, parent_area.east);
			
			if (all_sub_areas.length > 0 || parent_area != null || sibling_areas.length > 0) {
				var title = document.createElement("DIV"); div.appendChild(title);
				title.appendChild(document.createTextNode("Display"));
				title.style.fontWeight = "bold";
				title.style.backgroundColor = "#C0C0C0";
				if (all_sub_areas.length > 0) {
					var color = map_colors[color_index++];
					var d = document.createElement("DIV"); div.appendChild(d);
					d.style.color = color.border;
					var cb = document.createElement("INPUT"); d.appendChild(cb);
					cb.type = "checkbox";
					if (sub_areas.length == 0) cb.disabled = "disabled";
					d.appendChild(document.createTextNode(" Sub-areas"));
					var button = document.createElement("BUTTON");
					button.className = "flat small_icon";
					button.innerHTML = "<img src='"+theme.icons_10.arrow_down_context_menu+"'/>";
					button.style.marginLeft = "5px";
					d.appendChild(button);
					button.onclick = function() {
						require("context_menu.js", function() {
							var menu = new context_menu();
							for (var i = 0; i < all_sub_areas.length; ++i) {
								menu.addIconItem(null, all_sub_areas[i].area_name, function(i) {
									// TODO
								}, i);
							}
							menu.showBelowElement(button);
						});
					};
					cb._color = color;
					cb.onchange = function() {
						if (this.checked) {
							this.rects = [];
							for (var i = 0; i < sub_areas.length; ++i) {
								var rect = new window.top.google.maps.Rectangle({
									bounds: ec.map.createBounds(parseFloat(sub_areas[i].south), parseFloat(sub_areas[i].west), parseFloat(sub_areas[i].north), parseFloat(sub_areas[i].east)),
									strokeColor: this._color.border,
									strokeWeight: 2,
									strokeOpacity: 0.7,
									fillColor: this._color.fill,
									fillOpacity: 0.2,
									editable: false,
								});
								ec.map.addShape(rect);
								this.rects.push(rect);
							}
						} else {
							for (var i = 0; i < this.rects.length; ++i)
								ec.map.removeShape(this.rects[i]);
							this.rects = null;
						}
					};
				}
				if (parent_area != null) {
					var color = map_colors[color_index++];
					var d = document.createElement("DIV"); div.appendChild(d);
					d.style.color = color.border;
					var cb = document.createElement("INPUT"); d.appendChild(cb);
					cb.type = "checkbox";
					d.appendChild(document.createTextNode(" Parent area"));
					cb._color = color;
					cb.onchange = function() {
						if (this.checked) {
							this.rect = new window.top.google.maps.Rectangle({
								bounds: ec.map.createBounds(parseFloat(parent_area.south), parseFloat(parent_area.west), parseFloat(parent_area.north), parseFloat(parent_area.east)),
								strokeColor: this._color.border,
								strokeWeight: 2,
								strokeOpacity: 0.7,
								fillColor: this._color.fill,
								fillOpacity: 0.2,
								editable: false,
							});
							ec.map.addShape(this.rect);
						} else {
							ec.map.removeShape(this.rect);
							this.rect = null;
						}
					};
				}
				if (sibling_areas.length > 0) {
					var color = map_colors[color_index++];
					var d = document.createElement("DIV"); div.appendChild(d);
					d.style.color = color.border;
					var cb = document.createElement("INPUT"); d.appendChild(cb);
					cb.type = "checkbox";
					d.appendChild(document.createTextNode(" Sibling areas"));
					cb._color = color;
					cb.onchange = function() {
						if (this.checked) {
							this.rects = [];
							for (var i = 0; i < sibling_areas.length; ++i) {
								var rect = new window.top.google.maps.Rectangle({
									bounds: ec.map.createBounds(parseFloat(sibling_areas[i].south), parseFloat(sibling_areas[i].west), parseFloat(sibling_areas[i].north), parseFloat(sibling_areas[i].east)),
									strokeColor: this._color.border,
									strokeWeight: 2,
									strokeOpacity: 0.7,
									fillColor: this._color.fill,
									fillOpacity: 0.2,
									editable: false,
								});
								ec.map.addShape(rect);
								this.rects.push(rect);
							}
						} else {
							for (var i = 0; i < this.rects.length; ++i)
								ec.map.removeShape(this.rects[i]);
							this.rects = null;
						}
					};
				}
			}
			
			if (from_kml)
				new ResultsList(div, "gadm.org", [from_kml], map_colors[color_index++], ec.coord, ec.map);
			// Geonames
			var search_geonames_title = document.createElement("DIV"); div.appendChild(search_geonames_title);
			search_geonames_title.appendChild(document.createTextNode("geonames.org"));
			search_geonames_title.style.fontWeight = "bold";
			search_geonames_title.style.backgroundColor = "#C0C0C0";
			var geonames_color = map_colors[color_index++];
			search_geonames_title.style.color = geonames_color.border;
			var search_geonames_div = document.createElement("DIV"); div.appendChild(search_geonames_div);
			new SearchGeonames(search_geonames_div, country.country_id, area.area_name, null, function(res) {
				div.removeChild(search_geonames_title);
				search_geonames_div.removeAllChildren();
				new ResultsList(search_geonames_div, "geonames.org", res, geonames_color, ec.coord, ec.map);
			});
			// Google
			var search_google_title = document.createElement("DIV"); div.appendChild(search_google_title);
			search_google_title.appendChild(document.createTextNode("Google"));
			search_google_title.style.fontWeight = "bold";
			search_google_title.style.backgroundColor = "#C0C0C0";
			var google_color = map_colors[color_index++];
			search_google_title.style.color = google_color.border;
			var search_google_div = document.createElement("DIV"); div.appendChild(search_google_div);
			new SearchGoogle(search_google_div, country.country_id, area.area_name, null, function(res) {
				div.removeChild(search_google_title);
				search_google_div.removeAllChildren();
				new ResultsList(search_google_div, "Google", res, google_color, ec.coord, ec.map);
			});
			onready(ec, input_name);
		});
	});
}

function ResultsList(container, from, results, color, coord, map) {
	this.title = document.createElement("DIV"); container.appendChild(this.title);
	this.title.appendChild(document.createTextNode("Results from "+from));
	this.title.style.fontWeight = "bold";
	this.title.style.backgroundColor = "#C0C0C0";
	this.title.style.color = color.border;
	this.results = document.createElement("DIV"); container.appendChild(this.results);
	for (var i = 0; i < results.length; ++i) {
		var line = document.createElement("DIV"); this.results.appendChild(line);
		var cb = document.createElement("INPUT"); cb.type = "checkbox"; line.appendChild(cb);
		cb.result = results[i];
		cb.onchange = function() {
			if (this.checked) {
				this.rect = new window.top.google.maps.Rectangle({
					bounds: map.createBounds(parseFloat(this.result.south), parseFloat(this.result.west), parseFloat(this.result.north), parseFloat(this.result.east)),
					strokeColor: color.border,
					strokeWeight: 2,
					strokeOpacity: 0.7,
					fillColor: color.fill,
					fillOpacity: 0.2,
					editable: false,
				});
				map.addShape(this.rect);
			} else {
				map.removeShape(this.rect);
				this.rect = null;
			}
		};
		cb.style.marginRight = "5px";
		var link = document.createElement("A");
		link.href = "#";
		link.className = "black_link";
		link.appendChild(document.createTextNode(results[i].name));
		link.title = "Click to use coordinates of this result";
		line.appendChild(link);
		link.result = results[i];
		link.onclick = function() {
			coord.setCoordinates(this.result.north, this.result.east, this.result.south, this.result.west);
			return false;
		};
		line.appendChild(link);
	}
	layout.invalidate(container);
}

function ImportKML(container, ondone) {
	this.div = document.createElement("DIV");
	this.div.style.padding = "5px";
	container.appendChild(this.div);
	
	this._init = function() {
		var link = document.createElement("A");
		link.href = '#';
		link.className = "black_link";
		link.style.fontWeight = "bold";
		link.innerHTML = "Upload KML file (Google Earth Format)";
		link._t = this;
		link.onclick = function(ev) {
			this._t.startUpload(this, ev);
			return false;
		};
		this.div.appendChild(link);
	};
	
	this.startUpload = function(link, ev) {
		link.parentNode.removeChild(link);
		this.div.innerHTML = "Initializing upload...";
		var t=this;
		var up = new upload("/dynamic/geography/service/import_kml", false, true);
		up.onstart = function(files, onready) {
			t.div.innerHTML = "Uploading file...";
			onready();
		};
		var done = false;
		up.onprogressfile = function(file, uploaded, total) {
			if (done) return;
			if (uploaded < total)
				t.div.innerHTML = "Uploading file ("+Math.floor(uploaded*100/total)+"%)...";
			else
				t.div.innerHTML = "Analyzing file...";
		};
		up.ondonefile = function(file, output, errors) {
			done = true;
			if (errors.length > 0) {
				t.div.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> An error occured.";
				t.div.appendChild(document.createElement("BR"));
				t.div.appendChild(link);
				return;
			}
			if (output.length == 0) {
				t.div.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> Nothing found in the file.";
				t.div.appendChild(document.createElement("BR"));
				t.div.appendChild(link);
				return;
			}
			t.div.innerHTML = output.length+" result(s) found.";
			var redo = document.createElement("A");
			redo.innerHTML = "Upload another KML file";
			redo.href = "#";
			redo.className = "black_link";
			redo.style.fontWeight = "bold";
			redo.style.marginLeft = "5px";
			redo.onclick = function(ev) {
				t.startUpload(this, ev);
				return false;
			};
			t.div.appendChild(redo);
			ondone(output, t.div);
		};
		up.openDialog(ev);
	};
	
	this._init();
}

function SearchGeonames(container, country_id, name, featureCode, ondone, auto_launch) {
	var div = document.createElement("DIV");
	div.style.padding = "5px";
	container.appendChild(div);
	
	var launch = function() {
		div.innerHTML = "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Searching...";
		layout.invalidate(div);
		
		var data = {
			country_id: country_id,
			name: name
		};
		if (featureCode) data.featureCode = featureCode;
		service.json("geography", "search_geonames", data, function(res) {
			var count = res ? res.length : 0;
			div.innerHTML = count+" result(s) found.";
			if (count == 0) {
				var retry = document.createElement("BUTTON");
				retry.className = "flat";
				retry.innerHTML = "<img src='"+theme.icons_16.refresh+"'/> Retry";
				retry.style.marginLeft = "5px";
				div.appendChild(retry);
				retry.onclick = function() {
					new SearchGeonames(container, country_id, name, featureCode, ondone, true);
				};
			}
			ondone(res, div);
		});
	};
	if (auto_launch)
		launch();
	else {
		var button = document.createElement("BUTTON");
		button.className = "action";
		button.innerHTML = "<img src='"+theme.icons_16.search+"'/> Search";
		div.appendChild(button);
		button.onclick = function() {
			launch();
		};
	}
}

function SearchGoogle(container, country_id, name, types, ondone, auto_launch) {
	var div = document.createElement("DIV");
	div.style.padding = "5px";
	container.appendChild(div);
	
	var launch = function() {
		div.innerHTML = "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Searching...";
		layout.invalidate(div);
		
		var data = {
			country_id: country_id,
			name: name,
			types: types ? types : "political"
		};
		service.json("geography", "search_google", data, function(res) {
			if (res)
				for (var i = 0; i < res.length; ++i) {
					if (res[i].geometry) {
						if (res[i].geometry.viewport) {
							res[i].north = res[i].geometry.viewport.northeast.lat;
							res[i].east = res[i].geometry.viewport.northeast.lng;
							res[i].south = res[i].geometry.viewport.southwest.lat;
							res[i].west = res[i].geometry.viewport.southwest.lng;
							// remove fake viewport
							if (res[i].north == 90 && res[i].south == -90) {
								res.splice(i,1);
								i--;
							} else if (res[i].east == 180 && res[i].west == -180) {
								res.splice(i,1);
								i--;
							}
						} else {
							res.splice(i,1);
							i--;
						}
					} else {
						res.splice(i,1);
						i--;
					}
				}
			var count = res ? res.length : 0;
			div.innerHTML = count+" result(s) found.";
			if (count == 0) {
				var retry = document.createElement("BUTTON");
				retry.className = "flat";
				retry.innerHTML = "<img src='"+theme.icons_16.refresh+"'/> Retry";
				retry.style.marginLeft = "5px";
				div.appendChild(retry);
				retry.onclick = function() {
					new SearchGoogle(container, country_id, name, types, ondone, true);
				};
			}
			ondone(res, div);
		});
	};
	if (auto_launch)
		launch();
	else {
		var button = document.createElement("BUTTON");
		button.className = "action";
		button.innerHTML = "<img src='"+theme.icons_16.search+"'/> Search";
		div.appendChild(button);
		button.onclick = function() {
			launch();
		};
	}
}