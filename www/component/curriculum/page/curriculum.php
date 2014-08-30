<?php 
class page_curriculum extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		if (!PNApplication::$instance->user_management->has_right("consult_curriculum")) {
			if (!in_array("student",PNApplication::$instance->user_management->people_types)) {
				PNApplication::error("Access denied");
				return;
			}
			$student = PNApplication::$instance->students->getStudent(PNApplication::$instance->user_management->people_id);
			$batch_id = $student["batch"];
			$period_id = null;
			$periods = PNApplication::$instance->curriculum->getBatchPeriodsWithAcademicPeriods($batch_id);
			$batch_info = PNApplication::$instance->curriculum->getBatch($batch_id);
			$start_date = $batch_info["start_date"];
			$end_date = $batch_info["end_date"];
		} else {
			if (!isset($_GET["batch"])) {
				echo "<img src='".theme::$icons_16["info"]."'/> ";
				echo "Please select a batch, an academic period, or a class, to display its curriculum";
				return;
			}
			if (isset($_GET["period"])) {
				$period_id = $_GET["period"];
				$single_period = PNApplication::$instance->curriculum->getAcademicPeriodAndBatchPeriod($period_id);
				$batch_id = $single_period["batch"];
				$start_date = $single_period["academic_period_start"];
				$end_date = $single_period["academic_period_end"];
				$batch_info = PNApplication::$instance->curriculum->getBatch($batch_id);
				$periods = array($single_period);
			} else {
				$batch_id = $_GET["batch"];
				$period_id = null;
				$periods = PNApplication::$instance->curriculum->getBatchPeriodsWithAcademicPeriods($batch_id);
				$batch_info = PNApplication::$instance->curriculum->getBatch($batch_id);
				$start_date = $batch_info["start_date"];
				$end_date = $batch_info["end_date"];
			}
		}
		
		$periods_ids = array();
		foreach ($periods as $period) array_push($periods_ids, $period["id"]);
		$periods_spes = PNApplication::$instance->curriculum->getBatchPeriodsSpecializationsWithName($periods_ids);
		
		$categories = PNApplication::$instance->curriculum->getSubjectCategories();
		$subjects = PNApplication::$instance->curriculum->getSubjects($batch_id, $period_id);
		$specializations = PNApplication::$instance->curriculum->getSpecializations();
		
		$can_edit = PNApplication::$instance->user_management->has_right("edit_curriculum");
		
		$editing = false;
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
					return;
				}
				$lock_categories = DataBaseLock::lockTable("CurriculumSubjectCategory", $locked_by);
				if ($lock_categories == null) {
					?>
					<script type='text/javascript'>
					var u=new window.URL(location.href);
					u.params.edit = 0;
					u.params.locker = <?php echo json_encode($locked_by);?>;
					location.href=u.toString();
					</script>
					<?php 
				}
				DataBaseLock::generateScript($lock);
				$editing = true;
				require_once("component/data_model/page/utils.inc");
			}
		}
		
		require_once("component/curriculum/CurriculumJSON.inc");
		if ($editing) {
			$this->requireJavascript("curriculum_objects.js");
			$this->requireJavascript("animation.js");
		}

		?>
		<style type='text/css'>
		#curriculum_table {
			border-collapse: collapse;
		}
		#curriculum_table>tbody>tr.period_title>td {
			border: none;
		}
		#curriculum_table>tbody>tr>th {
			padding: 1px 3px 1px 3px;
			vertical-align: bottom;
			white-space: nowrap;
		}
		#curriculum_table>tbody>tr>td {
			white-space: nowrap;
		}
		#curriculum_table>tbody>tr>td>img {
			vertical-align: bottom;
		}
		#curriculum_table>tbody>tr>td:first-child, #curriculum_table>tbody>tr>th:first-child {
			padding-left: 10px;
		}
		.category_title {
			color: #602000;
			font-weight: bold;
		}
		.specialization_title {
			color: #006000;
			font-weight: bold;
		}
		.subject_row>td:nth-child(2),.subject_row>td:nth-child(3),tr.total_period>td:nth-child(2),tr.total_period>td:nth-child(3) {
			text-align: right;
		}
		.subject_row>td:nth-child(4),tr.total_period>td:nth-child(4) {
			text-align: center;
		}
		<?php if ($editing) { ?>
		.subject_row>td:first-child:hover {
			text-decoration: underline;
			cursor: pointer;
		}
		<?php } ?>
		tr.total_period>td {
			border-top: 1px solid #8080C0;
			font-weight: bold;
		}
		tr.total_period>td:first-child {
			text-align: right;
		}
		</style>
		<div style="width:100%;height:100%;background-color:white;display:flex;flex-direction:column;">
			<div class="page_title" style="flex:none">
				<div style='float:right'>
					<?php 
					PNApplication::$instance->documents->insertDivForAttachedDocuments($this, "StudentBatch",null,$batch_id,"curriculum","medium","overview");
					?>
				</div>
				<img src='/static/curriculum/curriculum_32.png'/>
				Curriculum for Batch <span id='batch_name'><?php echo toHTML($batch_info["name"]);?></span><?php if ($period_id <> null) echo ", <span id='period_name'>".$single_period["name"]."</span>";?>
				<?php if ($can_edit) {
					if ($editing)
						echo "<button class='action' onclick=\"window.onuserinactive();\"><img src='".theme::$icons_16["no_edit"]."'/> Stop editing</button>";
					else {
						if (isset($_GET["locker"])) {
						?>
						<div style='font-size:10pt'>
							<img src='<?php echo theme::$icons_16["error"];?>'/> <?php echo $_GET["locker"];?> is already editing a batch, you cannot edit it.
						</div>
						<?php 
						} else
							echo "<button class='action' onclick=\"var u=new window.URL(location.href);u.params.edit = 1;location.href=u.toString();\"><img src='".theme::$icons_16["edit"]."'/> Edit</button>";
					}
				}
				?>
			</div>
			<div id="page_container" style="overflow:auto;flex:1 1 auto;">
				<table id='curriculum_table'><tbody>
				<?php 
				$script_init = "";
				foreach ($periods as &$period) {
					if ($period_id == null) {
						// several periods => add period title
						echo "<tr class='period_title'>";
						echo "<td colspan=3 class='page_section_title'>";
						echo "<img src='/static/calendar/calendar_24.png'/> ";
						$id = $this->generateID();
						echo "<span id='$id' style='margin-right:10px'>";
						echo toHTML($period["name"]);
						echo "</span>";
						$this->onload("window.top.datamodel.registerCellSpan(window,'BatchPeriod','name',".$period["id"].",document.getElementById('$id'));");
						if ($editing) {
							echo "<button class='action' onclick='import_subjects(".$period['id'].")'><img src='".theme::$icons_16["_import"]."'/> Import subjects from other batches</button>";
						}
						echo "</td>";
						echo "</tr>";
					}

					$spes = array();
					foreach ($periods_spes as $ps)
						if ($ps["period"] == $period["id"])
							array_push($spes, $ps);
					if (count($spes) == 0) array_push($spes, null);
					
					// title row
					echo "<tr>";
					echo "<th>Subject Code - Name</th>";
					echo "<th>Hrs/week</th>";
					echo "<th>Hrs total</th>";
					echo "<th>Coef.</th>";
					echo "</tr>";
						
					// Period content
					foreach ($spes as $spe) {
						if ($spe <> null) {
							// Specialization
							echo "<tr>";
							echo "<td colspan=4 class='specialization_title'>";
							echo "<img src='/static/curriculum/curriculum_16.png'/> ";
							$id = $this->generateID();
							echo "<span id='$id'>";
							echo toHTML($spe["name"]);
							echo "</span>";
							$this->onload("window.top.datamodel.registerCellSpan(window,'Specialization','name',".$spe["id"].",document.getElementById('$id'));");
							echo "</td>";
							echo "</tr>";
							$indent = 1;
						} else {
							$indent = 0;
						}
						foreach ($categories as $cat) {
							// Category
							$cat_subjects = array();
							foreach ($subjects as $s)
								if ($s["period"] == $period["id"] && $s["category"] == $cat["id"] && ($s["specialization"] == null || ($spe <> null && $s["specialization"] == $spe["id"])))
									array_push($cat_subjects, $s);
							if (count($cat_subjects) == 0 && !$editing) continue;
							if (count($cat_subjects) == 0 && $cat["obsolete"] <> null && strtotime($cat["obsolete"]) < strtotime($period["academic_period_start"])) continue;
							$cat_id = 'cat_'.$cat["id"].'_period_'.$period['id'];
							if ($spe <> null) $cat_id .= '_spe_'.$spe["id"];
							echo "<tr id='$cat_id'>";
							echo "<td colspan=4 class='category_title' style='padding-left:".(10+$indent*20)."px'>";
							echo "<img src='/static/curriculum/subjects_16.png'/> ";
							$id = $this->generateID();
							echo "<span id='$id'>";
							echo toHTML($cat["name"]);
							echo "</span>";
							$this->onload("window.top.datamodel.registerCellSpan(window,'CurriculumSubjectCategory','name',".$cat["id"].",document.getElementById('$id'));");
							if ($editing) {
								echo " <button class='flat small_icon' title='Add a subject in this category' onclick='new_subject(".$period["id"].",".$cat["id"].",".($spe <> null ? $spe["id"] : "null").",this.parentNode.parentNode);'><img src='".theme::$icons_10["add"]."'/></button>";
							}
							echo "</td>";
							echo "</tr>";
							foreach ($cat_subjects as $s) {
								// Subject
								$script_init .= "addSubjectRow(document.getElementById('$cat_id'),".CurriculumJSON::SubjectJSON($s).");\n";
							}
						}
						echo "<tr id='total_".$period['id']."' class='total_period'>";
						echo "<td>TOTAL</td>";
						echo "<td></td><td></td><td></td>";
						echo "</tr>";
					}
				}
				?>
				</tbody></table>
			</div>
			<?php if ($can_edit) { ?>
			<div class="page_footer" style="flex:none;">
				<button class='action' onclick='editBatch()'>
					<img src='/static/curriculum/batch_16.png'/>
					Edit batch: periods and specializations
				</button>
				<?php if ($editing) { ?>
				<button class='action' onclick="edit_categories();">
					<img src='/static/curriculum/subjects_16.png'/>
					Edit subject categories
				</button>
				<?php if ($period_id <> null) {
					echo "<button class='action' onclick='import_subjects(".$period_id.")'><img src='".theme::$icons_16["_import"]."'/> Import subjects from other batches</button>";
				}?>
				<?php } ?>
			<?php } ?>
			</div>
		</div>
		<script type='text/javascript'>
		window.top.datamodel.registerCellSpan(window, "StudentBatch", "name", <?php echo $batch_id;?>, document.getElementById("batch_name"));
		<?php
		if ($period_id <> null)
			echo "window.top.datamodel.registerCellSpan(window, 'BatchPeriod', 'name', ".$period_id.", document.getElementById('period_name'));"; 
		?> 
		function editBatch() {
			require("popup_window.js",function(){
				var popup = new popup_window("Edit Batch", theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.edit), "");
				popup.setContentFrame("/dynamic/curriculum/page/edit_batch?popup=yes&id=<?php echo $batch_id;?>&onsave=batchSaved");
				popup.show();
			});
		}
		function batchSaved(id) {
			if (window.parent.batchSaved) window.parent.batchSaved(id);
			location.reload();
		}
		var edit = <?php echo $editing ? "true" : "false"; ?>;

		var periods = [<?php
		$first = true;
		foreach ($periods as &$period) {
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "id:".$period["id"];
			echo ",academic_period:".$period["academic_period"];
			echo ",weeks:".$period["academic_period_weeks"];
			echo ",weeks_break:".$period["academic_period_weeks_break"];
			echo ",specializations:[";
			$first_spe = true;
			foreach ($periods_spes as $ps)
				if ($ps["period"] == $period["id"]) {
					if ($first_spe) $first_spe = false; else echo ",";
					echo $ps["id"];
				}
			echo "]";
			echo "}";
		}
		?>];
		var subjects = <?php echo CurriculumJSON::SubjectsJSON($subjects);?>;
		var specializations = <?php echo json_encode($specializations);?>;

		function getPeriodSpecializations(period_id) {
			for (var i = 0; i < periods.length; ++i)
				if (periods[i].id == period_id) return periods[i].specializations;
			return [];
		}
		function getSpecializationName(id) {
			for (var i = 0; i < specializations.length; ++i)
				if (specializations[i].id == id) return specializations[i].name;
			return null;
		}

		function hoursFloat(s) {
			s = s.toFixed(2);
			if (s.substr(s.length-3) == ".00") return s.substr(0,s.length-3);
			if (s.substr(s.length-1) == "0") return s.substr(0,s.length-1);
			return s;
		}

		function refreshTotal(period) {
			var tr = document.getElementById("total_"+period.id);
			var total_hours_period = 0;
			var total_hours_week = 0;
			var total_coef = 0;
			for (var i = 0; i < subjects.length; ++i) {
				if (subjects[i].period_id != period.id) continue;
				if (subjects[i].coefficient) total_coef += parseInt(subjects[i].coefficient);
				if (!subjects[i].hours) continue;
				var hw=0,ht=0;
				switch (subjects[i].hours_type) {
				case "Per week": hw = parseInt(subjects[i].hours); ht = parseInt(subjects[i].hours)*(period.weeks-period.weeks_break); break;
				case "Per period": ht = parseInt(subjects[i].hours); hw = parseInt(subjects[i].hours)/(period.weeks-period.weeks_break); break;
				}
				total_hours_period += ht;
				total_hours_week += hw;
			}
			tr.childNodes[1].innerHTML = total_hours_week+"h";
			tr.childNodes[2].innerHTML = total_hours_period+"h";
			tr.childNodes[3].innerHTML = total_coef;
		}
		
		function addSubjectRow(cat_row, subject) {
			var tr = createSubjectRow(cat_row, subject);
			var next_tr = cat_row.nextSibling;
			while (next_tr && next_tr.className == "subject_row") next_tr = next_tr.nextSibling;
			if (next_tr)
				cat_row.parentNode.insertBefore(tr, next_tr);
			else
				cat_row.parentNode.appendChild(tr);
		}
		function createSubjectRow(cat_row, subject) {
			var tr = document.createElement("TR");
			tr.className = "subject_row";
			var td;

			tr.appendChild(td = document.createElement("TD"));
			td.style.paddingLeft = (parseInt(cat_row.childNodes[0].style.paddingLeft)+20)+"px";
			if (edit) {
				td.title = "Click to edit the subject";
				td.onclick = function() { edit_subject(subject, tr, cat_row); };
			}
			td.innerHTML = "<img src='/static/curriculum/subject_16.png'/> ";
			td.appendChild(document.createTextNode(subject.code+" - "+subject.name));
			if (edit) {
				var button = document.createElement("BUTTON");
				button.className = "flat small_icon";
				button.innerHTML = "<img src='"+theme.icons_10.remove+"'/>";
				button.title = "Remove this subject";
				button.onclick = function(event) { remove_subject(subject,tr); stopEventPropagation(event); return false; };
				animation.appearsOnOver(td,button);
				td.appendChild(button);
			}

			var period;
			for (var i = 0; i < periods.length; ++i) if (periods[i].id == subject.period_id) { period = periods[i]; break; }
			
			var hw,ht;
			if (!subject.hours) {
				hw = ht = "";
			} else {
				switch (subject.hours_type) {
				case "Per week": hw = subject.hours; ht = subject.hours*(period.weeks-period.weeks_break); break;
				case "Per period": ht = subject.hours; hw = hoursFloat(subject.hours/(period.weeks-period.weeks_break)); break;
				}
			}
			tr.appendChild(td = document.createElement("TD"));
			td.innerHTML = hw+"h";
			tr.appendChild(td = document.createElement("TD"));
			td.innerHTML = ht+"h";

			tr.appendChild(td = document.createElement("TD"));
			td.innerHTML = subject.coefficient;

			refreshTotal(period);

			return tr;
		}

		<?php echo $script_init;?>
		for (var i = 0; i < periods.length; ++i) refreshTotal(periods[i]);
		
		<?php if ($editing) { ?>
		window.onuserinactive = function() {
			var u=new window.URL(location.href);
			u.params.edit = 0;
			location.href=u.toString();
		};

		var categories = <?php echo CurriculumJSON::SubjectCategoriesJSON($categories);?>;
		function edit_categories() {
			require(["popup_window.js","editable_cell.js","animation.js","curriculum_objects.js"],function() {
				var content = document.createElement("TABLE");
				var popup = new popup_window("Edit Subject Categories", theme.build_icon("/static/curriculum/subjects_16.png",theme.icons_10.edit), content);
				content.style.padding = "10px";
				var remove_category = function(button) {
					window.top.datamodel.confirm_remove("CurriculumSubjectCategory",button.cat.id,function(){
						var td = button.parentNode;
						var tr = td.parentNode;
						tr.parentNode.removeChild(tr);
					});
				};
				var tr,td;
				for (var i = 0; i < categories.length; ++i) {
					var cat = categories[i];
					content.appendChild(tr = document.createElement("TR"));
					tr.appendChild(td = document.createElement("TD"));
					var cell;
					<?php
					datamodel_cell_inline($this, "cell", "td", true, "CurriculumSubjectCategory", "name", "cat.id", null, "cat.name");
					?>
					cell.fillContainer();
					tr.appendChild(td = document.createElement("TD"));
					var cb = document.createElement("INPUT");
					cb.type = "checkbox";
					td.appendChild(cb);
					td.appendChild(document.createTextNode(" obsolete since "));
					var span_date = document.createElement("SPAN");
					td.appendChild(span_date);
					if (cat.obsolete) {
						cb.checked = "checked";
						window.top.datamodel.create_cell(window, "CurriculumSubjectCategory", null, "obsolete", cat.id, cat.obsolete, "field_date", "{can_be_null:false}", true, span_date);
					}
					cb.cat = cat;
					cb.span_date = span_date;
					cb.onchange = function() {
						var cat = this.cat;
						var cb = this;
						var span_date = this.span_date;
						if (this.checked) {
							require(["date_picker.js","context_menu.js"],function(){
								var menu = new context_menu();
								new date_picker(null,null,null,function(picker){
									var date = picker.getDate();
									picker.onchange = function(picker, d) {
										date = d;
									};
									picker.getElement().style.border = 'none';
									menu.addItem(picker.getElement());
									picker.getElement().onclick = null;
									menu.element.className = menu.element.className+" popup_date_picker";
									menu.showBelowElement(cb);
									menu.onclose = function() {
										confirm_dialog("Are you sure you want to make category <i>"+cat.name+"</i> obsolete starting on "+dateToSQL(date)+" ?",function(yes) {
											if (!yes) {
												cb.checked = "";
												return;
											}
											popup.freeze();
											var d = dateToSQL(date);
											service.json("data_model","save_entity",{table:"CurriculumSubjectCategory",sub_model:null,key:cat.id,lock:-1,field_obsolete:d},function(res){
												popup.unfreeze();
												if (!res) {
													cb.checked = "";
													return;
												}
												cat.obsolete = d;
												window.top.datamodel.create_cell(window, "CurriculumSubjectCategory", null, "obsolete", cat.id, cat.obsolete, "field_date", "{can_be_null:false}", true, span_date);
											});
										});
									};
								});
							});
						} else {
							confirm_dialog("Are you sure you want to put back category <i>"+cat.name+"</i> as actual (not obsolete anymore) ?",function(yes) {
								if (!yes) {
									cb.checked = "checked";
									return;
								}
								popup.freeze();
								service.json("data_model","save_entity",{table:"CurriculumSubjectCategory",sub_model:null,key:cat.id,lock:-1,field_obsolete:null},function(res){
									popup.unfreeze();
									if (!res) {
										cb.checked = "checked";
										return;
									}
									cat.obsolete = null;
									span_date.removeAllChildren();
								});
							});
						}
					};
					tr.appendChild(td = document.createElement("TD"));
					var button = document.createElement("BUTTON");
					button.innerHTML = "<img src='"+theme.icons_16.remove+"'/>";
					button.className = "flat small";
					button.cat = cat;
					animation.appearsOnOver(tr,button);
					td.appendChild(button);
					button.onclick = function() { remove_category(this); };
				}
				popup.addIconTextButton(theme.build_icon("/static/curriculum/subjects_16.png",theme.icons_10.add), "New Category...", 'new_cat', function() {
					input_dialog(theme.build_icon("/static/curriculum/subjects_16.png",theme.icons_10.add),"New Category","Name of the new category","",100,
						function(name){
							name = name.trim();
							if (!name.checkVisible()) return "Please enter a name";
							for (var i = 0; i < categories.length; ++i)
								if (name.toLowerCase() == categories[i].name.toLowerCase())
									return "A category already exists with this name";
							return null;
						},function(name){
							if (!name) return;
							name = name.trim();
							popup.freeze("Creation of category "+name+"...");
							service.json("data_model","save_entity",{
								table: "CurriculumSubjectCategory",
								lock: <?php echo $lock_categories;?>,
								field_name: name
							}, function(res){
								popup.unfreeze();
								if (!res || !res.key) return;
								content.appendChild(tr = document.createElement("TR"));
								tr.appendChild(td = document.createElement("TD"));
								var cell;
								<?php
								datamodel_cell_inline($this, "cell", "td", true, "CurriculumSubjectCategory", "name", "res.key", null, "name");
								?>
								cell.fillContainer();
								tr.appendChild(td = document.createElement("TD"));
								var button = document.createElement("BUTTON");
								button.innerHTML = "<img src='"+theme.icons_16.remove+"'/>";
								button.className = "flat small";
								button.cat = new CurriculumSubjectCategory(res.key,name);
								animation.appearsOnOver(tr,button);
								td.appendChild(button);
								button.onclick = function() { remove_category(this); };
								layout.invalidate(content);
							});
						}
					);
				});
				popup.addCloseButton();
				popup.onclose = function() { location.reload(); };
				popup.show();
			});
		}

		function new_subject(period_id, category_id, spe_id, cat_row) {
			require(["popup_window.js","edit_curriculum_subject.js","curriculum_objects.js"],function() {
				var content = document.createElement("DIV");
				var popup = new popup_window("New Subject",theme.build_icon("/static/curriculum/subject_16.png",theme.icons_10.add),content);
				popup.addCreateButton(function() {
					var subject = control.validate();
					popup.freeze("Creating new subject...");
					service.json("data_model","save_entity",{
						table: "CurriculumSubject",
						field_period: period_id,
						field_category: category_id,
						field_specialization: subject.specialization_id,
						field_code: subject.code,
						field_name: subject.name,
						field_hours: subject.hours,
						field_hours_type: subject.hours_type,
						field_coefficient: subject.coefficient
					},function(res){
						popup.unfreeze();
						if (res && res.key) {
							subject.id = res.key;
							popup.close();
							if (subject.specialization_id == null) {
								var spes = getPeriodSpecializations(subject.period_id);
								if (spes.length > 0) {
									location.reload();
									return;
								}
							}
							subjects.push(subject);
							addSubjectRow(cat_row,subject);
						}
					});
				});
				popup.addCancelButton();

				var existing_subjects = [];
				for (var i = 0; i < subjects.length; ++i) {
					var s = subjects[i];
					if (s.period_id != period_id) continue;
					existing_subjects.push(s);
				}
				
				var subject = new CurriculumSubject(-1, "", "", category_id, period_id, spe_id, 0, "Per week", 1);
				var control = new edit_curriculum_subject(subject, existing_subjects, function(ok) {
					if (ok) popup.enableButton('create');
					else popup.disableButton('create');
				});
				content.appendChild(control.element);

				popup.show();
			});
		}
		function edit_subject(subject, row, cat_row) {
			require(["popup_window.js","edit_curriculum_subject.js","curriculum_objects.js"],function() {
				var content = document.createElement("DIV");
				var popup = new popup_window("New Subject",theme.build_icon("/static/curriculum/subject_16.png",theme.icons_10.add),content);
				popup.addOkButton(function() {
					var ns = control.validate();
					popup.freeze("Saving subject...");
					service.json("data_model","save_entity",{
						table: "CurriculumSubject",
						key: subject.id,
						lock: -1,
						field_code: ns.code,
						field_name: ns.name,
						field_hours: ns.hours,
						field_hours_type: ns.hours_type,
						field_coefficient: ns.coefficient,
						field_specialization: ns.specialization_id
					},function(res){
						popup.unfreeze();
						if (res && res.key) {
							popup.close();
							for (var i = 0; i < subjects.length; ++i) if (subjects[i].id == subject.id) { subject = subjects[i]; break; }
							subject.code = ns.code;
							subject.name = ns.name;
							subject.hours = ns.hours;
							subject.hours_type = ns.hours_type;
							subject.coefficient = ns.coefficient; 
							if (subject.specialization_id == ns.specialization_id) {
								var new_row = createSubjectRow(cat_row, subject);
								row.parentNode.insertBefore(new_row, row);
								row.parentNode.removeChild(row);
							} else {
								location.reload();
							}
						}
					});
				});
				popup.addCancelButton();

				var existing_subjects = [];
				for (var i = 0; i < subjects.length; ++i) {
					var s = subjects[i];
					if (s.period_id != subject.period_id) continue;
					existing_subjects.push(s);
				}
				
				var control = new edit_curriculum_subject(subject, existing_subjects, function(ok) {
					if (ok) popup.enableButton('ok');
					else popup.disableButton('ok');
				});
				content.appendChild(control.element);

				popup.show();
			});
		}

		function remove_subject(subject,row) {
			window.top.datamodel.confirm_remove("CurriculumSubject",subject.id,function() {
				row.parentNode.removeChild(row);
				for (var i = 0; i < subjects.length; ++i)
					if (subjects[i].id == subject.id) { subjects.splice(i,1); break; }
				var period;
				for (var i = 0; i < periods.length; ++i) if (periods[i].id == subject.period_id) { period = periods[i]; break; }
				refreshTotal(period);
			});
		}

		function import_subjects(target_period) {
			popup_frame(theme.icons_16._import, "Import Subjects", "/dynamic/curriculum/page/import_subjects?period="+target_period+"&onimport=reload", null, null, null, function(frame,popup) {
				frame.reload = function() {
					window.location.reload();
				};
			});
		}

		<?php } ?>// if editing
		</script>
		<?php 
	}
}
?>