function contact_points(container, org, list, attached_location) {
	if (typeof container == 'string') container = document.getElementById(container);
	
	this.onchange = new Custom_Event();
	
	this._init = function() {
		theme.css("grid.css");
		this.table = document.createElement("TABLE");
		this.table.style.border = "1px solid #808080";
		this.table.style.borderSpacing = "0";
		setBorderRadius(this.table, 5, 5, 5, 5, 5, 5, 5, 5);
		container.appendChild(this.table);
		var tr = document.createElement("TR");
		this.table.appendChild(tr);
		var td = document.createElement("TD");
		tr.appendChild(td);
		td.innerHTML = "<img src='/static/contact/contact_point.png' style='vertical-align:bottom'/> Contact Points";
		td.style.padding = "2px 5px 2px 5px";
		td.style.backgroundColor = "#E0E0E0";
		td.style.fontWeight = "bold";
		setBorderRadius(td, 5, 5, 5, 5, 0, 0, 0, 0);
		
		this.table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		this.grid = document.createElement("TABLE");
		td.appendChild(this.grid);
		this.grid.className = "grid";
		this.grid.style.backgroundColor = "white";
		this.grid.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TH"));
		td.innerHTML = "Name";
		tr.appendChild(td = document.createElement("TH"));
		td.innerHTML = "Designation";
		tr.appendChild(td = document.createElement("TH"));
		td.innerHTML = "EMail";
		tr.appendChild(td = document.createElement("TH"));
		td.innerHTML = "Phone";
		tr.appendChild(td = document.createElement("TH"));
		td.innerHTML = "IM";
		
		for (var i = 0; i < list.length; ++i)
			this._createRow(list[i]);
		
		this.table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		setBorderRadius(td, 0, 0, 0, 0, 5, 5, 5, 5);
		td.style.backgroundColor = "#E0E0E0";
		var button = document.createElement("BUTTON");
		td.appendChild(button);
		button.className = "flat small";
		button.innerHTML = "Add contact point person";
		button.style.fontStyle ='italic';
		button.style.color = "#808080";
		button.style.fontSize = "8pt";
		button.style.whiteSpace = 'nowrap';
		var t=this;
		button.onclick = function(ev){
			t.newContactPoint();
			stopEventPropagation(ev);
			return false;
		};
	}
	
	this.newContactPoint = function() {
		var t=this;
		window.top.require("popup_window.js",function() {
			var p = new window.top.popup_window('New Contact Point', theme.build_icon("/static/contact/contact_point.png",theme.icons_10.add), "");
			var frame;
			if (org.id == -1) {
				frame = p.setContentFrame("/dynamic/people/page/popup_create_people?multiple=false&types=contact_"+org.creator+"&donotcreate=contact_point_created");
			} else {
				frame = p.setContentFrame(
					"/dynamic/people/page/popup_create_people?multiple=false&types=contact_"+org.creator+"&ondone=contact_point_created",
					null,
					{
						fixed_columns: [
						  {table:"ContactPoint",column:"organization",value:org.id},
						  {table:"ContactPoint",column:"attached_location",value:attached_location}
						]
					}
				);
			}
			frame.contact_point_created = function(peoples) {
				require(["contact_objects.js","people_objects.js"],function() {
					var paths = peoples[0];
					var people_path = null;
					var contact_point_path = null;
					var contacts_path = null;
					for (var i = 0; i < paths.length; ++i)
						if (paths[i].path == "People") people_path = paths[i];
						else if (paths[i].path == "People<<ContactPoint(people)") contact_point_path = paths[i];
						else if (paths[i].path == "People<<PeopleContact(people)") contacts_path = paths[i];
					
					var people_id = people_path.key;
					var first_name = "", last_name = "";
					for (var i = 0; i < people_path.value.length; ++i)
						if (people_path.value[i].name == "First Name") first_name = people_path.value[i].value;
						else if (people_path.value[i].name == "Last Name") last_name = people_path.value[i].value;
					var designation = contact_point_path.value;
					var people = new People(people_id,first_name,last_name);
					var point = new ContactPoint(people_id, people, designation, contacts_path ? contacts_path.value : [], attached_location);
					if (org.id == -1)
						point.create_people = paths;
					list.push(point);
					t._createRow(point);
					t.onchange.fire();
					p.close();
				});
			};
			p.show();
		});
	};
	
	this._createRow = function(cp) {
		var tr = document.createElement("TR");
		this.grid.appendChild(tr);
		var td;
		tr.appendChild(td = document.createElement("TD"));
		td.appendChild(document.createTextNode(cp.people.first_name+" "+cp.people.last_name));
		tr.appendChild(td = document.createElement("TD"));
		td.appendChild(document.createTextNode(cp.designation));
		var emails = [], phones = [], ims = [];
		for (var i = 0; i < cp.contacts.length; ++i)
			switch (cp.contacts[i].type) {
			case "email": emails.push(cp.contacts[i]); break;
			case "phone": phones.push(cp.contacts[i]); break;
			case "IM": ims.push(cp.contacts[i]); break;
			}
		require([["typed_field.js","field_contact_type.js"]], function(t) {
			tr.appendChild(td = document.createElement("TD"));
			var emails_control = new field_contact_type({type:"people",type_id:cp.people.id,contacts:emails}, false, {type:"email"});
			td.appendChild(emails_control.getHTMLElement());
			tr.appendChild(td = document.createElement("TD"));
			var phones_control = new field_contact_type({type:"people",type_id:cp.people.id,contacts:phones}, false, {type:"phone"});
			td.appendChild(phones_control.getHTMLElement());
			tr.appendChild(td = document.createElement("TD"));
			var ims_control = new field_contact_type({type:"people",type_id:cp.people.id,contacts:ims}, false, {type:"IM"});
			td.appendChild(ims_control.getHTMLElement());
			layout.changed(t.grid);
		}, this);
		tr.className = "highlight_hover";
		tr.style.cursor = "pointer";
		tr.title = "Open profile of this person";
		tr.onclick = function() {
			window.top.popupFrame("/static/people/profile_16.png","Profile","/dynamic/people/page/profile?people="+cp.people.id,null,95,95,function(frame,pop) {
				pop.onclose = function() {
					service.json("contact","get_contact_point",{people:cp.people.id},function(cp) {
						tr.childNodes[0].childNodes[0].nodeValue = cp.people.first_name+" "+cp.people.last_name;
						tr.childNodes[1].childNodes[0].nodeValue = cp.designation;
						emails = [], phones = [], ims = [];
						for (var i = 0; i < cp.contacts.length; ++i)
							switch (cp.contacts[i].type) {
							case "email": emails.push(cp.contacts[i]); break;
							case "phone": phones.push(cp.contacts[i]); break;
							case "IM": ims.push(cp.contacts[i]); break;
							}
						td = tr.childNodes[2];
						td.removeAllChildren();
						var emails_control = new field_contact_type({type:"people",type_id:cp.people.id,contacts:emails}, false, {type:"email"});
						td.appendChild(emails_control.getHTMLElement());
						td = tr.childNodes[3];
						td.removeAllChildren();
						var phones_control = new field_contact_type({type:"people",type_id:cp.people.id,contacts:phones}, false, {type:"phone"});
						td.appendChild(phones_control.getHTMLElement());
						td = tr.childNodes[4];
						td.removeAllChildren();
						var ims_control = new field_contact_type({type:"people",type_id:cp.people.id,contacts:ims}, false, {type:"IM"});
						td.appendChild(ims_control.getHTMLElement());
						layout.changed(tr);
						for (var i = 0; i < list.length; ++i) {
							if (list[i].people.id == cp.people.id) {
								list[i] = cp;
								break;
							}
						}
					});
				};
			});
		};
		// TODO remove
	};
	
	this._init();
}