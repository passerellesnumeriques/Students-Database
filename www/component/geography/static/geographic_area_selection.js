if (typeof window.top.require != 'undefined')
	window.top.require("geography.js");

/**
 * Screen to select a geographic area
 * @param {Element} container where to put the screen
 * @param {Number} country_id in which country to select
 * @param {Number} area_id currently selected area, or null
 * @param {String} orientation either 'horizontal' or 'vertical'
 * @param {Boolean} add_custom_search if true, the user can manually enter a name to search an area
 * @param {Function} onready called when the screen is loaded and ready to be used and manipulated
 */

function geographic_area_selection(container, country_id, area_id, orientation, add_custom_search, onready) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	/** {Number} the currently selected area */
	this.selected_area_id = undefined;
	/** {Function} if specified, it will be called each time the user change the selected area */
	this.onchange = null;

	/** Returns the currently selected area ID
	 * @returns {Number} the currently selected area, or <code>undefined</code> if nothing is selected
	 */
	this.getSelectedArea = function() { 
		return this.selected_area_id;
	};
	/** Returns the information to display the selected area in a text form
	 * @returns {GeographicAreaText} the text information
	 */
	this.getSelectedAreaText = function() {
		if (this.selected_area_id == null)
			return {
				country_id: country_id,
				division_id: null,
				id: null,
				text: ""
			};
		return window.top.geography.getGeographicAreaTextFromId(this.country_data, this.selected_area_id);
	};

	/** Set the selected area
	 * @param {Number} area_id the area to select
	 */
	this.setAreaId = function(area_id) {
		if (typeof area_id == 'string') {
			if (area_id == "") area_id = null;
			else {
				area_id = parseInt(area_id);
				if (isNaN(area_id)) area_id = null;
			}
		}
		if (area_id <= 0) area_id = null;
		if (this.selected_area_id === area_id) return;
		this.selected_area_id = area_id;
		if (area_id == null)
			this._setDivision(0, null);
		else {
			var area = window.top.geography.searchArea(this.country_data, area_id);
			var division_index = window.top.geography.getAreaDivisionIndex(this.country_data, area);
			var areas = [area];
			while (division_index > 0) {
				var parent = window.top.geography.getParentArea(this.country_data, area);
				areas.splice(0,0,parent);
				area = parent;
				division_index--;
			}
			for (var i = 0; i < areas.length; ++i)
				this._setDivision(i, areas[i]);
		}
		if (this.onchange) this.onchange();
	};
	
	/**
	 * Select the best matching area with the given coordinates
	 * @param {Number} lat latitude
	 * @param {Number} lng longitude
	 */
	this.selectByGeographicPosition = function(lat,lng) {
		if (this.country_data.length == 0) return;
		// first division
		var areas_id = [];
		for (var i = 0; i < this.country_data[0].areas.length; ++i) {
			var a = this.country_data[0].areas[i];
			if (!a.north) continue;
			if (lat < a.south) continue;
			if (lat > a.north) continue;
			if (lng < a.west) continue;
			if (lng > a.east) continue;
			areas_id.push(a.area_id);
		}
		if (areas_id.length == 0) return; // no match
		// go to next divisions
		var last_unique = areas_id.length == 1 ? areas_id[0] : null;
		var division_index = 1;
		while (division_index < this.country_data.length) {
			var sub_areas = [];
			var one_found = false;
			for (var i = 0; i < areas_id.length; ++i) sub_areas.push([]);
			for (var i = 0; i < this.country_data[division_index].areas.length; ++i) {
				var a = this.country_data[division_index].areas[i];
				if (!a.north) continue;
				if (lat < a.south) continue;
				if (lat > a.north) continue;
				if (lng < a.west) continue;
				if (lng > a.east) continue;
				var parent_index = areas_id.indexOf(a.area_parent_id);
				if (parent_index < 0) continue;
				sub_areas[parent_index].push(a.area_id);
				one_found = true;
			}
			if (!one_found) break;
			areas_id = [];
			for (var i = 0; i < sub_areas.length; ++i)
				if (sub_areas[i].length > 0)
					for (var j = 0; j < sub_areas[i].length; ++j)
						areas_id.push(sub_areas[i][j]);
			if (areas_id.length == 1) last_unique = areas_id[0];
		}
		if (areas_id.length == 1)
			this.setAreaId(areas_id[0]);
		else if (last_unique != null)
			this.setAreaId(last_unique);
	};
	
	/**
	 * Called to select an area in a specific division
	 * @param {Number} division_index in which division to select
	 * @param {GeographicArea} area the area to select  
	 */
	this._setDivision = function(division_index, area) {
		if (this.selects.length == 0) return; // nothing in this country
		/*
		if (division_index > 0) {
			// we need to select the parent
			if (area != null) {
				var parent = window.top.geography.getParentArea(this.country_data, area);
				this._setDivision(division_index-1, parent);
			} else {
				// this is an 'unselect' => keep the parent as it is
			}
		}
		*/
		// select the good option
		this.selects[division_index].value = area != null ? area.area_id : -1;
		// update the next select
		if (division_index < this.country_data.length-1 && area != null) {
			division_index++;
			this.selects[division_index].disabled = "";
			this._fillSelect(division_index);
		}
		// disable following selects
		for (division_index = division_index+1; division_index < this.country_data.length; division_index++) {
			while (this.selects[division_index].options.length > 1)
				this.selects[division_index].remove(1);
			this.selects[division_index].disabled = "disabled";
		}
		/*
		for (division_index = division_index+1; division_index < this.country_data.length; division_index++)
			this._fillSelect(division_index);
		*/
	};
	
	/**
	 * Fill the SELECT of a division, according to what is selected in the previous level
	 * @param {Number} division_index which division to fill
	 */
	this._fillSelect = function(division_index) {
		var select = this.selects[division_index];
		var prev = select.value;
		while (select.options.length > 1) select.remove(1);
		var parent_id = this.selects[division_index-1].value;
		var children = window.top.geography.getAreaChildren(this.country_data, division_index, parent_id);
		for (var i = 0; i < children.length; ++i) {
			var area = children[i];
			var o = document.createElement("OPTION");
			o.value = area.area_id;
			o.text = area.area_name;
			select.add(o);
		}
		/*
		var parent_ids = [];
		if (this.selects[division_index-1].value == -1) {
			// all are parents
			for (var i = 1; i < this.selects[division_index-1].options.length; ++i)
				parent_ids.push(this.selects[division_index-1].options[i].value);
		} else
			parent_ids.push(this.selects[division_index-1].value);
		for (var i = 0; i < this.country_data[division_index].areas.length; ++i) {
			var area = this.country_data[division_index].areas[i];
			if (parent_ids.contains(area.area_parent_id)) {
				var o = document.createElement("OPTION");
				o.value = area.area_id;
				o.text = area.area_name;
				select.add(o);
			}
		}
		*/
		select.value = prev;
	};
	
	/**
	 * Called when a SELECT changed its value, so we can fill the sub-areas
	 * @param {Element} select the SELECT which changed
	 */
	this._selectChanged = function(select) {
		var division_index = t.selects.indexOf(select);
		var area_id = select.value;
		var area = area_id <= 0 ? null : window.top.geography.getAreaFromDivision(this.country_data, area_id, division_index);
		this._setDivision(division_index, area);
		layout.changed(container);
		for (var i = this.selects.length-1; i >= 0; --i) {
			var value = this.selects[i].value;
			if (value > 0) {
				if (value == this.selected_area_id) return;
				this.selected_area_id = value;
				if (this.onchange) this.onchange();
				return;
			}
		}
		if (this.selected_area_id == null) return;
		this.selected_area_id = null;
		if (this.onchange) this.onchange();
	};
	
	/** Create the input node which calls the auto fill function
	* @param {Function} onready called when everything is loaded and it is ready to be used
	*/
	this.createAutoFillInput = function(onready){
		require("autocomplete.js",function(){
			var div = document.createElement("DIV");
			container.appendChild(div);
			div.style.paddingRight = "3px";
			if (orientation == "horizontal") div.style.display = "inline-block";
			var ac = new autocomplete(div, 3, 'Manually search', function(val, handler){
				t.autoFill(val, handler);
			}, function(item){
				t.setAreaId(item.value);
			});
			setBorderRadius(ac.input,8,8,8,8,8,8,8,8);
			setBoxShadow(ac.input,-1,2,2,0,'#D8D8F0',true);
			ac.input.style.background = "#ffffff url('"+theme.icons_16.search+"') no-repeat 3px 1px";
			ac.input.style.padding = "2px 4px 2px 23px";
			ac.input.style.width = "90%";
			onready();
		});
	};
	
	/** Find the string needle in the areas list. Creates two arrays: one with the areas which name begins with
	* needle; a second one with the areas which name contains needle
	* @param {String} needle the needle to find in the result object
	* @param {Function} handler called with the list of matching items
	*/
	this.autoFill = function(needle, handler){
		if (this.country_data.length == 0) return;
		if (!window.top.geography.isSearchDictionaryReady(this.country_data)) {
			setTimeout(function() {
				t.autoFill(needle,handler);
			},50);
			return;
		}
		var matching = window.top.geography.searchDictionary(this.country_data, needle);
		var items = [];
		for (var i = 0; i < matching.length; ++i) {
			var item = new autocomplete_item(matching[i].area.area_id, matching[i].name, matching[i].name);
			items.push(item);
		}
		handler(items);
	};

	
	// initialize
	window.top.require("geography.js",function() {
		window.top.geography.getCountryData(country_id, function(country_data) {
			t.country_data = country_data;
			t.selects = [];
			var table = document.createElement('table');
			if (orientation == 'horizontal') {
				table.style.display = "inline-block";
				table.style.verticalAlign = "middle";
			}
			var tbody = document.createElement('tbody'); table.appendChild(tbody); 
			var tr, td, select, o;
			for (var j=0; j<country_data.length; j++) {
				if (j == 0 || orientation == "vertical")
					tbody.appendChild(tr = document.createElement('TR'));
				// division name
				tr.appendChild(td = document.createElement("TD"));
				td.appendChild(document.createTextNode(country_data[j].division_name));
				// select
				tr.appendChild(td = document.createElement("TD"));
				td.appendChild(select = document.createElement('SELECT'));
				t.selects.push(select);
				select.style.minWidth = "100%";
				o = document.createElement('OPTION');
				o.value = -1;
				o.text = "";
				select.add(o);
				select.onchange = function() {
					t._selectChanged(this);
				};
				// if first division, we can fill the select
				if (j == 0) {
					for (var i = 0; i < country_data[0].areas.length; ++i) {
						o = document.createElement("OPTION");
						o.value = country_data[0].areas[i].area_id;
						o.text =  country_data[0].areas[i].area_name;
						select.add(o);
					}
				}
			}
			container.appendChild(table);
			t.setAreaId(area_id);
			var ready = function() {
				if (onready) onready(t);
				layout.changed(container);
			};
			window.top.geography.startComputingSearchDictionary(country_data);
			if (add_custom_search)
				t.createAutoFillInput(ready);
			else
				ready();
		});
	});
	
}

