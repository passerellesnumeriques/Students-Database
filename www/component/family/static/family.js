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
				t._init();
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
		tr.appendChild(td = document.createElement("TD"));
		var td_gender = td;
		tr.appendChild(td = document.createElement("TD"));
		var td_age = td;
		var create_people = function() {
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
					if (t.onchange) t.onchange();
					require("datadisplay.js", function() {
						member.people_create = peoples[0];
						member.people = {people_id:-1,first_name:"",last_name:"",sex:null,birthdate:null};
						for (var i = 0; i < member.people_create.length; ++i) {
							var path = new DataPath(member.people_create[i].path);
							if (path.lastElement().table != "People") continue;
							for (var j = 0; j < member.people_create[i].value.length; ++j) {
								if (member.people_create[i].value[j].name == "First Name") member.people.first_name = member.people_create[i].value[j].value;
								else if (member.people_create[i].value[j].name == "Last Name") member.people.last_name = member.people_create[i].value[j].value;
								else if (member.people_create[i].value[j].name == "Gender") member.people.sex = member.people_create[i].value[j].value;
								else if (member.people_create[i].value[j].name == "Birth Date") member.people.birthdate = member.people_create[i].value[j].value;
							}
						}
						td_people.removeAllChildren();
						td_people.style.color = "";
						td_people.style.fontStyle = "";
						td_people.appendChild(document.createTextNode(member.people.last_name+" "+member.people.first_name));
						td_gender.removeAllChildren();
						td_gender.innerHTML = member.people.sex ? member.people.sex : "";
						if (!member.people.birthdate)
							td_age.innerHTML = "";
						else {
							var now = new Date();
							var birth = parseSQLDate(member.people.birthdate);
							var age = now.getFullYear()-birth.getFullYear();
							if (now.getMonth() < birth.getMonth() || (now.getMonth() == birth.getMonth() && now.getDate() < birth.getDate()))
								age--;
							td_age.innerHTML = age;
						}
						var remove = document.createElement("BUTTON");
						remove.className = "flat small_icon";
						remove.innerHTML = "<img src='"+theme.icons_10.remove+"'/>";
						remove.title = "Remove this person ant put as unknown";
						td_people.appendChild(remove);
						remove.onclick = function(ev) {
							stopEventPropagation(ev);
							member.people_create = null;
							member.people = null;
							td_people.style.fontStyle = "italic";
							td_people.style.color = "#808080";
							td_people.innerHTML = "unknown";
							td_people.onclick = create_people;
							td_age.removeAllChildren();
							td_gender.removeAllChildren();
							return false;
						};
					});
				};
			});
		};
		if (member.people && !member.people_create) {
			var span_last_name = document.createElement("SPAN");
			span_last_name.appendChild(document.createTextNode(member.people.last_name));
			td_people.appendChild(span_last_name);
			window.top.datamodel.registerCellSpan(window, "People", "last_name", member.people.people_id, span_last_name);
			td_people.appendChild(document.createTextNode(" "));
			var span_first_name = document.createElement("SPAN");
			span_first_name.appendChild(document.createTextNode(member.people.first_name));
			td_people.appendChild(span_first_name);
			window.top.datamodel.registerCellSpan(window, "People", "first_name", member.people.people_id, span_first_name);
			td_people.onclick = function() {
				popup_frame(null,"Family Member","/dynamic/people/page/profile?people="+member.people.people_id,null,80,80);
			};
			if (member.people.people_id != fixed_people_id && can_edit) {
				var remove = document.createElement("BUTTON");
				remove.className = "flat small_icon";
				remove.innerHTML = "<img src='"+theme.icons_10.remove+"'/>";
				remove.title = "Remove this person ant put as unknown";
				td_people.appendChild(remove);
				remove.onclick = function(ev) {
					stopEventPropagation(ev);
					popup_frame(null,"Remove Family Member","/dynamic/people/page/remove_people_type?people="+member.people.people_id+"&type=family_member&ontyperemoved=removed&onpeopleremoved=removed",null,null,null,function(frame,pop){
						frame.removed = function() {
							for (var i = 0; i < members.length; ++i)
								if (members[i].people && members[i].people.people_id == member.people.people_id) {
									members.splice(i,1);
									break;
								}
							member.people = null;
							td_people.style.fontStyle = "italic";
							td_people.style.color = "#808080";
							td_people.innerHTML = "unknown";
							td_people.onclick = create_people;
							td_age.removeAllChildren();
							td_gender.removeAllChildren();
						};
					});
					return false;
				};
			}
		} else {
			td_people.style.fontStyle = "italic";
			td_people.style.color = "#808080";
			td_people.innerHTML = "unknown";
			if (can_edit) {
				td_people.onclick = create_people;
			} else {
				td_people.onmouseover = null;
				td_people.onmouseout = null;
				td_people.style.curosr = "";
			}
		}
		// gender
		td_gender.style.textAlign = "center";
		if (member.people && !member.people_create) {
			var span = document.createElement("SPAN");
			span.appendChild(document.createTextNode(member.people.sex));
			td_gender.appendChild(span);
			window.top.datamodel.registerCellSpan(window, "People", "sex", member.people.people_id, span);
		} else {
		}
		// age
		td_age.style.textAlign = "center";
		if (member.people && !member.people_create) {
			var span = document.createElement("SPAN");
			var age = "";
			if (member.people.birthdate) {
				var now = new Date();
				var birth = parseSQLDate(member.people.birthdate);
				age = now.getFullYear()-birth.getFullYear();
				if (now.getMonth() < birth.getMonth() || (now.getMonth() == birth.getMonth() && now.getDate() < birth.getDate()))
					age--;
			}
			span.appendChild(document.createTextNode(age));
			td_age.appendChild(span);
			window.top.datamodel.addCellChangeListener(window, "People", "birth", member.people.people_id, function(value) {
				if (!value)
					span.innerHTML = "";
				else {
					var now = new Date();
					var birth = parseSQLDate(value);
					var age = now.getFullYear()-birth.getFullYear();
					if (now.getMonth() < birth.getMonth() || (now.getMonth() == birth.getMonth() && now.getDate() < birth.getDate()))
						age--;
					span.innerHTML = age;
				}
			});
		} else {
		}
		// occupation
		tr.appendChild(td = document.createElement("TD"));
		if (can_edit) {
			var div = document.createElement("DIV");
			div.style.display = "flex";
			div.style.flexDirection = "row";
			td.appendChild(div);
			require([["typed_field.js","field_text.js"]],function() {
				var f = new field_text(member.occupation, true, {min_length:1,can_be_null:true,max_length:100});
				div.appendChild(f.getHTMLElement());
				f.getHTMLElement().style.flex = "1 1 auto";
				f.onchange.add_listener(function() {
					member.occupation = f.getCurrentData();
					if (t.onchange) t.onchange();
				});
				div.appendChild(document.createTextNode(" Type: "));
				t._addSelect(div, member, "occupation_type", [{value:null,text:"?"},{value:"Regular",text:"Regular"},{value:"Irregular",text:"Irregular"}]);
			});
		} else {
			td.appendChild(document.createTextNode((member.occupation ? member.occupation : "")+(member.occupation_type ? ","+member.occupation_type : "")));
		}
		// education level
		tr.appendChild(td = document.createElement("TD"));
		if (member.people && member.people.people_types.indexOf("/student/")>=0) {
			// PN student
			td.innerHTML = "<img src='/static/application/logo_16.png' style='vertical-align:bottom'/> PN Student";
		} else {
			if (can_edit) {
				var td_educ = td;
				require([["typed_field.js","field_text.js"]],function() {
					var f = new field_text(member.education_level, true, {min_length:1,can_be_null:true,max_length:100});
					td_educ.appendChild(f.getHTMLElement());
					f.fillWidth();
					f.onchange.add_listener(function() {
						member.education_level = f.getCurrentData();
						if (t.onchange) t.onchange();
					});
				});
			} else {
				if (member.education_level)
					td.appendChild(document.createTextNode(member.education_level));
				else
					td.innerHTML = "";
			}
		}
		// living with family
		tr.appendChild(td = document.createElement("TD"));
		this._addBooleanSelect(td, member, "living_with_family");
		if (!can_edit) td.style.textAlign = "center";
		// revenue
		tr.appendChild(td = document.createElement("TD"));
		if (can_edit) {
			var td_revenue = td;
			require([["typed_field.js","field_text.js"]],function() {
				var f = new field_text(member.revenue, true, {min_length:0,can_be_null:true,max_length:500});
				td_revenue.appendChild(f.getHTMLElement());
				f.fillWidth();
				f.onchange.add_listener(function() {
					member.revenue = f.getCurrentData();
					if (t.onchange) t.onchange();
				});
			});
		} else {
			td.appendChild(document.createTextNode(member.revenue ? member.revenue : ""));
		}
		// comment
		tr.appendChild(td = document.createElement("TD"));
		if (member.people && member.people.people_types.indexOf("/applicant/") >= 0 && member.people.people_types.indexOf("/student/") < 0) {
			var i = document.createElement("I");
			i.style.fontSize = "8pt";
			i.appendChild(document.createTextNode(" Note: applied to selection process "));
			td.appendChild(i);
		}
		if (can_edit) {
			var td_comment = td;
			require([["typed_field.js","field_text.js"]],function() {
				var f = new field_text(member.comment, true, {min_length:0,can_be_null:true,max_length:1000});
				td_comment.appendChild(f.getHTMLElement());
				f.fillWidth();
				f.onchange.add_listener(function() {
					member.comment = f.getCurrentData();
					if (t.onchange) t.onchange();
				});
			});
		} else {
			td.appendChild(document.createTextNode(member.comment ? member.comment : ""));
		}
		// last update
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = member.entry_date ? member.entry_date : "";
		return tr;
	};
	this._addParentsSituation = function() {
		var tr = document.createElement("TR"); this.table.appendChild(tr);
		var td = document.createElement("TD"); tr.appendChild(td);
		td.colSpan = 9;
		td.innerHTML = "Parents situation: ";
		if (can_edit) {
			var select = document.createElement("SELECT");
			var o;
			o = document.createElement("OPTION"); o.value = null; o.text = ""; select.add(o);
			o = document.createElement("OPTION"); o.value = "Married"; o.text = "Married"; select.add(o);
			o = document.createElement("OPTION"); o.value = "Separated"; o.text = "Separated"; select.add(o);
			o = document.createElement("OPTION"); o.value = "Divorced"; o.text = "Divorced"; select.add(o);
			o = document.createElement("OPTION"); o.value = "Widower"; o.text = "Widower"; select.add(o);
			td.appendChild(select);
			select.value = this.family.parents_situation;
			select.onchange = function() {
				t.family.parents_situation = this.value;
				if (t.onchange) t.onchange();
			};
		} else
			td.appendChild(document.createTextNode(this.family.parents_situation ? this.family.parents_situation : ""));
	};
	this._addColumnsTitles = function() {
		var tr, td;
		this.table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TH"));
		td.innerHTML = "Type";
		tr.appendChild(td = document.createElement("TH"));
		td.innerHTML = "Name";
		tr.appendChild(td = document.createElement("TH"));
		td.innerHTML = "Gender";
		td.style.fontSize = "9pt";
		tr.appendChild(td = document.createElement("TH"));
		td.innerHTML = "Age";
		tr.appendChild(td = document.createElement("TH"));
		td.innerHTML = "Occupation, Type";
		td.style.padding = "1px 6px";
		tr.appendChild(td = document.createElement("TH"));
		td.innerHTML = "Education level";
		td.style.padding = "1px 6px";
		tr.appendChild(td = document.createElement("TH"));
		td.innerHTML = "Living<br/>w/family";
		td.style.fontSize = "9pt";
		tr.appendChild(td = document.createElement("TH"));
		td.innerHTML = "Revenue<br/>Info.";
		td.style.fontSize = "9pt";
		tr.appendChild(td = document.createElement("TH"));
		td.innerHTML = "Comment";
		tr.appendChild(td = document.createElement("TH"));
		td.style.fontSize = "9pt";
		td.innerHTML = "Last<br/>Update";
		return tr;
	};
	this._addTitleRow = function(title) {
		var tr = document.createElement("TR"); this.table.appendChild(tr);
		tr.is_title = true;
		var td = document.createElement("TD"); tr.appendChild(td);
		td.colSpan = 9;
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
		this._addParentsSituation();
		
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