if (typeof require != 'undefined') {
	require("popup_window.js");
	require("upload.js");
	require("google_maps.js", function() { loadGoogleMaps(function() {}); });
}

function import_coordinates(country, country_data) {
	require("popup_window.js", function() {
		var content = document.createElement("DIV");
		var popup = new popup_window("Import Coordinates", "/static/geography/geography_16.png", content);
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

function import_country(popup, country, country_data) {
	if (country.north) {
		// already set, continue
		import_first_division(popup, country, country_data);
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
		}
		// step 2- display the results, by default take the largest bounds to contain results
		require("edit_coordinates.js", function() {
			search_container.removeAllChildren();
			var table = document.createElement("TABLE"); search_container.appendChild(table);
			var tr = document.createElement("TR"); table.appendChild(tr);
			var td_results = document.createElement("TD"); tr.appendChild(td_results);
			var td_coord = document.createElement("TD"); tr.appendChild(td_coord);
			new EditCoordinatesWithMap(td_coord, country, function(coord, map) {
				// TODO
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
	});
	var google_container = document.createElement("DIV");
	google_container.appendChild(createTitle("Import from Google",2));
	search_container.appendChild(google_container);
	new SearchGoogle(google_container, country.country_id, country.country_name, "country", function(results, div) {
		google = results;
		check_done();
	});
};

function import_first_division(popup, country, country_data) {
	// TODO
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
			ondone(output, t.div);
		};
		up.openDialog(ev);
	};
	
	this._init();
}

function SearchGeonames(container, country_id, name, featureCode, ondone) {
	var div = document.createElement("DIV");
	div.style.padding = "5px";
	container.appendChild(div);
	
	div.innerHTML = "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Searching...";
	
	var data = {
		country_id: country_id,
		name: name
	};
	if (featureCode) data.featureCode = featureCode;
	service.json("geography", "search_geonames", data, function(res) {
		var count = res ? res.length : 0;
		div.innerHTML = count+" result(s) found.";
		ondone(res, div);
	});
}

function SearchGoogle(container, country_id, name, types, ondone) {
	var div = document.createElement("DIV");
	div.style.padding = "5px";
	container.appendChild(div);
	
	div.innerHTML = "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Searching...";
	
	var data = {
		country_id: country_id,
		name: name,
		types: types ? types : "political"
	};
	service.json("geography", "search_google", data, function(res) {
		var count = res ? res.length : 0;
		div.innerHTML = count+" result(s) found.";
		ondone(res, div);
	});
}