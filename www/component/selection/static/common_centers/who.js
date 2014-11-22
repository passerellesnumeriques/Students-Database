function who_section(container,peoples,can_edit,type) {
	var t=this;
	if (typeof container == 'string') container = document.getElementById(container);
	
	require(["section.js","profile_picture.js"]);
	if (can_edit) require("mini_popup.js");
	
	this.peoples = peoples;
	
	this.addPeople = function(people) {
		this.peoples.push(people);
		if (this.peoples.length == 1) // first one
			t._content.removeAllChildren();
		this._createPeopleDIV(people);
		pnapplication.dataUnsaved('who');
	};
	
	this._createPeopleDIV = function(people) {
		var div = document.createElement("DIV");
		div.style.display = "inline-block";
		div.style.margin = "2px 3px";
		t._content.appendChild(div);
		if (typeof people == 'string') this._createCustomPeopleDIV(people, div);
		else this._createKnownPeopleDIV(people, div);
		layout.changed(t._content);
	};
	
	this._createCustomPeopleDIV = function(custom_name, div) {
		div.appendChild(document.createTextNode(custom_name));
	};
	this._createKnownPeopleDIV = function(people, div, readonly) {
		var pic_container = document.createElement("DIV");
		pic_container.style.textAlign = "center";
		div.appendChild(pic_container);
		var name = document.createElement("DIV");
		name.style.whiteSpace = "nowrap";
		div.appendChild(name);
		if (people.can_do) {
			var ok = document.createElement("IMG");
			ok.src = theme.icons_16.ok;
			ok.style.verticalAlign = 'bottom';
			ok.style.marginRight = "3px";
			ok.title = "This staff has the status to conduct this activity (see Staff Status page)";
			name.appendChild(ok);
		}
		name.appendChild(document.createTextNode(people.people.first_name));
		name.appendChild(document.createElement("BR"));
		name.appendChild(document.createTextNode(people.people.last_name));
		name.style.textAlign = "center";
		if (can_edit && !readonly) {
			var remove_button = document.createElement("BUTTON");
			remove_button.className = "flat small_icon";
			remove_button.innerHTML = "<img src='"+theme.icons_10.remove+"'/>";
			remove_button.style.marginLeft = "4px";
			remove_button.style.verticalAlign = "top";
			remove_button.style.marginTop = "2px";
			remove_button.onclick = function() {
				t.peoples.removeUnique(people);
				div.parentNode.removeChild(div);
				if (t.peoples.length == 0) t._addNobody();
				pnapplication.dataUnsaved('who');
			};
			name.appendChild(remove_button);
		}
		require("profile_picture.js", function() {
			new profile_picture(pic_container, 45, 60).loadPeopleObject(people.people);
		});
	};
	
	this.addSomeonePopup = function(button) {
		require("mini_popup.js",function() {
			var p = new mini_popup("Who will conduct this Information Session ?");
			p.content.innerHTML = "<img src='"+theme.icons_16.loading+"'/>";
			p.showBelowElement(button);
			var list = [];
			for (var i = 0; i < t.peoples.length; ++i)
				if (typeof t.peoples[i] != 'string')
					list.push(t.peoples[i].people.id);
			service.json("selection","common_centers/who",{type:type,already_there:list},function(res) {
				p.content.removeAllChildren();
				var table = document.createElement("TABLE");
				table.style.borderCollapse = "collapse";
				table.style.borderSpacing = "0px";
				p.content.appendChild(table);
				var tr = document.createElement("TR");
				table.appendChild(tr);
				var createHeader = function(title) {
					var td = document.createElement("TD");
					td.style.border = "1px solid #A0A0A0";
					td.style.fontWeight = "bold";
					td.style.textAlign = "center";
					td.appendChild(document.createTextNode(title));
					return td;
				}
				tr.appendChild(createHeader("Selection Team"));
				tr.appendChild(createHeader("Other staffs"));
				tr.appendChild(createHeader("Someone else ?"));
				
				var createPeoples = function(peoples) {
					var td = document.createElement("TD");
					td.style.verticalAlign = "top";
					td.style.border = "1px solid #A0A0A0";
					for (var i = 0; i < peoples.length; ++i) {
						var div = document.createElement("DIV");
						div.style.padding = "2px 5px";
						div.style.border = "1px solid white";
						div.style.cursor = "pointer";
						setBorderRadius(div,3,3,3,3,3,3,3,3);
						t._createKnownPeopleDIV(peoples[i], div, true);
						div.onmouseover = function() {
							this.style.border = "1px solid #F0D080";
							setBackgroundGradient(this, "vertical", [{pos:0,color:'#FFF0D0'},{pos:100,color:'#F0D080'}]);
						};
						div.onmouseout = function() {
							this.style.border = "1px solid white";
							this.style.background = "transparent";
						};
						div.people = peoples[i];
						div.onclick = function() {
							t.addPeople(this.people);
							p.close();
						};
						div.style.display = "inline-block";
						td.appendChild(div);
					}
					return td;
				};
				tr = document.createElement("TR");
				table.appendChild(tr);
				tr.appendChild(createPeoples(res.selection_team));
				tr.appendChild(createPeoples(res.staffs));
				// TODO someone else
				layout.changed(p.content);
			});
		});
	};
	
	this._addNobody = function() {
		var div = document.createElement("DIV");
		div.style.fontStyle = "italic";
		div.style.color = "darkorange";
		div.style.padding = "5px";
		div.innerHTML = "Nobody assigned yet";
		t._content.appendChild(div);
		layout.changed(t._content);
	};
	
	require("section.js", function() {
		t._content = document.createElement("DIV");
		t.section = new section("/static/selection/common_centers/who_black.png","Who ?",t._content,false,false,"soft");
		container.appendChild(t.section.element);
		if (peoples.length == 0)
			t._addNobody();
		else
			for (var i = 0; i < peoples.length; ++i)
				t._createPeopleDIV(peoples[i]);
		if (can_edit) {
			var add_button = document.createElement("BUTTON");
			add_button.className = "flat icon";
			add_button.innerHTML = "<img src='"+theme.build_icon('/static/people/people_16.png',theme.icons_10.add)+"'/>";
			add_button.onclick = function() {
				t.addSomeonePopup(this);
			};
			add_button.title = "Add someone";
			t.section.addToolRight(add_button);
		}
	});
}