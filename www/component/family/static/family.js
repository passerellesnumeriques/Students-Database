function family(container, family, members, fixed_people_id, can_edit, onchange) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	
	this.family = objectCopy(family,10);
	this.members = valueCopy(members,10);
	this.onchange = onchange;
	
	this.save = function(ondone) {
		var locker = lock_screen(null,"Saving Family Information...");
		service.json("family","save_family",{family:this.family,members:this.members},function(res) {
			unlock_screen(locker);
			if (res) {
				t.family = res.family;
				t.members = res.members;
				if (ondone) ondone();
			}
		});
	};
	this.cancel = function() {
		this.family = objectCopy(family,10);
		this.members = valueCopy(members,10);
		this._init();
	};
	
	this._addSelect = function(container, member, attribute, values) {
		if (can_edit) {
			var select = document.createElement("SELECT");
			var o;
			var sel = 0;
			for (var i = 0; i < values.length; ++i) {
				o = document.createElement("OPTION");
				o.text = values[i].text;
				if (typeof member[attribute] == 'undefined') {
					if (values[i].value === null) sel = i;
				} else {
					if (values[i].value === null) {
						if (member[attribute] === null) sel = i;
					} else
						if (member[attribute] == values[i].value) sel = i;
				}
				select.add(o);
			}
			select.selectedIndex = sel;
			container.appendChild(select);
			select.onchange = function() {
				member[attribute] = values[this.selectedIndex].value;
				if (t.onchange) t.onchange();
			};
		} else {
			var val = "?";
			if (typeof member[attribute] == 'undefined' || member[attribute] === null) {
				for (var i = 0; i < values.length; ++i)
					if (values[i].value === null) { val = values[i].text; break; }
			} else
				for (var i = 0; i < values.length; ++i)
					if (values[i].value !== null && values[i].value == member[attribute]) { val = values[i].text; break; }
			container.appendChild(document.createTextNode(val));
		}		
	};
	this._addBooleanSelect = function(container, member, attribute) {
		this._addSelect(container, member, attribute, [{value:null,text:"?"},{value:true,text:"Yes"},{value:false,text:"No"}]);
	};
	this._orderChildren = function(title_row, edited) {
		if (this._ordering_children) return;
		this._ordering_children = true;
		// get all children
		var children_trs = [];
		var tr = title_row.nextSibling;
		while (tr && !tr.is_title) {
			children_trs.push(tr);
			tr = tr.nextSibling;
		}
		// get maximum rank
		var max_rank = 0;
		for (var i = 0; i < children_trs.length; ++i) {
			if (can_edit) {
				if (!children_trs[i].rank) {
					this._ordering_children = false;
					setTimeout(function() { t._orderChildren(title_row, edited); }, 10);
					return;
				}
				if (children_trs[i].rank.getCurrentData() > max_rank) max_rank = children_trs[i].rank.getCurrentData();
			} else {
				if (parseInt(children_trs[i].rank.innerHTML) > max_rank) max_rank = parseInt(children_trs[i].rank.innerHTML);
			}
		}
		if (max_rank < children_trs.length) max_rank = children_trs.length;
		// if the rank is edited, we may need to change other ranks
		if (edited) {
			var edited_rank = edited.rank.getCurrentData();
			var prev_rank = children_trs.indexOf(edited)+1;
			if (edited_rank < prev_rank) {
				children_trs.splice(prev_rank-1,1);
				children_trs.splice(edited_rank-1,0,edited);
				for (var i = edited_rank; i < children_trs.length; ++i)
					children_trs[i].rank.setData(i+1);
			}
		}
		var change = false;
		// order
		var children = [];
		for (var i = 0; i < children_trs.length; ++i) children.push(null);
		var no_rank = [];
		for (var i = 0; i < children_trs.length; ++i) {
			var rank;
			if (can_edit) rank = children_trs[i].rank.getCurrentData(); else { rank = parseInt(children_trs[i].rank.innerHTML); if (isNaN(rank)) rank = null; }
			if (!rank)
				no_rank.push(children_trs[i]);
			else {
				if (children[rank-1] == null)
					children[rank-1] = children_trs[i];
				else {
					change = true;
					for (var j = 0; j < children_trs.length; ++j)
						if (children[j] == null) { children[j] = children_trs[i]; break; }
				}
			}
		}
		// put no rank
		if (no_rank.length > 0) {
			change = true;
			for (var i = 0; i < children.length; ++i)
				if (children[i] == null) {
					children[i] = no_rank[0];
					no_rank.splice(0,1);
					if (no_rank.length == 0) break;
				}
		}
		// put back real rank
		for (var i = 0; i < children.length; ++i) {
			if (children[i] == null) {
				var new_child = {member_type:"Child",child_rank:i+1};
				this.members.push(new_child);
				children[i] = this._addMemberRow(new_child,title_row);
				change = true;
			}
			if (can_edit) children[i].rank.setData(i+1);
			else children[i].rank.innerHTML = (i+1);
		}
		// order rows
		var prev = title_row;
		for (var i = 0; i < children.length; ++i) {
			children[i].member.child_rank = i+1;
			title_row.parentNode.insertBefore(children[i], prev.nextSibling);
			prev = children[i];
		}
		if (change && t.onchange) t.onchange();
		this._ordering_children = false;
	};
	this._addMemberRow = function(member, title_row) {
		var next = title_row.nextSibling;
		while (next && !next.is_title) next = next.nextSibling;
		var tr = document.createElement("TR"); this.table.insertBefore(tr, next);
		tr.member = member;
		// member type
		var td = document.createElement("TD"); tr.appendChild(td);
		if (member.member_type == "Child") {
			td.innerHTML = "Child #";
			var td_rank = td;
			if (can_edit) {
				var remove = document.createElement("BUTTON");
				remove.className = "flat small_icon";
				remove.innerHTML = "<img src='"+theme.icons_10.remove+"'/>";
				td.insertBefore(remove, td.childNodes[0]);
				remove.onclick = function() {
					var next = tr.nextSibling;
					members.remove(member);
					t.table.removeChild(tr);
					t._ordering_children = true;
					while (next && !next.is_title) {
						next.rank.setData(next.rank.getCurrentData()-1);
						next = next.nextSibling;
					}
					if (t.onchange) t.onchange();
					t._ordering_children = false;
				};
				if (member.people && fixed_people_id == member.people.people_id)
					remove.disabled = "disabled";
				require([["typed_field.js","field_integer.js"]], function() {
					tr.rank = new field_integer(member.child_rank ? parseInt(member.child_rank) : null, true, {min:1,can_be_null:false});
					td_rank.appendChild(tr.rank.getHTMLElement());
					tr.rank.onchange.add_listener(function() {
						t._orderChildren(title_row, tr);
						if (t.onchange) t.onchange();
					});
				});
			} else {
				tr.rank = document.createElement("SPAN");
				tr.rank.innerHTML = member.child_rank ? member.child_rank : "";
				td.appendChild(tr.rank);
			}
		} else if (member.member_type) {
			// Mother or Father
			td.innerHTML = member.member_type;
		} else {
			if (can_edit) {
				var td_type = td;
				require([["typed_field.js","field_text.js"]],function() {
					var f = new field_text(member.other_member_type, true, {min_length:1,can_be_null:false,max_length:50});
					td_type.appendChild(f.getHTMLElement());
					f.onchange.add_listener(function() {
						member.other_member_type = f.getCurrentData();
						if (t.onchange) t.onchange();
					});
				});
			} else {
				td.appendChild(document.createTextNode(member.other_member_type));
			}
		}
		// name/people
		tr.appendChild(td = document.createElement("TD"));
		td.onmouseover = function() { this.style.textDecoration = "underline"; };
		td.onmouseout = function() { this.style.textDecoration = ""; };
		td.style.cursor = "pointer";
		var td_people = td;
		if (member.people && !member.people_create) {
			var span_last_name = document.createElement("SPAN");
			span_last_name.appendChild(document.createTextNode(member.people.last_name));
			window.top.datamodel.registerCellSpan(window, "People", "last_name", member.people.people_id, span_last_name);
			var span_first_name = document.createElement("SPAN");
			span_first_name.appendChild(document.createTextNode(member.people.first_name));
			window.top.datamodel.registerCellSpan(window, "People", "first_name", member.people.people_id, span_first_name);
			td.appendChild(span_last_name);
			td.appendChild(document.createTextNode(" "));
			td.appendChild(span_first_name);
			td.onclick = function() {
				popup_frame(null,"Family Member","/dynamic/people/page/profile?people="+member.people.people_id,null,80,80);
			};
		} else {
			td.style.fontStyle = "italic";
			td.style.color = "#808080";
			td.innerHTML = "unknown";
			if (can_edit) {
				td.onclick = function() {
					var data = {};
					if (member.people_create) {
						data.prefilled_data = [];
						for (var i = 0; i < member.people_create.length; ++i) {
							var path = new DataPath(member.people_create[i].path);
							for (var j = 0; j < member.people_create[i].value.length; ++j) {
								var pd = {table:path.lastElement().table,data:member.people_create[i].value[j].name,value:member.people_create[i].value[j].value};
								data.prefilled_data.push(pd);
							}
						}
					}
					popup_frame(null, "New Family Member", "/dynamic/people/page/popup_create_people?multiple=false&donotcreate=oncreated&types=family_member", data, 80, 80, function(frame,pop){
						frame.oncreated = function(peoples) {
							require("datadisplay.js", function() {
								member.people_create = peoples[0];
								member.people = {people_id:-1,first_name:"",last_name:"",sex:null};
								for (var i = 0; i < member.people_create.length; ++i) {
									var path = new DataPath(member.people_create[i].path);
									if (path.lastElement().table != "People") continue;
									for (var j = 0; j < member.people_create[i].value.length; ++j) {
										if (member.people_create[i].value[j].name == "First Name") member.people.first_name = member.people_create[i].value[j].value;
										else if (member.people_create[i].value[j].name == "Last Name") member.people.last_name = member.people_create[i].value[j].value;
										else if (member.people_create[i].value[j].name == "Gender") member.people.sex = member.people_create[i].value[j].value;
									}
								}
								td_people.removeAllChildren();
								td.style.color = "";
								td.style.fontStyle = "";
								td_people.appendChild(document.createTextNode(member.people.last_name+" "+member.people.first_name));
							});
						};
					});
				};
			} else {
				td.onmouseover = null;
				td.onmouseout = null;
				td.style.curosr = "";
			}
		}
		// living with family
		tr.appendChild(td = document.createElement("TD"));
		this._addBooleanSelect(td, member, "living_with_family");
		// occupation
		tr.appendChild(td = document.createElement("TD"));
		if (can_edit) {
			var td_occ = td;
			require([["typed_field.js","field_text.js"]],function() {
				var f = new field_text(member.occupation, true, {min_length:1,can_be_null:true,max_length:100});
				td_occ.appendChild(f.getHTMLElement());
				f.onchange.add_listener(function() {
					member.occupation = f.getCurrentData();
					if (t.onchange) t.onchange();
				});
				td_occ.appendChild(document.createTextNode(" Type: "));
				t._addSelect(td_occ, member, "occupation_type", [{value:null,text:"?"},{value:"Regular",text:"Regular"},{value:"Irregular",text:"Irregular"}]);
			});
		} else {
			td.appendChild(document.createTextNode((member.occupation ? member.occupation : "?")+" (Type: "+(member.occupation_type ? member.occupation_type : "?")+")"));
		}
		// education level
		tr.appendChild(td = document.createElement("TD"));
		if (can_edit) {
			var td_educ = td;
			require([["typed_field.js","field_text.js"]],function() {
				var f = new field_text(member.education_level, true, {min_length:1,can_be_null:true,max_length:100});
				td_educ.appendChild(f.getHTMLElement());
				f.onchange.add_listener(function() {
					member.education_level = f.getCurrentData();
					if (t.onchange) t.onchange();
				});
			});
		} else {
			if (member.education_level)
				td.appendChild(document.createTextNode(member.education_level));
			else
				td.innerHTML = "?";
		}
		return tr;
	};
	this._addColumnsTitles = function() {
		var tr, td;
		this.table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TH"));
		td.innerHTML = "Type";
		tr.appendChild(td = document.createElement("TH"));
		td.innerHTML = "Name";
		tr.appendChild(td = document.createElement("TH"));
		td.innerHTML = "Living with family";
		tr.appendChild(td = document.createElement("TH"));
		td.innerHTML = "Occupation";
		tr.appendChild(td = document.createElement("TH"));
		td.innerHTML = "Education level";
		return tr;
	};
	this._addTitleRow = function(title) {
		var tr = document.createElement("TR"); this.table.appendChild(tr);
		tr.is_title = true;
		var td = document.createElement("TD"); tr.appendChild(td);
		td.colSpan = 5;
		td.className = "page_section_title3";
		if (typeof title == 'string')
			td.appendChild(document.createTextNode(title));
		else
			td.appendChild(title);
		return tr;
	};
	this._initNull = function() {
		container.removeAllChildren();
		var div = document.createElement("DIV");
		div.style.fontStyle = "italic";
		div.innerHTML = "Not yet specified";
		container.appendChild(div);
		if (can_edit) {
			var create = document.createElement("BUTTON");
			create.className = "action";
			create.innerHTML = "Specify";
			container.appendChild(create);
			create.onclick = function() {
				t._initFamily();
				if (t.onchange) t.onchange();
			};
		}
	};
	this._initFamily = function() {
		container.removeAllChildren();
		this.table = document.createElement("TABLE");
		container.appendChild(this.table);
		this._addColumnsTitles();
		
		var title_parents = this._addTitleRow("Parents");
		var member = null;
		for (var i = 0; i < this.members.length; ++i) if (this.members[i].member_type == "Father") { member = this.members[i]; break; }
		if (member == null) { member = {member_type:"Father"}; this.members.push(member); }
		this._addMemberRow(member, title_parents);
		var member = null;
		for (var i = 0; i < this.members.length; ++i) if (this.members[i].member_type == "Mother") { member = this.members[i]; break; }
		if (member == null) { member = {member_type:"Mother"}; this.members.push(member); }
		this._addMemberRow(member, title_parents);
		
		var span = document.createElement("SPAN");
		span.innerHTML = "Others (Guardians...) ";
		var add_member_button = document.createElement("BUTTON");
		add_member_button.className = "flat small_icon";
		add_member_button.innerHTML = "<img src='"+theme.icons_10.add+"'/>";
		if (can_edit)
			span.appendChild(add_member_button);
		var title_others = this._addTitleRow(span);
		add_member_button.onclick = function() {
			var new_member = {other_member_type:""};
			t.members.push(new_member);
			t._addMemberRow(new_member, title_others);
		};
		for (var i = 0; i < this.members.length; ++i)
			if (!this.members[i].member_type)
				this._addMemberRow(this.members[i], title_others);
		
		span = document.createElement("SPAN");
		span.innerHTML = "Children ";
		var add_child_button = document.createElement("BUTTON");
		add_child_button.className = "flat small_icon";
		add_child_button.innerHTML = "<img src='"+theme.icons_10.add+"'/>";
		if (can_edit)
			span.appendChild(add_child_button);
		var title_children = this._addTitleRow(span);
		add_child_button.onclick = function() {
			var new_child = {member_type:"Child"};
			t.members.push(new_child);
			t._addMemberRow(new_child, title_children);
			t._orderChildren(title_children);
		};
		for (var i = 0; i < this.members.length; ++i)
			if (this.members[i].member_type == "Child")
				this._addMemberRow(this.members[i], title_children);
		this._orderChildren(title_children);
	};
	this._init = function() {
		if (this.family.id < 0) this._initNull(); else this._initFamily();
	};
	this._init();
}