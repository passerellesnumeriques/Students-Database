<?php 
class page_curriculum extends Page {
	
	public function get_required_rights() { return array(); } // TODO
	
	public function execute() {
		if (!isset($_GET["batch"])) {
			echo "<img src='".theme::$icons_16["info"]."'/> ";
			echo "Please select a batch, an academic period, or a class, to display its curriculum";
			return;
		}
		if (isset($_GET["period"])) {
			$period_id = $_GET["period"];
			$period = PNApplication::$instance->curriculum->getAcademicPeriod($period_id);
			$batch_id = $period["batch"];
			$start_date = $period["start_date"];
			$end_date = $period["end_date"];
			$batch_info = PNApplication::$instance->curriculum->getBatch($batch_id);
		} else {
			$batch_id = $_GET["batch"];
			$period_id = null;
			$batch_info = PNApplication::$instance->curriculum->getBatch($batch_id);
			$start_date = $batch_info["start_date"];
			$end_date = $batch_info["end_date"];
		}
		
		$categories = PNApplication::$instance->curriculum->getSubjectCategories();
		$subjects = PNApplication::$instance->curriculum->getSubjects($batch_id, $period_id);
		$classes = PNApplication::$instance->curriculum->getAcademicClasses($batch_id, $period_id);
		$subjects_ids = array();
		foreach ($subjects as $s) array_push($subjects_ids, $s["id"]);
		$teachers_assigned = PNApplication::$instance->curriculum->getTeachersAssigned($subjects_ids);

		$can_edit = PNApplication::$instance->user_management->has_right("edit_curriculum");
		
		if ($can_edit) {
			if (isset($_GET["edit"]) && $_GET["edit"] == 1) {
				require_once("component/data_model/DataBaseLock.inc");
				$locked_by = null;
				$lock = DataBaseLock::lockRow("StudentBatch", $batch_id, $locked_by);
				if ($lock == null) {
					?>
					<script type='text/javascript'>
					var u=new window.URL(location.href);
					u.params.edit = 0;
					u.params.locker = <?php echo json_encode($locked_by);?>;
					location.href=u.toString();
					</script>
					<?php 
				} else DataBaseLock::generateScript($lock);
			}
		}
		
		$teachers_dates = SQLQuery::create()
			->select("TeacherDates")
			->where("`TeacherDates`.`start` <= '".$end_date."' AND (`TeacherDates`.`end` IS NULL OR `TeacherDates`.`end` > '".$start_date."')")
			->execute();
		$teachers_ids = array();
		foreach ($teachers_dates as $t)
			if (!in_array($t["people"], $teachers_ids))
				array_push($teachers_ids, $t["people"]);
		if (count($teachers_ids) > 0) {
			require_once("component/people/PeopleJSON.inc");
			$teachers = PeopleJSON::PeoplesFromID($teachers_ids);
		} else
			$teachers = "[]";
		
		require_once("component/curriculum/CurriculumJSON.inc");
		$this->require_javascript("curriculum_objects.js");
		
		$this->add_javascript("/static/widgets/tree/tree.js");
		$this->add_javascript("/static/widgets/header_bar.js");
		theme::css($this, "header_bar.css");
		$this->onload("new header_bar('page_header','toolbar');");
		$this->add_javascript("/static/widgets/vertical_layout.js");
		$this->onload("new vertical_layout('page_container');");
		$this->require_javascript("section.js");
		$this->onload("section_from_html('teachers_section');");
		?>
		<div id="page_container" style="width:100%;height:100%">
		<div id='page_header' 
			icon='/static/curriculum/curriculum_16.png' 
			title='Curriculum for Batch <?php echo htmlentities($batch_info["name"]); if (isset($period)) echo ", Period ".$period["name"];?>'
		>
			<?php if ($can_edit) {
				if (isset($_GET['edit']) && $_GET['edit'] == 1)
					echo "<div class='button_verysoft' onclick=\"window.onuserinactive();\"><img src='".theme::$icons_16["no_edit"]."'/> Stop editing</div>";
				else {
					if (isset($_GET["locker"])) {
					?>
					<span>
						<img src='<?php echo theme::$icons_16["error"];?>'/> The batch is already locked by <?php echo $_GET["locker"];?>
					</span>
					<?php 
					} else
						echo "<div class='button_verysoft' onclick=\"var u=new window.URL(location.href);u.params.edit = 1;location.href=u.toString();\"><img src='".theme::make_icon("/static/curriculum/curriculum_16.png",theme::$icons_10["edit"])."'/> Edit curriculum</div>";
				}
			?>
			<div class='button_verysoft' onclick='edit_batch()'>
				<img src='<?php echo theme::$icons_16["edit"];?>'/>
				Edit periods and specializations
			</div>
			<div class='button_verysoft' onclick="alert('Not yet implemented');/*TODO*/">
				<img src='<?php echo theme::make_icon("/static/curriculum/subjects_16.png", theme::$icons_10["edit"]);?>'/>
				Edit subject categories
			</div>
			<?php } ?>
		</div>
		<div id="page_content" layout="fill" style="background-color:white;overflow:auto">
			<div id='curriculum_tree' style='display:inline-block;vertical-align:top'></div>
			<div id="teachers_section" style="display:inline-block;vertical-align:top;margin:5px"
				icon="/static/curriculum/teacher_16.png"
				title="Teachers List"
				collapsable="false"
			>
				<div style='padding:5px'>
					<div id='teachers_container'></div>
					<?php if ($can_edit) {?><button onclick='new_teacher();return false;'><img src='<?php echo theme::make_icon("/static/curriculum/teacher_16.png", theme::$icons_10["add"]);?>'/> New Teacher</button><?php } ?>
				</div>
			</div>
		</div>
		</div>
		<script type='text/javascript'>
		function edit_batch() {
			require("popup_window.js",function(){
				var popup = new popup_window("Edit Batch", theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.edit), "");
				popup.setContentFrame("/dynamic/curriculum/page/edit_batch?popup=yes&id=<?php echo $batch_id;?>&onsave=batch_saved");
				popup.show();
			});
		}
		function batch_saved(id) {
			if (window.parent.batch_saved) window.parent.batch_saved(id);
			location.reload();
		}
		var edit = <?php echo $can_edit && isset($_GET["edit"]) && $_GET["edit"] == 1 ? "true" : "false"; ?>;
		if (edit)
			window.onuserinactive = function() {
				var u=new window.URL(location.href);
				u.params.edit = 0;
				location.href=u.toString();
			};
		var batch = <?php echo CurriculumJSON::BatchJSON($batch_id); ?>;
		var categories = <?php echo CurriculumJSON::SubjectCategoriesJSON($categories); ?>;
		var subjects = <?php echo CurriculumJSON::SubjectsJSON($subjects); ?>;
		var specializations = <?php echo CurriculumJSON::SpecializationsJSON();?>;
		var teachers = <?php echo $teachers;?>;
		var teachers_assigned = <?php echo CurriculumJSON::TeachersAssignedJSON($teachers_assigned); ?>;

