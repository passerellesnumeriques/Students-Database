<?php 
class page_curriculum extends Page {
	
	public function get_required_rights() { return array(); } // TODO
	
	public function execute() {
		if (!isset($_GET["batch"])) {
			if (!isset($_GET["period"])) {
				echo "<img src='".theme::$icons_16["info"]."'/> ";
				echo "Please select a batch, an academic period, or a class, to display its curriculum";
				return;
			}
		}
		if (isset($_GET["batch"])) {
			$periods = SQLQuery::create()->select("AcademicPeriod")->where("batch",$_GET["batch"])->orderBy("AcademicPeriod","start_date")->execute();
			$all_periods = $periods;
			$batch_id = $_GET["batch"];
		} else {
			$periods = SQLQuery::create()->select("AcademicPeriod")->where("id",$_GET["period"])->execute();
			$all_periods = SQLQuery::create()->select("AcademicPeriod")->where("batch",$periods[0]["batch"])->orderBy("AcademicPeriod","start_date")->execute();
			$batch_id = $periods[0]["batch"];
		}
		$batch_info = SQLQuery::create()->select("StudentBatch")->whereValue("StudentBatch", "id", $batch_id)->executeSingleRow();
		
		$this->add_javascript("/static/widgets/tree/tree.js");
		?>
		<?php if (PNApplication::$instance->user_management->has_right("edit_curriculum")) { ?>
		<div style='background-color:#ffffa0;border-bottom:1px solid #e0e0ff;padding:5px;font-family:Verdana'>
			<img src='<?php echo theme::$icons_16["info"];?>' style='vertical-align:bottom'/>
			Right-click on an element below to access functionalities and edit the curriculum
		</div>
		<?php } ?>
		<div id='curriculum_tree' style='cursor:default;background-color:white'></div>
		<script type='text/javascript'>
		var edit = <?php if (PNApplication::$instance->user_management->has_right("edit_curriculum")) echo "true"; else echo "false"; ?>;
		var periods = [<?php 
		$first = true;
		foreach ($periods as $period) {
			if ($first) $first = false; else echo ",";
			$period_spe = SQLQuery::create()->select("AcademicPeriodSpecialization")->whereValue("AcademicPeriodSpecialization","period",$period["id"])->join("AcademicPeriodSpecialization","Specialization",array("specialization"=>"id"))->execute();
			echo "{";
			echo "id:".$period["id"];
			echo ",name:".json_encode($period["name"]);
			echo ",next_periods:[";
			$period_found = false;
			$first_next = true;
			foreach ($all_periods as $p) {
				if (!$period_found) {
					if ($p["id"] == $period["id"]) $period_found = true;
					continue;
				}
				if ($first_next) $first_next = false; else echo ",";
				echo "{id:".$p["id"].",name:".json_encode($p["name"])."}";
			}
			echo "]";
			echo ",specializations:[";
			$first_spe = true;
			foreach ($period_spe as $s) {
				if ($first_spe) $first_spe = false; else echo ",";
				echo "{";
				echo "id:".$s["specialization"];
				echo ",name:".json_encode($s["name"]);
				echo ",subjects:[";
				$subjects = SQLQuery::create()->select("CurriculumSubject")->whereValue("CurriculumSubject", "period", $period["id"])->whereValue("CurriculumSubject", "specialization", $s["specialization"])->execute();
				$first_subject = true;
				foreach ($subjects as $subject) {
					if ($first_subject) $first_subject = false; else echo ",";
					echo "{";
					echo "id:".$subject["id"];
					echo ",name:".json_encode($subject["name"]);
					echo ",code:".json_encode($subject["code"]);
					echo ",category:".$subject["category"];
					echo "}";
				}
				echo "]";
				echo "}";
			}
			echo "]";
			echo ",subjects:[";
			if (count($period_spe) == 0) {
				$subjects = SQLQuery::create()->select("CurriculumSubject")->whereValue("CurriculumSubject", "period", $period["id"])->execute();
				$first_subject = true;
				foreach ($subjects as $subject) {
					if ($first_subject) $first_subject = false; else echo ",";
					echo "{";
					echo "id:".$subject["id"];
					echo ",name:".json_encode($subject["name"]);
					echo ",code:".json_encode($subject["code"]);
					echo ",hours:".json_encode($subject["hours"]);
					echo ",hours_type:".json_encode($subject["hours_type"]);
					echo ",category:".$subject["category"];
					echo "}";
				}
			}
			echo "]";
			echo "}";
		}
		?>];
		var categories = [<?php
		$categories = SQLQuery::create()->select("CurriculumSubjectCategory")->execute();
		$first = true;
		foreach ($categories as $cat) {
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "id:".$cat["id"];
			echo ",name:".json_encode($cat["name"]);
			echo "}";
		} 
		?>];
		var specializations = [<?php
		$spe = SQLQuery::create()->select("Specialization")->execute();
		$first = true;
		foreach ($spe as $s) {
			if ($first) $first = false; else echo ",";
			echo "{id:".$s["id"].",name:".json_encode($s["name"])."}";
		} 
		?>];

		var t = new tree('curriculum_tree');
		t.addColumn(new TreeColumn("Subject Code - Name"));
		t.addColumn(new TreeColumn("Hours"));
		t.setShowColumn(true);
		
		function build_element(icon, text, oncontextmenu) {
			var div = document.createElement("DIV");
			div.style.display = "inline-block";
			if (icon) {
				var img = document.createElement("IMG");
				img.src = icon;
				img.style.paddingRight = "3px";
				img.style.verticalAlign = 'bottom';
				div.appendChild(img);
			}
			div.appendChild(document.createTextNode(text));
			if (oncontextmenu)
				div.oncontextmenu = function(ev) {
					var elem = this;
					require("context_menu.js",function() {
						var menu = new context_menu();
						oncontextmenu(elem, menu);
						if (menu.getItems().length > 0)
							menu.showBelowElement(elem);
					});
					stopEventPropagation(ev);
					return false;
				};
				return div;
		}
		var root;
		function build_root() {
			var element = build_element(null, "Curriculum", function(elem, menu) {
				if (edit) {
					menu.addIconItem(theme.build_icon("/static/curriculum/academic_16.png",theme.icons_10.add,"right_bottom"), "New Academic Period", function() { window.parent.new_academic_period(window.parent.root.findTag("batch<?php echo $batch_id;?>")); });
				}
			});
			root = new TreeItem(element, true);
			t.addItem(root);
			for (var i = 0; i < periods.length; ++i)
				build_period(root, periods[i]);
		}
		function build_period(root, period) {
			var element = build_element(null, period.name, function(elem,menu) {
				if (edit) {
					menu.addIconItem(theme.build_icon("/static/curriculum/curriculum_16.png", theme.icons_10.add,"right_bottom"), "Add Specialization", function() { window.parent.new_specialization(window.parent.root.findTag("period"+period.id)); });
					menu.addSeparator();
					menu.addIconItem(theme.build_icon("/static/curriculum/subjects_16.png", theme.icons_10.add,"right_bottom"), "Add Subject Category", new_category);
					menu.addSeparator();
					menu.addIconItem(theme.build_icon("/static/curriculum/academic_16.png",theme.icons_10.remove,"right_bottom"), "Remove Academic Period", function() { window.parent.remove_period(window.parent.root.findTag("period"+period.id)); });
				}
			});
			var item = new TreeItem(element, true);
			item.period = period;
			element.item = item;
			period.item = item;
			root.addItem(item);
			if (period.specializations.length == 0) {
				for (var i = 0; i < categories.length; ++i)
					build_category(categories[i], period);
			} else {
				for (var i = 0; i < period.specializations.length; ++i)
					build_specialization(period.specializations[i], period);
			}
		}
		function build_specialization(spe, period) {
			var element = build_element("/static/curriculum/curriculum_16.png", spe.name, function(elem,menu) {
				menu.addIconItem(theme.build_icon("/static/curriculum/subjects_16.png", theme.icons_10.add,"right_bottom"), "Add Subject Category", new_category);
				menu.addSeparator();
				menu.addIconItem(theme.build_icon("/static/curriculum/academic_16.png",theme.icons_10.remove,"right_bottom"), "Remove Specialization", function() { window.parent.remove_specialization(window.parent.root.findTag("period"+period.id+"_specialization"+spe.id)); });
			});
			var item = new TreeItem(element, true);
			item.specialization = spe;
			item.period = period;
			element.item = item;
			spe.item = item;
			period.item.addItem(item);
			for (var i = 0; i < categories.length; ++i)
				build_category(categories[i], period, spe);
		}
		function build_category(cat, period, spe) {
			var element = build_element("/static/curriculum/subjects_16.png", cat.name, function(elem,menu) {
				if (edit) {
					menu.addIconItem(theme.build_icon("/static/curriculum/subject_16.png", theme.icons_10.add, "right_bottom"), "Add Subject", function() { new_subject(period, cat, spe); });
					//menu.addSeparator();
					//menu.addIconItem(theme.build_icon("/static/curriculum/subjects_16.png", theme.icons_10.remove,"right_bottom"), "Remove Category "+cat.name, function() { remove_category(cat); });
				}
			});
			var item = new TreeItem(element, true);
			item.category = cat;
			item.period = period;
			item.spe = spe;
			element.item = item;
			if (!spe) {
				period.item.addItem(item);
				if (!period.categories_items) period.categories_items = [];
				period.categories_items.push({category:cat.id,item:item});
				for (var i = 0; i < period.subjects.length; ++i)
					if (period.subjects[i].category == cat.id)
						build_subject(period.subjects[i], item);
			} else {
				spe.item.addItem(item);
				if (!spe.categories_items) spe.categories_items = [];
				spe.categories_items.push({category:cat.id,item:item});
				for (var i = 0; i < spe.subjects.length; ++i)
					if (spe.subjects[i].category == cat.id)
						build_subject(spe.subjects[i], item);
			}
		}
		function build_subject(subject, cat_item, index) {
			var item;
			var element = build_element("/static/curriculum/subject_16.png", subject.code+" - "+subject.name, function(elem,menu) {
				if (edit) {
					menu.addIconItem(theme.build_icon("/static/curriculum/subject_16.png", theme.icons_10.edit, "right_bottom"), "Edit Subject", function() { edit_subject(subject, function() {
						var index = cat_item.children.indexOf(item);
						cat_item.removeItem(item);
						build_subject(subject, cat_item, index);
					}); });
				}
			});
			var h = document.createElement("SPAN");
			h.style.paddingLeft = "5px";
			if (subject.hours && subject.hours_type)
				h.appendChild(document.createTextNode(subject.hours+" hours "+subject.hours_type));
			item = new TreeItem([new TreeCell(element),new TreeCell(h)]);
			item.subject = subject;
			element.item = item;
			subject.item = item;
			if (typeof index == 'undefined')
				cat_item.addItem(item);
			else
				cat_item.insertItem(item, index);
		}
		build_root();

		window.parent.specialization_added.add_listener(function(spe) {
			specializations.push(spe);
		});
		window.parent.specialization_added_to_period.add_listener(function (add) {
			// if we have the period on the screen, update it
			period = null;
			for (var i = 0; i < periods.length; ++i)
				if (periods[i].id == add.period_id) { period = periods[i]; break; }
			if (period != null) {
				var s = {id:add.specialization_id};
				for (var i = 0; i < specializations.length; ++i)
					if (specializations[i].id == s.id) { s["name"] = specializations[i].name; break; }
				s.subjects = [];
				if (period.subjects) {
					// all subjects have been moved
					s.subjects = period.subjects;
					period.subjects = null;
					for (var i = 0; i < s.subjects.length; ++i)
						s.subjects[i].item.parent_item.removeItem(s.subjects[i].item);
				}
				if (period.categories_items){
					for (var i = 0; i < period.categories_items.length; ++i)
						period.categories_items[i].item.parent_item.removeItem(period.categories_items[i].item);
					period.categories_items = null;
				}
				period.specializations.push(s);
				build_specialization(s, period);
			}
		});
		window.parent.specialization_removed_from_period.add_listener(function (remove) {
			var period = null
			for (var i = 0; i < periods.length; ++i)
				if (periods[i].id == remove.period_id) { period = periods[i]; break; }
			if (period == null) return; // not in the screen
			for (var i = 0; i < period.specializations.length; ++i)
				if (period.specializations[i].id == remove.specialization_id) {
					period.item.removeItem(period.specializations[i].item);
					period.specializations.splice(i,1);
					break;
				}
		});
		window.parent.academic_period_added.add_listener(function(add) {
			var period = {id:add.period_id, name: add.period_name, next_periods:[], specializations:[], subjects:[]};
			for (var i = 0; i < periods.length; ++i)
				periods[i].next_periods.push({id:period.id,name:period.name});
			periods.push(period);
			build_period(root, period);
		});
		window.parent.academic_period_removed.add_listener(function(id){
			for (var i = 0; i < periods.length; ++i) {
				if (periods[i].id == id) {
					periods[i].item.parent_item.removeItem(periods[i].item);
					periods.splice(i, 1);
					i--;
					continue;
				}
				for (var j = 0; j < periods[i].next_periods.length; ++j)
					if (periods[i].next_periods[j].id == id) {
						periods[i].next_periods.splice(j,1);
						break;
					}
			}
		});

		function new_category() {
			input_dialog(
				theme.build_icon("/static/curriculum/subjects_16.png",theme.icons_10.add,"right_bottom"),
				"New Subject Category",
				"Category name",
				"",
				100,
				function(name) {
					if (name.length == 0) return "Please enter a name";
					for (var i = 0; i < categories.length; ++i)
					if (categories[i].name.toLowerCase().trim() == name.toLowerCase().trim())
						return "This category already exists";
					return null;
				},function(name) {
					if (!name) return;
					var lock = lock_screen(null, "Creation of the category "+name);
					service.json("data_model","save_entity",{table:"CurriculumSubjectCategory",field_name:name},function(res) {
						unlock_screen(lock);
						if (res && res.key) {
							var cat = {id:res.key,name:name};
							categories.push(cat);
							for (var i = 0; i < periods.length; ++i)
								build_category(cat, periods[i]);
						}
					});
				}
			);
		}
		function remove_category(cat) {
			// TODO
			/*var lock = lock_screen(null, "Removing category "+cat.name);
			service.json("data_model","remove_row",{table:"CurriculumSubjectCategory",row_key:cat.id},function(res) {
				unlock_screen(lock);
				if (!res) return;
				// TODO remove from tree
			});*/
		}
		function new_subject(period, category, spe) {
			require("popup_window.js");
			service.json("curriculum", "get_subjects", {category:category.id,specialization:spe ? spe.id : null,period_to_exclude:period.id}, function(list) {
				var table = document.createElement("TABLE");
				table.className = 'all_borders';
				require("popup_window.js",function() {
					var p = new popup_window("New Subject", theme.build_icon("/static/curriculum/subject_16.png", theme.icons_10.add, "right_bottom"), table);
					var tr = document.createElement("TR"); table.appendChild(tr);
					var td = document.createElement("TH"); tr.appendChild(td);
					td.innerHTML = "Create new subject";
					td = document.createElement("TH"); tr.appendChild(td);
					td.innerHTML = "Or copy an existing one";
	
					tr = document.createElement("TR"); table.appendChild(tr);
					td = document.createElement("TD"); tr.appendChild(td);
					td.style.verticalAlign = "top";
					var new_subject_control = new EditSubjectControl({id:-1,code:"",name:"",hours:null,hours_type:null});
					td.appendChild(new_subject_control.element);
	
					td = document.createElement("TD"); tr.appendChild(td);
					td.style.verticalAlign = "top";
					var table2 = document.createElement("TABLE");
					td.appendChild(table2);
					tr = document.createElement("TR"); table2.appendChild(tr);
					td = document.createElement("TH"); tr.appendChild(td);
					td = document.createElement("TH"); tr.appendChild(td);
					td.innerHTML = "Code";
					td = document.createElement("TH"); tr.appendChild(td);
					td.innerHTML = "Name";
					td = document.createElement("TH"); tr.appendChild(td);
					td.innerHTML = "Hours";
	
					for (var i = 0; i < list.length; ++i) {
						tr = document.createElement("TR"); table2.appendChild(tr);
						td = document.createElement("TD"); tr.appendChild(td);
						list[i].button = document.createElement("INPUT");
						list[i].button.type = 'radio';
						list[i].button.name = 'existing_subject';
						td.appendChild(list[i].button);
						td = document.createElement("TD"); tr.appendChild(td);
						td.appendChild(document.createTextNode(list[i].code));
						td = document.createElement("TD"); tr.appendChild(td);
						td.appendChild(document.createTextNode(list[i].name));
						td = document.createElement("TD"); tr.appendChild(td);
						if (list[i].hours) {
							td.appendChild(document.createTextNode(list[i].hours));
							if (list[i].hours_type)
								td.appendChild(document.createTextNode(" "+list[i].hours_type));
						}
					}

					var create = function(code,name,hours,hours_type) {
						p.close();
						var lock = lock_screen(null, "Creation of the subject "+name);
						service.json("data_model","save_entity",{
							table: "CurriculumSubject",
							field_period: period.id,
							field_category: category.id,
							field_specialization: spe ? spe.id : null,
							field_code: code,
							field_name: name,
							field_hours: hours,
							field_hours_type: hours_type
						},function(res){
							unlock_screen(lock);
							if (res && res.key) {
								var subject = {
									id: res.key,
									code: code,
									name: name,
									hours: hours,
									hours_type: hours_type,
									category: category.id
								};
								var cat_item = null;
								if (spe) {
									spe.subjects.push(subject);
									for (var i = 0; i < spe.categories_items.length; ++i)
										if (spe.categories_items[i].category == category.id) {
											cat_item = spe.categories_items[i].item;
											break;
										}
									} else {
									period.subjects.push(subject);
									for (var i = 0; i < period.categories_items.length; ++i)
										if (period.categories_items[i].category == category.id) {
											cat_item = period.categories_items[i].item;
											break;
										}
								}
								build_subject(subject, cat_item);
							}
						});
					};
					
					var button;
					tr = document.createElement("TR"); table.appendChild(tr);
					td = document.createElement("TD"); tr.appendChild(td);
					td.style.textAlign = "center";
					button = document.createElement("DIV");
					button.className = 'button';
					button.appendChild(document.createTextNode("Create"));
					button.onclick = function() {
						var code = new_subject_control.input_code.value;
						var name = new_subject_control.input_name.value;
						var hours = new_subject_control.input_hours.value;
						var hours_type = new_subject_control.select_hours_type.value;
						if (code.length == 0) { alert('Please enter a code'); return; }
						if (name.length == 0) { alert('Please enter a name'); return; }
						if (hours.length == 0) { hours = null; hours_type = null; }
						else {
							hours = parseInt(hours);
							if (isNaN(hours)) { alert('Invalid number of hours'); return; }
							if (hours_type == "") hours_type = null;
						}
						create(code,name,hours,hours_type);
					};
					td.appendChild(button);
					td = document.createElement("TD"); tr.appendChild(td);
					td.style.textAlign = "center";
					button = document.createElement("DIV");
					button.className = 'button';
					button.appendChild(document.createTextNode("Copy selected subject"));
					button.onclick = function() {
						for (var i = 0; i < list.length; ++i) {
							if (list[i].button.checked) {
								create(list[i].code, list[i].name, list[i].hours, list[i].hours_type);
								return;
							}
						}
						alert('Please select a subject to copy');
					};
					td.appendChild(button);
					p.show();
				});
			});
		}

		// TODO lock the curriculum ?

		function edit_subject(subject, onchanged) {
			require("popup_window.js",function() {
				var control = new EditSubjectControl(subject);
				var p = new popup_window("Edit Subject", "/static/curriculum/subject_16.png", control.element);
				p.addOkCancelButtons(function(){
					var code = control.input_code.value;
					var name = control.input_name.value;
					var hours = control.input_hours.value;
					var hours_type = control.select_hours_type.value;
					if (code.length == 0) { alert('Please enter a code'); return; }
					if (name.length == 0) { alert('Please enter a name'); return; }
					if (hours.length == 0) { hours = null; hours_type = null; }
					else {
						hours = parseInt(hours);
						if (isNaN(hours)) { alert('Invalid number of hours'); return; }
						if (hours_type == "") hours_type = null;
					}
					p.freeze("Saving subject");
					service.json("data_model","save_entity",{
						table: "CurriculumSubject",
						key: subject.id,
						lock: -1, // TODO
						field_code: code,
						field_name: name,
						field_hours: hours,
						field_hours_type: hours_type
					},function(res){
						if (!res) {
							p.unfreeze();
							return;
						}
						subject.code = code;
						subject.name = name;
						subject.hours = hours;
						subject.hours_type = hours_type;
						onchanged();  
						p.close();
					});
				});
				p.show();
			});
		}

		function EditSubjectControl(subject) {
			this.element = document.createElement("TABLE");
			var tr,td;
			this.element.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TD"));
			td.innerHTML = "Code";
			tr.appendChild(td = document.createElement("TD"));
			this.input_code = document.createElement("INPUT");
			this.input_code.type = "text";
			this.input_code.maxLength = 100;
			this.input_code.value = subject.code;
			td.appendChild(this.input_code);
			
			this.element.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TD"));
			td.innerHTML = "Name";
			tr.appendChild(td = document.createElement("TD"));
			this.input_name = document.createElement("INPUT");
			this.input_name.type = "text";
			this.input_name.maxLength = 100;
			this.input_name.value = subject.name;
			td.appendChild(this.input_name);

			this.element.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TD"));
			td.innerHTML = "Hours";
			tr.appendChild(td = document.createElement("TD"));
			this.input_hours = document.createElement("INPUT");
			this.input_hours.type = "text";
			this.input_hours.maxLength = 5;
			this.input_hours.size = 5;
			this.input_hours.value = subject.hours ? subject.hours : "";
			td.appendChild(this.input_hours);
			this.select_hours_type = document.createElement("SELECT");
			var o;
			o = document.createElement("OPTION");
			o.value = ""; o.text = "";
			this.select_hours_type.add(o);
			o = document.createElement("OPTION");
			o.value = "Per week"; o.text = "Per week";
			this.select_hours_type.add(o);
			o = document.createElement("OPTION");
			o.value = "Per period"; o.text = "Per period";
			this.select_hours_type.add(o);
			switch (subject.hours_type) {
			case "Per week": this.select_hours_type.selectedIndex = 1; break;
			case "Per period": this.select_hours_type.selectedIndex = 2; break;
			default: this.select_hours_type.selectedIndex = 0; break;
			}
			td.appendChild(this.select_hours_type);
		}
		</script>
		<?php 
	}
	
}
?>