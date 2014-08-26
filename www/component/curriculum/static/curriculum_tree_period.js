// #depends[curriculum_tree.js]

/**
 * Period of a batch
 * @param {CurriculumTreeNode_Batch} parent batch node
 * @param {BatchPeriod} period period information
 */
function CurriculumTreeNode_BatchPeriod(parent, period) {
	this.period = period;
	/** {AcademicPeriod} academic period corresponding to the batch period */ 
	this.academic = getAcademicPeriod(period.academic_period);
	var now = new Date().getTime();
	CurriculumTreeNode.call(this, parent, "period"+period.id, parseSQLDate(this.academic.end).getTime() > now && parseSQLDate(this.academic.start).getTime() < now);
	this.item.cells[0].addStyle({color: parseSQLDate(this.academic.end).getTime() < now ? "#4040A0" : parseSQLDate(this.academic.start).getTime() > now ? "#A04040" : "#40A040"});
	if (period.available_specializations.length > 0) {
		for (var i = 0; i < period.available_specializations.length; ++i) {
			var spe_id = period.available_specializations[i];
			var spe = null;
			for (var j = 0; j < specializations.length; ++j)
				if (specializations[j].id == spe_id) { spe = specializations[j]; break; }
			new CurriculumTreeNode_Specialization(this, spe);
		}
	} else {
		for (var i = 0; i < period.classes.length; ++i)
			new CurriculumTreeNode_Class(this, period.classes[i]);
	}
}
CurriculumTreeNode_BatchPeriod.prototype = new CurriculumTreeNode;
CurriculumTreeNode_BatchPeriod.prototype.constructor = CurriculumTreeNode_BatchPeriod;
CurriculumTreeNode_BatchPeriod.prototype.getIcon = function() { return theme.build_icon("/static/curriculum/hat.png", "/static/curriculum/calendar_10.gif"); };
CurriculumTreeNode_BatchPeriod.prototype.createTitle = function(editable) {
	var span = document.createElement("SPAN");
	var period = this.period;
	window.top.datamodel.create_cell(window, "BatchPeriod", null, "name", period.id, period.name, "field_text", {can_be_null:false,max_length:100}, editable && can_edit_batches, span, function(value) { period.name = value; });
	return span;
};
CurriculumTreeNode_BatchPeriod.prototype.createInfo = function() {
	var aperiod = this.academic;
	var div = document.createElement("DIV");
	var span = document.createElement("SPAN");
	var b = document.createElement("B"); b.appendChild(document.createTextNode("Start")); span.appendChild(b); span.appendChild(document.createTextNode(": "));
	window.top.datamodel.create_cell(window, "BatchPeriod", null, "start", aperiod.id, aperiod.start, "field_date", {}, false, span, function(value) { aperiod.start = value; });
	div.appendChild(span);
	span = document.createElement("SPAN");
	span.style.marginLeft = "5px";
	var b = document.createElement("B"); b.appendChild(document.createTextNode("End")); span.appendChild(b); span.appendChild(document.createTextNode(": "));
	window.top.datamodel.create_cell(window, "BatchPeriod", null, "end", aperiod.id, aperiod.end, "field_date", {}, false, span, function(value) { aperiod.end = value; });
	div.appendChild(span);
	var buttons = document.createElement("DIV"); div.appendChild(buttons);
	if (window.can_edit_batches) {
		var button = document.createElement("BUTTON");
		button.className = "action";
		button.innerHTML = "<img src='"+theme.icons_16.edit+"'/> Edit";
		button.title = "Edit batch";
		button.node = this;
		button.onclick = function() {
			editBatch(this.node.parent.batch);
		};
		buttons.appendChild(button);
		if (this.period.available_specializations.length == 0) {
			button = document.createElement("BUTTON");
			button.className = "action green";
			button.innerHTML = "<img src='"+theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.add)+"'/> New Class";
			button.title = "Create a new class in period "+this.period.name;
			button.node = this;
			button.onclick = function() {
				newClass(this.node, null);
			};
			buttons.appendChild(button);
		}
	}
	return div;
};
CurriculumTreeNode_BatchPeriod.prototype.getURLParameters = function() {
	return {batch:this.parent.batch.id,period:this.period.id};
};

