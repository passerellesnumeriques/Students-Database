// #depends[curriculum_tree.js]

function buildGroupsTree(parent, period, spe) {
	var spe_list = [];
	for (var i = 0; i < groups.length; ++i)
		if (groups[i].period == period.id) { spe_list = groups[i].specializations; break; }
	var types_list = [];
	for (var i = 0; i < spe_list.length; ++i)
		if ((spe == null && spe_list[i].specialization == null) || (spe != null && spe_list[i].specialization == spe.id)) { types_list = spe_list[i].groups_types; break; }
	var groups_list = [];
	for (var i = 0; i < types_list.length; ++i)
		if (types_list[i].group_type == group_type_id) { groups_list = types_list[i].groups; break; }
	for (var i = 0; i < groups_list.length; ++i)
		new CurriculumTreeNode_Group(parent, groups_list[i]);
}

/**
 * A group
 * @param {CurriculumTreeNode} parent parent node (either period or specialization)
 */
function CurriculumTreeNode_Group(parent, group) {
	this.group = group;
	CurriculumTreeNode.call(this, parent, "group"+group.id, true);
	for (var i = 0; i < group.sub_groups.length; ++i)
		new CurriculumTreeNode_Group(this, group.sub_groups[i]);
}
CurriculumTreeNode_Group.prototype = new CurriculumTreeNode;
CurriculumTreeNode_Group.prototype.constructor = CurriculumTreeNode_Group;
CurriculumTreeNode_Group.prototype.getIcon = function() { return "/static/curriculum/batch_16.png"; };
CurriculumTreeNode_Group.prototype.createTitle = function(editable) {
	var span = document.createElement("SPAN");
	var group = this.group;
	window.top.datamodel.create_cell(window, "StudentsGroup", null, "name", group.id, group.name, "field_text", {can_be_null:false,max_length:100}, editable && can_edit_batches, span, function(value) { group.name = value; });
	return span;
};
CurriculumTreeNode_Group.prototype.createInfo = function() {
	var div = document.createElement("DIV");
	if (window.can_edit_batches) {
		var gt = getSelectedGroupType();
		var button = document.createElement("BUTTON");
		button.className = "action";
		button.innerHTML = "<img src='"+theme.icons_16.edit+"'/> Edit";
		button.title = "Rename "+gt.name;
		button.g = this.group;
		button.onclick = function() {
			var group = this.g;
			input_dialog(theme.icons_16.edit,"Edit "+gt.name+" Name","Name of the "+gt.name,group.name,100,
				function(name){
					if (!name.checkVisible()) return "Please enter a name";
					return null;
				},function(name){
					if (!name) return;
					name = name.trim();
					service.json("data_model","save_entity",{
						table: "StudentsGroup",
						key: group.id,
						lock: -1,
						field_name: name
					},function(res){
						if (res) {
							group.name = name;
							window.top.datamodel.cellChanged("StudentsGroup","name",group.id,name);
						}
					});
				}
			);
		};
		div.appendChild(button);
		button = document.createElement("BUTTON");
		button.className = "action red";
		button.innerHTML = "<img src='"+theme.icons_16.remove_white+"'/> Remove";
		button.title = "Remove this "+gt.name;
		button.node = this;
		button.onclick = function() { removeGroup(this.node); };	
		div.appendChild(button);
		if (gt.sub_groups) {
			button = document.createElement("BUTTON");
			button.className = "action green";
			button.innerHTML = "<img src='"+theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.add)+"'/> New Sub ";
			button.appendChild(document.createTextNode(gt.name));
			button.title = "Create a new sub "+gt.name+" in "+gt.name+" "+this.group.name;
			button.node = this;
			button.onclick = function() { newGroup(this.node); };
			div.appendChild(button);
			button.ondomremoved(function(b) {b.node = null;});
		}
	}
	return div;
};
CurriculumTreeNode_Group.prototype.getSpecializationNode = function() {
	var p = this.parent;
	do {
		if (p instanceof CurriculumTreeNode_Specialization) return p;
		if (p instanceof CurriculumTreeNode_BatchPeriod) return null;
		p = p.parent;
	} while (p != null);
	return null;
};
CurriculumTreeNode_Group.prototype.getPeriodNode = function() {
	var p = this.parent;
	while (!(p instanceof CurriculumTreeNode_BatchPeriod)) p = p.parent;
	return p;
};
CurriculumTreeNode_Group.prototype.getURLParameters = function() {
	var params = {};
	params["group"] = this.group.id;
	var spe = this.getSpecializationNode();
	var period = this.getPeriodNode();
	if (spe != null)
		params["specialization"] = spe.spe.id;
	params["period"] = period.period.id;
	params["batch"] = period.parent.batch.id;
	params["group_type"] = group_type_id;
	return params;
};

