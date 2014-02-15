if (typeof require != 'undefined') {
	require("people_objects.js");
	require("vertical_layout.js");
}
if (typeof theme != 'undefined')
	theme.css("section.css");

function assign_peoples(container) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	
	this._possibilities = [];
	this._peoples = [];
	
	this.addPossibleAssignment = function(id, name) {
		var poss = {id:id,name:name};
		this._possibilities.push(poss);
		poss.section = this._createSection(name, true);
		poss.section.content.style.overflowY = "scroll";
		var button = document.createElement("DIV");
		button.className = "button";
		button.appendChild(document.createTextNode(name+" "));
		var img = document.createElement("IMG");
		img.src = theme.icons_16.right;
		button.appendChild(img);
		button.onclick = function() {
			for (var i = 0; i < t._peoples.length; ++i) {
				var p = t._peoples[i];
				if (p.current == null && p.cb.checked) {
					p.div.parentNode.removeChild(p.div);
					poss.section.content.appendChild(p.div);
					p.current = id;
					var nb = parseInt(poss.section.nb_node.nodeValue);
					nb++;
					poss.section.nb_node.nodeValue = nb;
					nb = parseInt(t.non_assign_section.nb_node.nodeValue);
					nb--;
					t.non_assign_section.nb_node.nodeValue = nb;
				}
			}
		};
		this.assign_buttons.appendChild(button);
		this.assign_buttons.appendChild(document.createElement("BR"));
		poss.section.unassign_button.onclick = function() {
			for (var i = 0; i < t._peoples.length; ++i) {
				var p = t._peoples[i];
				if (p.current == id && p.cb.checked) {
					p.div.parentNode.removeChild(p.div);
					t.non_assign_div.appendChild(p.div);
					p.current = null;
					var nb = parseInt(t.non_assign_section.nb_node.nodeValue);
					nb++;
					t.non_assign_section.nb_node.nodeValue = nb;
					nb = parseInt(poss.section.nb_node.nodeValue);
					nb--;
					poss.section.nb_node.nodeValue = nb;
				}
			}
		};
	};
	this.addPeople = function(people, assignment) {
		var p = {people:people,original:assignment,current:assignment};
		p.div = document.createElement("DIV");
		p.div.style.whiteSpace = 'nowrap';
		p.cb = document.createElement("INPUT");
		p.cb.type = 'checkbox';
		p.div.appendChild(p.cb);
		p.div.appendChild(document.createTextNode(" "+people.first_name+" "+people.last_name));
		this._peoples.push(p);
		if (assignment == null) {
			this.non_assign_div.appendChild(p.div);
			var nb = parseInt(this.non_assign_section.nb_node.nodeValue);
			nb++;
			this.non_assign_section.nb_node.nodeValue = nb;
		} else for (var i = 0; i < this._possibilities.length; ++i)
			if (this._possibilities[i].id == assignment) {
				this._possibilities[i].section.content.appendChild(p.div);
				var nb = parseInt(this._possibilities[i].section.nb_node.nodeValue);
				nb++;
				this._possibilities[i].section.nb_node.nodeValue = nb;
			}
	};
	
	this.getPeoples = function() {
		var peoples = [];
		for (var i = 0; i < this._peoples.length; ++i)
			peoples.push(this._peoples[i].people);
		return peoples;
	};
	this.getOriginalAssignment = function(people_id) {
		for (var i = 0; i < this._peoples.length; ++i)
			if (this._peoples[i].people.id == people_id)
				return this._peoples[i].original;
	};
	this.getNewAssignment = function(people_id) {
		for (var i = 0; i < this._peoples.length; ++i)
			if (this._peoples[i].people.id == people_id)
				return this._peoples[i].current;
	};
	this.getAssignmentName = function(id) {
		for (var i = 0; i < this._possibilities.length; ++i)
			if (this._possibilities[i].id == id)
				return this._possibilities[i].name;
		return null;
	};

	this._createSection = function(name, has_footer) {
		var container = document.createElement("DIV");
		container.style.margin = "5px";
		container.className = "section";
		var header = document.createElement("DIV");
		header.className = "section_header";
		var title = document.createElement("DIV");
		title.appendChild(document.createTextNode(name));
		title.appendChild(document.createTextNode(" ("));
		var nb = document.createTextNode("0");
		title.appendChild(nb);
		title.appendChild(document.createTextNode(")"));
		header.appendChild(title);
		container.appendChild(header);
		var content = document.createElement("DIV");
		content.setAttribute("layout", "fill");
		content.style.backgroundColor = 'white';
		container.appendChild(content);
		var unassign_button = null;
		if (has_footer) {
			var footer = document.createElement("DIV");
			footer.className = "section_footer";
			container.appendChild(footer);
			unassign_button = document.createElement("DIV");
			unassign_button.className = "button";
			unassign_button.innerHTML = "<img src='"+theme.icons_16.remove+"'/> Unassign";
			footer.appendChild(unassign_button);
		}
		require("vertical_layout.js", function() {
			new vertical_layout(container, true);
		});
		container.style.display = "inline-block";
		this.global_div.appendChild(container);
		fireLayoutEventFor(this.global_div);
		return {content:content,nb_node:nb,unassign_button:unassign_button};
	};
	
	this._init = function() {
		this.global_div = document.createElement("DIV");
		this.global_div.style.height = "100%";
		container.appendChild(this.global_div);
		
		this.non_assign_section = this._createSection("Non-assigned");
		var table = document.createElement("TABLE");
		table.style.height = "100%";
		var tr = document.createElement("TR"); table.appendChild(tr);
		this.non_assign_div = document.createElement("TD"); tr.appendChild(this.non_assign_div);
		this.non_assign_div.style.overflowY = "auto";
		this.assign_buttons = document.createElement("TD"); tr.appendChild(this.assign_buttons);
		this.assign_buttons.style.verticalAlign = "middle";
		this.assign_buttons.style.textAlign = "right";
		this.assign_buttons.style.borderLeft = "1px solid black";
		this.non_assign_section.content.appendChild(table);
		require("horizontal_layout.js",function() {
			new horizontal_layout(t.global_div);
		});
	};
	this._init();
}