function who_container(container,peoples,can_edit,type) {
	var t=this;
	if (typeof container == 'string') container = document.getElementById(container);
	
	require("profile_picture.js");
	if (can_edit) require("mini_popup.js");
	
	this.peoples = peoples;
	
	this.onadded = new Custom_Event();
	this.onremoved = new Custom_Event();
	
	this.addPeople = function(people) {
		this.peoples.push(people);
		if (this.peoples.length == 1) // first one
			t._content.removeAllChildren();
		this._createPeopleDIV(people);
		pnapplication.dataUnsaved('who');
		this.onadded.fire(people);
	};
	this.removePeople = function(people) {
		t.peoples.removeUnique(people);
		var div = null;
		for (var i = 0; i < t._content.childNodes.length; ++i) if (t._content.childNodes[i]._people == people) { div = t._content.childNodes[i]; break; }
		div.parentNode.removeChild(div);
		if (t.peoples.length == 0) t._addNobody();
		pnapplication.dataUnsaved('who');
		this.onremoved.fire(people);
	};
	
	this._createPeopleDIV = function(people) {
		var div = document.createElement("DIV");
		div.style.display = "inline-block";
		div.style.margin = "2px 3px";
		t._content.appendChild(div);
		if (typeof people == 'string') this._createCustomPeopleDIV(people, div);
		else this._createKnownPeopleDIV(people, div);
		div._people = people;
		layout.changed(t._content);
	};
	
	this._createCustomPeopleDIV = function(custom_name, div) {
		var img = document.createElement("IMG");
		img.src = "/static/selection/common_centers/who_black.png";
		div.appendChild(img);
		div.appendChild(document.createElement("BR"));
		div.appendChild(document.createTextNode(custom_name));
		div.style.textAlign = "center";
		if (can_edit) {
			var remove_button = document.createElement("BUTTON");
			remove_button.className = "flat small_icon";
			remove_button.innerHTML = "<img src='"+theme.icons_10.remove+"'/>";
			remove_button.style.marginLeft = "4px";
			remove_button.style.verticalAlign = "top";
			remove_button.style.marginTop = "2px";
			remove_button.onclick = function() { t.removePeople(custom_name); };
			div.appendChild(remove_button);
		}
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
			remove_button.onclick = function() { t.removePeople(people); };
			name.appendChild(remove_button);
		}
		require("profile_picture.js", function() {
			new profile_picture(pic_container, 45, 60).loadPeopleObject(people.people);
		});
	};
	
	this.addSomeonePopup = function(button, title) {
		require("mini_popup.js",function() {
			var p = new mini_popup(title);
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
				
				var createPeoples = function(td, peoples) {
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
				};
				var createPeoplesTD = function(peoples) {
					var td = document.createElement("TD");
					td.style.verticalAlign = "top";
					td.style.border = "1px solid #A0A0A0";
					createPeoples(td, peoples);
					return td;
				};
				tr = document.createElement("TR");
				table.appendChild(tr);
				tr.appendChild(createPeoplesTD(res.selection_team));
				tr.appendChild(createPeoplesTD(res.staffs));
				
				var td = document.createElement("TD");
				td.style.verticalAlign = "top";
				td.style.border = "1px solid #A0A0A0";
				tr.appendChild(td);
				var table_else = document.createElement("TABLE");
				td.appendChild(table_else);
				table_else.appendChild(tr = document.createElement("TR"));
				tr.appendChild(td = document.createElement("TD"));
				td.style.whiteSpace = "nowrap";
				td.innerHTML = "First Name";
				tr.appendChild(td = document.createElement("TD"));
				var input_fn = document.createElement("INPUT");
				input_fn.type = "text";
				td.appendChild(input_fn);
				table_else.appendChild(tr = document.createElement("TR"));
				tr.appendChild(td = document.createElement("TD"));
				td.style.whiteSpace = "nowrap";
				td.innerHTML = "Last Name";
				tr.appendChild(td = document.createElement("TD"));
				var input_ln = document.createElement("INPUT");
				input_ln.type = "text";
				td.appendChild(input_ln);
				table_else.appendChild(tr = document.createElement("TR"));
				tr.appendChild(td = document.createElement("TD"));
				td.colSpan = 2;
				td.style.verticalAlign = "top";
				var td_results = td;
				table_else.appendChild(tr = document.createElement("TR"));
				tr.appendChild(td = document.createElement("TD"));
				td.colSpan = 2;
				var td_button = td;
				var button = document.createElement("BUTTON");
				button.className = "action";
				button.innerHTML = "Not in the list, but add him/her";
				td_button.appendChild(button);
				button.onclick = function() {
					var name = input_fn.value + " " + input_ln.value;
					name = name.trim();
					t.addPeople(name);
					p.close();
				};
				
				var searching = null;
				var search = function() {
					if (searching) searching.cancel = true;
					var s = {cancel:false};
					searching = s;
					td_results.removeAllChildren();
					td_results.innerHTML = "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Searching...";
					td_button.style.display = "none";
					var name = input_fn.value + " " + input_ln.value;
					name = name.trim();
					if (name.length < 3) {
						td_results.removeAllChildren();
						searching = null;
						return;
					}
					service.json("people","search",{name:name,include_picture:true,exclude_types:["staff"]},function(list) {
						if (s.cancel) return;
						if (searching == s) searching = null;
						td_results.removeAllChildren();
						if (list.length == 0)
							td_results.innerHTML = "<i>Nobody found with this name</i>";
						else {
							var peoples = [];
							for (var i = 0; i < list.length; ++i)
								peoples.push({people:list[i],can_do:false});
							createPeoples(td_results, peoples)
						}
						td_button.style.display = "";
					});
				};
				input_fn.onkeyup = function() { setTimeout(search, 1); };
				input_ln.onkeyup = function() { setTimeout(search, 1); };
				
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
	
	this.createAddButton = function(add_people_question) {
		var add_button = document.createElement("BUTTON");
		add_button.className = "flat icon";
		add_button.innerHTML = "<img src='"+theme.build_icon('/static/people/people_16.png',theme.icons_10.add)+"'/>";
		add_button.onclick = function() {
			t.addSomeonePopup(this, add_people_question);
		};
		add_button.title = "Add someone";
		return add_button;
	};
	
	t._content = document.createElement("DIV");
	container.appendChild(t._content);

	if (peoples.length == 0)
		t._addNobody();
	else
		for (var i = 0; i < peoples.length; ++i)
			t._createPeopleDIV(peoples[i]);
}

function who_section(container,peoples,can_edit,type,add_people_question) {
	var t=this;
	if (typeof container == 'string') container = document.getElementById(container);
	require("section.js",function() {
		var div = document.createElement("DIV");
		t.section = new section("/static/selection/common_centers/who_black.png","Who ?",div,false,false,"soft");
		container.appendChild(t.section.element);
		who_container.call(t,div,peoples,can_edit,type);
		if (can_edit)
			t.section.addToolRight(t.createAddButton(add_people_question));
	});
}
