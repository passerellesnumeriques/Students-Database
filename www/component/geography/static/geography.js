if (window == window.top && !window.top.geography) {
	var wt = window.top;
	window.top.geography = {
		_countries: null,
		_countries_ongoing: false,
		_countries_listeners: [],
		_countries_data: [],
		_countries_data_listeners: [],
		getCountries: function(onready) {
			if (wt.geography._countries_ongoing) {
				wt.geography._countries_listeners.push(onready);
				return;
			}
			if (wt.geography._countries == null) {
				wt.geography._countries_ongoing = true;
				wt.geography._countries_listeners.push(onready);
				service.json("geography", "get_countries_list", {}, function(result) {
					if (!result) return;
					wt.geography._countries = result;
					wt.geography._countries_ongoing = false;
					for (var i = 0; i < wt.geography._countries_listeners.length; ++i)
						wt.geography._countries_listeners[i](result);
				});
				return;
			}
			onready(wt.geography._countries);
		},
		getCountry: function(country_id, onready) {
			this.getCountries(function(countries) {
				for (var i = 0; i < countries.length; ++i)
					if (countries[i].country_id == country_id) { onready(countries[i]); return; }
			});
		},
		getCountryName: function(country_id, onready) {
			this.getCountry(country_id, function(country) {
				onready(country.country_name);
			});
		},
		getCountryIdFromCode: function(country_code, callback) {
			this.getCountries(function(countries) {
				for (var i = 0; i < countries.length; ++i)
					if (countries[i].country_code.toLowerCase() == country_code.toLowerCase()) {
						callback(countries[i].country_id);
						return;
					}
				callback(null);
			});
		},
		getCountryData: function(country_id, onready) {
			for (var i = 0; i < this._countries_data.length; ++i)
				if (this._countries_data[i].id == country_id) {
					onready(this._countries_data[i].data);
					return;
				}
			for (var i = 0; i < this._countries_data_listeners.length; ++i)
				if (this._countries_data_listeners[i].id == country_id) {
					this._countries_data_listeners[i].listeners.push(onready);
					return;
				}
			this._countries_data_listeners.push({id:country_id,listeners:[onready]});
			var t=this;
			service.json("geography", "get_country_data", {country_id:country_id}, function(result) {
				if (!result) return;
				t._countries_data.push({id:country_id,data:result});
				for (var i = 0; i < t._countries_data_listeners.length; ++i) {
					if (t._countries_data_listeners[i].id == country_id) {
						for (var j = 0; j < t._countries_data_listeners[i].listeners.length; ++j)
							t._countries_data_listeners[i].listeners[j](result);
						t._countries_data_listeners.splice(i,1);
						return;
					}
				}
			});
		},
		getCountryIdFromData: function(country_data) {
			for (var i = 0; i < this._countries_data.length; ++i)
				if (this._countries_data[i].data == country_data)
					return this._countries_data[i].id;
			return -1;
		},
		
		/* Functions to get additional info, and which store this info
		 * Additional info are:
		 *  - parent_area: the parent object
		 *  - division index: in which division index it belongs to
		 *  - division: in which division index it belongs to  
		 */
		
		searchArea: function(country_data, area_id) {
			for (var division = 0; division < country_data.length; ++division) {
				for (var area = 0; area < country_data[division].areas.length; ++area)
					if (country_data[division].areas[area].area_id == area_id) {
						if (typeof country_data[division].areas[area].division_index == 'undefined')
							country_data[division].areas[area].division_index = division;
						if (typeof country_data[division].areas[area].division_id == 'undefined')
							country_data[division].areas[area].division = country_data[division];
						return country_data[division].areas[area];
						return;
					}
			}
		},
		getAreaDivisionIndex: function(country_data, area) {
			if (typeof area.division_index != 'undefined')
				return area.division_index;
			if (typeof area.division != 'undefined')
				return area.division_index = country_data.indexOf(area.division);
			for (var division = 0; division < country_data.length; ++division) {
				if (country_data[division].areas.contains(area)) {
					area.division_index = division;
					area.division = country_data[division];
					return division;
				}
			}
			return -1;
		},
		getAreaDivision: function(country_data, area) {
			if (typeof area.division != 'undefined')
				return area.division;
			if (typeof area.division_index != 'undefined')
				return area.division = country_data[area.division_index];
			for (var division = 0; division < country_data.length; ++division) {
				if (country_data[division].areas.contains(area)) {
					area.division_index = division;
					area.division = country_data[division];
					return area.division;
				}
			}
			return null;
		},
		getAreaDivisionId: function(country_data, area) {
			var div = window.top.geography.getAreaDivision(country_data, area);
			if (div == null) return -1;
			return div.division_id;
		},
		getParentArea: function(country_data, area) {
			if (area.area_parent_id <= 0) return null;
			if (typeof area.parent_area != 'undefined') return area.parent_area;
			var div_index = window.top.geography.getAreaDivisionIndex(country_data, area);
			if (div_index <= 0) return null;
			for (var i = 0; i < country_data[div_index-1].areas.length; ++i)
				if (country_data[div_index-1].areas[i].area_id == area.area_parent_id) {
					area.parent_area = country_data[div_index-1].areas[i];
					area.parent_area.division_index = div_index-1;
					area.parent_area.division = country_data[div_index-1];
					return area.parent_area;
				}
			return null;
		},
		getAreaChildren: function(country_data, division_index, parent_id) {
			var list = [];
			for (var i = 0; i < country_data[division_index].areas.length; ++i)
				if (country_data[division_index].areas[i].area_parent_id == parent_id)
					list.push(country_data[division_index].areas[i]);
			return list;
		},
		getSiblingAreas: function(country_data, division_index, area) {
			var siblings = [];
			if (division_index == 0) {
				for (var i = 0; i < country_data[0].areas.length; ++i)
					if (country_data[0].areas[i] != area) siblings.push(country_data[0].areas[i]);
			} else {
				for (var i = 0; i < country_data[division_index].areas.length; ++i) {
					var a = country_data[division_index].areas[i];
					if (a == area) continue;
					if (a.area_parent_id != area.area_parent_id) continue;
					siblings.push(a);
				}
			}
			return siblings;
		},
		
		
		/* Functions to get information about areas and divisions */
		
		isAreaIncludedIn: function(country_data, area, included_in_id) {
			if (included_in_id == null) return true;
			do {
				if (area.area_parent_id == included_in_id) return true;
				area = window.top.geography.getParentArea(country_data, area);
			} while (area != null);
			return false;
		},
		getAreaFromDivision: function(country_data, area_id, division_index) {
			for (var i = 0; i < country_data[division_index].areas.length; ++i)
				if (country_data[division_index].areas[i].area_id == area_id) {
					var area = country_data[division_index].areas[i];
					area.division_index = division_index;
					area.division = country_data[division_index];
					return area;
				}
			return null;
		},

		searchAreaByNameInDivision: function(country_data, division_index, area_name) {
			area_name = area_name.trim().toLowerCase();
			if (division_index >= country_data.length) return null;
			for (var i = 0; i < country_data[division_index].areas.length; ++i) {
				var area = country_data[division_index].areas[i];
				area.division_index = division_index;
				area.division = country_data[division_index];
				if (area.area_name.toLowerCase() == area_name) return area;
			}
			return null;
		},
		
		searchAreaByNames: function(country_data, areas_names) {
			return window.top.geography._searchAreaByNames(country_data, areas_names, 0, 0);
		},
		_searchAreaByNames: function(country_data, areas_names, start_division_index, start_names_index) {
			for (var division_index = start_division_index; division_index < country_data.length; ++division_index) {
				for (var i = 0; i < country_data[division_index].areas.length; ++i) {
					if (country_data[division_index].areas[i].area_name.toLowerCase() == areas_names[start_names_index].trim().toLowerCase()) {
						// found it !
						if (start_names_index == areas_names.length-1) {
							// last one => return it
							return country_data[division_index].areas[i];
						}
						// continue with next names
						for (var next = start_names_index+1; next < areas_names.length; ++next) {
							var area = window.top.geography._searchAreaByNames(country_data, areas_names, division_index+1, next);
							if (area != null) return area;
						}
						// did not find a next name => return the current one
						return country_data[division_index].areas[i];
					}
				}
			}
			// not found
			return null;
		},
		
		getGeographicAreaTextFromId: function(country_data, area_id) {
			var area = this.searchArea(country_data, area_id);
			if (area == null) return { 
				country_id: window.top.geography.getCountryIdFromData(country_data),
				division_id: -1,
				id: -1,
				text: "Invalid area ID"
			};
		},
		getGeographicAreaText: function(country_data, area) {
			var text = area.area_name;
			var p = area;
			while (p.area_parent_id > 0) {
				p = window.top.geography.getParentArea(country_data, p);
				if (p == null) break;
				text += ", "+p.area_name;
			}
			return {
				country_id: window.top.geography.getCountryIdFromData(country_data),
				division_id: window.top.geography.getAreaDivisionId(country_data, area),
				id: area_id,
				text: text
			};
		},
		getGeographicAreaFullName: function(country_data, area) {
			var text = [area.area_name];
			var p = area;
			while (p.area_parent_id > 0) {
				p = window.top.geography.getParentArea(country_data, p);
				if (p == null) break;
				text.splice(0,0,p.area_name);
			}
			return text;
		}
	};
}