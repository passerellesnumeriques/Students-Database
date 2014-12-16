if (typeof window.top.require != 'undefined') window.top.require("geography.js");if (typeof require != 'undefined') {	require("geographic_area_selection.js");	require("select.js");}/** * This javascript does not save / lock any data. When users update a field, the structure object is updated (with an additional field corresponding to the country code) * @param {Element} container where to display * @param {PostalAddress} address the address to edit */function edit_address(container, address){	if(typeof(container) == 'string') container = document.getElementById(container);	var t = this;	t.address = address;	/** Flag to indicate if we are currently handling the change of exact position, and avoid infinite loop between exact location and geographic area */	t._in_change_pos = false;	/** Create the part containing the geographic area */	t._initArea = function() {		while (t.area_div.childNodes.length > 0) t.area_div.removeChild(t.area_div.childNodes[0]);		if (t.select_country.value == 0) return;		if (t._initializing_area) { t._reinit_area = true; return; }		t._initializing_area = true;		require("geographic_area_selection.js", function() {			var country_id = t.select_country.getSelectedValue();			t.area_selection = new geographic_area_selection(t.area_div, country_id, t.address.geographic_area ? t.address.geographic_area.id : null, 'vertical', true, function(area) {				area.onchange = function() {					t.address.geographic_area = area.getSelectedAreaText();					if (!t._in_change_pos) {						t._updateMap();						//t.lat_input.value = "";						//t.lng_input.value = "";						//t._setPosition(null, null);					}				};				t._initializing_area = false;				if (t._reinit_area) {					t._reinit_area = false;					t._initArea();				}			});		});	};		/** Create a table with a title 	 * @param {String} title the title	 * @param {Element} container where to put the table	 * @returns {Element} the cell where we can put the content	 */	t._createTable = function(title, container) {		var table = document.createElement("TABLE"); container.appendChild(table);		var thead = document.createElement("THEAD"); table.appendChild(thead);		var tbody = document.createElement("TBODY"); table.appendChild(tbody);		var tr = document.createElement("TR"); thead.appendChild(tr);		var td = document.createElement("TD"); tr.appendChild(td);		table.style.border = "1px solid #808080";		table.style.borderSpacing = "0";		table.style.marginBottom = "3px";		setBorderRadius(table, 5, 5, 5, 5, 5, 5, 5, 5);		td.style.textAlign = "center";		td.style.padding = "2px 5px 2px 5px";		td.innerHTML = title;		td.style.backgroundColor = "#F0F0F0";		td.style.borderBottom = "1px solid #808080";		setBorderRadius(td, 5, 5, 5, 5, 0, 0, 0, 0);		tr = document.createElement("TR"); tbody.appendChild(tr);		td = document.createElement("TD"); tr.appendChild(td);		td.style.padding = "0px";		return td;	};	/**	 * Update the Google Map, with marker and/or rectangle, corresponding to the exact location/geographic area	 * @param {Boolean} keep_map if true, we won't zoom/move the map	 */	t._updateMap = function(keep_map) {		if (!t.map) return;		// marker		if (t.address.lat) {			if (!t.marker)				t.marker = t.map.addMarker(parseFloat(t.address.lat), parseFloat(t.address.lng), 1);			else				t.marker.setPosition(new window.top.google.maps.LatLng(parseFloat(t.address.lat), parseFloat(t.address.lng)));		} else {			if (t.marker) {				t.map.removeShape(t.marker);				t.marker = null;			}		}		// area rectangle		if (t.address.geographic_area && t.address.geographic_area.id && t.address.geographic_area.id > 0) {			window.top.geography.getCountryData(t.select_country.getSelectedValue(), function(country_data) {				var area = window.top.geography.searchArea(country_data, t.address.geographic_area.id);				while (area) {					if (area.north) break;					area = window.top.geography.getParentArea(country_data, area);				}				if (!area && !t.address.lat) {					if (t.area_rect) {						t.map.removeShape(t.area_rect);						t.area_rect = null;					}					window.top.geography.getCountry(t.select_country.getSelectedValue(), function(country) {						if (country && country.north)							t.map.fitToBounds(parseFloat(country.south), parseFloat(country.west), parseFloat(country.north), parseFloat(country.east));					});				}				if (area) {					if (!t.area_rect)						t.area_rect = t.map.addRect(parseFloat(area.south), parseFloat(area.west), parseFloat(area.north), parseFloat(area.east), "#8080FF", "#D0D0F0", 0.4);					else						t.area_rect.setBounds(t.map.createBounds(parseFloat(area.south), parseFloat(area.west), parseFloat(area.north), parseFloat(area.east)));				}			});		} else {			if (t.area_rect) {				t.map.removeShape(t.area_rect);				t.area_rect = null;			}			if (!t.address.lat) {				window.top.geography.getCountry(t.select_country.getSelectedValue(), function(country) {					if (country && country.north)						t.map.fitToBounds(parseFloat(country.south), parseFloat(country.west), parseFloat(country.north), parseFloat(country.east));				});			}		}		if (t.marker || t.area_rect)			if (!keep_map)				t.map.fitToShapes();	};	/**	 * Set the exact location	 * @param {Number} lat latitude	 * @param {Number} lng longitude	 * @param {Boolean} keep_map if true, we won't zoom/move the map to this new position	 */	t._setPosition = function(lat,lng,keep_map) {		t._in_change_pos = true;		t.address.lat = lat;		t.address.lng = lng;		if (lat !== null)			t.area_selection.selectByGeographicPosition(lat,lng);		t._updateMap(keep_map);		t._in_change_pos = false;	};	/** Create the display */	t._init = function() {		var table, tr, td;		// table to split geographic area, and postal address		container.appendChild(table = document.createElement("TABLE"));		table.appendChild(tr = document.createElement("TR"));		tr.appendChild(td = document.createElement("TD"));		td.style.verticalAlign = "top";		var tbody = t._createTable("Geographic Area", td);		t.country_div = document.createElement("DIV");		t.country_div.style.borderBottom = "1px solid #808080";		t.country_div.style.padding = "2px";		tbody.appendChild(t.country_div);		t.area_div = document.createElement("DIV");		t.area_div.style.padding = "2px";		tbody.appendChild(t.area_div);		tr.appendChild(td = document.createElement("TD"));		td.style.verticalAlign = "top";		tbody = t._createTable("Postal Address", td);		t.postal_div = document.createElement("DIV");		t.postal_div.style.padding = "2px";		tbody.appendChild(t.postal_div);				// map		var map_coordinates = document.createElement("DIV");		container.appendChild(map_coordinates);		map_coordinates.appendChild(document.createTextNode("Geographic location: "));		map_coordinates.appendChild(document.createTextNode("Latitude"));		t.lat_input = document.createElement("INPUT");		t.lat_input.type = "text";		t.lat_input.size = 5;		t.lat_input.onchange = function() {			var lat = parseFloat(t.lat_input.value);			var lng = parseFloat(t.lng_input.value);			if (isNaN(lat) || t.lat_input.value.length == 0 || isNaN(lng) || t.lng_input.value.length == 0)				t._setPosition(null,null);			else				t._setPosition(lat,lng);		};		if (t.address.lat) t.lat_input.value = t.address.lat;		map_coordinates.appendChild(t.lat_input);		map_coordinates.appendChild(document.createTextNode(" Longitude"));		t.lng_input = document.createElement("INPUT");		t.lng_input.type = "text";		t.lng_input.size = 5;		t.lat_input.onchange = function() {			var lat = parseFloat(t.lat_input.value);			var lng = parseFloat(t.lng_input.value);			if (isNaN(lat) || t.lat_input.value.length == 0 || isNaN(lng) || t.lng_input.value.length == 0)				t._setPosition(null,null);			else				t._setPosition(lat,lng);		};		if (t.address.lng) t.lng_input.value = t.address.lng;		map_coordinates.appendChild(t.lng_input);		map_coordinates.appendChild(document.createElement("BR"));		var msg = document.createElement("I");		msg.appendChild(document.createTextNode("Double-click on the map the set the exact location"));		map_coordinates.appendChild(msg);		var map_container = document.createElement("DIV");		map_container.style.width = "450px";		map_container.style.height = "280px";		container.appendChild(map_container);		t.map = null;		t.marker = null;		/* Country */		t.country_div.style.whiteSpace = "nowrap";		t.country_div.appendChild(document.createTextNode("Country "));		require("select.js", function() {			t.select_country = new select(t.country_div);			t.select_country.getHTMLElement().style.verticalAlign = "bottom";			window.top.require("geography.js", function() {				window.top.geography.getCountries(function(countries) {					t.countries = countries;					for(var i = 0; i < countries.length; i++){						t.select_country.add(							countries[i].country_id,							"<img src='/static/geography/flags/"+countries[i].country_code.toLowerCase()+".png' style='vertical-align:bottom;padding-right:2px' />"+countries[i].country_name						);					}					if(!t.select_country.onchange && t.address.country_id == null)						t.address.country_id = t.select_country.getSelectedValue();											t.select_country.select(t.address.country_id);					t._initArea();					/* Set onchange after the initialization */					t.select_country.onchange = function(){						t.address.country_id = t.select_country.getSelectedValue();						t.address.geographic_area = null;						t._initArea();						t._updateMap();					};					// create the map					window.top.google.loadGoogleMap(map_container, function(m) {						window.top.google.maps.event.addListener(m.map, 'dblclick', function(ev) {							t.lat_input.value = ev.latLng.lat();							t.lng_input.value = ev.latLng.lng();							t._setPosition(ev.latLng.lat(), ev.latLng.lng(), true);							ev.stop();						});						t.map = m;						t._updateMap();					});				});			});		});				/* Building and Unit */		t.postal_div.appendChild(table = document.createElement("TABLE"));		table.style.borderCollapse = "collapse";		table.style.borderSpacing = "0";		table.appendChild(tr = document.createElement("TR"));		tr.appendChild(td = document.createElement("TD"));		td.style.color = "#808080";		td.style.fontStyle = "italic";		td.style.fontSize = "80%";		td.style.padding = "0px";		td.innerHTML = "Building";		tr.appendChild(td = document.createElement("TD"));		td.style.color = "#808080";		td.style.fontStyle = "italic";		td.style.fontSize = "80%";		td.style.padding = "0px";		td.innerHTML = "Unit";		table.appendChild(tr = document.createElement("TR"));		tr.appendChild(td = document.createElement("TD"));		td.style.padding = "0px";		td.appendChild(t.input_building = document.createElement("INPUT"));		t.input_building.type = 'text';		t.input_building.value = t.address.building;		t.input_building.onchange = function() { t.address.building = t.input_building.value; };		require("input_utils.js", function() { inputAutoresize(t.input_building, 10); });		tr.appendChild(td = document.createElement("TD"));		td.style.padding = "0px";		td.appendChild(t.input_unit = document.createElement("INPUT"));		t.input_unit.type = 'text';		t.input_unit.value = t.address.unit;		t.input_unit.onchange = function() { t.address.unit = t.input_unit.value; };		require("input_utils.js", function() { inputAutoresize(t.input_unit, 5); });				/* Street number and street name */		t.postal_div.appendChild(table = document.createElement("TABLE"));		table.style.borderCollapse = "collapse";		table.style.borderSpacing = "0";		table.appendChild(tr = document.createElement("TR"));		tr.appendChild(td = document.createElement("TD"));		td.style.color = "#808080";		td.style.fontStyle = "italic";		td.style.fontSize = "80%";		td.style.padding = "0px";		td.innerHTML = "Number";		tr.appendChild(td = document.createElement("TD"));		td.style.color = "#808080";		td.style.fontStyle = "italic";		td.style.fontSize = "80%";		td.style.padding = "0px";		td.innerHTML = "Street";		table.appendChild(tr = document.createElement("TR"));		tr.appendChild(td = document.createElement("TD"));		td.style.padding = "0px";		td.appendChild(t.input_street_number = document.createElement("INPUT"));		t.input_street_number.type = 'text';		t.input_street_number.value = t.address.street_number;		t.input_street_number.onchange = function() { t.address.street_number = t.input_street_number.value; };		require("input_utils.js", function() { inputAutoresize(t.input_street_number, 5); });		tr.appendChild(td = document.createElement("TD"));		td.style.padding = "0px";		td.appendChild(t.input_street_name = document.createElement("INPUT"));		t.input_street_name.type = 'text';		t.input_street_name.value = t.address.street;		t.input_street_name.onchange = function() { t.address.street = t.input_street_name.value; };		require("input_utils.js", function() { inputAutoresize(t.input_street_name, 10); });		/* Additional */		t.postal_div.appendChild(table = document.createElement("TABLE"));		table.style.borderCollapse = "collapse";		table.style.borderSpacing = "0";		table.appendChild(tr = document.createElement("TR"));		tr.appendChild(td = document.createElement("TD"));		td.style.color = "#808080";		td.style.fontStyle = "italic";		td.style.fontSize = "80%";		td.style.padding = "0px";		td.innerHTML = "Additional information";		table.appendChild(tr = document.createElement("TR"));		tr.appendChild(td = document.createElement("TD"));		td.style.padding = "0px";		td.appendChild(t.input_additional = document.createElement("INPUT"));		t.input_additional.type = 'text';		t.input_additional.value = t.address.additional;		t.input_additional.onchange = function() { t.address.additional = t.input_additional.value; };		require("input_utils.js", function() { inputAutoresize(t.input_additional, 15); });	};	t._init();}