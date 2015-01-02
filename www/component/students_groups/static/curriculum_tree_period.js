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
		var spes = [];
		for (var i = 0; i < period.available_specializations.length; ++i) {
			var spe_id = period.available_specializations[i];
			var spe = null;
			for (var j = 0; j < specializations.length; ++j)
				if (specializations[j].id == spe_id) { spe = specializations[j]; break; }
			if (spe == null) { spes = null; break; }
			spes.push(spe);
		}
		if (spes === null) {
			// seems to have been modified, we need to refresh from back-end
			var t=this;
			service.json("curriculum","get_specializations",null,function(res) {
				specializations = res;
				spes = [];
				for (var i = 0; i < period.available_specializations.length; ++i) {
					var spe_id = period.available_specializations[i];
					var spe = null;
					for (var j = 0; j < specializations.length; ++j)
						if (specializations[j].id == spe_id) { spe = specializations[j]; break; }
					spes.push(spe);
				}
				for (var i = 0; i < spes.length; ++i)
					new CurriculumTreeNode_Specialization(t, spes[i]);
			});
		} else for (var i = 0; i < spes.length; ++i)
			new CurriculumTreeNode_Specialization(this, spes[i]);
	}
	buildGroupsTree(this, period, null);
}
CurriculumTreeNode_BatchPeriod.prototype = new CurriculumTreeNode;
CurriculumTreeNode_BatchPeriod.prototype.constructor = CurriculumTreeNode_BatchPeriod;
CurriculumTreeNode_BatchPeriod.prototype.createGroupsNodes = function() {
	buildGroupsTree(this, this.period, null);
};
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
		button.ondomremoved(function(b) {b.node = null;});
		var gt = getSelectedGroupType();
		if (this.period.available_specializations.length == 0 || !gt.specialization_dependent) {
			button = document.createElement("BUTTON");
			button.className = "action green";
			button.innerHTML = "<img src='"+theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.add)+"'/> New ";
			button.appendChild(document.createTextNode(gt.name));
			button.title = "Create a new "+gt.name+" in period "+this.period.name;
			button.node = this;
			button.onclick = function() { newGroup(this.node); };
			buttons.appendChild(button);
			button.ondomremoved(function(b) {b.node = null;});
		}
	}
	return div;
};
CurriculumTreeNode_BatchPeriod.prototype.getURLParameters = function() {
	return {batch:this.parent.batch.id,period:this.period.id,group_type:group_type_id};
};