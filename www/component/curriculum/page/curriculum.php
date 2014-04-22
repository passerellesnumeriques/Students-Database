<?php 
class page_curriculum extends Page {
	
	public function get_required_rights() { return array("consult_curriculum"); }
	
	public function execute() {
		if (!isset($_GET["batch"])) {
			echo "<img src='".theme::$icons_16["info"]."'/> ";
			echo "Please select a batch, an academic period, or a class, to display its curriculum";
			return;
		}
		if (isset($_GET["period"])) {
			$period_id = $_GET["period"];
			$single_period = PNApplication::$instance->curriculum->getAcademicPeriodAndBatchPeriod($period_id);
			$batch_id = $single_period["batch"];
			$start_date = $single_period["start"];
			$end_date = $single_period["end"];
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
		
		$periods_ids = array();
		foreach ($periods as $period) array_push($periods_ids, $period["id"]);
		$periods_spes = PNApplication::$instance->curriculum->getBatchPeriodsSpecializationsWithName($periods_ids);
		
		$categories = PNApplication::$instance->curriculum->getSubjectCategories();
		$subjects = PNApplication::$instance->curriculum->getSubjects($batch_id, $period_id);
		$classes = PNApplication::$instance->curriculum->getAcademicClasses($batch_id, $period_id);
		$subjects_ids = array();
		foreach ($subjects as $s) array_push($subjects_ids, $s["id"]);
		$teachers_assigned = PNApplication::$instance->curriculum->getTeachersAssigned($subjects_ids);
		$teachers_ids = array();
		foreach ($teachers_assigned as $a) if (!in_array($a["people"], $teachers_ids)) array_push($teachers_ids, $a["people"]);
		
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
			$this->require_javascript("curriculum_objects.js");
			$this->require_javascript("animation.js");
			$this->require_javascript("section.js");
			theme::css($this, "section.css");
			$teachers_dates = SQLQuery::create()
				->select("TeacherDates")
				->where("`TeacherDates`.`start` <= '".$end_date."' AND (`TeacherDates`.`end` IS NULL OR `TeacherDates`.`end` > '".$start_date."')")
				->execute();
			foreach ($teachers_dates as $td) if (!in_array($td["people"], $teachers_ids)) array_push($teachers_ids, $td["people"]);
			
			$academic_periods_ids = array();
			foreach ($periods as $period) if (!in_array($period["academic_period"], $academic_periods_ids)) array_push($academic_periods_ids, $period["academic_period"]);
			$all_parallel_periods = PNApplication::$instance->curriculum->getBatchPeriodsForAcademicPeriods($academic_periods_ids);
			$all_periods_ids = array();
			foreach ($all_parallel_periods as $p) array_push($all_periods_ids, $p["id"]);
			$full_teachers_assign = PNApplication::$instance->curriculum->getTeacherAssignedForBatchPeriods($all_periods_ids, true);
			foreach ($full_teachers_assign as &$a) {
				foreach ($all_parallel_periods as $p)
					if ($p["id"] == $a["batch_period_id"]) {
						$a["academic_period_id"] = $p["academic_period"];
						break;
					}
			}
		}

		if (count($teachers_ids) > 0) {
			$q_teachers = PNApplication::$instance->people->getPeoplesSQLQuery($teachers_ids, false);
			require_once("component/people/PeopleJSON.inc");
			PeopleJSON::PeopleSQL($q_teachers);
			$teachers = $q_teachers->execute();
		} else {
			$teachers = array();
		}
		
		$this->add_javascript("/static/widgets/vertical_layout.js");
		$this->onload("new vertical_layout('top_container');");
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
		.subject_row>td:nth-child(2),.subject_row>td:nth-child(3) {
			text-align: right;
		}
		.subject_row>td:nth-child(4) {
			text-align: center;
		}
		<?php if ($editing) { ?>
		.subject_row>td:first-child:hover {
			text-decoration: underline;
			cursor: pointer;
		}
		<?php } ?>
		</style>
		<div id="top_container" style="width:100%;height:100%;background-color:white">
			<div class="page_title">
				<img src='/static/curriculum/curriculum_32.png'/>
				Curriculum for Batch <span id='batch_name'><?php echo htmlentities($batch_info["name"]);?></span><?php if ($period_id <> null) echo ", <span id='period_name'>".$single_period["name"]."</span>";?>
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
			<?php if ($editing) {?>
			<div class='info_header'>
				<img src='<?php echo theme::$icons_16["info"];?>' style='vertical-align:bottom'/>
				Drag and drop teachers to assign them to subjects.
			</div>
			<?php } ?>
			<div id="page_container" style="overflow:auto" layout="fill">
				<table id='curriculum_table'><tbody>
				<?php 
				$script_init = "";
				$max_classes = 0;
				foreach ($periods as &$period) {
					// find classes for this period
					$period_classes = array();
					foreach ($classes as $cl)
						if ($cl["period"] == $period["id"])
							array_push($period_classes, $cl);
					$period["classes"] = $period_classes;
					$spes = array();
					foreach ($periods_spes as $ps)
						if ($ps["period"] == $period["id"])
							array_push($spes, $ps);
					if (count($spes) == 0) {
						if (count($period_classes) > $max_classes) $max_classes = count($period_classes);
					} else {
						foreach ($periods_spes as $ps)
							if ($ps["period"] == $period["id"]) {
								$spe_classes = array();
								foreach ($period_classes as $cl) if ($cl["specialization"] == $ps["id"]) array_push($spe_classes, $cl);
								if (count($spe_classes) > $max_classes) $max_classes = count($spe_classes);
							}
					}
				}
				foreach ($periods as &$period) {
					$period_classes = $period["classes"];
					// find available teachers for this period
					if ($editing) {
						$period_start = datamodel\ColumnDate::toTimestamp($period["start"]);
						$period_end = datamodel\ColumnDate::toTimestamp($period["end"]);
						$avail = array();
						foreach ($teachers_dates as $td) {
							if (in_array($td["people"], $avail)) continue; // already there
							$start = datamodel\ColumnDate::toTimestamp($td["start"]);
							$end = $td["end"] <> null ? datamodel\ColumnDate::toTimestamp($td["end"]) : null;
							if ($start > $period_end) continue; // start after period
							if ($end <> null && $end < $period_start) continue; // end before period
							array_push($avail, $td["people"]);
						}
						$period["available_teachers"] = $avail;
					}
					
					if ($period_id == null) {
						// several periods => add period title
						echo "<tr class='period_title'>";
						echo "<td colspan=".(4+(count($period_classes) > 0 ? count($period_classes) : 1)+1)." class='page_section_title'>";
						echo "<img src='/static/calendar/calendar_24.png'/> ";
						$id = $this->generateID();
						echo "<span id='$id' style='margin-right:10px'>";
						echo htmlentities($period["name"]);
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
					echo "<th rowspan=2>Subject Code - Name</th>";
					echo "<th colspan=2>Hours</th>";
					echo "<th rowspan=2>Coef.</th>";
					echo "<th colspan=".($max_classes > 0 ? $max_classes : 1)." rowspan=".(count($spes) > 1 ? 2 : 1).">Teachers Assigned</th>";
					$rows = 2;
					foreach ($spes as $spe) {
						if ($spe <> null) $rows++;
						foreach ($categories as $cat) {
							$rows++;
							foreach ($subjects as $s)
								if ($s["period"] == $period["id"] && $s["category"] == $cat["id"] && ($spe == null || $s["specialization"] == $spe["id"]))
									$rows++;
						}
					}
					
					// Available teachers
					echo "<td valign=top rowspan=$rows id='avail_teachers_".$period["id"]."'>";
					echo "</td>";
					echo "</tr>";
					
					echo "<tr>";
					echo "<th>Week</th><th>Total</th>";
					if (count($spes) == 1) {
						if (count($period_classes) == 0)
							echo "<th><i>No class</i></th>";
						else foreach ($period_classes as $cl) {
							echo "<th>Class ";
							$id = $this->generateID();
							echo "<span id='$id'>";
							echo htmlentities($cl["name"]);
							echo "</span>";
							$this->onload("window.top.datamodel.registerCellSpan(window,'AcademicClass','name',".$cl["id"].",document.getElementById('$id'));");
							echo "</th>";
						}
					}
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
							echo htmlentities($spe["name"]);
							echo "</span>";
							$this->onload("window.top.datamodel.registerCellSpan(window,'Specialization','name',".$spe["id"].",document.getElementById('$id'));");
							echo "</td>";
							$spe_classes = array();
							foreach ($period_classes as $cl) if ($cl["specialization"] == $spe["id"]) array_push($spe_classes, $cl);
							if (count($spe_classes) == 0)
								echo "<th><i>No class</i></th>";
							else {
								$total_cols = $max_classes;
								$nb = count($spe_classes);
								foreach ($spe_classes as $cl) {
									$cols = floor($total_cols/$nb);
									$nb--;
									$total_cols -= $cols;
									echo "<th colspan=$cols>Class ";
									$id = $this->generateID();
									echo "<span id='$id'>";
									echo htmlentities($cl["name"]);
									echo "</span>";
									$this->onload("window.top.datamodel.registerCellSpan(window,'AcademicClass','name',".$cl["id"].",document.getElementById('$id'));");
									echo "</th>";
								}
							}
							echo "</tr>";
							$indent = 1;
						} else {
							$indent = 0;
						}
						foreach ($categories as $cat) {
							// Category
							$cat_id = $this->generateID();
							echo "<tr id='$cat_id'>";
							echo "<td colspan=4 class='category_title' style='padding-left:".(10+$indent*20)."px'>";
							echo "<img src='/static/curriculum/subjects_16.png'/> ";
							$id = $this->generateID();
							echo "<span id='$id'>";
							echo htmlentities($cat["name"]);
							echo "</span>";
							$this->onload("window.top.datamodel.registerCellSpan(window,'CurriculumSubjectCategory','name',".$cat["id"].",document.getElementById('$id'));");
							if ($editing) {
								echo " <button class='flat small_icon' title='Add a subject in this category' onclick='new_subject(".$period["id"].",".$cat["id"].",".($spe <> null ? $spe["id"] : "null").",this.parentNode.parentNode);'><img src='".theme::$icons_10["add"]."'/></button>";
							}
							echo "</td>";
							echo "</tr>";
							$cat_subjects = array();
							foreach ($subjects as $s)
								if ($s["period"] == $period["id"] && $s["category"] == $cat["id"] && ($spe == null || $s["specialization"] == $spe["id"]))
									array_push($cat_subjects, $s);
							foreach ($cat_subjects as $s) {
								// Subject
								$script_init .= "addSubjectRow(document.getElementById('$cat_id'),".CurriculumJSON::SubjectJSON($s).");\n";
							}
						}
					}
				}
				?>
				</tbody></table>
			</div>
			<?php if ($can_edit) { ?>
			<div class="page_footer">
				<button class='action' onclick='edit_batch()'>
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
		var edit = <?php echo $editing ? "true" : "false"; ?>;

		var periods = [<?php
		$first = true;
		foreach ($periods as &$period) {
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "id:".$period["id"];
			echo ",academic_period:".$period["academic_period"];
			echo ",weeks:".$period["weeks"];
			echo ",weeks_break:".$period["weeks_break"];
			echo ",classes:[";
			$first_cl = true;
			foreach ($period["classes"] as $cl) {
				if ($first_cl) $first_cl = false; else echo ",";
				echo "{";
				echo "id:".$cl["id"];
				echo ",name:".json_encode($cl["name"]);
				echo ",spe_id:".json_encode($cl["specialization"]);
				echo "}";
			}
			echo "]";
			if ($editing) {
				echo ",teachers:[";
				$first_teacher = true;
				foreach ($period["available_teachers"] as $people_id) {
					if ($first_teacher) $first_teacher = false; else echo ",";
					echo $people_id;
				}
				echo "]";
			}
			echo "}";
		}
		?>];
		var subjects = <?php echo CurriculumJSON::SubjectsJSON($subjects);?>;
		var teachers_assigned = <?php echo CurriculumJSON::TeachersAssignedJSON($teachers_assigned);?>;
		var teachers_people = <?php echo count($teachers) > 0 ? PeopleJSON::Peoples($q_teachers, $teachers) : "[]";?>;

		function hoursFloat(s) {
			s = s.toFixed(2);
			if (s.substr(s.length-3) == ".00") return s.substr(0,s.length-3);
			if (s.substr(s.length-1) == "0") return s.substr(0,s.length-1);
			return s;
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
				button.onclick = function(event) { remove_subject(subject.id,tr); stopEventPropagation(event); return false; };
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

			if (period.classes.length == 0) {
				tr.appendChild(td = document.createElement("TD"));
			} else {
				var total_cols = <?php echo $max_classes;?>;
				var nb = 0;
				for (var i = 0; i < period.classes.length; ++i)
					if (period.classes[i].spe_id == subject.specialization_id) nb++;
				for (var i = 0; i < period.classes.length; ++i) {
					var cl = period.classes[i];
					if (cl.spe_id != subject.specialization_id) continue;
					tr.appendChild(td = document.createElement("TD"));
					var cols = Math.floor(total_cols/nb);
					nb--;
					total_cols -= cols;
					td.colSpan = cols;
					var unassign = function(t,ondone) {
						service.json("curriculum","unassign_teacher",{people_id:t.teacher.id,subject_id:subject.id,class_id:t.cl.id},function(res) {
							if (!res) { if (ondone) ondone(false); return; }
							for (var i = 0; i < teachers_assigned.length; ++i)
								if (teachers_assigned[i].subject_id == subject.id && teachers_assigned[i].class_id == t.cl.id) {
									teachers_assigned.splice(i,1);
									break;
								}
							t.innerHTML = "<i>No teacher</i>";
							updateTeacherLoad(t.teacher.id,t.period.academic_period);
							t.teacher = null;
							if (ondone) ondone(true);
						});
					};
					var add_unassign_button = function(td) {
						var button = document.createElement("BUTTON");
						button.className = "flat small_icon";
						button.innerHTML = "<img src='"+theme.icons_10.remove+"'/>";
						button.title = "Unassign this teacher from this subject";
						button.onclick = function() {
							var lock = lock_screen();
							unassign(td, function() {
								unlock_screen(lock);
							});
						};
						animation.appearsOnOver(td,button);
						td.appendChild(button);
					};
			
					if (edit) {
						td.subject = subject;
						td.cl = cl;
						td.period = period;
						td.ondragenter = function(event) {
							if (event.dataTransfer.types.contains("teacher_"+this.subject.period_id)) {
								this.style.backgroundColor = "#D0D0D0";
								this.style.outline = "1px dotted #808080";
								event.dataTransfer.dropEffect = "copy";
								event.preventDefault();
								return true;
							}
						};
						td.ondragover = function(event) {
							if (event.dataTransfer.types.contains("teacher_"+this.subject.period_id)) {
								this.style.backgroundColor = "#D0D0D0";
								this.style.outline = "1px dotted #808080";
								event.dataTransfer.dropEffect = "copy";
								event.preventDefault();
								return false;
							}
						};
						td.ondragleave = function(event) {
							this.style.backgroundColor = "";
							this.style.outline = "";
						};
						td.ondrop = function(event) {
							this.style.backgroundColor = "";
							this.style.outline = "";
							var teacher_id = event.dataTransfer.getData("teacher_"+this.subject.period_id);
							if (this.teacher && this.teacher.id == teacher_id) return; // same teacher
							var lock = lock_screen();
							var t=this;
							var assign = function() {
								service.json("curriculum","assign_teacher",{people_id:teacher_id,subject_id:subject.id,classes_ids:[t.cl.id]},function(res) {
									if (res) {
										for (var i = 0; i < teachers_people.length; ++i) if (teachers_people[i].id == teacher_id) { t.teacher = teachers_people[i]; break; }
										t.innerHTML = "";
										t.appendChild(document.createTextNode(t.teacher.first_name+" "+t.teacher.last_name));
										add_unassign_button(t);
										teachers_assigned.push(new TeacherAssigned(teacher_id,subject.id,t.cl.id));
										updateTeacherLoad(teacher_id, t.period.academic_period);
									}
									unlock_screen(lock);
								});
							};
							if (this.teacher) unassign(this,function(ok) {
								if (!ok) { unlock_screen(lock); return; }
								assign();
							}); else 
								assign();
						};
					}
					var people = null;
					for (var j = 0; j < teachers_assigned.length; ++j) {
						if (teachers_assigned[j].subject_id == subject.id && teachers_assigned[j].class_id == cl.id) {
							for (var k = 0; k < teachers_people.length; ++k)
								if (teachers_people[k].id == teachers_assigned[j].people_id) {
									people = teachers_people[k];
									break;
								}
							break;
						}
					}
					td.teacher = people;
					if (people == null) {
						var it = document.createElement("I");
						it.innerHTML = "No teacher";
						td.appendChild(it);
					} else {
						td.appendChild(document.createTextNode(people.first_name+" "+people.last_name));
						if (edit) {
							add_unassign_button(td);
						}
					}
				}
			}
			return tr;
		}

		<?php echo $script_init;?>
		
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
					var button = document.createElement("BUTTON");
					button.innerHTML = "<img src='"+theme.icons_16.remove+"'/>";
					button.className = "flat small";
					button.cat = cat;
					animation.appearsOnOver(tr,button);
					td.appendChild(button);
					button.onclick = function() { remove_category(this); };
				}
				var popup = new popup_window("Edit Subject Categories", theme.build_icon("/static/curriculum/subjects_16.png",theme.icons_10.edit), content);
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
						field_specialization: spe_id,
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
						field_coefficient: ns.coefficient
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
							var new_row = createSubjectRow(cat_row, subject);
							row.parentNode.insertBefore(new_row, row);
							row.parentNode.removeChild(row);
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

