function contact_map(container, title, type, entities_ids, addresses_types) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	
	this.entities = null;
	this.entities_addresses = null;
	this.map = null;
	this.entities_filled = false;
	this.entities_checkboxes = null;
	this.entities_markers = null;
	this.entities_highlight = null;
	
	this._fillEntities = function() {
		this.entities_checkboxes = [];
		this.entities_markers = [];
		this.entities_highlight = [];
		for (var i = 0; i < this.entities.length; ++i) {
			var div = document.createElement("DIV");
			div.style.borderBottom = "1px solid #A0A0A0";
			var highlight = document.createElement("DIV");
			var link = document.createElement("A");
			link.style.fontWeight = "bold";
			link.style.fontSize = "9pt";
			link.appendChild(document.createTextNode(this.getEntityName(this.entities[i])));
			link.className = "black_link";
			link.href = "#";
			link.entity = this.entities[i];
			link.onclick = function() {
				popup_frame(null,t.getEntityName(this.entity),t.getEntityURL(this.entity),null,95,95);
				return false;
			};
			highlight.appendChild(link);
			div.appendChild(highlight);
			this.entities_highlight.push(highlight);
			this._entities_container.appendChild(div);
			var checkboxes = [];
			var markers = [];
			for (var j = 0; j < this.entities_addresses[i].length; ++j) {
				var a = this.entities_addresses[i][j];
				var d = document.createElement("DIV");
				d.style.fontSize = "8pt";
				d.style.display = "flex";
				d.style.flexDirection = "row";
				div.appendChild(d);
				var cb = document.createElement("INPUT");
				cb.type = "checkbox";
				cb.style.margin = "0px";
				cb.style.marginLeft = "5px";
				cb.style.marginRight = "2px";
				cb.style.verticalAlign = "bottom";
				cb.style.flex = "none";
				checkboxes.push(cb);
				markers.push(null);
				d.appendChild(cb);
				if (addresses_types.contains(a.address_type)) cb.checked = "checked";
				cb.onchange = function() { t.refreshMap(); };
				var span_type = document.createElement("SPAN");
				span_type.style.color = "#808080";
				span_type.style.fontStyle = "italic";
				span_type.appendChild(document.createTextNode(a.address_type));
				span_type.style.marginRight = "5px";
				span_type.style.flex = "none";
				d.appendChild(span_type);
				var span_text = document.createElement("DIV");
				span_text.style.display = "inline-block";
				span_text.style.flex = "1 1 auto";
				span_text.appendChild(document.createTextNode(a.geographic_area ? a.geographic_area.text : ""));
				d.appendChild(span_text);
			}
			this.entities_checkboxes.push(checkboxes);
			this.entities_markers.push(markers);
		}
		this.entities_filled = true;
	};
	this.refreshMap = function() {
		for (var i = 0; i < this.entities.length; ++i) {
			var entity = this.entities[i];
			var shown = false;
			for (var j = 0; j < this.entities_addresses[i].length; ++j) {
				var a = this.entities_addresses[i][j];
				var cb = this.entities_checkboxes[i][j];
				if (cb.checked) {
					if (this.entities_markers[i][j]) continue;
					if (a.lat && a.lng) {
						this.entities_markers[i][j] = new window.top.PNMapMarker(a.lat,a.lng,'#C0C0FF',this.getEntityMarkerContent(entity));
						this.map.addShape(this.entities_markers[i][j]);
						shown = true;
					} else if (a.country_id && a.geographic_area && a.geographic_area.id) {
						var marker = new window.top.PNMapMarker(0,0,'#C0C0FF',this.getEntityMarkerContent(entity));
						this.entities_markers[i][j] = marker;
						shown = true;
						window.top.geography.getCountryData(a.country_id, function(country_data){
							var area = window.top.geography.searchArea(country_data, a.geographic_area.id);
							if (area && area.north) {
								var lat = area.south+(area.north-area.south)/2;
								var lng = area.west+(area.east-area.west)/2;
								marker.setPosition(lat,lng);
								t.map.addShape(marker);
							}
						});
					}
				} else {
					if (!this.entities_markers[i][j]) continue;
					this.map.removeShape(this.entities_markers[i][j]);
					this.entities_markers[i][j] = null;
				}
			}
			if (shown) {
				//this.entities_highlight[i].style.background = "linear-gradient(to bottom, #FFF0D0 0%, #F0D080 100%)";
				this.entities_highlight[i].markers = this.entities_markers[i]; 
				this.entities_highlight[i].onmouseover = function() {
					this.style.background = "#C0F0C0";
					for (var i = 0; i < this.markers.length; ++i) {
						if (!this.markers[i]) continue;
						this.markers[i].setColor("#C0F0C0");
						this.markers[i].bringToFront();
					}
				};
				this.entities_highlight[i].onmouseout = function() {
					//this.style.background = "linear-gradient(to bottom, #FFF0D0 0%, #F0D080 100%)";
					this.style.background = "";
					for (var i = 0; i < this.markers.length; ++i) {
						if (!this.markers[i]) continue;
						this.markers[i].setColor("#C0C0F0");
						this.markers[i].cancelBringToFront();
					}
				};
			} else {
				this.entities_highlight[i].style.background = "#FFFFFF";
				this.entities_highlight[i].onmouseover = null;
				this.entities_highlight[i].onmouseout = null;
			}
		}
		this.map.fitToShapes();
	};
	
	this._init = function() {
		var oneReady = function() {
			if (t.entities != null && t.entities_addresses != null) {
				if (!t.entities_filled) t._fillEntities();
				if (t.map) t.refreshMap();
			}
		};
		// retrieve entities' info
		if (type == 'people') {
			service.json('people','get_peoples',{ids:entities_ids},function(peoples){ t.entities = peoples; oneReady(); });
			this.getEntityName = function(people) { return people.last_name+" "+people.first_name; };
			require("profile_picture.js");
			this.getEntityMarkerContent = function(people) {
				var div = document.createElement("DIV");
				div.style.textAlign = "center";
				div.style.fontSize = "8pt";
				var picture_container = document.createElement("DIV");
				picture_container.style.display = "inline-block";
				picture_container.style.width = "50px";
				picture_container.style.height = "50px";
				div.appendChild(picture_container);
				div.appendChild(document.createElement("BR"));
				div.appendChild(document.createTextNode(people.last_name));
				div.appendChild(document.createElement("BR"));
				div.appendChild(document.createTextNode(people.first_name));
				require("profile_picture.js",function() {
					new profile_picture(picture_container,50,50,"center","center").loadPeopleObject(people);
				});
				return div;
			};
			this.getEntityURL = function(people) {
				return "/dynamic/people/page/profile?people="+people.id;
			};
		} else {
			// TODO
		}
		// retrieve addresses
		service.json('contact','get_addresses',{type:type,ids:entities_ids},function(res) { t.entities_addresses = res; oneReady(); });
		// layout
		container.style.display = "flex";
		container.style.flexDirection = "column";
		this._div_title = document.createElement("DIV");
		this._div_title.style.flex = "none";
		this._div_title.className = "page_title";
		this._div_title.innerHTML = "<img src='/static/contact/map_32.png'/> ";
		this._div_title.appendChild(document.createTextNode(title));
		container.appendChild(this._div_title);
		this._header = document.createElement("DIV");
		this._header.style.flex = "none";
		container.appendChild(this._header);
		this._content = document.createElement("DIV");
		this._content.style.flex = "1 1 auto";
		this._content.style.display = "flex";
		this._content.style.flexDirection = "row";
		container.appendChild(this._content);
		this._entities_container = document.createElement("DIV");
		this._entities_container.style.flex = "none";
		this._entities_container.style.overflow = "auto";
		this._entities_container.style.backgroundColor = "white";
		this._content.appendChild(this._entities_container);
		this._map_container = document.createElement("DIV");
		this._map_container.style.flex = "1 1 auto";
		this._map_container.style.position = "relative";
		this._content.appendChild(this._map_container);
		window.top.google.loadGoogleMap(this._map_container, function(map) {
			t.map = map;
			oneReady();
		});
	};
	this._init();
}