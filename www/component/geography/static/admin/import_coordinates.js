if (typeof require != 'undefined') {
	require("wizard.js");
	require("upload.js");
	require("google_maps.js", function() { loadGoogleMaps(function() {}); });
}

function import_coordinates(country, country_data) {
	require("wizard.js", function() {
		var wiz = new wizard();
		var init_page = {
			icon: theme.icons_32.loading,
			title: "Loading, please wait...",
			content: document.createElement("DIV")
		};
		wiz.addPage(init_page);
		require(["google_maps.js","upload.js"], function() {
			var map_container = document.createElement("DIV");
			map_container.style.width = "400px";
			map_container.style.height = "300px";
			new GoogleMap(map_container, function(map) {
				new ImportCountry(wiz, country, country_data, map);
				for (var i = 0; i < country_data.length; ++i)
					new ImportDivision(wiz, country, country_data, i, map);
				// remove the init page
				wiz.removePage(0);
			});
		});
		wiz.launch();
	});
}

function searchExactMatch(results, name) {
	var list = [];
	name = name.trim().toLowerCase();
	for (var i = 0; i < results.length; ++i)
		if (results[i].name.trim().toLowerCase() == name)
			list.push(results[i]);
	return list;
}

function setEntityCoordinates(entity, coord) {
	entity.north = coord.north;
	entity.south = coord.south;
	entity.west = coord.west;
	entity.east = coord.east;
}

function handleResults(results, entities, entity_name_getter) {
	for (var i = 0; i < entities.length; ++i) {
		var matches = searchExactMatch(results, entity_name_getter(entities[i]));
		if (matches.length == 1) {
			// we have one !
			results.remove(matches[0]);
			setEntityCoordinates(entities[i], matches[0]);
			continue;
		}
	}
	// TODO continue
}

function ImportCountry(wiz, country, country_data, map) {
	if (country.north) {
		new PageCoordinatesDone(wiz, "Country "+country.country_name);
		return;
	}
	var page = {
		icon: "/static/geography/geography_32.png",
		title: "Country "+country.country_name
	};
	page.content = document.createElement("DIV");
	page.content.style.padding = "10px";
	page.validate = function(wiz, handler) {
		if (!country.north)
			handler(false);
		handler(true);
	};
	new ImportKML(page.content, function(results, content) {
		handleResults(results, [country], function(c) { return c.country_name; });
		wiz.validate();
		if (country.north) {
			page.content.innerHTML = "<img src='"+theme.icons_16.ok+"' style='vertical-align:bottom'/> Coordinates successfully imported. You can continue to next step.";
			return;
		}
	});
	wiz.addPage(page);
}

function ImportDivision(wiz, country, country_data, division_index, map) {
	var total_areas = country_data[division_index].areas.length;
	var to_search = [];
	for (var i = 0; i < country_data[division_index].areas.length; ++i)
		if (!country_data[division_index].areas[i].north)
			to_search.push(country_data[division_index].areas[i]);
	
	if (to_search.length == 0) {
		new PageCoordinatesDone(wiz, "the "+total_areas+" areas in division "+country_data[division_index].division_name);
		return;
	}
	
	var page = {
		icon: "/static/geography/geography_32.png",
		title: "Division "+country_data[division_index].division_name
	};
	page.content = document.createElement("DIV");
	page.content.style.padding = "10px";
	page.content.appendChild(document.createTextNode(total_areas+" areas in this division, "));
	var span_nb_missing = document.createElement("SPAN");
	span_nb_missing.innerHTML = to_search.length;
	page.content.appendChild(span_nb_missing);
	page.content.appendChild(document.createTextNode(" don't have coordinates."));
	page.content.appendChild(document.createElement("BR"));
	
	page.validate = function(wiz, handler) {
		handler(to_search.length == 0);
	};
	new ImportKML(page.content, function(results, content) {
		handleResults(results, to_search, function(obj) {
			return obj.area_name;
		});
		var done = [];
		for (var i = 0; i < to_search.length; ++i) {
			if (to_search[i].north) {
				done.push(to_search[i]);
				to_search.splice(i,1);
				i--;
			}
		}
		if (to_search.length == 0) {
			page.content.innerHTML = "<img src='"+theme.icons_16.ok+"' style='vertical-align:bottom'/> Coordinates successfully imported. You can continue to next step.";
		} else {
			page.content.innerHTML = "We found coordinates for "+done.length+" areas, "+to_search.length+" not found.";
			if (results.length > 0) {
				// we still have some results, so we ask the user to try to match them
				new AskToMatch()
			}
			s += "<ul>";
			for (var i = 0; i < to_search.length; ++i) s += "<li>"+to_search[i].area_name+"</li>";
			s += "</ul>Remaining results:<ul>";
			for (var i = 0; i < results.length; ++i) s += "<li>"+results[i].name+"</li>";
			s += "</ul>";
			page.content.innerHTML = s;
		}
		wiz.validate();
	});
	wiz.addPage(page);
}

function PageCoordinatesDone(wiz, message) {
	this.icon = "/static/geography/geography_32.png";
	this.title = message;
	this.content = document.createElement("DIV");
	this.content.style.padding = "10px";
	this.validate = function() { return null; };
	
	this.content.innerHTML = "The coordinates of "+message+" are already set, you can move to the next step.";
}

function ImportKML(container, ondone) {
	this.div = document.createElement("DIV");
	container.appendChild(this.div);
	
	this._init = function() {
		var link = document.createElement("A");
		link.href = '#';
		link.className = "black_link";
		link.innerHTML = "Import from KML file (Google Earth Format)";
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
		up.onprogressfile = function(file, uploaded, total) {
			if (uploaded < total)
				t.div.innerHTML = "Uploading file ("+Math.floor(uploaded*100/total)+"%)...";
			else
				t.div.innerHTML = "Analyzing file...";
		};
		up.ondonefile = function(file, output, errors) {
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
			ondone(output, t.div);
		};
		up.openDialog(ev);
	};
	
	this._init();
}