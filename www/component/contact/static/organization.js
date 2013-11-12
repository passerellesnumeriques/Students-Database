if (typeof require != 'undefined') {
	require("editable_cell.js");
	require([["typed_field.js","field_text.js"]]);
}

function organization(container, org, can_edit) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	
	this.getStructure = function() {
		return org;
	};
	
	this._init = function() {
		// title: name of organization
		container.appendChild(t.title_container = document.createElement("DIV"));
		if (org.id != -1) {
			require("editable_cell.js", function() {
				t.title = new editable_cell(t.title_container, "Organization", "name", org.id, "field_text", {min_length:1,max_length:100,can_be_null:false,style:{fontSize:"x-large"}}, org.name, null, function(field){
					org.name = field.getCurrentData();
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
				});
			});
		}
		
		// list of organization types
		container.appendChild(t.types_container = document.createElement("DIV"));
		var span = document.createElement("SPAN");
		span.style.fontStyle = "italic";
		span.innerHTML = "Types: ";
		t.types_container.appendChild(span);
		require("labels.js", function() {
			var types = [];
			for (var i = 0; i < org.types.length; ++i) {
				for (var j = 0; j < org.existing_types.length; ++j)
					if (org.existing_types[j].id == org.types[i]) {
						types.push(org.existing_types[j]);
						break;
					}
			}
			t.types = new labels("#90D090", types, function(id) {
				// TODO onedit
			}, function(id, handler) {
				var ok = function() {
					for (var i = 0; i < org.types.length; ++i)
						if (org.types[i] == id) {
							org.types.splice(i,1);
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
				var items = [];
				for (var i = 0; i < org.existing_types.length; ++i) {
					var found = false;
					for (var j = 0; j < org.types.length; ++j)
						if (org.types[j] == org.existing_types[i].id) { found = true; break; }
					if (!found) {
						var item = document.createElement("DIV");
						item.className = "context_menu_item";
						item.innerHTML = org.existing_types[i].name;
						item.org_type = org.existing_types[i];
						item.style.fontSize = "8pt";
						item.onclick = function() {
							if (org.id != -1) {
								var tt=this;
								service.json("contact", "assign_organization_type", {organization:org.id,type:this.org_type.id}, function(res) {
									if (res) {
										org.types.push(tt.org_type.id);
										t.types.addItem(tt.org_type.id, tt.org_type.name);
									}
								});
							} else {
								org.types.push(this.org_type.id);
								t.types.addItem(this.org_type.id, this.org_type.name);
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
						for (var i = 0; i < org.existing_types.length; ++i)
							if (org.existing_types[i].name.toLowerCase().trim() == name.toLowerCase().trim())
								return "This organization type already exists";
						return null;
					},function(name){
						if (!name) return;
						service.json("contact","new_organization_type",{creator:org.creator,name:name},function(res){
							if (!res) return;
							if (org.id != -1) {
								service.json("contact", "assign_organization_type", {organization:org.id,type:res.id}, function(res) {
									if (res) {
										org.types.push(res.id);
										org.existing_types.push({id:res.id,name:name});
										t.types.addItem(res.id, name);
									}
								});
							} else {
								org.types.push(res.id);
								org.existing_types.push({id:res.id,name:name});
								t.types.addItem(res.id, name);
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
			var c = new contacts(td_contacts, "Organization_contact", "organization", org.id, org.contacts, can_edit, can_edit, can_edit);
			c.onchange.add_listener(function(c){
				org.contacts = c.getContacts();
			});
		});
			// addresses
		tr.appendChild(td_addresses = document.createElement("TD"));
		td_addresses.style.verticalAlign = "top";
		require("addresses.js", function() {
			var a = new addresses(td_addresses, true, "Organization_address", "organization", org.id, org.addresses, can_edit, can_edit, can_edit);
			a.onchange.add_listener(function(a){
				org.addresses = a.getAddresses();
			});
		});
			// contact points
		tr.appendChild(td_points = document.createElement("TD"));
		td_points.style.verticalAlign = "top";
		var table = document.createElement("TABLE");
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
				require("popup_window.js",function() {
					var frame = document.createElement("IFRAME");
					frame.style.border = "0px";
					frame.style.width = "100%";
					frame.style.height = "100%";
					var p = new popup_window("New Contact Point", '/static/application/icon.php?main=/static/contact/contact_point.png&small='+theme.icons_10.add+'&where=right_bottom',frame);
					p.show();
					frame.onload = function() {
						p.resize();
					};
					window.create_contact_point_success = function(people) {
						var point = {
							designation: people.contact_point_designation,
							people_id: people.people_id,
							first_name: people.people.first_name,
							last_name: people.people.last_name
						};
						if (org.id == -1)
							point.create_people = people;
						org.points.push(point);
						t._addContactPointRow(point, tbody);
						p.close();
					};
					var data =
					{
							types:['organization_contact_point'],
							icon:'/static/application/icon.php?main=/static/contact/contact_point_32.png&small='+theme.icons_16.add+'&where=right_bottom',
							title:'New Contact Point For '+org.name,
							contact_point_organization: org.id
					};
					if (org.id != -1)
						data.onsuccess='window.parent.create_contact_point_success';
					else
						data.donotcreate='window.parent.create_contact_point_success';
					post_data(
						'/dynamic/people/page/create_people',
						data,
						getIFrameWindow(frame)
					);
				});
			};
		}
		
		for (var i = 0; i < org.points.length; ++i)
			t._addContactPointRow(org.points[i], tbody);
	};
	this._addContactPointRow = function(point, tbody) {
		var tr, td_design, td;
		tbody.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td_design = document.createElement("TD"));
		if (org.id != -1) {
			require("editable_cell.js",function() {
				new editable_cell(td_design, "Contact_point", "designation", {organization:org.id,people:point.people_id}, "field_text", {min_length:1,max_length:100,can_be_null:false}, point.designation, function(new_data){
					point.designation = new_data;
				}, null, null);
			});
		} else {
			require([["typed_field.js","field_text.js"]], function() {
				var f = new field_text(point.designation, true, {min_length:1,max_length:100,can_be_null:false});
				f.onchange.add_listener(function() {
					point.designation = f.getCurrentData();
				});
			});
		}
		tr.appendChild(td = document.createElement("TD"));
		td.appendChild(document.createTextNode(point.first_name+" "+point.last_name));
	};
	
	this._init();
}