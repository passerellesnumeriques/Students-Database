if (typeof require != 'undefined')
	require(["editable_cell.js"]);

function EditCountryDivisionsControl(divisions_section, country_id) {
	var t=this;
	this.country_data = null;
	
	/** Fired with the division index as parameter */
	this.division_added = new Custom_Event();
	/** Fired with the division index as parameter */
	this.division_removed = new Custom_Event();
	
	this.appendDivision = function(name) {
		var parent = null;
		if (this.country_data.length > 0)
			parent = this.country_data[this.country_data.length-1].division_id;
		var lock = lock_screen(null, "Adding new division...");
		service.json("data_model","save_entity", {table:"CountryDivision", field_name:name, field_parent:parent, field_country:country_id}, function(res){
			unlock_screen(lock);
			if(!res) return;
			var div = {division_id:res.key, division_name:name, areas:[]};
			t.country_data.push(div);
			t._createDivisionRow(t.country_data.length-1);
			t.division_added.fire(t.country_data.length-1);
		});
	};
	
	this.removeDivision = function(division_id) {
		var index;
		for (index = 0; index < this.country_data.length; ++index)
			if (this.country_data[index].division_id == division_id) break;
		if (index >= this.country_data.length) return; // invalid id
		
		confirm_dialog("Are you sure you want to delete this division?<br/><b>Note: All geographic areas of this division will bre removed.</b>", function(yes) {
			if (!yes) return;
			var lock = lock_screen(null, "Removing division...");
			service.json("data_model","remove_row",{table:"CountryDivision", row_key:division_id}, function(res){
				unlock_screen(lock);
				if(!res) return;
				t._table.removeChild(t._table.childNodes[index]);
				t.country_data.splice(index,1);
				if (t.country_data.length > 0) {
					var last_tr = t._table.childNodes[t._table.childNodes.length-1];
					last_tr.remove_button.style.visibility = 'visible';
					last_tr.remove_button.disabled = "";
				}
				t.division_removed.fire(t.country_data.length);
			});
		});
	};
	
	this._createDivisionRow = function(division_index) {
		var division = this.country_data[division_index];
		var tr, td_name, td_remove;
		this._table.appendChild(tr = document.createElement('TR'));
		// name
		tr.appendChild(td_name = document.createElement('TD'));
		require("editable_cell.js", function() {
			var edit = new editable_cell(td_name, 'CountryDivision', 'name', division.division_id, 'field_text', {can_be_null:false,max_length:50}, division.division_name);
			edit.onsave = function(text){
				text = text.trim().uniformFirstLetterCapitalized();
				if (!text.checkVisible()) {
					error_dialog("You must enter at least one visible character");
					return division.division_name;
				}
				for (var i = 0; i < t.country_data.length; ++i) {
					if (t.country_data[i].division_id == division.division_id) continue; // this is us
					if (t.country_data[i].division_name.toLowerCase() == text.toLowerCase()) {
						error_dialog("A division already exists with this name");
						return division.division_name;
					}
				}
				division.division_name = text;
				return text;
			};
		});
		// remove button
		tr.appendChild(td_remove = document.createElement('TD'));
		tr.remove_button = document.createElement('BUTTON');
		tr.remove_button.className = "flat";
		tr.remove_button.innerHTML = "<img src ='"+theme.icons_16.remove+"'/>";
		tr.remove_button.title = "Remove this division";
		tr.remove_button.onclick = function(){
			t.removeDivision(division.division_id);
			return false;
		};
		td_remove.appendChild(tr.remove_button);
		// hide the previous button
		if (division_index > 0) {
			var last_tr = tr.previousSibling;
			last_tr.remove_button.disabled = "disabled";
			last_tr.remove_button.style.visibility = "hidden";
		}
	};
	
	this._initDisplay = function() {
		this._table = document.createElement('TABLE');
		// create one row per division
		for (var i = 0; i < this.country_data.length; ++i)
			this._createDivisionRow(i);
		
		var add_button = document.createElement('BUTTON');
		add_button.className = 'action';
		add_button.innerHTML = "<img src='"+theme.icons_16.add+"'/> Append a new division";
		add_button.onclick = function(){
			input_dialog(theme.icons_16.question,
				"Add a new division",
				"Please enter the name of the new division",
				"",
				50,
				function(text){
					if (!text.checkVisible()) return "You must enter at least one visible character";
					for (var i = 0; i < t.country_data.length; ++i)
						if (t.country_data[i].division_name.toLowerCase() == text.toLowerCase())
							return "A division already exists with this name";
					return null;
				},
				function(text){
					if (!text) return;
					text = text.trim().uniformFirstLetterCapitalized();
					t.appendDivision(text);
				}
			);
		};
		divisions_section.addToolBottom(add_button);
		divisions_section.content.removeAllChildren();
		divisions_section.content.appendChild(this._table);
	};
	
	// load country data, and initialize the display
	window.top.geography.getCountryData(country_id, function(data) {
		t.country_data = data;
		t._initDisplay();
	});
}