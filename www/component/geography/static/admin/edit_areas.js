function GeographicAreasTree(areas_section, country_id) {

	var t=this;
	
	this.tr = null;
	this.country_data = null;
	this.country = null;
	this.area_added = new Custom_Event();
	this.area_removed = new Custom_Event();
	
	this.reset = function() {
		require("tree.js", function() {
			t._initTree();
		});
	};
	
	this._initTree = function() {
		if (!this.tr) {
			areas_section.content.removeAllChildren();
			this.tr = new tree(areas_section.content);
			this.tr.table.style.margin = "10px";
			this.tr.addColumn(new TreeColumn(""));
		} else {
			this.tr.clearItems();
		}
		// root level = country name
		window.top.geography.getCountry(country_id, function(country) {
			t.country = country;
			var span = document.createElement("SPAN");
			span.appendChild(document.createTextNode(country.country_name));
			var item = new TreeItem([new TreeCell(span)], false, null, function(root_item, onready) {
				window.top.geography.getCountryData(country_id, function(country_data) {
					t.country_data = country_data;
					if (country_data.length == 0) { onready(); return; };
					for (var i = 0; i < country_data[0].areas.length; ++i)
						t._createItem(root_item, 0, i);
					onready();
				});
			});
			t.tr.addItem(item);
			t._addAddButton(span, 0, null, item);
			t._addCoordinatesButton(span);
		});

		// create info on the section title
		if (!this.span_nb_total) {
			var span = document.createElement("SPAN");
			this.span_nb_have_coordinates = document.createElement("SPAN");
			span.appendChild(this.span_nb_have_coordinates);
			span.appendChild(document.createTextNode("/"));
			this.span_nb_total = document.createElement("SPAN");
			span.appendChild(this.span_nb_total);
			span.appendChild(document.createTextNode(" area(s) have geographic coordinates"));
			span.style.margin = "4px 5px 0px 5px";
			areas_section.addToolRight(span);
	
			var button;
	
			button = document.createElement("BUTTON");
			button.appendChild(document.createTextNode("Import coordinates"));
			areas_section.addToolRight(button);
			button.onclick = function() {
				require("import_coordinates.js", function() {
					import_coordinates(t.country, t.country_data, function() {
						location.reload();
					});
				});
				return false; 
			};
			
			button = document.createElement("BUTTON");
			button.appendChild(document.createTextNode("Check coordinates"));
			areas_section.addToolRight(button);
			button.onclick = function() { t.checkCoordinates(); return false; };
		}
		window.top.geography.getCountryData(country_id, function(country_data) {
			var nb_to_set = 0;
			var nb_total = 0;
			for(var division_index=0; division_index < country_data.length; division_index++){
				for (var area_index = 0; area_index < country_data[division_index].areas.length; ++area_index) {
					var area = country_data[division_index].areas[area_index];
					nb_total++;
					if (area.north == null) nb_to_set++;
				}
			}
			t.span_nb_have_coordinates.innerHTML = (nb_total-nb_to_set);
			t.span_nb_total.innerHTML = nb_total;
		});
	};
	this._createItem = function(parent_item, division_index, area_index) {
		var div = document.createElement('DIV');
		div.style.display ='inline-block';
		var area = t.country_data[division_index].areas[area_index];
		var item = new TreeItem([new TreeCell(div)], false, null, function(item, onready) {
			if (division_index == t.country_data.length-1) { onready(); return; }
			for (var i = 0; i < t.country_data[division_index+1].areas.length; ++i)  {
				if (t.country_data[division_index+1].areas[i].area_parent_id != area.area_id) continue;
				t._createItem(item, division_index+1, i);
			}
			onready();
		});
		item.area = area;
		parent_item.addItem(item);
		var edit_container = document.createElement("SPAN");
		require("editable_cell.js", function() {
			new editable_cell(edit_container, 'GeographicArea', 'name', area.area_id, 'field_text', {can_be_null:false,max_length:100,min_length:1}, area.area_name)
			.onsave = function(text){
				text = text.trim();
				if (!text.checkVisible()) {
					errorDialog("You must enter at least one visible character");
					return area.area_name;
				}
				// check unicity
				var children = window.top.geography.getAreaChildren(t.country_data, division_index, area.area_parent_id);
				for (var i = 0; i < children.length; ++i)
					if (children[i].area_name.toLowerCase() == text.toLowerCase()) {
						errorDialog("An area already exists with this name");
						return area.area_name;
					}
				area.area_name = text;
				return text;
			};
		});
		div.appendChild(edit_container);
		t._addAddButton(div, division_index+1, area.area_id, item);
		t._addRemoveButton(div, division_index, area_index);
		t._addCoordinatesButton(div, division_index, area_index);
	};
	this._findAreaItem = function(area) {
		return this._findAreaItemIn(area, this.tr.items);
	};
	this._findAreaItemIn = function(area, items) {
		for (var i = 0; i < items.length; ++i) {
			if (items[i].area == area) return items[i];
			if (!items[i].children) continue;
			var a = this._findAreaItemIn(area, items[i].children);
			if (a) return a;
		}
		return null;
	};
	this._addAddButton = function(container, division_index, parent_area_id, parent_item) {
		var add_button = document.createElement('BUTTON');
		add_button.className = 'flat small_icon';
		add_button.innerHTML = "<img src='"+theme.icons_10.add+"'/>";
		add_button.title = "Create sub-areas";
		add_button.onclick = function(){t._dialogAddAreas(division_index, parent_area_id, parent_item);};
		container.appendChild(add_button);
	};
	this._addRemoveButton = function(container, division_index, area_index) {
		var remove_button = document.createElement('BUTTON');
		remove_button.className = 'flat small_icon';
		remove_button.innerHTML = "<img src='"+theme.icons_10.remove+"'/>";
		remove_button.title = "Remove this area and all its content";
		remove_button.onclick = function(){t._dialogRemoveArea(division_index, area_index); };
		container.appendChild(remove_button);
	};
	this._addCoordinatesButton = function(container, division_index, area_index) {
		var area;
		if (typeof division_index != 'undefined')
			area = this.country_data[division_index].areas[area_index];
		else
			area = this.country;
		var button = document.createElement('BUTTON');
		button.className = 'flat small_icon';
		button.title = "Edit geographic coordinates";
		if (area.north != null)
			button.innerHTML = "<img src='/static/geography/earth_12.png'/>";
		else
			button.innerHTML = "<img src='"+theme.build_icon('/static/geography/earth_12.png',theme.icons_10.warning)+"'/>";
		button._is_coordinates = true;
		button.onclick = function() { t._dialogCoordinates(division_index, area_index); return false; };
		container.appendChild(button);
	};
	this._updateCoordinatesButton = function(area) {
		var item = this._findAreaItem(area);
		if (!item) return;
		var div = item.cells[0].element;
		var button = null;
		for (var i = 0; i < div.childNodes.length; ++i)
			if (div.childNodes[i]._is_coordinates) { button = div.childNodes[i]; break; }
		if (!button) return;
		if (area.north != null)
			button.innerHTML = "<img src='/static/geography/earth_12.png'/>";
		else
			button.innerHTML = "<img src='"+theme.build_icon('/static/geography/earth_12.png',theme.icons_10.warning)+"'/>";
	};
	
	this._dialogAddAreas = function(division_index, parent_area_id, parent_item) {
		if (division_index >= t.country_data.length) {
			errorDialog("You cannot add sub-areas because you are in the last division");
			return;
		}
		var content = document.createElement("DIV");
		content.style.padding = "10px";
		content.appendChild(document.createTextNode("Please enter new areas (one by line):"));
		content.appendChild(document.createElement("BR"));
		var ei = document.createElement("I");
		ei.appendChild(document.createTextNode("Note: empty lines are ignored, spaces at the beginning or end of a line are ignored"));
		content.appendChild(ei);
		content.appendChild(document.createElement("BR"));
		var text_area = document.createElement("TEXTAREA");
		text_area.rows = 10;
		text_area.cols = 50;
		content.appendChild(text_area);
		content.appendChild(document.createElement("BR"));
		content.appendChild(document.createTextNode("Cleaning after copy/paste:"));
		content.appendChild(document.createElement("BR"));
		var link_clean = document.createElement("A");
		link_clean.className = "black_link";
		link_clean.appendChild(document.createTextNode("Remove spaces and keep only first column"));
		link_clean.href = '#';
		link_clean.onclick = function() {
			var s = text_area.value;
			var lines = s.split("\n");
			s = "";
			for (var i = 0; i < lines.length; ++i) {
				var line = lines[i].trim();
				if (line.length == 0) continue;
				if (line.charCodeAt(0) == 9) {
					// skip and remove next line
					i++;
					continue;
				}
				var j = line.indexOf('\t');
				if (j > 0) line = line.substring(0,j).trim();
				var only_digits = true;
				for (var j = 0; j < line.length; ++j) {
					var c = line.charAt(j);
					if (c == "," || c == "." || isSpace(c)) continue;
					var cc = line.charCodeAt(j);
					if (cc < "0".charCodeAt(0) || cc > "9".charCodeAt(0)) { only_digits = false; break; }
				}
				if (only_digits) continue;
				if (line.length == 0) continue;
				s += line+"\n";
			}
			text_area.value = s;
			return false;
		};
		content.appendChild(link_clean);
		content.appendChild(document.createTextNode("  "));
		var link_clean2 = document.createElement("A");
		link_clean2.className = "black_link";
		link_clean2.appendChild(document.createTextNode("Remove leading numbers"));
		link_clean2.href = '#';
		link_clean2.onclick = function() {
			var s = text_area.value;
			var lines = s.split("\n");
			s = "";
			for (var i = 0; i < lines.length; ++i) {
				var line = lines[i].trim();
				if (line.length == 0) continue;
				var j = 0;
				while (isSpace(line.charAt(j)) || isDigit(line.charAt(j))) j++;
				if (j > 0) line = line.substring(j).trim();
				if (line.length == 0) continue;
				s += line+"\n";
			}
			text_area.value = s;
			return false;
		};
		content.appendChild(link_clean2);
		content.appendChild(document.createTextNode("  "));
		var link_clean3 = document.createElement("A");
		link_clean3.className = "black_link";
		link_clean3.appendChild(document.createTextNode("Split by comma"));
		link_clean3.href = '#';
		link_clean3.onclick = function() {
			var s = text_area.value;
			var lines = s.split("\n");
			s = "";
			for (var i = 0; i < lines.length; ++i) {
				var line = lines[i].trim();
				if (line.length == 0) continue;
				var ss = line.split(",");
				for (var j = 0; j < ss.length; ++j) {
					line = ss[j].trim();
					if (line.length == 0) continue;
					s += line+"\n";
				}
			}
			text_area.value = s;
			return false;
		};
		content.appendChild(link_clean3);
		content.appendChild(document.createElement("BR"));
		content.appendChild(document.createTextNode("Remove beggining of lines until: "));
		var input_start = document.createElement("INPUT");
		input_start.type = "text";
		input_start.size = 5;
		content.appendChild(input_start);
		var button_start = document.createElement("BUTTON");
		button_start.innerHTML = "Go";
		content.appendChild(button_start);
		button_start.onclick = function() {
			var s = text_area.value;
			var lines = s.split("\n");
			s = "";
			for (var i = 0; i < lines.length; ++i) {
				var line = lines[i].trim();
				if (line.length == 0) continue;
				var j = line.indexOf(input_start.value);
				if (j >= 0) line = line.substring(j+input_start.value.length);
				s += line+"\n";
			}
			text_area.value = s;
			return false;
		};
		content.appendChild(document.createTextNode(" Remove end of lines starting from: "));
		var input_end = document.createElement("INPUT");
		input_end.type = "text";
		input_end.size = 5;
		content.appendChild(input_end);
		var button_end = document.createElement("BUTTON");
		button_end.innerHTML = "Go";
		content.appendChild(button_end);
		button_end.onclick = function() {
			var s = text_area.value;
			var lines = s.split("\n");
			s = "";
			for (var i = 0; i < lines.length; ++i) {
				var line = lines[i].trim();
				if (line.length == 0) continue;
				var j = line.indexOf(input_end.value);
				if (j > 0) line = line.substring(0,j);
				s += line+"\n";
			}
			text_area.value = s;
			return false;
		};
		content.appendChild(document.createElement("BR"));
		require("popup_window.js",function() {
			var popup = new popup_window("New Geographic Areas", null, content);
			popup.addOkCancelButtons(function() {
				popup.freeze("Checking names...");
				setTimeout(function(){
					var text = text_area.value;
					var lines = text.split("\n");
					var names = [];
					for (var i = 0; i < lines.length; ++i) {
						var name = lines[i].trim();
						if (!name.checkVisible()) continue;
						var found = false;
						for (var j = 0; j < t.country_data[division_index].areas.length; ++j) {
							if (t.country_data[division_index].areas[j].area_parent_id != parent_area_id) continue;
							if (name.toLowerCase() == t.country_data[division_index].areas[j].area_name.toLowerCase()) {
								found = true;
								break;
							}
						}
						if (found) {
							alert("Area already exists: "+name);
							continue;
						}
						names.push(name);
					}
					popup.unfreeze();
					popup.freeze_progress("Creation of "+names.length+" Geographic Area(s)", names.length, function(span,pb) {
						var done = 0;
						for (var i = 0; i < names.length; ++i) {
							var added = function() {
								pb.addAmount(1);
								done++;
								if (done == names.length) {
									popup.close();
								}
							};
							t._addArea(names[i], division_index, parent_area_id, parent_item, added);
						}
					});
				},1);
			});
			popup.show();
		});
	};
	this._dialogRemoveArea = function(division_index, area_index) {
		confirmDialog("Are you sure you want to remove this area ?<br/><b>Note: all its sub-areas will be also removed.</b>", function(yes) {
			if (!yes) return;
			var lock = lock_screen(null, "Removing area...");
			service.json("data_model","remove_row",{table:"GeographicArea", row_key:t.country_data[division_index].areas[area_index].area_id}, function(res){
				if(!res) { unlock_screen(lock); return; }
				// remove in country_data
				var area_id = t.country_data[division_index].areas[area_index].area_id;
				t.country_data[division_index].areas.splice(area_index,1);
				t.area_removed.fire({division_index:division_index,area_id:area_id});
				var parent_ids = [area_id];
				var div = division_index+1;
				while (div < t.country_data.length) {
					var ids = [];
					for (var i = 0; i < t.country_data[div].areas.length; ++i) {
						var a = t.country_data[div].areas[i];
						if (parent_ids.contains(a.area_parent_id)) {
							ids.push(a.area_id);
							t.country_data[div].areas.splice(i,1);
							t.area_removed.fire({division_index:div,area_id:a.id});
							i--;
						}
					}
					parent_ids = ids;
					div++;
				}
				// reset the tree as all indexes changed
				t.reset();
				unlock_screen(lock);
			});
		});
	};
	
	this._dialogCoordinates = function(division_index, area_index) {
		require("import_coordinates.js", function() {
			dialog_coordinates(t.country, t.country_data, division_index, area_index);
		});
	};
	
	this._addArea = function(name, division_index, parent_area_id, parent_item, ondone) {
		var division_id = this.country_data[division_index].division_id;
		service.json("data_model","save_entity", {table:"GeographicArea", field_name:name, field_parent:parent_area_id, field_country_division:division_id}, function(res){
			if(res) {
				// create area
				var area = {area_id:res.key, area_name:name, area_parent_id: parent_area_id};
				// add in country_data
				var area_index = t.country_data[division_index].areas.length;
				t.country_data[division_index].areas.push(area);
				// add in the tree
				t._createItem(parent_item, division_index, area_index);
				// update number
				t.span_nb_total.innerHTML = parseInt(t.span_nb_total.innerHTML)+1;
				t.area_added.fire({division_index:division_index,area_id:res.key});
			}
			ondone();
		});

	};
	
	this.checkCoordinates = function() {
		if (t.country_data.length == 0) return;
		if (!t.country.north) return;
		var locker = lock_screen();
		var total = 1;
		for (var i = 0; i < t.country_data.length-1; ++i) total += t.country_data[i].areas.length;
		set_lock_screen_content_progress(locker, total, "Checking coordinates...", null, function(span,pb,sub) {
			t._checkCoordinates(undefined, t.country, t.country_data[0].areas, function() {
				pb.addAmount(1);
				var next_division = function(division_index) {
					if (division_index >= t.country_data.length-1) {
						unlock_screen(locker);
						return;
					}
					var area_index = 0;
					var next_area = function() {
						if (area_index >= t.country_data[division_index].areas.length) {
							next_division(division_index+1);
							return;
						}
						var area = t.country_data[division_index].areas[area_index];
						if (!area.north) {
							pb.addAmount(1);
							area_index++;
							setTimeout(next_area,1);
							return;
						}
						var children = window.top.geography.getAreaChildren(t.country_data, division_index+1, area.area_id);
						t._checkCoordinates(division_index, area, children, function() {
							pb.addAmount(1);
							area_index++;
							setTimeout(next_area,1);
						});
					};
					next_area();
				};
				next_division(0);
			});
		});
	};
	this._checkCoordinates = function(parent_division_index, parent, children, ondone) {
		var next_child = function(child_index) {
			if (child_index >= children.length) {
				setTimeout(ondone,1);
				return;
			}
			var child = children[child_index];
			if (!child.north) { next_child(child_index+1); return; }
			if (window.top.geography.boxContains(parent, child)) {
				next_child(child_index+1);
				return;
			}
			require("import_coordinates.js", function() {
				var area_index = typeof parent_division_index == 'undefined' ? undefined : t.country_data[parent_division_index].areas.indexOf(parent);
				dialog_coordinates(t.country, t.country_data, parent_division_index, area_index, function() {
					next_child(child_index+1);
				},"Sub-area "+child.area_name+" is not fully included ("+child.north+","+child.east+","+child.south+","+child.west+")");
			});
		};
		next_child(0);
	};
	
	// load info in background
	window.top.require("geography.js", function() {
		window.top.geography.getCountryName(country_id, function(name){});
		window.top.geography.getCountryData(country_id, function(data){ t.country_data = data; });
		require(["editable_cell.js"]);
		// create tree
		t.reset();
	});
}