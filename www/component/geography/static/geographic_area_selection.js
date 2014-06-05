if (typeof window.top.require != 'undefined')
	window.top.require("geography.js");

/**
 * @method geographic_area_selection
 * @parameter container
 * @parameter country_id
 * @parameter {function} onready, function that handle the parameter to_return = {area_id: ,field: the string to display}
 */

function geographic_area_selection(container, country_id, area_id, onready) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	this.selected_area_id = undefined;
	
	this.onchange = null;

	this.getSelectedArea = function() { 
		return this.selected_area_id;
	};
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
			this._setDivision(division_index, area);
		}
		if (this.onchange) this.onchange();
	};
	
	this._setDivision = function(division_index, area) {
		if (this.selects.length == 0) return; // nothing in this country
		if (division_index > 0) {
			// we need to select the parent
			if (area != null) {
				var parent = window.top.geography.getParentArea(this.country_data, area);
				this._setDivision(division_index-1, parent);
			} else {
				// this is an 'unselect' => keep the parent as it is
			}
		}
		// select the good option
		this.selects[division_index].value = area != null ? area.area_id : -1;
		// update the next select
		for (division_index = division_index+1; division_index < this.country_data.length; division_index++)
			this._fillSelect(division_index);
	};
	
	this._fillSelect = function(division_index) {
		var select = this.selects[division_index];
		var prev = select.value;
		while (select.options.length > 1) select.remove(1);
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
		select.value = prev;
	};
	
	this._selectChanged = function(select) {
		var division_index = t.selects.indexOf(select);
		var area_id = select.value;
		var area = area_id <= 0 ? null : window.top.geography.getAreaFromDivision(this.country_data, area_id, division_index);
		this._setDivision(division_index, area);
		layout.invalidate(container);
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
	* @method geographic_area_selection#createAutoFillInput
	* @parameter parent the container
	*/
	this.createAutoFillInput = function(){
		require("autocomplete.js",function(){
			var div = document.createElement("DIV");
			container.appendChild(div);
			div.style.paddingRight = "3px";
			var ac = new autocomplete(div, 3, 'Manually search', function(val, handler){
				handler(t.autoFill(val));
			}, function(item){
				t.setAreaId(item.value);
			});
			div.style.border = "1px solid #C0C0F0";
			setBorderRadius(div,8,8,8,8,8,8,8,8);
			setBoxShadow(div,-1,2,2,0,'#D8D8F0',true);
			div.style.background = "#ffffff url('"+theme.icons_16.search+"') no-repeat 3px 1px";
			div.style.padding = "2px 4px 2px 23px";
			ac.input.style.width = "100%";
			ac.input.style.background = "transparent";
			ac.input.style.border = "none";
		});
	};
	
	this._all_areas = null;
	
	/** Find the string needle in the areas list. Creates two arrays: one with the areas which name begins with
	* needle; a second one with the areas which name contains needle
	* @method geographic_area_selection#autoFill
	* @param needle = the needle to find in the result object
	*/
	this.autoFill = function (needle){
		needle = needle.toLowerCase();
		var needle_words = prepareMatchScore(needle);
		if (this._all_areas == null) {
			// first time => compute everything
			this._all_areas = [];
			for (var division_index = 0; division_index < this.country_data.length; ++division_index) {
				var areas = this.country_data[division_index].areas;
				for (var area_index = 0; area_index < areas.length; ++area_index) {
					var area = areas[area_index];
					var o = new Object();
					o.area = area;
					o.names = window.top.geography.getGeographicAreaFullName(this.country_data, area);
					o.full_name = "";
					for (var i = 0; i < o.names.length; ++i) {
						if (i > 0) o.full_name += " ";
						o.full_name += o.names[i].toLowerCase();
					}
					o.words = prepareMatchScore(o.full_name);
					this._all_areas.push(o);
				}
			}
		}
		var matching = [];
		for (var i = 0; i < this._all_areas.length; ++i) {
			var a = this._all_areas[i];
			var score = matchScorePrepared(a.full_name, a.words, needle, needle_words);
			if (score == 0) continue;
			matching.push({area:a,score:score});
		}
		matching.sort(function(a1,a2){
			if (a1.score > a2.score) return -1;
			if (a1.score == a2.score) return 0;
			return 1;
		});

		var items = [];
		for (var i = 0; i < matching.length; ++i) {
			var text = "";
			for (var j = 0; j < matching[i].area.names.length; ++j) {
				if (j > 0) text += ", ";
				text += matching[i].area.names[matching[i].area.names.length-1-j];
			}
			var item = new autocomplete_item(matching[i].area.area.area_id, text, text);
			items.push(item);
		}
		return items;
	};

	
	// initialize
	window.top.require("geography.js",function() {
		window.top.geography.getCountryData(country_id, function(country_data) {
			t.country_data = country_data;
			t.selects = [];
			var table = document.createElement('table');
			var tbody = document.createElement('tbody'); table.appendChild(tbody); 
			var tr, td, select, o;
			for (var j=0; j<country_data.length; j++) {
				tbody.appendChild(tr = document.createElement('TR'));
				// division name
				tr.appendChild(td = document.createElement("TD"));
				td.appendChild(document.createTextNode(country_data[j].division_name));
				// select
				tr.appendChild(td = document.createElement("TD"));
				td.appendChild(select = document.createElement('SELECT'));
				t.selects.push(select);
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
			t.createAutoFillInput();
			if (onready) onready(t);
			layout.invalidate(container);
		});
	});
	
}