		var t = new tree('curriculum_tree');
		t.addColumn(new TreeColumn("Subject Code - Name"));
		t.addColumn(new TreeColumn("Hours"));
		t.addColumn(new TreeColumn("Coef."));
		t.addColumn(new TreeColumn("Assigned Teachers"));
		t.setShowColumn(true);

		function build_tree() {
			for (var i = 0; i < batch.periods.length; ++i) {
				<?php if ($period_id <> null) echo "if (batch.periods[i].id != ".$period_id.") continue;"?>
				build_period(batch.periods[i], batch);
			}
		}
		function build_period(period, batch) {
			var item = t.addHeader("/static/calendar/calendar_32.png", "Period "+period.name, "toolbar_big");
			if (period.available_specializations.length > 0) {
				for (var i = 0; i < period.available_specializations.length; ++i) {
					var spe = null;
					for (var j = 0; j < specializations.length; ++j)
						if (specializations[j].id == period.available_specializations[i]) { spe = specializations[j]; break; }
					build_specialization(item, spe, period, batch);
				}
			} else {
				build_categories(item, period, null, batch);
			}
		}
		function build_specialization(parent, spe, period, batch) {
			var item = createTreeItemSingleCell("/static/curriculum/curriculum_16.png", "Specialization "+spe.name, true);
			parent.addItem(item);
			build_categories(item, period, spe, batch);
		}
		function build_categories(parent, period, spe, batch) {
			for (var i = 0; i < categories.length; ++i) {
				var item = createTreeItemSingleCell("/static/curriculum/subjects_16.png", categories[i].name, true);
				item.cells[0].addStyle({fontWeight:"bold",color:"#602000"});
				if (edit) {
					img = document.createElement("IMG");
					img.src = theme.icons_10.add;
					img.className = "button_verysoft";
					img.style.marginLeft = "2px";
					img.style.verticalAlign = "middle";
					item.cells[0].element.appendChild(img);
					img.cat = categories[i];
					img.item = item;
					img.title = "Add a subject to category "+categories[i].name;
					img.onclick = function() {
						new_subject(this.cat, batch, period, spe, this.item);
					};
				}
				parent.addItem(item);
				build_subjects(item, categories[i].id, period.id, spe ? spe.id : null);
			}
		}
		function build_subjects(parent, category_id, period_id, spe_id) {
			for (var i = 0; i < subjects.length; ++i) {
				if (subjects[i].period_id != period_id) continue;
				if (subjects[i].specialization_id != spe_id) continue;
				if (subjects[i].category_id != category_id) continue;
				build_subject(parent, subjects[i]);
			}
		}
		function build_subject(parent, subject, index) {
			var cells = [];
			var item;
			
			var div = document.createElement("DIV");
			div.style.display = "inline-block";
			var img = document.createElement("IMG");
			img.src = "/static/curriculum/subject_16.png";
			img.style.marginRight = "2px";
			img.style.verticalAlign = "bottom";
			div.appendChild(img);
			if (edit) {
				var link = document.createElement("A");
				link.href = '#';
				link.className = "black_link";
				link.onclick = function() {
					edit_subject(subject, function() {
						var index = parent.children.indexOf(item);
						parent.removeItem(item);
						build_subject(parent, subject, index);
					}); 
					return false; 
				};
				link.appendChild(document.createTextNode(subject.code + " - " + subject.name));
				div.appendChild(link);
				img = document.createElement("IMG");
				img.src = theme.icons_10.remove;
				img.className = "button_verysoft";
				img.style.marginLeft = "2px";
				img.style.verticalAlign = "middle";
				div.appendChild(img);
				img.onclick = function() {
					alert('Not yet implemented');
					// TODO
				};
			} else
				div.appendChild(document.createTextNode(subject.code + " - " + subject.name));
			cells.push(new TreeCell(div));

			var h = document.createElement("SPAN");
			h.style.paddingLeft = "5px";
			if (subject.hours && subject.hours_type)
				h.appendChild(document.createTextNode(subject.hours+" "+subject.hours_type));
			cells.push(new TreeCell(h));

			var coef = document.createElement("DIV");
			coef.style.textAlign = "center";
			if (subject.coefficient)
				coef.appendChild(document.createTextNode(subject.coefficient));
			cells.push(new TreeCell(coef));

			var assigned = document.createElement("DIV");
			var list = [];
			for (var i = 0; i < teachers_assigned.length; ++i) {
				if (teachers_assigned[i].subject_id != subject.id) continue;
				var cl = null;
				for (var j = 0; j < batch.periods.length && cl == null; ++j)
					for (var k = 0; k < batch.periods[j].classes.length; ++k)
						if (batch.periods[j].classes[k].id == teachers_assigned[i].class_id) { cl = batch.periods[j].classes[k]; break; }
				var teacher = null;
				for (var j = 0; j < teachers.length; ++j)
					if (teachers[j].id == teachers_assigned[i].people_id) { teacher = teachers[j]; break; }
				var t = null;
				for (var j = 0; j < list.length; ++j)
					if (list[j].teacher.id == teacher.id) { t = list[j]; break; }
				if (t == null) {
					t = {teacher:teacher,classes:[]};
					list.push(t);
				}
				t.classes.push(cl);
			}
			for (var i = 0; i < list.length; ++i) {
				if (i > 0) assigned.appendChild(document.createTextNode(", "));
				var span_teacher = document.createElement("SPAN"); assigned.appendChild(span_teacher);
				span_teacher.appendChild(document.createTextNode(list[i].teacher.first_name+" "+list[i].teacher.last_name));
				if (edit) {
					var img = document.createElement("IMG");
					img.src = theme.icons_10.remove;
					img.className = "button_verysoft";
					img.style.padding = "1px";
					img.style.verticalAlign = "bottom";
					img.title = "Remove all assignments of "+list[i].teacher.first_name+" "+list[i].teacher.last_name;
					span_teacher.appendChild(img);
					img.teacher_id = list[i].teacher.id;
					img.subject_id = subject.id;
					img.onclick = function() {
						var lock = lock_screen(null, "Removing assignment...");
						var t=this;
						service.json("curriculum","unassign_teacher",{people_id:this.teacher_id,subject_id:this.subject_id},function(res){
							unlock_screen(lock);
							if (res) {
								for (var i = 0; i < teachers_assigned.length; ++i)
									if (teachers_assigned[i].people_id == t.teacher_id && teachers_assigned[i].subject_id == t.subject_id) {
										teachers_assigned.splice(i,1);
										i--;
									}
								var index = parent.children.indexOf(item);
								parent.removeItem(item);
								build_subject(parent, subject, index);
							}
						});
					};
				}
				span_teacher.appendChild(document.createTextNode(": Class "))
				for (var j = 0; j < list[i].classes.length; ++j) {
					if (j > 0) span_teacher.appendChild(document.createTextNode(","));
					var span_class = document.createElement("SPAN");
					span_teacher.appendChild(span_class);
					span_class.appendChild(document.createTextNode(list[i].classes[j].name));
					if (edit) {
						var img = document.createElement("IMG");
						img.src = theme.icons_10.remove;
						img.className = "button_verysoft";
						img.style.padding = "1px";
						img.style.verticalAlign = "bottom";
						img.title = "Remove assignment of "+list[i].teacher.first_name+" "+list[i].teacher.last_name+" on class "+list[i].classes[j].name;
						span_class.appendChild(img);
						img.teacher_id = list[i].teacher.id;
						img.class_id = list[i].classes[j].id;
						img.subject_id = subject.id;
						img.onclick = function() {
							var lock = lock_screen(null, "Removing assignment...");
							var t=this;
							service.json("curriculum","unassign_teacher",{people_id:this.teacher_id,class_id:this.class_id,subject_id:this.subject_id},function(res){
								unlock_screen(lock);
								if (res) {
									for (var i = 0; i < teachers_assigned.length; ++i)
										if (teachers_assigned[i].people_id == t.teacher_id && teachers_assigned[i].class_id == t.class_id && teachers_assigned[i].subject_id == t.subject_id) {
											teachers_assigned.splice(i,1);
											break;
										}
									var index = parent.children.indexOf(item);
									parent.removeItem(item);
									build_subject(parent, subject, index);
								}
							});
						};
					}
				}
			}
			if (edit) {
				var img = document.createElement("IMG");
				img.src = theme.icons_10.add;
				img.className = "button_verysoft";
				img.style.padding = "1px";
				img.style.verticalAlign = "bottom";
				img.title = "Assign teacher";
				assigned.appendChild(img);
				var assign = function(preselected_teacher) {
					require("context_menu.js");
					var table, tr, td;
					table = document.createElement("TABLE");
					table.style.borderSpacing = "0px";
					table.style.borderCollapse = "collapse";
					table.appendChild(tr = document.createElement("TR"));
					tr.appendChild(td = document.createElement("TH"));
					td.style.borderRight = "1px solid black";
					td.className = "context_menu_title";
					td.appendChild(document.createTextNode("Teacher"));
					tr.appendChild(td = document.createElement("TH"));
					td.className = "context_menu_title";
					td.appendChild(document.createTextNode("Classes"));
					
					table.appendChild(tr = document.createElement("TR"));
					tr.appendChild(td = document.createElement("TD"));
					td.style.borderRight = "1px solid black";
					td.style.verticalAlign = "top";
					var selected_teacher = preselected_teacher;
					for (var i = 0; i < teachers.length; ++i) {
						var radio = document.createElement("INPUT");
						radio.type = "radio";
						radio.name = "assign_teacher";
						if (preselected_teacher == teachers[i].id) radio.checked = "checked";
						td.appendChild(radio);
						td.appendChild(document.createTextNode(" "+teachers[i].first_name+" "+teachers[i].last_name));
						radio.teacher = teachers[i];
						radio.onchange = function() {
							if (this.checked) selected_teacher = this.teacher.id;
						};
						td.appendChild(document.createElement("BR"));
					}
					tr.appendChild(td = document.createElement("TD"));
					td.style.verticalAlign = "top";
					var selected_classes = [];
					var period = null;
					for (var i = 0; i < batch.periods.length; ++i)
						if (batch.periods[i].id == subject.period_id) { period = batch.periods[i]; break; }
					for (var i = 0; i < period.classes.length; ++i) {
						if (subject.specialization_id != period.classes[i].spe_id) continue;
						var cb = document.createElement("INPUT");
						cb.type = "checkbox";
						var found = false;
						for (var j = 0; j < teachers_assigned.length; ++j)
							if (teachers_assigned[j].subject_id == subject.id && teachers_assigned[j].class_id == period.classes[i].id) { found = true; break; }
						if (found) cb.disabled = "disabled";
						td.appendChild(cb);
						td.appendChild(document.createTextNode(" Class "+period.classes[i].name));
						td.appendChild(document.createElement("BR"));
						cb.class_id = period.classes[i].id;
						cb.onchange = function() {
							if (this.checked) selected_classes.push(this.class_id); else selected_classes.remove(this.class_id);
						};
					}
					table.appendChild(tr = document.createElement("TR"));
					tr.appendChild(td = document.createElement("TD"));
					tr.className = "popup_window_buttons";
					theme.css("popup_window.css");
					td.colSpan = 2;
					var button = document.createElement("BUTTON");
					button.appendChild(document.createTextNode("Assign"));
					td.appendChild(button);
					button.onclick = function() {
						this.menu.close();
						if (selected_teacher == null) { alert("Please select a teacher to assign"); return; }
						if (selected_classes.length == 0) { alert("Please select at least one class to assign the teacher"); return; }
						var lock = lock_screen(null, "Assigning teacher...");
						service.json("curriculum","assign_teacher",{people_id:selected_teacher,classes_ids:selected_classes,subject_id:subject.id},function(res){
							unlock_screen(lock);
							if (res) {
								for (var i = 0; i < selected_classes.length; ++i) {
									var ta = {people_id:selected_teacher,class_id:selected_classes[i],subject_id:subject.id};
									teachers_assigned.push(ta);
								}
								var index = parent.children.indexOf(item);
								parent.removeItem(item);
								build_subject(parent, subject, index);
							}
						});
					};
					require("context_menu.js",function() {
						var menu = new context_menu();
						button.menu = menu;
						menu.addItem(table, true);
						menu.showBelowElement(img);
					});
				};
				img.onclick = function() {
					assign();
				};
				
				assigned.ondragover = function(event) {
					if (event.dataTransfer.types.contains("teacher")) {
						event.dataTransfer.dropEffect = "copy";
						event.preventDefault();
						return false;
					}
				};
				assigned.ondragenter = function(event) {
					if (event.dataTransfer.types.contains("teacher")) {
						event.dataTransfer.dropEffect = "copy";
						event.preventDefault();
						return true;
					}
				};
				assigned.ondrop = function(event) {
					var teacher = event.dataTransfer.getData("teacher");
					assign(teacher);
					event.stopPropagation();
					return false;
				};
			}
			cells.push(new TreeCell(assigned));
			
			item = new TreeItem(cells);
			if (typeof index != 'undefined')
				parent.insertItem(item, index);
			else
				parent.addItem(item);
		}
		build_tree();

