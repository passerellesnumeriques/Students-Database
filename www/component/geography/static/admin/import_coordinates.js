if (typeof require != 'undefined') {
	require("popup_window.js");
	require("upload.js");
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
	name = name.trim().latinize().toLowerCase();
	for (var i = 0; i < results.length; ++i)
		if (results[i].name.trim().latinize().toLowerCase() == name)
			list.push(results[i]);
	return list;
}

function searchAllWords(results, name) {
	var list = [];
	name = name.trim().latinize().toLowerCase();
	for (var i = 0; i < results.length; ++i) {
		var n = results[i].name.trim().latinize().toLowerCase();
		if (n.indexOf(name) >= 0 || name.indexOf(n) >= 0)
			list.push(results[i]);
	}
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
		dialog_coordinates(country, country_data, division_index, area_index);
	};
	return link;
}
function createTitleCountry(country) {
	var link = document.createElement("A");
	link.href = "#";
	link.className = "black_link";
	link.appendChild(document.createTextNode(country.country_name));
	link.onclick = function() {
		dialog_coordinates(country, country_data);
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
	var parent_area_index = country_data[division_index-1].areas.indexOf(parent_area);
	createParentAreaTitle(title, country, country_data, division_index-1, parent_area_index);
	title.appendChild(createTitleAreaElement(country, country_data, division_index-1, parent_area_index));
	title.appendChild(document.createTextNode(" > "));
}

function createTitleForArea(country, country_data, division_index, area_index) {
	var title = document.createElement("DIV");
	title.className = "page_section_title";
	createParentAreaTitle(title, country, country_data, division_index, area_index);
	var area = country_data[division_index].areas[area_index];
	var span = document.createElement("SPAN");
	title.appendChild(span);
	require("editable_cell.js",function() {
		new editable_cell(span, "GeographicArea", "name", area.area_id, "field_text", {min_length:1,max_length:100,can_be_null:false}, area.area_name);
	});
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
		import_division_level(popup, country, country_data, 0, -1, undefined, undefined, function() {
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
						import_division_level(popup, country, country_data, 0, -1, undefined, undefined, function() {
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
	new SearchGoogle(google_container, country.country_id, country.country_name, "country", null, function(results, div) {
		google = results;
		check_done();
	}, true);
};

function import_division_level(popup, country, country_data, division_index, parent_area_index, areas, gadm, ondone) {
	if (country_data.length <= division_index) {
		// this is the end
		popup.close();
		return;
	}

	// get parent
	var parent_area = division_index > 0 ? country_data[division_index-1].areas[parent_area_index] : country;
	if (!parent_area.north) {
		// we don't continue here as the parent doesn't have coordinates
		setTimeout(ondone,1);
		return;
	}
	
	// get children
	if (typeof areas == 'undefined') {
		areas = [];
		for (var i = 0; i < country_data[division_index].areas.length; ++i) {
			if (division_index == 0 || country_data[division_index].areas[i].area_parent_id == parent_area.area_id)
				areas.push(country_data[division_index].areas[i]);
		}
	}
	
	if (areas.length == 0) {
		// nothing there
		setTimeout(ondone,1);
		return;
	}

	popup.content.removeAllChildren();
	popup.removeButtons();
	if (division_index>0 && parent_area) {
		popup.addIconTextButton(null, "Skip "+parent_area.area_name, "skip", function() {
			setTimeout(ondone,1);
		});
		popup.addIconTextButton(null, "Skip all areas in this division", "skip_division", function() {
			ondone(true);
		});
		popup.addIconTextButton(null, "Skip areas already started", "skip_started", function() {
			ondone(false, true, false);
		});
		popup.addIconTextButton(null, "Skip areas already fully imported", "skip_started", function() {
			ondone(false, false, true);
		});
	} else
		popup.addIconTextButton(null, "Skip and go to next division", "skip", function() {
			setTimeout(ondone,1);
		});
	
	var gadm_imported = function(gadm) {
		popup.content.removeAllChildren();
		var matches = [];
		if (match_division_level_names(popup.content, country, country_data, division_index, parent_area, areas, gadm, matches))
			import_division_level_coordinates(popup, country, country_data, division_index, parent_area, areas, matches, ondone);
		else
			popup.addNextButton(function() {
				import_division_level_coordinates(popup, country, country_data, division_index, parent_area, areas, matches, ondone);
			});
	};
	if (typeof gadm == 'function' || !gadm) {
		popup.content.appendChild(createTitle("Import Divisions '"+country_data[division_index].division_name+"' in Country "+country.country_name));
		import_division_level_names(popup.content, country, country_data, division_index, function(g) {
			if (typeof gadm == 'function') gadm(g);
			gadm_imported(g);
		});
	} else
		gadm_imported(gadm);
}

function import_division_level_names(container, country, country_data, division_index, ondone) {
	var search_container = document.createElement("DIV");
	container.appendChild(search_container);
	
	var kml_container = document.createElement("DIV");
	kml_container.appendChild(createTitle("Import from gadm.org",2));
	search_container.appendChild(kml_container);
	new ImportKML(kml_container, function(results, div) {
		container.removeChild(search_container);
		ondone(results);
	});
}

function match_division_level_names(container, country, country_data, division_index, parent_area, areas, gadm, matches) {
	var page_container = document.createElement("DIV");
	page_container.style.width = "100%";
	page_container.style.height = "100%";
	page_container.style.overflow = "hidden";
	page_container.style.display = "flex";
	page_container.style.flexDirection = "column";
	container.appendChild(page_container);
	container.style.height = "100%";
	// title
	var title;
	if (division_index == 0)
		title = createTitle("Import Divisions '"+country_data[division_index].division_name+"' in Country "+country.country_name);
	else
		title = createTitleForArea(country, country_data, division_index-1, country_data[division_index-1].areas.indexOf(parent_area));
	title.style.flex = "none";
	page_container.appendChild(title);
	
	var names_inside = [];
	var names_intersect = [];
	var names_near = [];
	var other_names = [];
	
	var ps = parseFloat(parent_area.south);
	var pn = parseFloat(parent_area.north);
	var pw = parseFloat(parent_area.west);
	var pe = parseFloat(parent_area.east);
	
	for (var i = 0; i < gadm.length; ++i) {
		var gs = parseFloat(gadm[i].south);
		var gn = parseFloat(gadm[i].north);
		var gw = parseFloat(gadm[i].west);
		var ge = parseFloat(gadm[i].east);
		if (window.top.geography.rectContains(ps,pn,pw,pe,gs,gn,gw,ge))
			names_inside.push(gadm[i]);
		else if (window.top.geography.rectIntersect(ps,pn,pw,pe,gs,gn,gw,ge))
			names_intersect.push(gadm[i]);
		else if (window.top.geography.rectContains(ps-(pn-ps),pn+(pn-ps),pw-(pe-pw),pe+(pe-pw),gs,gn,gw,ge))
			names_near.push(gadm[i]);
		else
			other_names.push(gadm[i]);
	}
	
	var missing_matches = areas.length;
	var names_near_matching = [];
	var other_names_matching = [];
	// match with names inside
	for (var i = 0; i < areas.length; ++i) {
		var match = searchExactMatch(names_inside, areas[i].area_name);
		if (match.length == 1) {
			matches.push(match[0]);
			missing_matches--;
			gadm.remove(match[0]);
			names_inside.remove(match[0]);
			continue;
		}
		matches.push(null);
	}
	// match with names having intersection
	for (var i = 0; i < areas.length; ++i) {
		if (matches[i] != null) continue;
		match = searchExactMatch(names_intersect, areas[i].area_name);
		if (match.length == 1) {
			// check it is not an exact match of another area
			var found = false;
			if (division_index>0) {
				// 1- search another parent area containing it
				var gs = parseFloat(match[0].south);
				var gn = parseFloat(match[0].north);
				var gw = parseFloat(match[0].west);
				var ge = parseFloat(match[0].east);
				for (var j = 0; j < country_data[division_index-1].areas.length; ++j) {
					var p = country_data[division_index-1].areas[j];
					if (!p.north) continue;
					if (p == parent_area) continue;
					var ps = parseFloat(p.south);
					var pn = parseFloat(p.north);
					var pw = parseFloat(p.west);
					var pe = parseFloat(p.east);
					if (window.top.geography.rectContains(ps,pn,pw,pe,gs,gn,gw,ge)) {
						// it is inside also
						var children = window.top.geography.getAreaChildren(country_data, division_index-1, p.area_id);
						var n = match[0].name.trim().latinize().toLowerCase();
						for (var k = 0; k < children.length; ++k)
							if (n == children[k].area_name.latinize().toLowerCase()) { found = true; break; }
					}
					if (found) break;
				}
			}
			if (!found) {
				matches[i] = match[0];
				missing_matches--;
				gadm.remove(match[0]);
				names_intersect.remove(match[0]);
				continue;
			}
		}
	}
	// match inside with all words
	for (var i = 0; i < areas.length; ++i) {
		if (matches[i] != null) continue;
		match = searchAllWords(names_inside, areas[i].area_name);
		if (match.length == 1) {
			matches[i] = match[0];
			missing_matches--;
			gadm.remove(match[0]);
			names_inside.remove(match[0]);
			continue;
		}
	}
	// match intersection with all words
	/*
	for (var i = 0; i < areas.length; ++i) {
		if (matches[i] != null) continue;
		match = searchAllWords(names_intersect, areas[i].area_name);
		if (match.length == 1) {
			matches[i] = match[0];
			missing_matches--;
			gadm.remove(match[0]);
			names_intersect.remove(match[0]);
			continue;
		}
	}*/
	// match with geographic coordinates, in case we already imported
	for (var i = 0; i < areas.length; ++i) {
		if (matches[i] != null) continue;
		if (!areas[i].north) continue;
		var exact = null;
		var intersect = [];
		for (var j = 0; j < names_inside.length; ++j) {
			if (names_inside[j].north == areas[i].north &&
				names_inside[j].south == areas[i].south &&
				names_inside[j].west == areas[i].west &&
				names_inside[j].east == areas[i].east) {
				exact = names_inside[j];
				break;
			}
			if (window.top.geography.rectIntersect(names_inside[j].south, names_inside[j].north, names_inside[j].west, names_inside[j].east, areas[i].south, areas[i].north, areas[i].west, areas[i].east))
				intersect.push(names_inside[j]);
		}
		if (exact) {
			matches[i] = exact;
			missing_matches--;
			gadm.remove(exact);
			names_inside.remove(exact);
		} else if (intersect.length == 1) {
			matches[i] = intersect[0];
			missing_matches--;
			gadm.remove(intersect[0]);
			names_inside.remove(intersect[0]);
		} else if (intersect.length > 0) {
			match = searchExactMatch(intersect, areas[i].area_name);
			if (match.length == 1) {
				matches[i] = match[0];
				missing_matches--;
				gadm.remove(match[0]);
				names_inside.remove(match[0]);
				continue;
			}
			match = searchAllWords(intersect, areas[i].area_name);
			if (match.length == 1) {
				matches[i] = match[0];
				missing_matches--;
				gadm.remove(match[0]);
				names_inside.remove(match[0]);
				continue;
			}
		}
	}	
	// match with other names
	for (var i = 0; i < areas.length; ++i) {
		if (matches[i] != null) continue;
		for (var j = 0; j < names_near.length; ++j) {
			match = wordsMatch(areas[i].area_name, names_near[j].name);
			if (match.nb_words1_in_words2 >= match.nb_words_1/2) {
				names_near_matching.push(names_near[j]);
				names_near.splice(j,1);
				j--;
			}
		}
		for (var j = 0; j < other_names.length; ++j) {
			match = wordsMatch(areas[i].area_name, other_names[j].name);
			if (match.nb_words1_in_words2 >= match.nb_words_1/2) {
				other_names_matching.push(other_names[j]);
				other_names.splice(j,1);
				j--;
			}
		}
	}
	
	// for remaining names inside, check if this is also inside another parent area where the name exists
	var names_inside_found_somewhere_else = 0;
	if (division_index > 0 && names_inside.length > 0) {
		for (var ni = 0; ni < names_inside.length; ni++) {
			var name = names_inside[ni];
			var gs = parseFloat(name.south);
			var gn = parseFloat(name.north);
			var gw = parseFloat(name.west);
			var ge = parseFloat(name.east);
			for (var i = 0; i < country_data[division_index-1].areas.length; ++i) {
				var a = country_data[division_index-1].areas[i];
				if (a == parent_area) continue;
				if (!a.north) continue;
				var ps = parseFloat(a.south);
				var pn = parseFloat(a.north);
				var pw = parseFloat(a.west);
				var pe = parseFloat(a.east);
				if (window.top.geography.rectContains(ps,pn,pw,pe,gs,gn,gw,ge)) {
					// it is also inside a
					// check if there is this name in a
					var children = window.top.geography.getAreaChildren(country_data, division_index, a.area_id);
					var found = false;
					var n = name.name.trim().latinize().toLowerCase();
					for (var j = 0; j < children.length; ++j)
						if (children[j].area_name.latinize().toLowerCase() == n) { found = true; break; } 
					if (found) {
						// got it !
						names_inside_found_somewhere_else++;
						break;
					}
				}
			}
		}
	}

	if (names_inside.length == names_inside_found_somewhere_else && missing_matches == 0) {
		// everything match, we can continue
		return true;
	}
	var parent_name = division_index == 0 ? parent_area.country_name : parent_area.area_name;

	var table = document.createElement("DIV");
	table.style.padding = "5px";
	table.style.flex = "1 1 auto";
	table.style.display = "flex";
	table.style.flexDirection = "row";
	page_container.appendChild(table);

	var col, col_title, col_content;
	var map = null;
	
	// matching areas in database
	col = document.createElement("DIV");
	col.style.flex = "1 1 auto";
	col.style.display = "flex";
	col.style.flexDirection = "column";
	table.appendChild(col);
	col_title = document.createElement("DIV");
	col_title.style.flex = "none";
	col_title.style.textAlign = "center";
	col_title.style.fontWeight = "bold";
	col_title.appendChild(document.createTextNode("Matching areas in database"));
	col.appendChild(col_title);
	var div = document.createElement("DIV");
	div.style.flex = "none";
	var cb_show = document.createElement("INPUT");
	cb_show.type = "checkbox";
	div.appendChild(cb_show);
	div.appendChild(document.createTextNode("Show all on map"));
	div.onclick = function(ev) {
		if (ev.target == cb_show) return;
		cb_show.checked = cb_show.checked ? "" : "checked";
		cb_show.onchange();
	};
	cb_show.onchange = function() {
		if (!map) {
			setTimeout(function() {cb_show.onchange();}, 100);
			return;
		}
		if (this.checked) {
			this.rects = [];
			for (var i = 0; i < matches.length; ++i) {
				if (matches[i] == null) continue;
				this.rects.push(map.addRect(matches[i].south, matches[i].west, matches[i].north, matches[i].east, map_colors[2].border, map_colors[2].fill, 0.3));
			}
			map.fitToShapes();
		} else {
			if (!this.rects) return;
			for (var i = 0; i < this.rects.length; ++i)
				map.removeShape(this.rects[i]);
			this.rects = null;
		}
	};
	col.appendChild(div);
	col_content = document.createElement("DIV");
	col_content.style.flex = "1 1 auto";
	col_content.style.overflow = "auto";
	col_content.style.maxHeight = "300px";
	col.appendChild(col_content);
	var matching_content = col_content;
	col.style.borderRight = "1px solid #808080";
	col = document.createElement("DIV");
	col.style.flex = "1 1 auto";
	col.style.display = "flex";
	col.style.flexDirection = "column";
	table.appendChild(col);
	col_title = document.createElement("DIV");
	col_title.style.flex = "none";
	col_title.style.textAlign = "center";
	col_title.style.fontWeight = "bold";
	col_title.appendChild(document.createTextNode("Remaining areas in database"));
	col.appendChild(col_title);
	col_content = document.createElement("DIV");
	col_content.style.flex = "1 1 auto";
	col_content.style.overflow = "auto";
	col_content.style.maxHeight = "300px";
	col.appendChild(col_content);
	var remaining_db_content = col_content;
	col.style.borderRight = "1px solid #808080";
	col = document.createElement("DIV");
	col.style.flex = "1 1 auto";
	col.style.display = "flex";
	col.style.flexDirection = "column";
	table.appendChild(col);
	col_title = document.createElement("DIV");
	col_title.style.flex = "none";
	col_title.style.textAlign = "center";
	col_title.style.fontWeight = "bold";
	col_title.appendChild(document.createTextNode("Remaining areas inside "+parent_name+" from gadm"));
	col.appendChild(col_title);
	col_content = document.createElement("DIV");
	col_content.style.flex = "1 1 auto";
	col_content.style.overflow = "auto";
	col_content.style.maxHeight = "300px";
	col.appendChild(col_content);
	var names_content = col_content;
	
	var radios_db = [];
	var radios_names = [];
	
	var createAddButton = function(div, name) {
		var add_button = document.createElement("BUTTON");
		add_button.className = "flat small_icon";
		add_button.innerHTML = "<img src='"+theme.icons_10.add+"'/>";
		add_button.style.marginLeft = "5px";
		add_button.title = "Add this area to the database";
		div.appendChild(add_button);
		add_button._radio_index = radios_names.length-1;
		add_button.onclick = function() {
			var add_button = this;
			var div = document.createElement("DIV");
			var area = {
				area_id: -1,
				area_parent_id: division_index == 0 ? null : parent_area.area_id,
				area_name: name.name,
				north: null, south: null, east: null, west:null
			};
			var area_popup = new popup_window("Add area", null, div);
			import_area_coordinates(div, area, division_index, country, country_data, name, null, null, function(ec, input_name) {
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
							areas.push(area);
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
		if (division_index > 0) {
			var more_button = document.createElement("BUTTON");
			more_button.className = "flat small_icon";
			more_button.style.marginLeft = "5px";
			more_button.innerHTML = "<img src='"+theme.icons_10.arrow_down_context_menu+"'/>";
			div.appendChild(more_button);
			more_button.onclick = function() {
				require("context_menu.js",function() {
					var menu = new context_menu();
					menu.addTitleItem(null,"Additional info regarding "+name.name);
					// search if it is inside others areas at upper level
					var gs = parseFloat(name.south);
					var gn = parseFloat(name.north);
					var gw = parseFloat(name.west);
					var ge = parseFloat(name.east);
					for (var i = 0; i < country_data[division_index-1].areas.length; ++i) {
						var a = country_data[division_index-1].areas[i];
						if (a == parent_area) continue;
						var ps = parseFloat(a.south);
						var pn = parseFloat(a.north);
						var pw = parseFloat(a.west);
						var pe = parseFloat(a.east);
						if (window.top.geography.rectContains(ps,pn,pw,pe,gs,gn,gw,ge)) {
							var item = document.createElement("DIV");
							item.appendChild(document.createTextNode("Inside "+country_data[division_index-1].division_name+" "+a.area_name));
							item.north = pn;
							item.south = ps;
							item.west = pw;
							item.east = pe;
							item.onmouseover = function() {
								if (this.rect) return;
								if (!map) return;
								this.rect = map.addRect(this.south, this.west, this.north, this.east, map_colors[5].border, map_colors[5].fill, 0.3);
							};
							item.onmouseout = function() {
								if (!this.rect) return;
								map.removeShape(this.rect);
								this.rect = null;
							};
							menu.addItem(item);
						}
					}
					// search if it matches other areas in db at the same level
					var n = name.name.toLowerCase().trim();
					for (var i = 0; i < country_data[division_index].areas.length; ++i) {
						var a = country_data[division_index].areas[i];
						if (a.area_parent_id == parent_area.area_id) continue; // same parent
						if (n == a.area_name.toLowerCase()) {
							var item = document.createElement("DIV");
							var p = window.top.geography.getParentArea(country_data, a);
							item.appendChild(document.createTextNode("Exists in "+country_data[division_index-1].division_name+" "+p.area_name));
							menu.addItem(item);
						}
					}
					// display menu
					menu.showBelowElement(more_button);
				});
			};
		}
	};
	var createGadmLink = function(name, radio) {
		var link = document.createElement("A");
		link.style.marginLeft = "5px";
		link.className = "black_link";
		link.href = "#";
		var s = name.name;
		if (name.description) s += " ["+name.description+"]";
		link.appendChild(document.createTextNode(s));
		link.onclick = function() { radio.checked = radio.checked ? "" : "checked"; return false; };
		link.rect = null;
		link.onmouseover = function() {
			if (!map) return;
			if (this.rect) return;
			this.rect = map.addRect(name.south, name.west, name.north, name.east, map_colors[0].border, map_colors[0].fill, 0.3);
			map.fitToShapes();
		};
		link.onmouseout = function() {
			if (!this.rect) return;
			map.removeShape(this.rect);
			this.rect = null;
			map.fitToShapes();
		};
		return link;
	};
	var add_to_db = function(i) {
		var div = document.createElement("DIV");
		remaining_db_content.appendChild(div);
		var radio = document.createElement("INPUT");
		radio.type = "radio";
		radio.name = "db";
		radio.value = i;
		radio.style.marginRight = "5px";
		div.appendChild(radio);
		radios_db.push(radio);
		new editable_cell(div, 'GeographicArea', 'name', areas[i].area_id, 'field_text', {can_be_null:false,max_length:100,min_length:1}, areas[i].area_name, 
			function(text,ec){
				text = text.trim();
				if (!text.checkVisible()) {
					error_dialog("You must enter at least one visible character");
					return ec.area.area_name;
				}
				// check unicity
				for (var i = 0; i < areas.length; ++i)
					if (areas[i] != ec.area && areas[i].area_name.toLowerCase() == text.toLowerCase()) {
						error_dialog("An area already exists with this name");
						return ec.area.area_name;
					}
				ec.area.area_name = text;
				return text;
			}
		).area = areas[i];
		if (areas[i].north) {
			var img = document.createElement("IMG");
			img.src = theme.icons_10.ok;
			img.style.verticalAlign = "bottom";
			img.style.marginLeft = "3px";
			div.appendChild(img);
			img.area = areas[i];
			img.onmouseover = function() {
				if (!map) return;
				if (this.rect) return;
				this.rect = map.addRect(this.area.south, this.area.west, this.area.north, this.area.east, map_colors[1].border, map_colors[1].fill, 0.3);
				map.fitToShapes();
			};
			img.onmouseout = function() {
				if (!this.rect) return;
				map.removeShape(this.rect);
				this.rect = null;
				map.fitToShapes();
			};
		}
		layout.changed(remaining_db_content);
	};
	var add_to_match = function(name) {
		var div = document.createElement("DIV");
		names_content.appendChild(div);
		var radio = document.createElement("INPUT");
		radio.type = "radio";
		radio.name = "names";
		radio._name = name;
		div.appendChild(radio);
		radios_names.push(radio);
		div.appendChild(createGadmLink(name, radio));
		createAddButton(div, name);
		layout.changed(names_content);
	};
	var add_matching = function(i) {
		var div = document.createElement("DIV");
		var link = document.createElement("A");
		link.className = "black_link";
		link.href = "#";
		link.appendChild(document.createTextNode(areas[i].area_name));
		link.onclick = function() { return false; };
		link.rect = null;
		link.onmouseover = function() {
			if (!map) return;
			if (this.rect) return;
			if (areas[i].north)
				this.rect = map.addRect(areas[i].south, areas[i].west, areas[i].north, areas[i].east, map_colors[1].border, map_colors[1].fill, 0.3);
			else
				this.rect = map.addRect(matches[i].south, matches[i].west, matches[i].north, matches[i].east, map_colors[1].border, map_colors[1].fill, 0.3);
			map.fitToShapes();
		};
		link.onmouseout = function() {
			if (!this.rect) return;
			map.removeShape(this.rect);
			this.rect = null;
			map.fitToShapes();
		};
		div.appendChild(link);
		if (areas[i].north) {
			var img = document.createElement("IMG");
			img.src = theme.icons_10.ok;
			img.style.verticalAlign = "bottom";
			img.style.marginLeft = "3px";
			div.appendChild(img);
		}
		var remove = document.createElement("IMG");
		remove.src = theme.icons_10.remove;
		remove.style.verticalAlign = "bottom";
		remove.style.marginLeft = "3px";
		div.appendChild(remove);
		remove.onclick = function() {
			add_to_db(i);
			add_to_match(matches[i]);
			matches[i] = null;
			matching_content.removeChild(div);
		};
		
		matching_content.appendChild(div);
		layout.changed(matching_content);
	};
	for (var i = 0; i < areas.length; ++i) {
		if (matches[i] == null) continue;
		add_matching(i);
	}

	require("editable_cell.js", function() {
		for (var i = 0; i < matches.length; ++i) {
			if (matches[i] != null) continue;
			add_to_db(i);
		}
	});
	
	
	for (var i = 0; i < names_inside.length; ++i) {
		add_to_match(names_inside[i]);
	}
	
	if (names_near_matching.length > 0) {
		col.style.borderRight = "1px solid #808080";
		col = document.createElement("DIV");
		col.style.flex = "1 1 auto";
		col.style.display = "flex";
		col.style.flexDirection = "column";
		table.appendChild(col);
		col_title = document.createElement("DIV");
		col_title.style.flex = "none";
		col_title.style.textAlign = "center";
		col_title.style.fontWeight = "bold";
		col_title.appendChild(document.createTextNode("Areas from gadm near "+parent_name+" partially matching"));
		col.appendChild(col_title);
		col_content = document.createElement("DIV");
		col_content.style.flex = "1 1 auto";
		col_content.style.overflow = "auto";
		col_content.style.maxHeight = "300px";
		col.appendChild(col_content);
		for (var i = 0; i < names_near_matching.length; ++i) {
			var div = document.createElement("DIV");
			col_content.appendChild(div);
			var radio = document.createElement("INPUT");
			radio.type = "radio";
			radio.name = "names";
			radio.value = i;
			radio._name = names_near_matching[i];
			div.appendChild(radio);
			radios_names.push(radio);
			div.appendChild(createGadmLink(names_near_matching[i], radio));
			createAddButton(div, names_near_matching[i]);
		}
	}
	if (other_names_matching.length > 0) {
		col.style.borderRight = "1px solid #808080";
		col = document.createElement("DIV");
		col.style.flex = "1 1 auto";
		col.style.display = "flex";
		col.style.flexDirection = "column";
		table.appendChild(col);
		col_title = document.createElement("DIV");
		col_title.style.flex = "none";
		col_title.style.textAlign = "center";
		col_title.style.fontWeight = "bold";
		col_title.appendChild(document.createTextNode("Other areas from gadm partially matching"));
		col.appendChild(col_title);
		col_content = document.createElement("DIV");
		col_content.style.flex = "1 1 auto";
		col_content.style.overflow = "auto";
		col_content.style.maxHeight = "300px";
		col.appendChild(col_content);
		for (var i = 0; i < other_names_matching.length; ++i) {
			var div = document.createElement("DIV");
			col_content.appendChild(div);
			var radio = document.createElement("INPUT");
			radio.type = "radio";
			radio.name = "names";
			radio.value = i;
			radio._name = other_names_matching[i];
			div.appendChild(radio);
			radios_names.push(radio);
			div.appendChild(createGadmLink(other_names_matching[i], radio));
			createAddButton(div, other_names_matching[i]);
		}
	}
	
	// the map
	col = document.createElement("DIV");
	col.style.flex = "1 1 auto";
	col.style.display = "flex";
	col.style.justifyContent = "center";
	table.appendChild(col);
	var map_container = document.createElement("DIV");
	map_container.style.width = "400px";
	map_container.style.height = "300px";
	col.appendChild(map_container);
	window.top.google.loadGoogleMap(map_container, function(m) {
		map = gm;
		map.addRect(parent_area.south, parent_area.west, parent_area.north, parent_area.east, "#6060F0", "#D0D0F0", 0.2);
		map.fitToShapes();
		layout.changed(container);
	});
	
	// link button
	col = document.createElement("DIV");
	col.style.flex = "none";
	page_container.appendChild(col);
	col.style.textAlign = "center";
	var button_match = document.createElement("BUTTON");
	button_match.className = "action";
	button_match.innerHTML = "Link selected areas";
	col.appendChild(button_match);
	button_match.onclick = function() {
		var radio_db = null, radio_names = null;
		for (var i = 0; i < radios_db.length; ++i) if (radios_db[i].checked) { radio_db = radios_db[i]; break;}
		for (var i = 0; i < radios_names.length; ++i) if (radios_names[i].checked) { radio_names = radios_names[i]; break;}
		if (radio_db == null || radio_names == null) {
			alert("Please select one area from database and one from gadm to link them");
			return;
		}
		var db_index = radio_db.value;
		var name = radio_names._name;
		matches[db_index] = name;
		radios_db.remove(radio_db);
		radios_names.remove(radio_names);
		radio_db.parentNode.parentNode.removeChild(radio_db.parentNode);
		radio_names.parentNode.parentNode.removeChild(radio_names.parentNode);
		add_matching(radio_db.value);
		cb_show.checked = "";
		cb_show.onchange();
	};
	layout.changed(container);
	return false;
}

function import_division_level_coordinates(popup, country, country_data, division_index, parent_area, areas, matches, ondone) {
	var isEverythingDone = function() {
		for (var i = 0; i < areas.length; ++i)
			if (!areas[i].north) return false;
		return true;
	};
	if (isEverythingDone()) {
		setTimeout(ondone,1);
		return;
	}
	
	popup.content.removeAllChildren();
	popup.removeButtons();

	// title
	if (parent_area) {
		var title = createTitle("Import areas in "+parent_area.area_name);
		popup.content.appendChild(title);
	}
	
	// list of areas to be imported
	var div = document.createElement("DIV");
	div.style.display = "inline-block";
	div.margin = "5px";
	popup.content.appendChild(div);
	var title = document.createElement("DIV");
	title.style.textAlign = "center";
	title.style.fontWeight = "bold";
	title.innerHTML = "Areas to import";
	div.appendChild(title);
	var divs = [];
	for (var i = 0; i < areas.length; ++i) {
		var d = document.createElement("DIV");
		d.appendChild(document.createTextNode(areas[i].area_name+" "));
		if (areas[i].north)
			d.innerHTML += "<img src='"+theme.icons_16.ok+"'/>";
		divs.push(d);
		div.appendChild(d);
	}
	
	var manual = function(google_results, geonames_results) {
		var next = function(index) {
			if (index == areas.length) {
				setTimeout(ondone,1);
				return;
			}
			if (areas[index].north) {
				// coordinates are already present, continue to the next area
				next(index+1);
				return;
			}
			popup.content.removeAllChildren();
			popup.removeButtons();
			//popup.content.appendChild(createTitleForArea(country, country_data, division_index, index));
			import_area_coordinates(popup.content, areas[index], division_index, country, country_data, matches[index], google_results ? google_results[index] : null, geonames_results ? geonames_results[index] : null, function(ec) {
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
						key: areas[index].area_id,
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
	};
	
	var auto = function (use_google, use_geonames) {
		var google_results = [];
		var geonames_results = [];
		var finish = function(span,pb) {
			span.innerHTML = "Matching coordinates between different sources...";
			var nb_saving = 0;
			var nb_saved = 0;
			var matching_done = false;
			var check_done = function() {
				if (matching_done && nb_saved == nb_saving) {
					popup.unfreeze();
					if (isEverythingDone())
						setTimeout(ondone,1);
					else
						manual(use_google ? google_results : null, use_geonames ? geonames_results : null);
				}
			};
			var saved = function() {
				span.innerHTML = "Saving geographic coordinates...";
				pb.setTotal(nb_saving);
				pb.setPosition(++nb_saved);
				check_done();
			};
			for (var i = 0; i < areas.length; ++i) {
				if (areas[i].north) continue;
				if (matches[i] == null) continue; // nothing from gadm.org, need manual
				// find in google something matching within 20% of half the size
				matches[i].south = parseFloat(matches[i].south);
				matches[i].north = parseFloat(matches[i].north);
				matches[i].west = parseFloat(matches[i].west);
				matches[i].east = parseFloat(matches[i].east);
				var south = matches[i].south-(matches[i].north-matches[i].south)/2/5;
				var north = matches[i].north+(matches[i].north-matches[i].south)/2/5;
				var west = matches[i].west-(matches[i].east-matches[i].west)/2/5;
				var east = matches[i].east+(matches[i].east-matches[i].west)/2/5;
				var found = false;
				if (use_google)
					for (var j = 0; j < google_results[i].length; ++j) {
						if (window.top.geography.rectContains(south, north, west, east, google_results[i][j].south, google_results[i][j].north, google_results[i][j].west, google_results[i][j].east)) {
							// found one !
							areas[i].north = Math.max(matches[i].north, google_results[i][j].north);
							areas[i].south = Math.min(matches[i].south, google_results[i][j].south);
							areas[i].west = Math.min(matches[i].west, google_results[i][j].west);
							areas[i].east = Math.max(matches[i].east, google_results[i][j].east);
							nb_saving++;
							found = true;
							service.json("data_model","save_entity",{
								table: "GeographicArea",
								key: areas[i].area_id,
								lock: -1,
								field_north: areas[i].north,
								field_south: areas[i].south,
								field_west: areas[i].west,
								field_east: areas[i].east
							},function(res) {
								saved();
							});
							break;
						};
					}
				if (!found && use_geonames)
					for (var j = 0; j < geonames_results[i].length; ++j) {
						if (window.top.geography.rectContains(south, north, west, east, geonames_results[i][j].south, geonames_results[i][j].north, geonames_results[i][j].west, geonames_results[i][j].east)) {
							// found one !
							areas[i].north = Math.max(matches[i].north, geonames_results[i][j].north);
							areas[i].south = Math.min(matches[i].south, geonames_results[i][j].south);
							areas[i].west = Math.min(matches[i].west, geonames_results[i][j].west);
							areas[i].east = Math.max(matches[i].east, geonames_results[i][j].east);
							nb_saving++;
							found = true;
							service.json("data_model","save_entity",{
								table: "GeographicArea",
								key: areas[i].area_id,
								lock: -1,
								field_north: areas[i].north,
								field_south: areas[i].south,
								field_west: areas[i].west,
								field_east: areas[i].east
							},function(res) {
								saved();
							});
							break;
						};
					}
			}
			matching_done = true;
			check_done();
		};
		var todo = areas.length*((use_google ? 1 : 0)+(use_geonames ? 1 : 0));
		var done = 0;
		theme.css("progress_bar.css");
		popup.freeze_progress("Importing information...", todo, function(span,pb){
			var check_finish = function() {
				pb.addAmount(1);
				if (++done == todo)
					finish(span,pb);
			};
			for (var i = 0; i < areas.length; ++i) {
				google_results.push(null);
				geonames_results.push(null);
				if (areas[i].north) {
					if (use_google) check_finish();
					if (use_geonames) check_finish();
					continue;
				}
				if (use_google)
					new SearchGoogle(null, country.country_id, areas[i].area_name, null, areas[i], function(res,div,i) {
						google_results[i] = res;
						check_finish();
					}, true, i);
				if (use_geonames)
					new SearchGeonames(null, country.country_id, areas[i].area_name, null, function(res,div,i) {
						geonames_results[i] = res;
						check_finish();
					}, true, i);
			}
		});
	};
	
	var div_buttons = document.createElement("DIV");
	div_buttons.style.display = "inline-block";
	div_buttons.style.margin = "5px";
	div_buttons.style.verticalAlign = "top";
	popup.content.appendChild(div_buttons);
	div_buttons.appendChild(document.createTextNode("You can try to import automatically:"));
	div_buttons.appendChild(document.createElement("BR"));
	var button_auto1 = document.createElement("BUTTON");
	button_auto1.className = "action";
	button_auto1.innerHTML = "Automatic import matching Google with Gadm";
	div_buttons.appendChild(button_auto1);
	div_buttons.appendChild(document.createElement("BR"));
	var button_auto2 = document.createElement("BUTTON");
	button_auto2.className = "action";
	button_auto2.innerHTML = "Automatic import matching Geonames with Gadm";
	div_buttons.appendChild(button_auto2);
	div_buttons.appendChild(document.createElement("BR"));
	var button_auto3 = document.createElement("BUTTON");
	button_auto3.className = "action";
	button_auto3.innerHTML = "Automatic import matching Google and/or Geonames with Gadm";
	div_buttons.appendChild(button_auto3);
	div_buttons.appendChild(document.createElement("BR"));
	var button_auto4 = document.createElement("BUTTON");
	button_auto4.className = "action";
	button_auto4.innerHTML = "Import coordinates only using Gadm";
	div_buttons.appendChild(button_auto4);
	div_buttons.appendChild(document.createElement("BR"));
	div_buttons.appendChild(document.createTextNode("Or you can do all manually"));
	div_buttons.appendChild(document.createElement("BR"));
	var button_manual = document.createElement("BUTTON");
	button_manual.className = "action";
	button_manual.innerHTML = "Manual import";
	div_buttons.appendChild(button_manual);
	
	button_auto1.onclick = function() {
		auto(true,false);
	};
	button_auto2.onclick = function() {
		auto(false,true);
	};
	button_auto3.onclick = function() {
		auto(true,true);
	};
	button_auto4.onclick = function() {
		popup.freeze_progress("Saving coordinates from gadm.org...", areas.length, function(span,pb){
			var check_finish = function() {
				pb.addAmount(1);
				if (pb.position == pb.total) {
					popup.unfreeze();
					if (isEverythingDone())
						setTimeout(ondone,1);
					else
						manual();
				}
			};
			for (var i = 0; i < areas.length; ++i) {
				if (matches[i] == null)
					check_finish();
				else {
					areas[i].north = matches[i].north;
					areas[i].south = matches[i].south;
					areas[i].west = matches[i].west;
					areas[i].east = matches[i].east;
					service.json("data_model","save_entity",{
						table: "GeographicArea",
						key: areas[i].area_id,
						lock: -1,
						field_north: areas[i].north,
						field_south: areas[i].south,
						field_west: areas[i].west,
						field_east: areas[i].east
					},function(res) {
						check_finish();
					});
				}
			}
		});
	};
	
	button_manual.onclick = function() {
		setTimeout(manual,1);
	};
	
	popup.addIconTextButton(null, "Skip this area", "skip", function() {
		setTimeout(ondone,1);
	});
}

function import_sub_divisions(popup, country, country_data, division_index) {
	if (division_index >= country_data.length-1) {
		// this is the end !!
		popup.close();
		return;
	}
	var gadm = undefined;
	var skip_all_started = false;
	var skip_all_imported = false;
	var next = function(index) {
		if (index == country_data[division_index].areas.length) {
			// all done, go to next division
			import_sub_divisions(popup, country, country_data, division_index+1);
			return;
		}
		var areas = [];
		for (var i = 0; i < country_data[division_index+1].areas.length; ++i) {
			if (country_data[division_index+1].areas[i].area_parent_id == country_data[division_index].areas[index].area_id)
				areas.push(country_data[division_index+1].areas[i]);
		}

		if (skip_all_started) {
			for (var i = 0; i < areas.length; ++i)
				if (areas[i].north) {
					next(index+1);
					return;
				}
		}
		if (skip_all_imported) {
			var all = true;
			for (var i = 0; i < areas.length; ++i)
				if (!areas[i].north) { all = false; break; }
			if (all) {
				next(index+1);
				return;
			}
		}

		import_division_level(popup, country, country_data, division_index+1, index, areas, typeof gadm == 'undefined' ? function(g){gadm=g;} : gadm,function(skip_all, skip_started, skip_imported) {
			if (skip_all) {
				import_sub_divisions(popup, country, country_data, division_index+1);
				return;
			}
			if (skip_started) skip_all_started = true;
			if (skip_imported) skip_all_imported = true;
			next(index+1);
		});
	};
	next(0);
}

function dialog_coordinates(country, country_data, division_index, area_index, onclose, message) {
	require("popup_window.js",function() {
		var content = document.createElement("DIV");
		if (message) {
			var msg_div = document.createElement("DIV");
			msg_div.className = "info_box";
			msg_div.innerHTML = message;
			content.appendChild(msg_div);
		}
		var popup = new popup_window("Geographic Coordinates", "/static/geography/geography_16.png", content);
		if (onclose) popup.onclose = onclose;
		var area;
		if (typeof division_index == 'undefined')
			area = country;
		else
			area = country_data[division_index].areas[area_index];
		popup.show();
		import_area_coordinates(content, area, division_index, country, country_data, null, null, null, function(ec) {
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

function import_area_coordinates(container, area, division_index, country, country_data, from_kml, from_google, from_geonames, onready) {
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
			for (var i = 0; i < sub_areas.length; ++i) {
				all_sub_areas.push(sub_areas[i]);
				if (sub_areas[i].north === null) { sub_areas.splice(i,1); i--; }
			}
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
								var item = menu.addIconItem(null, all_sub_areas[i].area_name, function(ev,i) {
									var d = typeof division_index == 'undefined' ? 0 : division_index+1;
									dialog_coordinates(country, country_data, division_index+1, country_data[d].areas.indexOf(all_sub_areas[i]));
								}, i);
								item.area = all_sub_areas[i];
								item.onmouseover = function() {
									if (this.rect) return;
									if (!this.area.north) return;
									this.rect = new window.top.google.maps.Rectangle({
										bounds: ec.map.createBounds(parseFloat(this.area.south), parseFloat(this.area.west), parseFloat(this.area.north), parseFloat(this.area.east)),
										strokeColor: map_colors[5].border,
										strokeWeight: 2,
										strokeOpacity: 0.7,
										fillColor: map_colors[5].fill,
										fillOpacity: 0.2,
										editable: false,
									});
									ec.map.addShape(this.rect);
								};
								item.onmouseout = function() {
									if (!this.rect) return;
									ec.map.removeShape(this.rect);
									this.rect = null;
								};
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
								ec.map.fitToShapes();
								this.rects.push(rect);
							}
						} else {
							for (var i = 0; i < this.rects.length; ++i)
								ec.map.removeShape(this.rects[i]);
							this.rects = null;
							ec.map.fitToShapes();
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
							ec.map.fitToShapes();
						} else {
							ec.map.removeShape(this.rect);
							this.rect = null;
							ec.map.fitToShapes();
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
								ec.map.fitToShapes();
							}
						} else {
							for (var i = 0; i < this.rects.length; ++i)
								ec.map.removeShape(this.rects[i]);
							this.rects = null;
							ec.map.fitToShapes();
						}
					};
				}
			}
			
			if (from_kml)
				new ResultsList(div, "gadm.org", [from_kml], map_colors[color_index++], ec.coord, ec.map);
			// Geonames
			var geonames_color = map_colors[color_index++];
			if (from_geonames) {
				var search_geonames_div = document.createElement("DIV"); div.appendChild(search_geonames_div);
				new ResultsList(search_geonames_div, "geonames.org", from_geonames, geonames_color, ec.coord, ec.map);
			} else {
				var search_geonames_title = document.createElement("DIV"); div.appendChild(search_geonames_title);
				search_geonames_title.appendChild(document.createTextNode("geonames.org"));
				search_geonames_title.style.fontWeight = "bold";
				search_geonames_title.style.backgroundColor = "#C0C0C0";
				search_geonames_title.style.color = geonames_color.border;
				var search_geonames_div = document.createElement("DIV"); div.appendChild(search_geonames_div);
				new SearchGeonames(search_geonames_div, country.country_id, area.area_name, null, function(res) {
					div.removeChild(search_geonames_title);
					search_geonames_div.removeAllChildren();
					new ResultsList(search_geonames_div, "geonames.org", res, geonames_color, ec.coord, ec.map);
				});
			}
			var google_color = map_colors[color_index++];
			// Google
			if (from_google) {
				var search_google_div = document.createElement("DIV"); div.appendChild(search_google_div);
				new ResultsList(search_google_div, "Google", from_google, google_color, ec.coord, ec.map);
			} else {
				var search_google_title = document.createElement("DIV"); div.appendChild(search_google_title);
				search_google_title.appendChild(document.createTextNode("Google"));
				search_google_title.style.fontWeight = "bold";
				search_google_title.style.backgroundColor = "#C0C0C0";
				search_google_title.style.color = google_color.border;
				var search_google_div = document.createElement("DIV"); div.appendChild(search_google_div);
				new SearchGoogle(search_google_div, country.country_id, area.area_name, null, area, function(res) {
					div.removeChild(search_google_title);
					search_google_div.removeAllChildren();
					new ResultsList(search_google_div, "Google", res, google_color, ec.coord, ec.map);
				});
			}
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
				map.fitToShapes();
			} else {
				map.removeShape(this.rect);
				this.rect = null;
				map.fitToShapes();
			}
		};
		cb.style.marginRight = "5px";
		var link = document.createElement("A");
		link.href = "#";
		link.className = "black_link";
		link.appendChild(document.createTextNode(results[i].name));
		if (results[i].fullname) link.appendChild(document.createTextNode(" ("+results[i].fullname+")"));
		link.title = "Click to use coordinates of this result";
		line.appendChild(link);
		link.result = results[i];
		link.onclick = function() {
			coord.setCoordinates(this.result.north, this.result.east, this.result.south, this.result.west);
			return false;
		};
		line.appendChild(link);
	}
	layout.changed(container);
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
			// filter output
			for (var i = 0; i < output.length; ++i)
				if (output[i].description == "Waterbody") {
					output.splice(i,1);
					i--;
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

function SearchGeonames(container, country_id, name, featureCode, ondone, auto_launch, ondone_param) {
	var div = null;
	if (container) {
		div = document.createElement("DIV");
		div.style.padding = "5px";
		container.appendChild(div);
	}
	
	var launch = function() {
		if (div) {
			div.innerHTML = "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Searching...";
			layout.changed(div);
		}
		
		var i = name.indexOf('(');
		var n = i>0 ? name.substring(0,i).trim() : name;
		var data = {
			country_id: country_id,
			name: n
		};
		if (featureCode) data.featureCode = featureCode;
		service.json("geography", "search_geonames", data, function(res) {
			if (div) {
				var count = res ? res.length : 0;
				div.innerHTML = count+" result(s) found.";
				if (count == 0) {
					var retry = document.createElement("BUTTON");
					retry.className = "flat";
					retry.innerHTML = "<img src='"+theme.icons_16.refresh+"'/> Retry";
					retry.style.marginLeft = "5px";
					div.appendChild(retry);
					retry.onclick = function() {
						new SearchGeonames(container, country_id, name, featureCode, ondone, true, ondone_param);
					};
				}
			}
			ondone(res, div, ondone_param);
		});
	};
	if (auto_launch || !container)
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

function SearchGoogle(container, country_id, name, types, area, ondone, auto_launch, ondone_param) {
	var div = null;
	if (container) {
		div = document.createElement("DIV");
		div.style.padding = "5px";
		container.appendChild(div);
	}
	
	var launch = function() {
		if (div) {
			div.innerHTML = "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Searching...";
			layout.changed(div);
		}
		
		var requests = [];
		var i = name.indexOf('(');
		var n = i > 0 ? name.substring(0,i).trim() : name;
		requests.push({
			country_id: country_id,
			name: n,
			types: types ? types : "political"
		});
		if (area != null) {
			var fullname = n;
			var a = area;
			window.top.geography.getCountryData(country_id,function(country_data){
				while (a.area_parent_id > 0) {
					a = window.top.geography.getParentArea(country_data, a);
					i = a.area_name.indexOf('(');
					var an = i > 0 ? a.area_name.substring(0,i).trim() : a.area_name;
					fullname += ","+an;
				}
			});
			window.top.geography.getCountryName(country_id,function(country_name){
				fullname += ","+country_name;
			});
			requests.push({
				country_id: country_id,
				name: fullname,
				types: types ? types : "political"
			});
		}
		
		var parseResults = function(res) {
			if (!res) res = [];
			for (var i = 0; i < res.length; ++i) {
				if (res[i].geometry) {
					if (res[i].geometry.viewport) {
						res[i].north = parseFloat(res[i].geometry.viewport.northeast.lat);
						res[i].east = parseFloat(res[i].geometry.viewport.northeast.lng);
						res[i].south = parseFloat(res[i].geometry.viewport.southwest.lat);
						res[i].west = parseFloat(res[i].geometry.viewport.southwest.lng);
						res[i].fullname = res[i].formatted_address;
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
			return res;
		};
		
		var requests_results = [];
		var newResults = function(res) {
			requests_results.push(parseResults(res));
			if (requests_results.length < requests.length) return;
			var results = [];
			for (var i = 0; i < requests_results.length; ++i) {
				for (var j = 0; j < requests_results[i].length; ++j) {
					var r = requests_results[i][j];
					var found = false;
					for (var k = 0; k < results.length; ++k)
						if (results[k].id == r.id) { found = true; break; }
					if (!found) results.push(r);
				}
			}
			if (div) {
				div.innerHTML = results.length+" result(s) found.";
				if (results.length == 0) {
					var retry = document.createElement("BUTTON");
					retry.className = "flat";
					retry.innerHTML = "<img src='"+theme.icons_16.refresh+"'/> Retry";
					retry.style.marginLeft = "5px";
					div.appendChild(retry);
					retry.onclick = function() {
						new SearchGoogle(container, country_id, name, types, area, ondone, true, ondone_param);
					};
				}
			}
			ondone(results, div, ondone_param);
		};
		
		var error_limit = false;
		for (var i = 0; i < requests.length; ++i) {
			service.json("geography", "search_google", requests[i], function(res) {
				newResults(res);
			}, false, null, function(error, input) {
				if (error.startsWith("Google replied OVER_QUERY_LIMIT:") && !error_limit) {
					error_limit = true;
					requests.push(input);
					service.json("geography", "search_google", input, function(res) {
						newResults(res);
					});
				}
			});
		}
	};
	if (auto_launch || !container)
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