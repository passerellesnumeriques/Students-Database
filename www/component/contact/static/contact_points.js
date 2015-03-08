function contact_points(container, org, list) {
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
						  {table:"ContactPoint",column:"organization",value:org.id}
						]
					}
				);
			}
			frame.contact_point_created = function(peoples) {
				require(["contact_objects.js","people_objects.js"],function() {
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
					var people = new People(people_id,first_name,last_name);
					var point = new ContactPoint(people_id, people, designation);
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
		// TODO contacts
		layout.changed(this.grid);
	};
	
	this._init();
}