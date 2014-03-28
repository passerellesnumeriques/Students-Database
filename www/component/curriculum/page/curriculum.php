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
			->where("start <= '".$end_date."' AND (end IS NULL OR end > '".$start_date."'")
			->execute();
		$teachers_ids = array();
		foreach ($teachers_dates as $t)
			if (!in_array($t["people"], $teachers_ids))
				array_push($teachers_ids, $t["people"]);
		if (count($teachers_ids) > 0) {
			$teachers = PNApplication::$instance->people->getPeoples($teachers_ids);
		} else
			$teachers = array();
		// TODO continue for teachers
		
		require_once("component/curriculum/CurriculumJSON.inc");
		
		$this->add_javascript("/static/widgets/tree/tree.js");
		$this->add_javascript("/static/widgets/header_bar.js");
		theme::css($this, "header_bar.css");
		$this->onload("new header_bar('page_header','toolbar');");
		$this->add_javascript("/static/widgets/vertical_layout.js");
		$this->onload("new vertical_layout('page_container');");
		$this->require_javascript("section.js");
		$this->onload("section_from_html('teachers_container');");
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
			<div id="teachers_container" style="display:inline-block;vertical-align:top;margin:5px"
				icon="/static/curriculum/teacher_16.png"
				title="Teachers List"
				collapsable="false"
			>
				TODO: list of teachers
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

		var t = new tree('curriculum_tree');
		t.addColumn(new TreeColumn("Subject Code - Name"));
		t.addColumn(new TreeColumn("Hours"));
		t.addColumn(new TreeColumn("Coef."));
		t.setShowColumn(true);

		function build_tree() {
			for (var i = 0; i < batch.periods.length; ++i) {
				<?php if ($period_id <> null) echo "if (batch.periods[i].id != ".$period_id.") continue;"?>
				build_period(batch.periods[i]);
			}
		}
		function build_period(period) {
			var item = createTreeItemSingleCell("/static/calendar/calendar_24.png", "Period "+period.name, true);
			item.cells[0].addStyle({fontSize:"12pt",fontWeight:"bold",color:"#404080"});
			t.addItem(item);
			if (period.available_specializations.length > 0) {
				for (var i = 0; i < period.available_specializations.length; ++i) {
					var spe = null;
					for (var j = 0; j < specializations.length; ++j)
						if (specializations[j].id == period.available_specializations[i]) { spe = specializations[j]; break; }
					build_specialization(item, spe, period);
				}
			} else {
				build_categories(item, period, null);
			}
		}
		function build_specialization(parent, spe, period) {
			var item = createTreeItemSingleCell("/static/curriculum/curriculum_16.png", "Specialization "+spe.name, true);
			parent.addItem(item);
			build_categories(item, period, spe);
		}
		function build_categories(parent, period, spe) {
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
					img.title = "Add a subject to category "+categories[i].name;
					img.onclick = function() {
						new_subject(this.cat, period, spe);
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
			
			item = new TreeItem(cells);
			if (typeof index != 'undefined')
				parent.insertItem(item, index);
			else
				parent.addItem(item);
		}
		build_tree();

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

		function new_subject(category, period, spe) {
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
							if (res && res.key)
								location.reload();
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
		
		</script>
		<?php 
	}
	
}
?>