function searchGroupNode(node, group_id) {
	for (var i = 0; i < node.item.children.length; ++i) {
		var n = node.item.children[i].node;
		if (n instanceof CurriculumTreeNode_Group) {
			if (n.group.id == group_id) return n;
		}
		n = searchGroupNode(n, group_id);
		if (n != null) return n;
	}
	return null;
}

/** Open a popup to create a new group
 * @param {CurriculumTreeNode} parent_node node in which the group will be created
 */
function newGroup(parent_node) {
	require("popup_window.js",function() {
		var gt = getSelectedGroupType();
		var content = document.createElement("DIV");
		content.style.padding = "10px";
		content.appendChild(document.createTextNode("Create "+gt.name+" "));
		var input = document.createElement("INPUT");
		input.type = 'text';
		input.maxLength = 100;
		content.appendChild(input);
		require("input_utils.js",function() {
			inputAutoresize(input, "15");
			inputDefaultText(input, gt.name+" Name");
		});
		
		var inside_spe = null;
		var possible_periods = [];
		var possible_periods_node = [];
		var from_period_index = -1;
		if (parent_node instanceof CurriculumTreeNode_BatchPeriod) {
			// independent from specialization => possible periods are all periods without specialization if the group type depends on specializations
			var batch_node = parent_node.parent;
			for (var i = 0; i < batch_node.item.children.length; ++i)
				if (batch_node.item.children[i].node.period.available_specializations.length == 0 || !gt.specialization_dependent) {
					if (batch_node.item.children[i].node.period.id == parent_node.period.id) from_period_index = possible_periods.length;
					possible_periods.push(batch_node.item.children[i].node.period);
					possible_periods_node.push(batch_node.item.children[i].node);
				}
		} else if (parent_node instanceof CurriculumTreeNode_Specialization) {
			// possible periods are all periods having the same specialization
			inside_spe = parent_node.spe.id;
			var period_node = parent_node.parent;
			var batch_node = period_node.parent;
			for (var i = 0; i < batch_node.item.children.length; ++i) {
				for (var j = 0; j < batch_node.item.children[i].children.length; ++j) {
					var node = batch_node.item.children[i].children[j].node;
					if (!(node instanceof CurriculumTreeNode_Specialization)) continue;
					if (node.spe.id != parent_node.spe.id) continue;
					if (batch_node.item.children[i].node.period.id == period_node.period.id) from_period_index = possible_periods.length;
					possible_periods.push(batch_node.item.children[i].node.period);
					possible_periods_node.push(node);
				}
			}
		} else if (parent_node instanceof CurriculumTreeNode_Group) {
			// possible periods are all periods having the same specialization and parent group
			var parent_group_id = parent_node.group.id;
			var specialization_node = null;
			var period_node = null;
			var p = parent_node.parent;
			do {
				if (p instanceof CurriculumTreeNode_Specialization) {
					specialization_node = p;
					period_node = p.parent;
					break;
				}
				if (p instanceof CurriculumTreeNode_BatchPeriod) {
					period_node = p;
					break;
				}
				p = p.parent;
			} while (p != null);
			if (specialization_node != null) inside_spe = specialization_node.spe.id;
			var batch_node = period_node.parent;
			for (var i = 0; i < batch_node.item.children.length; ++i) {
				if (specialization_node != null) {
					var n = null;
					for (var j = 0; j < batch_node.item.children[i].children.length; ++j) {
						var node = batch_node.item.children[i].children[j].node;
						if (!(node instanceof CurriculumTreeNode_Specialization)) continue;
						if (node.spe.id != specialization_node.spe.id) continue;
						if (parent_group_id != null) n = searchGroupNode(node, parent_group_id);
						else n = node;
						break;
					}
					if (!n) continue;
					if (batch_node.item.children[i].node.period.id == period_node.period.id) from_period_index = possible_periods.length;
					possible_periods.push(batch_node.item.children[i].node.period);
					possible_periods_node.push(n);
				} else {
					var n;
					if (parent_group_id == null) n = batch_node.item.children[i].node;
					else n = searchGroupNode(batch_node.item.children[i].node, parent_group_id);
					if (!n) continue;
					if (batch_node.item.children[i].node.period.id == period_node.period.id) from_period_index = possible_periods.length;
					possible_periods.push(batch_node.item.children[i].node.period);
					possible_periods_node.push(n);
				}
			}
		}
		
		var selected_periods = [];
		if (possible_periods.length == 1) {
			content.appendChild(document.createTextNode(" in period "+possible_periods[0].name));
			selected_periods.push(0);
		} else {
			content.appendChild(document.createTextNode(" in the following periods:"));
			content.appendChild(document.createElement("BR"));
			for (var i = 0; i < possible_periods.length; ++i) {
				var cb = document.createElement("INPUT");
				cb.type = "checkbox";
				cb._index = i;
				if (from_period_index == i) {
					cb.checked = 'checked';
					selected_periods.push(i);
				}
				cb.style.marginLeft = "10px";
				cb.onchange = function() {
					if (this.checked) selected_periods.push(this._index);
					else selected_periods.removeUnique(this._index);
				};
				content.appendChild(cb);
				content.appendChild(document.createTextNode(possible_periods[i].name));
				content.appendChild(document.createElement("BR"));
			}
		}
		
		var p = new popup_window("Add "+gt.name,theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.add,"right_bottom"),content);
		p.addOkCancelButtons(function(){
			if (input.value.length == 0) {
				alert("Please enter a name");
				return;
			}
			if (selected_periods.length == 0) {
				alert("Please select at least one period");
				return;
			}
			for (var i = 0; i < selected_periods.length; ++i) {
				var node = possible_periods_node[selected_periods[i]];
				for (var j = 0; j < node.item.children.length; ++j)
					if (node.item.children[j].node instanceof CurriculumTreeNode_Group)
						if (node.item.children[j].node.group.name.isSame(input.value)) {
							alert("A group already exists with this name in period "+possible_periods[selected_periods[i]].name);
							return;
						}
			}

			var add_to_period = function(period_index) {
				var group_name = input.value.trim();
				var period = possible_periods[selected_periods[period_index]];
				var node = possible_periods_node[selected_periods[period_index]];
				p.freeze("Add "+gt.name+" "+group_name+" to period "+period.name+"...");
				var data = {
					period: period.id,
					specialization: inside_spe,
					type: gt.id,
					parent: node instanceof CurriculumTreeNode_Group ? node.group.id : null,
					name: group_name
				};
				service.json("students_groups","new_group",data,function(res){
					if (!res || !res.id) { p.unfreeze(); return; }

					// add the group node to the tree
					var g = new StudentsGroup(res.id, group_name, gt.id, period.id, inside_spe, data.parent);
					if (node instanceof CurriculumTreeNode_Group)
						node.group.sub_groups.push(g);
					else {
						var spe_id = null;
						var period_id = null;
						if (node instanceof CurriculumTreeNode_Specialization) {
							spe_id = node.spe.id;
							period_id = node.parent.period.id;
						} else
							period_id = node.period.id;
						for (var i = 0; i < groups.length; ++i)
							if (groups[i].period == period_id) {
								for (var j = 0; j < groups[i].specializations.length; ++j)
									if (groups[i].specializations[j].specialization == spe_id) {
										for (var k = 0; k < groups[i].specializations[j].groups_types.length; ++k)
											if (groups[i].specializations[j].groups_types[k].group_type == group_type_id) {
												groups[i].specializations[j].groups_types[k].groups.push(g);
												break;
											}
										break;
									}
								break;
							}
					}
					new CurriculumTreeNode_Group(node, g);
					
					p.unfreeze();
					if (period_index == selected_periods.length-1)
						p.close();
					else
						add_to_period(period_index+1);
				});
			};
			add_to_period(0);
		});
		p.show();
		input.focus();
	});
}
/** Ask the user to confirm, then remove the StudentsGroup from database and from the tree
 * @param {CurriculumTreeNode_Class} class_node tree node of the class to remove
 */
