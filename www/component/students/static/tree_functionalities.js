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
function new_academic_period(batch) {
	var container = document.createElement("DIV");
	var error_div = document.createElement("DIV");
	error_div.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> ";
	var error_text = document.createElement("SPAN");
	error_text.style.color = "red";
	error_div.appendChild(error_text);
	error_div.style.visibility = "hidden";
	error_div.style.visibility = "absolute";
	container.appendChild(error_div);

	require("popup_window.js",function(){
		var popup = new popup_window("Create New Academic Period", theme.icons_16.add, container);
		var config = [];
		config.push({
			data: "Period Start",
			config: {
				minimum:dateToSQL(batch.children.length == 0 ? batch.start : batch.children[batch.children.length-1].end),
				maximum:dateToSQL(batch.end)
			}
		});
		config.push({
			data: "Period End",
			config: {
				minimum:dateToSQL(batch.children.length == 0 ? batch.start : batch.children[batch.children.length-1].end),
				maximum:dateToSQL(batch.end)
			}
		});
		var table = new create_academic_period_table(container, function(error) {
			if (error) {
				error_text.innerHTML = error;
				error_div.style.visibility = 'visible';
				error_div.style.position = 'static';
				popup.disableButton('ok');
			} else {
				error_div.style.visibility = 'hidden';
				error_div.style.position = 'absolute';
				popup.enableButton('ok');
			}
		}, config);
		popup.addOkCancelButtons(function(){
			popup.freeze();
			table.save(function(id){
				if (id) { 
					popup.close(); 
					academic_period_added.fire({batch_id:batch.id, period_id: id, period_name: table.get_data("Period Name"), period_start: parseSQLDate(table.get_data("Period Start")), period_end: parseSQLDate(table.get_data("Period End"))});
					return; 
				}
				popup.unfreeze();
			},{batch:batch.id});
		});
		popup.show(); 
	});
}
function remove_period(period) {
	confirm_dialog("Are you sure you want to remove the academic period '"+period.name+"' and its content ?",function(yes){
		if (!yes) return;
		var lock = lock_screen();
		service.json("data_model","remove_row",{table:"AcademicPeriod",row_key:period.id},function(res){
			unlock_screen(lock);
			if (!res) return;
			academic_period_removed.fire(period.id);
		});
	});
}
function new_specialization(period) {
	require("popup_window.js",function() {
		var content = document.createElement("DIV");
		var selection = -1;
		for (var i = 0; i < specializations.length; ++i) {
			var found = false;
			for (var j = 0; j < period.children.length; ++j)
				if (period.children[j].id == specializations[i].id) { found = true; break; }
			if (found) continue;
			var radio = document.createElement("INPUT");
			radio.type = 'radio';
			radio.name = 'specialization';
			radio.value = specializations[i].id;
			radio.onchange = function() { if (this.checked) selection = this.value; };
			content.appendChild(radio);
			content.appendChild(document.createTextNode(specializations[i].name));
			content.appendChild(document.createElement("BR"));
		}
		var radio = document.createElement("INPUT");
		radio.type = 'radio';
		radio.name = 'specialization';
		radio.value = 0;
		radio.onchange = function() { if (this.checked) selection = this.value; };
		content.appendChild(radio);
		content.appendChild(document.createTextNode("New specialization: "));
		var input = document.createElement("INPUT");
		input.type = 'text';
		input.maxLength = 100;
		content.appendChild(input);
		content.appendChild(document.createElement("BR"));
		content.appendChild(document.createElement("HR"));
		var div = document.createElement("DIV"); content.appendChild(div);
		div.style.padding = "3px";
		div.appendChild(document.createTextNode("Add this specialization from period "+period.name+" to period "));
		var select_to_period = document.createElement("SELECT");
		var o = document.createElement("OPTION");
		o.value = period.id;
		o.text = period.name;
		select_to_period.add(o);
		var found = false;
		for (var i = 0; i < period.parent.children.length; ++i) {
			if (!found) {
				if (period.parent.children[i].id == period.id) found = true;
				continue;
			}
			// add the period in the possibilities
			o = document.createElement("OPTION");
			o.value = period.parent.children[i].id;
			o.text = period.parent.children[i].name;
			select_to_period.add(o);
		}
		div.appendChild(select_to_period);

		var p = new popup_window("Add Specialization",null,content);
		p.addOkCancelButtons(function(){
			if (selection == -1) {
				alert("You didn't select anything");
				return;
			}
			var add_spe = function(spe) {
				var found = false;
				var periods = [period];
				for (var i = 0; i < period.parent.children.length; ++i) {
					if (!found) {
						if (period.parent.children[i].id == period.id) found = true;
						if (period.parent.children[i].id == select_to_period.value) break;
						continue;
					}
					var spe_found = false;
					for (var j = 0; j < period.parent.children[i].children.length; ++j)
						if (period.parent.children[i].children[j] instanceof Specialization && period.parent.children[i].children[j].id == spe.id) { spe_found = true; break; }
					if (!spe_found)
						periods.push(period.parent.children[i]);
					if (period.parent.children[i].id == select_to_period.value) break;
				}
				var add_spe_to_period = function(period_index) {
					p.freeze("Add specialization "+spe.name+" to period "+periods[period_index].name+"...");
					service.json("curriculum","add_period_specialization",{period:periods[period_index].id,specialization:spe.id},function(res){
						if (!res) { p.unfreeze(); return; }
						specialization_added_to_period.fire({period_id:periods[period_index].id, specialization_id:spe.id});
						p.unfreeze();
						if (period_index == periods.length-1)
							p.close();
						else
							add_spe_to_period(period_index+1);
					});
				};
				add_spe_to_period(0);
			};
			if (selection == 0) {
				if (input.value.length == 0) {
					alert("Please enter a name");
					return;
				}
				for (var i = 0; i < specializations.length; ++i)
					if (specializations[i].name.toLowerCase().trim() == input.value.toLowerCase().trim()) {
						alert("A specialization already exists with this name");
						return;
					}
				p.freeze("Create specialization "+input.value.trim()+"...");
				service.json("data_model","save_entity",{table:"Specialization",field_name:input.value.trim()},function(res) {
					p.unfreeze();
					if (!res || !res.key) return;
					var spe = {id:res.key,name:input.value.trim()};
					specialization_added.fire(spe);
					add_spe(spe);
				});
				return;
			}
			var spe;
			for (var i = 0; i < specializations.length; ++i)
				if (specializations[i].id == selection) { spe = specializations[i]; break; }
			add_spe(spe);
		});
		p.show();
	});
}
function remove_specialization(spe) {
	confirm_dialog("Are you sure you want to remove the specialization '"+spe.name+"' from the period '"+spe.parent.name+"', and all classes of this specialization ?",function(yes){
		if (!yes) return;
		var lock = lock_screen();
		service.json("curriculum","remove_specialization_from_period",{specialization:spe.id,period:spe.parent.id},function(res){
			unlock_screen(lock);
			if (!res) return;
			specialization_removed_from_period.fire({specialization_id:spe.id,period_id:spe.parent.id});
		});
	});
}