		function build_teacher_item(teacher) {
			var container = document.getElementById('teachers_container');
			var div = document.createElement("DIV");
			var link = document.createElement("A"); div.appendChild(link);
			link.href = "#";
			link.className = "black_link";
			link.people = teacher;
			link.onclick = function() {
				var people = this.people;
				window.top.require("popup_window.js",function(){
					var p = new window.top.popup_window("Teacher Profile","/static/curriculum/teacher_16.png","");
					p.setContentFrame("/dynamic/people/page/profile?people="+people.id);
					p.showPercent(95,95);
				});
				return false;
			};
			link.draggable = true;
			link.ondragstart = function(event) {
				event.dataTransfer.setData("teacher",this.people.id);
				event.dataTransfer.effectAllowed = "copy";
				return true;
			};
			link.appendChild(document.createTextNode(teacher.first_name+" "+teacher.last_name));
			container.appendChild(div);
		}
		function build_teachers_list() {
			for (var i = 0; i < teachers.length; ++i) {
				build_teacher_item(teachers[i]);
			}
		}
		build_teachers_list();

		function edit_subject(subject, onchanged) {
			require("popup_window.js",function() {
				var control = new EditSubjectControl(subject);
				var p = new popup_window("Edit Subject", "/static/curriculum/subject_16.png", control.element);
				p.addOkCancelButtons(function(){
					var code = control.input_code.value;
					var name = control.input_name.value;
					var hours = control.input_hours.value;
					var hours_type = control.select_hours_type.value;
					var coef = control.input_coef.value;
					if (code.length == 0) { alert('Please enter a code'); return; }
					if (name.length == 0) { alert('Please enter a name'); return; }
					if (hours.length == 0) { hours = null; hours_type = null; }
					else {
						hours = parseInt(hours);
						if (isNaN(hours)) { alert('Invalid number of hours'); return; }
						if (hours_type == "") hours_type = null;
					}
					if (coef.length == 0) coef = null;
					else {
						coef = parseInt(coef);
						if (isNaN(coef) || coef < 0 || coef > 50) { alert("Invalid coefficient: must be an integer between 0 and 50"); return; }
					}
					p.freeze("Saving subject");
					service.json("data_model","save_entity",{
						table: "CurriculumSubject",
						key: subject.id,
						lock: -1, // TODO
						field_code: code,
						field_name: name,
						field_hours: hours,
						field_hours_type: hours_type,
						field_coefficient: coef
					},function(res){
						if (!res) {
							p.unfreeze();
							return;
						}
						subject.code = code;
						subject.name = name;
						subject.hours = hours;
						subject.hours_type = hours_type;
						subject.coefficient = coef;
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
			this.input_code.size = "15";
			this.input_code.maxLength = 100;
			this.input_code.value = subject.code;
			td.appendChild(this.input_code);
			
			this.element.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TD"));
			td.innerHTML = "Name";
			tr.appendChild(td = document.createElement("TD"));
			this.input_name = document.createElement("INPUT");
			this.input_name.type = "text";
			this.input_name.size = "40";
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
			
			this.element.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TD"));
			td.innerHTML = "Coefficient";
			tr.appendChild(td = document.createElement("TD"));
			this.input_coef = document.createElement("INPUT");
			this.input_coef.type = "text";
			this.input_coef.size = "2";
			this.input_coef.maxLength = 2;
			if (subject.coefficient)
				this.input_coef.value = subject.coefficient;
			td.appendChild(this.input_coef);
		}

		function new_subject(category, batch, period, spe, parent_item) {
			require("popup_window.js");
			service.json("curriculum", "get_subjects", {category:category.id,specialization:spe ? spe.id : null,period_to_exclude:period.id}, function(list) {
				// remove the ones which are already in the curriculum
				for (var i = 0; i < list.length; ++i) {
					var found = false;
					for (var j = 0; j < batch.periods.length && !found; ++j) {
						for (var k = 0; k < subjects.length; ++k) {
							if (subjects[k].code != list[i].code) continue;
							if (subjects[k].name != list[i].name) continue;
							if (subjects[k].period_id != batch.periods[j].id) continue;
							found = true;
							break;
						}
					}
					if (found) {
						list.splice(i,1);
						i--;
					}
				}
				
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
					td = document.createElement("TH"); tr.appendChild(td);
					td.innerHTML = "Coef.";
					
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
						td = document.createElement("TD"); tr.appendChild(td);
						td.style.textAlign = "center";
						if (list[i].coefficient) td.appendChild(document.createTextNode(list[i].coefficient));
					}

					var create = function(code,name,hours,hours_type,coef) {
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
							field_hours_type: hours_type,
							field_coefficient: coef
						},function(res){
							unlock_screen(lock);
							if (res && res.key) {
								var s = new CurriculumSubject(res.key, code, name, category.id, period.id, spe ? spe.id : null, hours, hours_type, coef);
								subjects.push(s);
								build_subject(parent_item, s);
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
						var coef = control.input_coef.value;
						if (code.length == 0) { alert('Please enter a code'); return; }
						if (name.length == 0) { alert('Please enter a name'); return; }
						if (hours.length == 0) { hours = null; hours_type = null; }
						else {
							hours = parseInt(hours);
							if (isNaN(hours)) { alert('Invalid number of hours'); return; }
							if (hours_type == "") hours_type = null;
						}
						if (coef.length == 0) coef = null;
						else {
							coef = parseInt(coef);
							if (isNaN(coef) || coef < 0 || coef > 50) { alert("Invalid coefficient: must be an integer between 0 and 50"); return; }
						}
						create(code,name,hours,hours_type,coef);
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
								create(list[i].code, list[i].name, list[i].hours, list[i].hours_type, list[i].coefficient);
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

		function new_teacher() {
			var w=window;
			window.top.require("popup_window.js", function() {
				var p = new window.top.popup_window("New Teacher", theme.build_icon("/static/curriculum/teacher_16.png",theme.icons_10.add), "");
				var frame = p.setContentFrame("/dynamic/curriculum/page/popup_create_teacher?start=<?php echo urlencode($start_date);?>&end=<?php echo urlencode($end_date);?>&ondone=reload");
				frame.reload = function() {
					w.location.reload();
				};
				p.show();
			});
		}
		
		</script>
		<?php 
	}
	
}
?>