// #depends[curriculum_tree.js]

/**
 * A class in a period or in a specialization
 * @param {CurriculumTreeNode} parent parent node (either period or specialization)
 * @param {AcademicClass} cl class object
 */
function CurriculumTreeNode_Class(parent, cl) {
	this.cl = cl;
	CurriculumTreeNode.call(this, parent, "class"+cl.id, true);
}
CurriculumTreeNode_Class.prototype = new CurriculumTreeNode;
CurriculumTreeNode_Class.prototype.constructor = CurriculumTreeNode_Class;
CurriculumTreeNode_Class.prototype.getIcon = function() { return "/static/curriculum/batch_16.png"; };
CurriculumTreeNode_Class.prototype.createTitle = function(editable) {
	var span = document.createElement("SPAN");
	span.appendChild(document.createTextNode("Class "));
	var cl = this.cl;
	window.top.datamodel.create_cell(window, "AcademicClass", null, "name", cl.id, cl.name, "field_text", {can_be_null:false,max_length:100}, editable && can_edit_batches, span, function(value) { cl.name = value; });
	return span;
};
CurriculumTreeNode_Class.prototype.createInfo = function() {
	var div = document.createElement("DIV");
	if (window.can_edit_batches) {
		var button = document.createElement("BUTTON");
		button.className = "action";
		button.innerHTML = "<img src='"+theme.icons_16.edit+"'/> Edit";
		button.title = "Rename Class";
		button.cl = this.cl;
		button.onclick = function() {
			var cl = this.cl;
			input_dialog(theme.icons_16.edit,"Edit Class Name","Name of the class",this.cl.name,100,
				function(name){
					if (!name.checkVisible()) return "Please enter a name";
					return null;
				},function(name){
					if (!name) return;
					name = name.trim();
					service.json("data_model","save_entity",{
						table: "AcademicClass",
						key: cl.id,
						lock: -1,
						field_name: name
					},function(res){
						if (res) window.top.datamodel.cellChanged("AcademicClass","name",cl.id,name);
					});
				}
			);
		};
		div.appendChild(button);
		button = document.createElement("BUTTON");
		button.className = "action red";
		button.innerHTML = "<img src='"+theme.icons_16.remove_white+"'/> Remove";
		button.node = this;
		button.onclick = function() {
			removeClass(this.node);
		};	
		div.appendChild(button);
	}
	return div;
};
CurriculumTreeNode_Class.prototype.getURLParameters = function() {
	var params = {};
	params["class"] = this.cl.id;
	if (this.parent.spe) {
		params["specialization"] = this.parent.spe.id;
		params["period"] = this.parent.parent.period.id;
		params["batch"] = this.parent.parent.parent.batch.id;
	} else {
		params["period"] = this.parent.period.id;
		params["batch"] = this.parent.parent.batch.id;
	}
	return params;
};
