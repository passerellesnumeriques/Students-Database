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
			layout.invalidate(t.global_div);
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
			layout.invalidate(t.global_div);
		};
		layout.invalidate(t.global_div);
	};
	this._non_movable_reason = null;
	this.setNonMovableReason = function(reason) { this._non_movable_reason = reason; };
	this._has_non_movable_people = false;
	this.addPeople = function(people, assignment, can_be_moved) {
		var p = {people:people,original:assignment,current:assignment,can_be_moved:can_be_moved};
		p.div = document.createElement("DIV");
		p.div.style.whiteSpace = 'nowrap';
		p.cb = document.createElement("INPUT");
		p.cb.type = 'checkbox';
		if (!can_be_moved) {
			p.cb.disabled = 'disabled';
			p.div.style.color = "#808080";
			if (!t._has_non_movable_people && t._non_movable_reason) {
				t._has_non_movable_people = true;
				var info_div = document.createElement("DIV");
				info_div.setAttribute("layout", "fixed");
				info_div.className = "info_header";
				info_div.innerHTML = "<img src='"+theme.icons_16.info+"' style='vertical-align:bottom'/> "+t._non_movable_reason;
				container.insertBefore(info_div, t.global_div);
				t.global_div.setAttribute("layout", "fill");
				require("vertical_layout.js", function() {
					new vertical_layout(container);
					layout.invalidate(t.global_div);
				});
			}
		}
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
		layout.invalidate(t.global_div);
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
		layout.invalidate(this.global_div);
		return {content:content,nb_node:nb,unassign_button:unassign_button};
	};
	
	this._init = function() {
		this.global_div = document.createElement("DIV");
		this.global_div.style.height = "100%";
		this.global_div.style.whiteSpace = "nowrap";
		container.appendChild(this.global_div);
		
		this.non_assign_section = this._createSection("Non-assigned");
		var div_container = document.createElement("DIV"); this.non_assign_section.content.appendChild(div_container);
		div_container.style.height = "100%";
		div_container.style.display = "inline-block";
		this.non_assign_div = document.createElement("DIV"); div_container.appendChild(this.non_assign_div);
		this.non_assign_div.style.height = "100%";
		this.non_assign_div.style.overflowY = "scroll";
		this.non_assign_div.style.display = "inline-block";
		var right_div = document.createElement("DIV"); div_container.appendChild(right_div);
		right_div.style.height = "100%";
		right_div.style.display = "inline-block";
		right_div.style.borderLeft = "1px solid black";
		this.assign_buttons = document.createElement("DIV"); right_div.appendChild(this.assign_buttons);
		this.assign_buttons.style.textAlign = "right";
		require("horizontal_layout.js",function() {
			new horizontal_layout(t.global_div);
			new horizontal_layout(div_container);
		});
		require("vertical_align.js",function() {
			new vertical_align(right_div, "middle");
		});
	};
	this._init();
}