function removeGroup(group_node) {
	confirm_dialog("Are you sure you want to remove the "+getSelectedGroupType().name+" '"+group_node.group.name+"' ?",function(yes){
		if (!yes) return;
		var lock = lock_screen();
		service.json("data_model","remove_row",{table:"StudentsGroup",row_key:group_node.group.id},function(res){
			unlock_screen(lock);
			if (!res) return;
			if (group_node.parent instanceof CurriculumTreeNode_Group) {
				group_node.parent.group.sub_groups.removeUnique(group_node.group);
			} else {
				var spe_id = null;
				var period_id = null;
				if (group_node.parent instanceof CurriculumTreeNode_Specialization) {
					spe_id = group_node.parent.spe.id;
					period_id = group_node.parent.parent.period.id;
				} else
					period_id = group_node.parent.period.id;
				for (var i = 0; i < groups.length; ++i)
					if (groups[i].period == period_id) {
						for (var j = 0; j < groups[i].specializations.length; ++j)
							if (groups[i].specializations[j].specialization == spe_id) {
								for (var k = 0; k < groups[i].specializations[j].groups_types.length; ++k)
									if (groups[i].specializations[j].groups_types[k].group_type == group_type_id) {
										groups[i].specializations[j].groups_types[k].groups.removeUnique(group_node.group);
										break;
									}
								break;
							}
						break;
					}
			}
			group_node.item.remove();
		});
	});
}
