if (typeof require != 'undefined') {
	require("editable_cell.js");
	require([["typed_field.js","field_text.js"]]);
}

/**
 * UI Control for an organization
 * @param {Element} container where to display
 * @param {Organization} org organization to display
 * @param {Array} existing_types list of {id:...,name:...} listing all existing organization types in database that can be used
 * @param {Boolean} can_edit indicates if the user can modify the organization
 */
function organization(container, org, existing_types, can_edit) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	
	/** Return the Organization structure
	 * @returns Organization the structure updated with actual values on the screen 
	 */
	this.getStructure = function() {
		return org;
	};
	
	this.onchange = new Custom_Event();
	this.onaddresschange = new Custom_Event();
	this.oncontactpointchange = new Custom_Event();
	
	/** Create the display */
	this._init = function() {
		// title: name of organization
		container.appendChild(t.title_container = document.createElement("DIV"));
		t.title_container.style.backgroundColor = "white";
		if (org.id != -1) {
			require("editable_cell.js", function() {
				t.title = new editable_cell(t.title_container, "Organization", "name", org.id, "field_text", {min_length:1,max_length:100,can_be_null:false,style:{fontSize:"x-large"}}, org.name, null, function(field){
					org.name = field.getCurrentData();
					t.onchange.fire();
				}, function(edit){
					if (!can_edit) edit.cancelEditable();
				});
			});
		} else {
			require([["typed_field.js","field_text.js"]],function(){
				t.title = new field_text(org.name, true, {min_length:1,max_length:100,can_be_null:false,style:{fontSize:"x-large"}});
				t.title_container.appendChild(t.title.getHTMLElement());
				t.title.onchange.add_listener(function() {
					org.name = t.title.getCurrentData();
					t.onchange.fire();
				});
			});
		}
		
		// list of organization types
		container.appendChild(t.types_container = document.createElement("DIV"));
		t.types_container.style.backgroundColor = "white";
		var span = document.createElement("SPAN");
		span.style.fontStyle = "italic";
		span.innerHTML = "Types: ";
		t.types_container.appendChild(span);
		require("labels.js", function() {
			var types = [];
			for (var i = 0; i < org.types_ids.length; ++i) {
				for (var j = 0; j < existing_types.length; ++j)
					if (existing_types[j].id == org.types_ids[i]) {
						types.push(existing_types[j]);
						break;
					}
			}
			t.types = new labels("#90D090", types, function(id) {
				// onedit
				alert('Edit not yet implemented');
				// TODO
			}, function(id, handler) {
				// onremove
				var ok = function() {
					for (var i = 0; i < org.types_ids.length; ++i)
						if (org.types_ids[i] == id) {
							org.types_ids.splice(i,1);
							t.onchange.fire();
							handler();
							break;
						}
				};
				if (org.id != -1) {
					service.json("contact", "unassign_organization_type", {organization:org.id,type:id}, function(res) {
						if (res) ok();
					});
				} else
					ok();
			}, function() {
				// add_list_provider
				var items = [];
				for (var i = 0; i < existing_types.length; ++i) {
					var found = false;
					for (var j = 0; j < org.types_ids.length; ++j)
						if (org.types_ids[j] == existing_types[i].id) { found = true; break; }
					if (!found) {
						var item = document.createElement("DIV");
						item.className = "context_menu_item";
						item.innerHTML = existing_types[i].name;
						item.org_type = existing_types[i];
						item.style.fontSize = "8pt";
						item.onclick = function() {
							if (org.id != -1) {
								var tt=this;
								service.json("contact", "assign_organization_type", {organization:org.id,type:this.org_type.id}, function(res) {
									if (res) {
										org.types_ids.push(tt.org_type.id);
										t.types.addItem(tt.org_type.id, tt.org_type.name);
										t.onchange.fire();
									}
								});
							} else {
								org.types_ids.push(this.org_type.id);
								t.types.addItem(this.org_type.id, this.org_type.name);
								t.onchange.fire();
							}
						};
						items.push(item);
					}
				}
				var item = document.createElement("DIV");
				item.className = "context_menu_item";
				item.innerHTML = "<img src='"+theme.icons_16.add+"' style='vertical-align:bottom;padding-right:3px'/> Create a new type";
				item.style.fontSize = "8pt";
				item.onclick = function() {
					input_dialog(theme.icons_16.add,"New Organization Type","Enter the name of the organization type","",100,function(name){
						if (name.length == 0) return "Please enter a name";
						for (var i = 0; i < existing_types.length; ++i)
							if (existing_types[i].name.toLowerCase().trim() == name.toLowerCase().trim())
								return "This organization type already exists";
						return null;
					},function(name){
						if (!name) return;
						service.json("contact","new_organization_type",{creator:org.creator,name:name},function(res){
							if (!res) return;
							if (org.id != -1) {
								service.json("contact", "assign_organization_type", {organization:org.id,type:res.id}, function(res2) {
									if (res2) {
										org.types_ids.push(res.id);
										existing_types.push({id:res.id,name:name});
										t.types.addItem(res.id, name);
										t.onchange.fire();
									}
								});
							} else {
								org.types_ids.push(res.id);
								existing_types.push({id:res.id,name:name});
								t.types.addItem(res.id, name);
								t.onchange.fire();
							}
						});
					});
				};
				items.push(item);
				return items;
			});
			t.types_container.appendChild(t.types.element);
		});

		t.types_container.style.paddingTop = "3px";
		t.types_container.style.paddingBottom = "5px";
		t.types_container.style.marginBottom = "5px";
		t.types_container.style.borderBottom = "1px solid #A0A0A0";
		
		// content: addresses, contacts, contact points
		container.appendChild(t.content_container = document.createElement("TABLE"));
		var tr, td_contacts, td_addresses;
		t.content_container.appendChild(tr = document.createElement("TR"));
			// contacts
		tr.appendChild(td_contacts = document.createElement("TD"));
		td_contacts.style.verticalAlign = "top";
		require("contacts.js", function() {
			var c = new contacts(td_contacts, "organization", org.id, org.contacts, can_edit, can_edit, can_edit);
			c.onchange.add_listener(function(c){
				org.contacts = c.getContacts();
				t.onchange.fire();
			});
		});
			// addresses
		tr.appendChild(td_addresses = document.createElement("TD"));
		td_addresses.style.verticalAlign = "top";
		require("addresses.js", function() {
			var a = new addresses(td_addresses, true, "organization", org.id, org.addresses, can_edit, can_edit, can_edit);
			a.onchange.add_listener(function(a){
				org.addresses = a.getAddresses();
				t.onchange.fire();
				t.onaddresschange.fire();
			});
		});
			// contact points
		t._contact_points_rows = [];
		tr.appendChild(td_points = document.createElement("TD"));
		td_points.style.verticalAlign = "top";
		var table = document.createElement("TABLE");
		table.style.backgroundColor = "white";
		td_points.appendChild(table);
		var thead = document.createElement("THEAD");
		table.appendChild(thead);
		thead.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "<img src='/static/contact/contact_point.png' style='vertical-align:bottom'/> Contact Points";
		
		table.style.border = "1px solid #808080";
		table.style.borderSpacing = "0";
		table.style.marginBottom = "3px";
		setBorderRadius(table, 5, 5, 5, 5, 5, 5, 5, 5);
		td.style.padding = "2px 5px 2px 5px";
		td.style.backgroundColor = "#E0E0E0";
		td.style.fontWeight = "bold";
		td.colSpan = 2;
		setBorderRadius(td, 5, 5, 5, 5, 0, 0, 0, 0);
		
		var tbody = document.createElement("TBODY");
		table.appendChild(tbody);
		if (can_edit) {
			var tfoot = document.createElement("TFOOT");
			table.appendChild(tfoot);
			tfoot.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TD"));
			td.innerHTML = "Add Contact Point";
			td.style.cursor = 'pointer';
			td.style.fontStyle ='italic';
			td.style.color = "#808080";
			td.colSpan = 2;
			td.onclick = function() {
				window.top.require("popup_window.js",function() {
					var p = new window.top.popup_window('New Contact Point', theme.build_icon("/static/contact/contact_point.png",theme.icons_10.add), "");
					var frame;
					if (org.id == -1) {
						frame = p.setContentFrame("/dynamic/people/page/popup_create_people?types=contact_"+org.creator+"&donotcreate=contact_point_created");
					} else {
						frame = p.setContentFrame(
							"/dynamic/people/page/popup_create_people?types=contact_"+org.creator+"&ondone=contact_point_created",
							null,
							{
								fixed_columns: [
								  {table:"ContactPoint",column:"organization",value:org.id}
								]
							}
						);
					}
					frame.contact_point_created = function(peoples) {
						var paths = peoples[0];
						var people_path = null;
						var contact_path = null;
						for (var i = 0; i < paths.length; ++i)
							if (paths[i].path == "People") people_path = paths[i];
							else if (paths[i].path == "People<<ContactPoint(people)") contact_path = paths[i];
						
						var people_id = people_path.key;
						var first_name = "", last_name = "";
						for (var i = 0; i < people_path.value.length; ++i)
							if (people_path.value[i].name == "First Name") first_name = people_path.value[i].value;
							else if (people_path.value[i].name == "Last Name") last_name = people_path.value[i].value;
						var designation = contact_path.value;
						var point = new ContactPoint(people_id, first_name, last_name, designation);
						if (org.id == -1)
							point.create_people = paths;
						org.contact_points.push(point);
						t._addContactPointRow(point, tbody);
						t.onchange.fire();
						t.oncontactpointchange.fire();
						p.close();
						layout.invalidate(tbody);
					};
					p.show();
				});
			};
		}
		
		for (var i = 0; i < org.contact_points.length; ++i)
			t._addContactPointRow(org.contact_points[i], tbody);
		
		layout.invalidate(container);
	};
	/**
	 * Add a contact point to the table
	 * @param {ContactPoint} point the contact point to add
	 * @param {Element} tbody the table where to put it
	 */
	this._addContactPointRow = function(point, tbody) {
		var tr, td_design, td;
		tbody.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td_design = document.createElement("TD"));
		t._contact_points_rows.push(tr);
		tr.people_id = point.people_id;
		if (org.id != -1) {
			require("editable_cell.js",function() {
				new editable_cell(td_design, "ContactPoint", "designation", {organization:org.id,people:point.people_id}, "field_text", {min_length:1,max_length:100,can_be_null:false}, point.designation, function(new_data){
					point.designation = new_data;
					t.onchange.fire();
					return new_data;
				}, null, null);
			});
		} else {
			require([["typed_field.js","field_text.js"]], function() {
				var f = new field_text(point.designation, true, {min_length:1,max_length:100,can_be_null:false});
				f.onchange.add_listener(function() {
					point.designation = f.getCurrentData();
					t.onchange.fire();
				});
			});
		}
		tr.appendChild(td = document.createElement("TD"));
		var link = document.createElement("img");
		link.src = "/static/people/profile_16.png";
		link.style.verticalAlign = "center";
		link.title = "See profile";
		link.style.cursor = "pointer";
		link.people_id = point.people_id;
		link.onclick = function(){
			var people_id = this.people_id;
			require("popup_window.js",function(){
				var pop = new popup_window("People Profile","/static/people/people_16.png");
				pop.setContentFrame("/dynamic/people/page/profile?plugin=people&people="+people_id);
				pop.show();
			});
		};
		var first_name = document.createTextNode(point.first_name);
		var last_name = document.createTextNode(point.last_name);
		window.top.datamodel.registerCellText(window, "People", "first_name", point.people_id, first_name);
		window.top.datamodel.registerCellText(window, "People", "last_name", point.people_id, last_name);
		td.appendChild(first_name);
		td.appendChild(document.createTextNode(" "));
		td.appendChild(last_name);
		td.appendChild(link);
		if(can_edit){
			var remove_button = document.createElement("div");
			td.appendChild(remove_button);
			remove_button.className = "button_verysoft";
			remove_button.innerHTML = "<img src = '"+theme.icons_16.remove+"' style = 'vertical-align:center;'/>";
			remove_button.people_id = point.people_id;
			remove_button.onclick = function(){
				var people_id = this.people_id;
				if(org.id != -1){
					//Remove from db
					service.json("contact","unassign_contact_point",{organization:org.id, people:people_id},function(res){
						if(res){
							//Remove from table
							var index = t._findRowIndexInContactPointsRows(people_id);
							if(index != null){
								//Remove from DOM
								tbody.removeChild(t._contact_points_rows[index]);
								//Remove from t._contact_points_rows
								t._contact_points_rows.splice(index,1);
								t.onchange.fire();
								t.oncontactpointchange.fire();
							}
						}
					});
				} else {
					//Remove from table
					var index = t._findRowIndexInContactPointsRows(people_id);
					if(index != null){
						//Remove from DOM
						tbody.removeChild(t._contact_points_rows[index]);
						//Remove from t._contact_points_rows
						t._contact_points_rows.splice(index,1);
						t.onchange.fire();
						t.oncontactpointchange.fire();
					}
				}
				
			};
		}
	};
	
	t._findRowIndexInContactPointsRows = function(people_id){
		for(var i = 0; i < t._contact_points_rows.length; i++){
			if(t._contact_points_rows[i].people_id == people_id)
				return i;
		}
		return null;
	};
	
	this._init();
}