function new_class(period, spe) {
	require("popup_window.js",function() {
		var content = document.createElement("DIV");
		content.appendChild(document.createTextNode("New class: "));
		var input = document.createElement("INPUT");
		input.type = 'text';
		input.maxLength = 100;
		content.appendChild(input);
		content.appendChild(document.createElement("BR"));
		content.appendChild(document.createElement("HR"));
		var div = document.createElement("DIV"); content.appendChild(div);
		div.style.padding = "3px";
		div.appendChild(document.createTextNode("Add this class from period "+period.name+" to period "));
		var select_to_period = document.createElement("SELECT");
		var o = document.createElement("OPTION");
		o.value = period.id;
		o.text = period.name;
		select_to_period.add(o);
		var found = false;
		for (var i = 0; i < period.parent.children.length; ++i) {
			if (!found) {
				if (period.parent.children[i].id == period.id) found = true;
				continue;
			}
			if (spe) {
				var has_classes = false;
				for (var j = 0; j < period.parent.children[i].children.length; ++j)
					if (period.parent.children[i].children[j] instanceof Class) { has_classes = true; break; }
				if (has_classes) break;
				var spe_found = false;
				for (var j = 0; j < period.parent.children[i].children.length; ++j)
					if (period.parent.children[i].children[j].id == spe.id) { spe_found = true; break; }
				if (!spe_found) break;
			} else {
				var has_spe = false;
				for (var j = 0; j < period.parent.children[i].children.length; ++j)
					if (period.parent.children[i].children[j] instanceof Specialization) { has_spe = true; break; }
				if (has_spe) break;
			}
			o = document.createElement("OPTION");
			o.value = period.parent.children[i].id;
			o.text = period.parent.children[i].name;
			select_to_period.add(o);
		}
		div.appendChild(select_to_period);

		var p = new popup_window("Add Class",theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.add,"right_bottom"),content);
		p.addOkCancelButtons(function(){
			if (input.value.length == 0) {
				alert("Please enter a name");
				return;
			}
			var existing = spe ? spe.children : period.children;
			for (var i = 0; i < existing.length; ++i)
				if (existing[i].name.toLowerCase().trim() == input.value.toLowerCase().trim()) {
					alert("A class already exists with this name");
					return;
				}

			var found = false;
			var periods = [period];
			for (var i = 0; i < period.parent.children.length; ++i) {
				if (!found) {
					if (period.parent.children[i].id == period.id) found = true;
					if (period.parent.children[i].id == select_to_period.value) break;
					continue;
				}

				var classes = null;
				if (spe) {
					var has_spe = false;
					for (var j = 0; j < period.parent.children[i].children.length; ++j)
						if (period.parent.children[i].children[j] instanceof Specialization) { has_spe = true; break; }
					if (!has_spe) break;
					for (var j = 0; j < period.parent.children[i].children.length; ++j)
						if (period.parent.children[i].children[j].id == spe.id) { classes = period.parent.children[i].children[j].children; break; }
					if (classes == null) break;
				} else {
					classes = period.children;
				}
				var class_found = false;
				for (var j = 0; j < classes.length; ++j)
					if (classes[j].name.toLowerCase().trim() == input.value.toLowerCase().trim()) { class_found = true; break; }
				
				if (!class_found)
					periods.push(period.parent.children[i]);
				if (period.parent.children[i].id == select_to_period.value) break;
			}
			
			var add_to_period = function(period_index) {
				p.freeze("Add class "+input.value.trim()+" to period "+periods[period_index].name+"...");
				service.json("curriculum","new_class",{period:periods[period_index].id,specialization:spe ? spe.id : null,name:input.value.trim()},function(res){
					if (!res || !res.id) { p.unfreeze(); return; }

					var s = null;
					if (spe) {
						for (var i = 0; i < periods[period_index].children.length; ++i)
							if (periods[period_index].children[i].id == spe.id) {
								s = periods[period_index].children[i];
								break;
							}
						new Class(s, res.id, input.value.trim());
					} else
						new Class(periods[period_index], res.id, input.value.trim());
					
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
function remove_class(cl) {
	confirm_dialog("Are you sure you want to remove the class '"+cl.name+"' ?",function(yes){
		if (!yes) return;
		var lock = lock_screen();
		service.json("data_model","remove_row",{table:"AcademicClass",row_key:cl.id},function(res){
			unlock_screen(lock);
			if (!res) return;
			cl.remove();
		});
	});
}

academic_period_added = new Custom_Event();
academic_period_removed = new Custom_Event();
specialization_added = new Custom_Event();
specialization_removed = new Custom_Event();
specialization_added_to_period = new Custom_Event();
specialization_removed_from_period = new Custom_Event();

academic_period_added.add_listener(function (add) {
	var batch = root.findTag("batch"+add.batch_id);
	new AcademicPeriod(batch, add.period_id, add.period_name, add.period_start, add.period_end);
});
academic_period_removed.add_listener(function (id) {
	root.findTag("period"+id).remove();
});

specialization_added.add_listener(function(spe) {
	specializations.push(spe);
});
specialization_added_to_period.add_listener(function (add) {
	var period_id = add.period_id;
	var spe_id = add.specialization_id;
	var batches = [];
	for (var i = 0; i < root.children[0].children.length; ++i)
		for (var j = 0; j < root.children[0].children[i].children.length; ++j)
			batches.push(root.children[0].children[i].children[j]);
	
	var period = null;
	// search period
	for (var i = 0; i < batches.length; ++i) {
		for (var j = 0; j < batches[i].children.length; ++j)
			if (batches[i].children[j].id == period_id) { period = batches[i].children[j]; break; }
		if (period != null) break;
	}
	if (period == null) {
		alert('Cannot find period!');
		return; // should never happen...
	}
	// search specialization
	var spe = null;
	for (var i = 0; i < specializations.length; ++i)
		if (specializations[i].id == spe_id) { spe = specializations[i]; break; }
	if (spe == null) {
		alert('Cannot find specialization !');
		return; // should never happend...
	}
	
	var classes = [];
	var has_classes = false;
	for (var i = 0; i < period.children.length; ++i) if (period.children[i] instanceof Class) { has_classes = true; break; }
	if (has_classes) {
		// classes have been moved to the new specialization
		for (var i = 0; i < period.children.length; ++i) {
			classes.push(period.children[i]);
			period.children[i].remove();
		}
	}
	var s = new Specialization(period, spe_id, spe.name);
	for (var i = 0; i < classes.length; ++i)
		new Class(s, classes[i].id, classes[i].name);
});
specialization_removed_from_period.add_listener(function (remove) {
	root.findTag("period"+remove.period_id+"_specialization"+remove.specialization_id).remove();
});