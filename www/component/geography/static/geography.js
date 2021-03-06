if (window == window.top && !window.top.geography) {
	var wt = window.top;
	/**
	 * Functionalities to get geographic information
	 */
	window.top.geography = {
		/** {Array} the list of countries */
		_countries: null,
		/** Indicates if we are currently retrieving the list of countries */
		_countries_ongoing: false,
		/** List of functions waiting for the list of countries */
		_countries_listeners: [],
		/** Geographic data for countries */
		_countries_data: [],
		/** Functions waiting for geographic data of a country */
		_countries_data_listeners: [],
		/** Get the list of countries
		 * @param {Function} onready called with the list of countries (eash being a CountryInfo), when it's loaded
		 */
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
		/** Get a country information
		 * @param {Number} country_id the country to get
		 * @param {Function} onready called when the information is ready (with a CountryInfo as parameter)
		 */
		getCountry: function(country_id, onready) {
			this.getCountries(function(countries) {
				for (var i = 0; i < countries.length; ++i)
					if (countries[i].country_id == country_id) { onready(countries[i]); return; }
			});
		},
		/** Given a list of countries, search the given country by id
		 * @param {Number} country_id the country to search
		 * @param {Array} countries the list of known countries
		 * @returns {CountryInfo} the searched country, or null if not found
		 */
		getCountryFromList: function(country_id, countries) {
			for (var i = 0; i < countries.length; ++i)
				if (countries[i].country_id == country_id) return countries[i];
			return null;
		},
		/** Get the name of a country from its id
		 * @param {Number} country_id the id
		 * @param {Function} onready called with the name as parameter once found
		 */
		getCountryName: function(country_id, onready) {
			this.getCountry(country_id, function(country) {
				onready(country.country_name);
			});
		},
		/** Get the id of a country from its code
		 * @param {String} country_code the code to search
		 * @param {Function} callback called with the country id once found, or null if not found
		 */
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
			service.json("geography", "get_country_timestamp", {country_id:country_id}, function(timestamp) {
				ajax.call("GET", "/static/geography/country_data.js.php?id="+country_id+"&ts="+timestamp, null, null, function(error) {
					window.top.status_manager.addStatus(new window.top.StatusMessage(window.top.Status_TYPE_ERROR,error,[{action:"popup"},{action:"close"}],10000));
				}, function(xhr) {
					var result = eval("("+xhr.responseText+")");
					t._countries_data.push({id:country_id,data:result});
					t._processIdMapping(result);
					for (var i = 0; i < t._countries_data_listeners.length; ++i) {
						if (t._countries_data_listeners[i].id == country_id) {
							for (var j = 0; j < t._countries_data_listeners[i].listeners.length; ++j)
								t._countries_data_listeners[i].listeners[j](result);
							t._countries_data_listeners.splice(i,1);
							return;
						}
					}
				}, false);
			});
		},
		getCountryIdFromData: function(country_data) {
			for (var i = 0; i < this._countries_data.length; ++i)
				if (this._countries_data[i].data == country_data)
					return this._countries_data[i].id;
			return -1;
		},
		getCountryArea: function(country_id, area_id, onready) {
			this.getCountryData(country_id, function(country_data) {
				onready(window.top.geography.searchArea(country_data, area_id));
			});
		},
		getCountryAreaRect: function(country_id, area_id, onready) {
			this.getCountryData(country_id, function(country_data) {
				var area = window.top.geography.searchArea(country_data, area_id);
				while (!area.north) {
					if (area.area_parent_id > 0) {
						area = window.top.geography.getParentArea(country_data, area);
						continue;
					}
					window.top.geography.getCountry(country_id, function(country) {
						if (country.north) onready(country); else onready(null);
					});
					return;
				}
				onready(area);
			});
		},
		
		_processIdMapping: function(country_data, division_index, area_index, mapping) {
			if (country_data.length == 0) return;
			if (!division_index) division_index = 0;
			if (!area_index) area_index = 0;
			if (!mapping) mapping = {};
			var done = 0;
			for (var di = division_index; di < country_data.length; ++di)
				for (var ai = di == division_index ? area_index : 0; ai < country_data[di].areas.length; ++ai) {
					mapping[country_data[di].areas[ai].area_id] = country_data[di].areas[ai];
					if (++done >= 250) {
						var t=this;
						setTimeout(function() {
							t._processIdMapping(country_data, di, ai, mapping);
						},100);
						return;
					}
				}
			country_data[0]._mapping = mapping;
		},
		
		/* Functions to get additional info, and which store this info
		 * Additional info are:
		 *  - parent_area: the parent object
		 *  - division index: in which division index it belongs to
		 *  - division: in which division index it belongs to  
		 */
		
		/** Optimize same search */
		_last_search_area: [],
		searchArea: function(country_data, area_id) {
			if (country_data.length > 0 && typeof country_data[0]._mapping != 'undefined')
				return country_data[0]._mapping[area_id];
			for (var i = 0; i < this._last_search_area.length; ++i)
				if (this._last_search_area[i].country_data == country_data && this._last_search_area[i].area_id == area_id)
					return this._last_search_area[i].result;
			if (this._last_search_area.length == 10)
				this._last_search_area.splice(0,1);
			for (var division = 0; division < country_data.length; ++division) {
				for (var area = 0; area < country_data[division].areas.length; ++area)
					if (country_data[division].areas[area].area_id == area_id) {
						if (typeof country_data[division].areas[area].division_index == 'undefined')
							country_data[division].areas[area].division_index = division;
						if (typeof country_data[division].areas[area].division_id == 'undefined')
							country_data[division].areas[area].division = country_data[division];
						this._last_search_area.push({country_data:country_data,area_id:area_id,result:country_data[division].areas[area]});
						return country_data[division].areas[area];
					}
			}
			this._last_search_area.push({country_data:country_data,area_id:area_id,result:null});
			return null;
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
			if (area.area_parent_id != null) area.area_parent_id = parseInt(area.area_parent_id);
			if (!area.area_parent_id || area.area_parent_id <= 0) return null;
			if (typeof area.parent_area != 'undefined') return area.parent_area;
			if (typeof country_data[0]._mapping != 'undefined') return country_data[0]._mapping[area.area_parent_id];
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
		
		searchAreasByCoordinates: function(country_data, division_index, lat, lng, parents_ids) {
			var list = [];
			for (var i = 0; i < country_data[division_index].areas.length; ++i) {
				var area = country_data[division_index].areas[i];
				if (!area.north) continue;
				if (parents_ids && !parents_ids.contains(area.area_parent_id)) continue;
				if (window.top.geography.boxContainsPoint(area, lat, lng))
					list.push(area);
			}
			return list;
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

		/** Optimize several identical searches */
		_last_search_by_name_in_division: null,
		searchAreaByNameInDivision: function(country_data, division_index, area_name) {
			if (division_index >= country_data.length) return null;
			area_name = area_name.trim().latinize().toLowerCase();
			if (this._last_search_by_name_in_division && this._last_search_by_name_in_division.country_data == country_data && this._last_search_by_name_in_division.division_index == division_index && this._last_search_by_name_in_division.area_name == area_name)
				return this._last_search_by_name_in_division.result;
			this._last_search_by_name_in_division = {
				country_data: country_data,
				division_index: division_index,
				area_name: area_name,
				result: null
			};
			for (var i = 0; i < country_data[division_index].areas.length; ++i) {
				var area = country_data[division_index].areas[i];
				area.division_index = division_index;
				area.division = country_data[division_index];
				if (area.area_name.latinize().toLowerCase() == area_name) {
					this._last_search_by_name_in_division.result = area;
					return area;
				}
			}
			return null;
		},
		searchAreaByNameInDivisionBestMatch: function(country_data, division_index, area_name) {
			if (division_index >= country_data.length) return null;
			area_name = area_name.trim().latinize().toLowerCase();
			var area_words = prepareMatchScore(area_name);
			var best_match = null;
			var best_score = 0;
			for (var i = 0; i < country_data[division_index].areas.length; ++i) {
				var area = country_data[division_index].areas[i];
				area.division_index = division_index;
				area.division = country_data[division_index];
				var n = area.area_name.latinize().toLowerCase();
				var words = prepareMatchScore(n);
				var score = matchScorePrepared(n,words,area_name,area_words);
				if (score > 50 && score > best_score) {
					best_match = area;
					best_score = score;
				}
			}
			return best_match;
		},
		/** Optimize several identical searches */
		_last_search_by_name_in_parent: null,
		searchAreaByNameInParent: function(country_data, parent_id, division_index, area_name) {
			if (division_index >= country_data.length) return null;
			area_name = area_name.trim().latinize().toLowerCase();
			if (this._last_search_by_name_in_parent && this._last_search_by_name_in_parent.country_data == country_data && this._last_search_by_name_in_parent.division_index == division_index && this._last_search_by_name_in_parent.parent_id == parent_id && this._last_search_by_name_in_parent.area_name == area_name)
				return this._last_search_by_name_in_parent.result;
			this._last_search_by_name_in_parent = {
				country_data: country_data,
				division_index: division_index,
				area_name: area_name,
				parent_id: parent_id,
				result: null
			};
			for (var i = 0; i < country_data[division_index].areas.length; ++i) {
				var area = country_data[division_index].areas[i];
				if (area.area_parent_id != parent_id) continue;
				area.division_index = division_index;
				area.division = country_data[division_index];
				if (area.area_name.latinize().toLowerCase() == area_name) {
					this._last_search_by_name_in_parent.result = area;
					return area;
				}
			}
			return null;
		},
		searchAreaByNameInParentBestMatch: function(country_data, parent_id, division_index, area_name) {
			if (division_index >= country_data.length) return null;
			area_name = area_name.trim().latinize().toLowerCase();
			var area_words = prepareMatchScore(area_name);
			var best_match = null;
			var best_score = 0;
			for (var i = 0; i < country_data[division_index].areas.length; ++i) {
				var area = country_data[division_index].areas[i];
				if (area.area_parent_id != parent_id) continue;
				area.division_index = division_index;
				area.division = country_data[division_index];
				var n = area.area_name.latinize().toLowerCase();
				var words = prepareMatchScore(n);
				var score = matchScorePrepared(n,words,area_name,area_words);
				if (score > 50 && score > best_score) {
					best_match = area;
					best_score = score;
				}
			}
			return best_match;
		},
		
		searchAreaByNames: function(country_data, areas_names, remaining_names) {
			return window.top.geography._searchAreaByNames(country_data, areas_names, 0, 0, null, remaining_names);
		},
		_searchAreaByNames: function(country_data, areas_names, start_division_index, start_names_index, parent_area, remaining_names) {
			var next_name = areas_names[start_names_index].trim().latinize().toLowerCase();
			for (var division_index = start_division_index; division_index < country_data.length; ++division_index) {
				for (var i = 0; i < country_data[division_index].areas.length; ++i) {
					if (parent_area && country_data[division_index].areas[i].area_parent_id != parent_area.area_id) continue;
					var aname = country_data[division_index].areas[i].area_name.latinize().toLowerCase();
					if (aname == next_name || country_data[division_index].division_name.latinize().toLowerCase()+" "+aname == next_name) {
						// found it !
						if (start_names_index == areas_names.length-1) {
							// last one => return it
							return country_data[division_index].areas[i];
						}
						// continue with next names
						for (var next = start_names_index+1; next < areas_names.length; ++next) {
							var area = window.top.geography._searchAreaByNames(country_data, areas_names, division_index+1, next, country_data[division_index].areas[i], remaining_names);
							if (area != null) return area;
						}
						// did not find a next name => return the current one
						if (remaining_names)
							for (var j = start_names_index+1; j < areas_names.length; ++j)
								remaining_names.push(areas_names[j]);
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
			return this.getGeographicAreaText(country_data, area);
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
				id: area.area_id,
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
		},
		
		startComputingSearchDictionary: function (country_data) {
			if (country_data.length == 0) return;
			country_data[0]._search = {
				done: false,
				division: 0,
				area: 0,
				result: [],
				dictionary: {}
			};
			setTimeout(function() { window.top.geography._computeSearchDictionary(country_data); }, 2);
		},
		_computeSearchDictionary: function(country_data) {
			var search = country_data[0]._search;
			if (search.done) return;
			// continue current division
			var computed = 0;
			while (search.area < country_data[search.division].areas.length && computed < 1000) {
				var area = country_data[search.division].areas[search.area];
				var o = new Object();
				o.area = area;
				var n = area.area_name.latinize().toLowerCase();
				o.words = prepareMatchScore(n);
				o.full_name = n;
				o.display_name = area.area_name;
				if (area.area_parent_id) {
					var a = window.top.geography.getParentArea(country_data, area);
					for (var i = 0; i < a._search_object.words.length; ++i)
						o.words.push(a._search_object.words[i]);
					o.full_name += " "+a.area_name.latinize().toLowerCase();
					o.display_name += ", "+a.area_name;
					while (a.area_parent_id) {
						a = window.top.geography.getParentArea(country_data, a);
						o.full_name += " "+a.area_name.latinize().toLowerCase();
						o.display_name += ", "+a.area_name;
					}
				}
				for (var i = 0; i < o.words.length; ++i) {
					if (typeof search.dictionary[o.words[i]] == 'undefined')
						search.dictionary[o.words[i]] = [];
					if (search.dictionary[o.words[i]].indexOf(search.result.length) < 0)
						search.dictionary[o.words[i]].push(search.result.length);
				}
				area._search_object = o;
				search.result.push(o);
				computed++;
				search.area++;
				if (search.area >= country_data[search.division].areas.length) {
					search.division++;
					search.area = 0;
					if (search.division >= country_data.length) {
						search.done = true;
						break;
					}
				}
			}
			if (!search.done)
				setTimeout(function() { window.top.geography._computeSearchDictionary(country_data); }, 50);
		},
		isSearchDictionaryReady: function(country_data) {
			if (country_data.length == 0) return true;
			return country_data[0]._search.done;
		},
		searchDictionary: function(country_data, needle) {
			if (country_data.length == 0) return [];
			needle = needle.latinize().toLowerCase();
			var needle_words = prepareMatchScore(needle);
			var eligibles = [];
			for (var word in country_data[0]._search.dictionary) {
				var e = needle.indexOf(word) >= 0;
				if (!e) {
					for (var i = 0; i < needle_words.length && !e; ++i)
						if (needle_words[i].indexOf(word) >= 0 || word.indexOf(needle_words[i]) >= 0)
							e = true;
				}
				if (e) {
					var indexes = country_data[0]._search.dictionary[word];
					for (var i = 0; i < indexes.length; ++i)
						if (eligibles.indexOf(indexes[i]) < 0)
							eligibles.push(indexes[i]);
				}
			}
			var matching = [];
			for (var i = 0; i < eligibles.length; ++i) {
				var a = country_data[0]._search.result[eligibles[i]];
				var score = matchScorePrepared(a.full_name, a.words, needle, needle_words);
				if (score <= 0) continue;
				matching.push({area:a.area,name:a.display_name,score:score});
			}
			matching.sort(function(a1,a2){
				if (a1.score > a2.score) return -1;
				if (a1.score == a2.score) return 0;
				return 1;
			});
			if (matching.length < 100)
				return matching;
			var m = [];
			for (var i = 0; i < 100; ++i) m.push(matching[i]);
			return m;
		},
		
		boxContains: function(area1, area2) {
			return this.rectContains(
				parseFloat(parseFloat(area1.south).toFixed(6)),
				parseFloat(parseFloat(area1.north).toFixed(6)),
				parseFloat(parseFloat(area1.west).toFixed(6)),
				parseFloat(parseFloat(area1.east).toFixed(6)),
				parseFloat(parseFloat(area2.south).toFixed(6)),
				parseFloat(parseFloat(area2.north).toFixed(6)),
				parseFloat(parseFloat(area2.west).toFixed(6)),
				parseFloat(parseFloat(area2.east).toFixed(6))
			);
		},
		rectContains: function(r1_south, r1_north, r1_west, r1_east, r2_south, r2_north, r2_west, r2_east) {
			return (
				r2_south >= r1_south &&
				r2_south <= r1_north &&
				r2_north >= r1_south &&
				r2_north <= r1_north &&
				r2_west >= r1_west &&
				r2_west <= r1_east &&
				r2_east >= r1_west &&
				r2_east <= r1_east);
		},
		boxIntersect: function(a1,a2) {
			return this.rectIntersect(
				parseFloat(a1.south),
				parseFloat(a1.north),
				parseFloat(a1.west),
				parseFloat(a1.east),
				parseFloat(a2.south),
				parseFloat(a2.north),
				parseFloat(a2.west),
				parseFloat(a2.east)
			);
		},
		rectIntersect: function(a1s, a1n, a1w, a1e, a2s, a2n, a2w, a2e) {
			// order is: south - north, west - east
			// 1. Intersect if one of the corner is inside
			// 1.1 Corner of a1 in a2
			// 1.1.1 south,west
			if (this.rectContainsPoint(a2s, a2n, a2w, a2e, a1s, a1w)) return true;
			// 1.1.2 south,east
			if (this.rectContainsPoint(a2s, a2n, a2w, a2e, a1s, a1e)) return true;
			// 1.1.3 north,west
			if (this.rectContainsPoint(a2s, a2n, a2w, a2e, a1n, a1w)) return true;
			// 1.1.4 north,east
			if (this.rectContainsPoint(a2s, a2n, a2w, a2e, a1n, a1e)) return true;
			// 1.2 Corner of a2 in a1
			if (this.rectContainsPoint(a1s, a1n, a1w, a1e, a2s, a2w)) return true;
			if (this.rectContainsPoint(a1s, a1n, a1w, a1e, a2s, a2e)) return true;
			if (this.rectContainsPoint(a1s, a1n, a1w, a1e, a2n, a2w)) return true;
			if (this.rectContainsPoint(a1s, a1n, a1w, a1e, a2n, a2e)) return true;
			// 2. Intersect if one rect contains the other
			if (this.rectContains(a1s,a1n,a1w,a1e,a2s,a2n,a2w,a2e)) return true;
			if (this.rectContains(a2s,a2n,a2w,a2e,a1s,a1n,a1w,a1e)) return true;
			return false;
		},
		boxContainsPoint: function(box, point_lat, point_lng) {
			return this.rectContainsPoint(parseFloat(box.south),parseFloat(box.north),parseFloat(box.west),parseFloat(box.east),point_lat,point_lng);
		},
		rectContainsPoint: function(rect_south, rect_north, rect_west, rect_east, point_lat, point_lng) {
			if (point_lat < rect_south) return false;
			if (point_lat > rect_north) return false;
			if (point_lng < rect_west) return false;
			if (point_lng > rect_east) return false;
			return true;
		}

	};
}