		function remove_subject(id,row) {
			window.top.datamodel.confirm_remove("CurriculumSubject",id,function() {
				row.parentNode.removeChild(row);
			});
		}

		var teachers_periods = [];

		// available teachers
		function build_avail_teachers() {
			for (var i = 0; i < periods.length; ++i) {
				var teachers_period = {period:periods[i],teachers:[]};
				teachers_periods.push(teachers_period);
				var period = periods[i];
				var container = document.getElementById('avail_teachers_'+period.id);
				var content = document.createElement("TABLE");
				var sec = new section('/static/curriculum/teacher_16.png', 'Available Teachers', content, true);
				container.appendChild(sec.element);
				var tr,td;
				content.appendChild(tr = document.createElement("TR"));
				tr.appendChild(td = document.createElement("TH"));
				td.rowSpan = 2;
				td.innerHTML = "Teacher";
				tr.appendChild(td = document.createElement("TH"));
				td.colSpan = 2;
				td.innerHTML = "This period";
				tr.appendChild(td = document.createElement("TH"));
				td.colSpan = 2;
				td.innerHTML = "Other periods";
				tr.appendChild(td = document.createElement("TH"));
				td.colSpan = 2;
				td.innerHTML = "Total";
				content.appendChild(tr = document.createElement("TR"));
				for (var j = 0; j < 3; ++j) {
					tr.appendChild(td = document.createElement("TH"));
					td.innerHTML = "Week";
					tr.appendChild(td = document.createElement("TH"));
					td.innerHTML = "Total";
				}
				for (var j = 0; j < period.teachers.length; ++j) {
					var people_id = period.teachers[j];
					var teacher;
					for (var k = 0; k < teachers_people.length; ++k) if (teachers_people[k].id == people_id) { teacher = teachers_people[k]; break; }
					content.appendChild(tr = document.createElement("TR"));
					teachers_period.teachers.push({teacher:teacher,row:tr});
					tr.appendChild(td = document.createElement("TD"));
					var span = document.createElement("SPAN");
					span.style.cursor = "default";
					span.style.whiteSpace = "nowrap";
					span.title = "Click to see teacher's profile&#13;Drag and drop to assign this teacher to a subject";
					span.draggable = true;
					span.period = period;
					span.teacher = teacher;
					span.ondragstart = function(event) {
						event.dataTransfer.setData('teacher_'+this.period.id,this.teacher.id);
						event.dataTransfer.effectAllowed = 'copy';
						return true;
					};
					span.onmouseover = function() { this.style.textDecoration = "underline"; };
					span.onmouseout = function() { this.style.textDecoration = ""; };
					span.onclick = function() {
						window.top.popup_frame('/static/people/profile_16.png','Profile','/dynamic/people/page/profile?people='+this.teacher.people_id,null,95,95);
					};
					span.appendChild(document.createTextNode(teacher.first_name+" "+teacher.last_name));
					td.appendChild(span);
					tr.appendChild(td = document.createElement("TD"));
					tr.appendChild(td = document.createElement("TD"));
					tr.appendChild(td = document.createElement("TD"));
					tr.appendChild(td = document.createElement("TD"));
					tr.appendChild(td = document.createElement("TD"));
					tr.appendChild(td = document.createElement("TD"));
					updateTeacherLoad(people_id, period.academic_period);
				}
			}
		}