/** Open a popup to create a new AcademicClass
 * @param {CurriculumTreeNode_Period} period_node node of the period where the class will be created
 * @param {Specialization} spe Specialization object or null
 */
function newClass(period_node, spe) {
	require("popup_window.js",function() {
		var content = document.createElement("DIV");
		content.style.padding = "10px";
		content.appendChild(document.createTextNode("Create class "));
		var input = document.createElement("INPUT");
		input.type = 'text';
		input.maxLength = 100;
		content.appendChild(input);
		require("input_utils.js",function() {
			inputAutoresize(input, "15");
			inputDefaultText(input, "Class Name");
		});
		content.appendChild(document.createTextNode(" from period "+period_node.period.name+" to period "));
		var select_to_period = document.createElement("SELECT");
		var o = document.createElement("OPTION");
		o.value = period_node.period.id;
		o.text = period_node.period.name;
		select_to_period.add(o);
		var found = false;
		for (var i = 0; i < period_node.parent.item.children.length; ++i) {
			var node = period_node.parent.item.children[i].node;
			if (!found) {
				if (node.period.id == period_node.period.id) found = true;
				continue;
			}
			if (!spe && node.period.available_specializations.length > 0) break;
			o = document.createElement("OPTION");
			o.value = node.period.id;
			o.text = node.period.name;
			select_to_period.add(o);
		}
		content.appendChild(select_to_period);

		var p = new popup_window("Add Class",theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.add,"right_bottom"),content);
		p.addOkCancelButtons(function(){
			if (input.value.length == 0) {
				alert("Please enter a name");
				return;
			}
			for (var i = 0; i < period_node.period.classes.length; ++i)
				if (period_node.period.classes[i].name.toLowerCase().trim() == input.value.toLowerCase().trim()) {
					alert("A class already exists with this name");
					return;
				}

			var found = false;
			var periods = [period_node.period];
			for (var i = 0; i < period_node.parent.item.children.length; ++i) {
				var node = period_node.parent.item.children[i].node;
				if (!found) {
					if (node.period.id == period_node.period.id) found = true;
					if (node.period.id == select_to_period.value) break;
					continue;
				}

				var class_found = false;
				for (var j = 0; j < node.period.classes.length; ++j)
					if (node.period.classes[j].name.toLowerCase().trim() == input.value.toLowerCase().trim()) { class_found = true; break; }
				
				if (!class_found)
					periods.push(node.period);
				if (node.period.id == select_to_period.value) break;
			}
			
			var add_to_period = function(period_index) {
				p.freeze("Add class "+input.value.trim()+" to period "+periods[period_index].name+"...");
				service.json("curriculum","new_class",{period:periods[period_index].id,specialization:spe ? spe.id : null,name:input.value.trim()},function(res){
					if (!res || !res.id) { p.unfreeze(); return; }

					// add the class node to the tree
					var pnode = null;
					for (var i = 0; i < period_node.item.parent.children.length; ++i)
						if (period_node.item.parent.children[i].node.period.id == periods[period_index].id) {
							pnode = period_node.item.parent.children[i].node;
							break;
						}
					var cl = new AcademicClass(res.id, input.value.trim(), spe ? spe.id : null);
					if (spe) {
						var spe_node = null;
						for (var i = 0; i < pnode.item.children.length; ++i)
							if (pnode.item.children[i].node.spe.id == spe.id) {
								spe_node = pnode.item.children[i].node;
								break;
							}
						new CurriculumTreeNode_Class(spe_node, cl);
					} else
						new CurriculumTreeNode_Class(pnode, cl);
					
					p.unfreeze();
					if (period_index == periods.length-1)
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
/** Ask the user to confirm, then remove the AcademicClass from database and from the tree
 * @param {CurriculumTreeNode_Class} class_node tree node of the class to remove
 */
function removeClass(class_node) {
	confirm_dialog("Are you sure you want to remove the class '"+class_node.cl.name+"' ?",function(yes){
		if (!yes) return;
		var lock = lock_screen();
		service.json("data_model","remove_row",{table:"AcademicClass",row_key:class_node.cl.id},function(res){
			unlock_screen(lock);
			if (!res) return;
			class_node.item.remove();
		});
	});
}
