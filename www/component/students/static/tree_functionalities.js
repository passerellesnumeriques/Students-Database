function create_new_batch() {
	require("popup_window.js",function(){
		var popup = new popup_window("Create New Batch", theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.add), "");
		popup.setContentFrame("/dynamic/curriculum/page/edit_batch?popup=yes&onsave=new_batch_created");
		popup.show();
	});
}
function new_batch_created(id) {
	service.json("curriculum","get_batch",{id:id},function(batch){
		tree_build_batch(batch);
	});
}
function edit_batch(batch) {
	require("popup_window.js",function(){
		var popup = new popup_window("Edit Batch", theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.edit), "");
		popup.setContentFrame("/dynamic/curriculum/page/edit_batch?popup=yes&id="+batch.id+"&onsave=batch_saved");
		popup.show();
	});
}
function batch_saved(id) {
	var node = root.findTag("batch"+id);
	node.item.parent.removeItem(node.item);
	service.json("curriculum","get_batch",{id:id},function(batch){
		tree_build_batch(batch);
	});
}
function remove_batch(batch) {
	window.top.datamodel.confirm_remove("StudentBatch", batch.id, function() {
		var node = root.findTag("batch"+batch.id);
		node.item.parent.removeItem(node.item);
	});
}

function new_class(period_node, spe) {
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
					var cl = new StudentClass(res.id, input.value.trim(), spe ? spe.id : null);
					if (spe) {
						var spe_node = null;
						for (var i = 0; i < pnode.item.children.length; ++i)
							if (pnode.item.children[i].node.spe.id == spe.id) {
								spe_node = pnode.item.children[i].node;
								break;
							}
						new ClassNode(spe_node, cl);
					} else
						new ClassNode(pnode, cl);
					
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
	});
}
function remove_class(class_node) {
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