		var other_loads = [<?php
		$first = true;
		foreach ($full_teachers_assign as &$a) {
			if (in_array($a["subject"], $subjects_ids)) continue; // already in this batch
			if ($a["class"] == null) continue;
			if ($a["hours"] == null) continue;
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "people_id:".$a["people"];
			echo ",academic_period:".$a["academic_period_id"];
			echo ",hours:".$a["hours"];
			echo ",hours_type:".json_encode($a["hours_type"]);
			echo "}";
		} 
		?>];

		function updateTeacherLoad(teacher_id, academic_period_id) {
			var period = null;
			for (var i = 0; i < periods.length; ++i)
				if (periods[i].academic_period == academic_period_id) { period = periods[i]; break; }
			
			// for this batch
			var this_batch_total = 0;
			for (var j = 0; j < teachers_assigned.length; ++j) {
				if (teachers_assigned[j].people_id != teacher_id) continue;
				var found = false;
				for (var k = 0; k < period.classes.length; ++k)
					if (period.classes[k].id == teachers_assigned[j].class_id) { found = true; break; }
				if (!found) continue;
				var subject = null;
				for (var k = 0; k < subjects.length; ++k)
					if (subjects[k].id == teachers_assigned[j].subject_id) { subject = subjects[k]; break; }
				if (!subject) continue;
				switch (subject.hours_type) {
				case "Per week": this_batch_total += parseInt(subject.hours)*(parseInt(period.weeks)-parseInt(period.weeks_break)); break;
				case "Per period": this_batch_total += parseInt(subject.hours); break;
				}
			}
			// for other batches
			var other_batches_total = 0;
			for (var i = 0; i < other_loads.length; ++i) {
				if (other_loads[i].people_id != teacher_id) continue;
				if (other_loads[i].academic_period != academic_period_id) continue;
				switch (other_loads[i].hours_type) {
				case "Per week": other_batches_total += parseInt(other_loads[i].hours)*(parseInt(period.weeks)-parseInt(period.weeks_break)); break;
				case "Per period": other_batches_total += parseInt(other_loads[i].hours); break;
				}
			}
			// update teacher info
			var teacher = null, row = null;
			for (var i = 0; i < teachers_periods.length; ++i) {
				if (teachers_periods[i].period.id != period.id) continue;
				for (var j = 0; j < teachers_periods[i].teachers.length; ++j) {
					if (teachers_periods[i].teachers[j].teacher.id != teacher_id) continue;
					teacher = teachers_periods[i].teachers[j].teacher;
					row = teachers_periods[i].teachers[j].row;
					break;
				}
				break;
			}
			row.childNodes[1].innerHTML = hoursFloat(this_batch_total/(period.weeks-period.weeks_break))+"h";
			row.childNodes[2].innerHTML = this_batch_total+"h";
			row.childNodes[3].innerHTML = hoursFloat(other_batches_total/(period.weeks-period.weeks_break))+"h";
			row.childNodes[4].innerHTML = other_batches_total+"h";
			row.childNodes[5].innerHTML = hoursFloat((other_batches_total+this_batch_total)/(period.weeks-period.weeks_break))+"h";
			row.childNodes[6].innerHTML = (other_batches_total+this_batch_total)+"h";
		}
		
		build_avail_teachers();

		function import_subjects(target_period) {
			// TODO
			alert("Not yet implemented");
		}

		<?php } ?>// if editing
		</script>
		<?php 
	}
}
?>