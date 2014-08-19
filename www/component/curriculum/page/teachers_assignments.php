<?php 
class page_teachers_assignments extends Page {
	
	public function getRequiredRights() { return array("consult_curriculum"); }
	
	public function execute() {
		$academic_period_id = @$_GET["period"];
		if ($academic_period_id == null) {
			// by default, get the current one
			$today = date("Y-m-d", time());
			$academic_period = SQLQuery::create()
				->select("AcademicPeriod")
				->where("`start` <= '".$today."'")
				->where("`end` >= '".$today."'")
				->executeSingleRow();
			if ($academic_period == null) {
				// next one
				$academic_period = SQLQuery::create()
					->select("AcademicPeriod")
					->where("`start` >= '".$today."'")
					->orderBy("AcademicPeriod","start")
					->limit(0, 1)
					->executeSingleRow();
				if ($academic_period == null) {
					// last one
					$academic_period = SQLQuery::create()
						->select("AcademicPeriod")
						->orderBy("AcademicPeriod","start", false)
						->limit(0, 1)
						->executeSingleRow();
				}
			}
			$academic_period_id = @$academic_period["id"];
			if ($academic_period_id == null) $academic_period_id = 0;
		} else
			$academic_period = SQLQuery::create()->select("AcademicPeriod")->whereValue("AcademicPeriod","id",$academic_period_id)->executeSingleRow();
		
		$years = SQLQuery::create()->select("AcademicYear")->execute();
		$periods = SQLQuery::create()->select("AcademicPeriod")->orderBy("AcademicPeriod","start")->execute();

		require_once("AcademicPeriod.inc");
		$ap = $academic_period <> null ? new AcademicPeriod($academic_period) : null;
		
		$can_edit = PNApplication::$instance->user_management->has_right("edit_curriculum");
		
		$locked_by = null;
		if ($can_edit) {
			require_once("component/data_model/DataBaseLock.inc");
			$lock_id = DataBaseLock::lockTable("TeacherAssignment", $locked_by);
			if ($lock_id == null)
				$can_edit = false;
			else
				DataBaseLock::generateScript($lock_id);
		}
		
		require_once("component/curriculum/CurriculumJSON.inc");
		$this->requireJavascript("section.js");
		theme::css($this, "section.css");
		?>
		<div style="width:100%;height:100%;display:flex;flex-direction:column">
		<div class='page_title' style='flex:none'>
			<img src='/static/curriculum/teacher_assign_32.png'/>
			Teachers Assignments
		</div>
		<?php
		if ($ap == null) {
			echo "<div style='background-color:white;padding:15px;'><center><img src='".theme::$icons_16["warning"]."' style='vertical-align:bottom'/> <i>No academic period defined yet.</i><center></div>";
			return;
		} 
		?>
		<div class='page_section_title' style='background-color:white;flex:none'>
			Academic Period: <select onchange="if (this.value == <?php echo $academic_period_id;?>) return; location.href='?period='+this.value;">
			<?php
			foreach ($periods as $period) {
				echo "<option value='".$period["id"]."'";
				if ($period["id"] == $academic_period_id) echo " selected='selected'";
				$year = $this->getAcademicYear($period["year"], $years);
				echo ">Academic Year ".htmlentities($year["name"]).", ".htmlentities($period["name"]);
				echo "</option>";
			} 
			?>
			</select>
		</div>
		<?php
		if ($locked_by <> null) {
			echo "<div class='error_box' style='flex:none'>$locked_by is already editing teachers assignment. You cannot edit it at the same time.</div>";
			$can_edit = false;
		} 
		?>
		<div style="flex:1 1 auto;overflow:auto;display:flex;flex-direction:row">
		<div style="flex:1 1 auto">
		<?php 
		if ($ap <> null)
		foreach ($ap->batch_periods as $bp) {
			echo "<div style='background-color:white;margin-left:5px;' class='section'>";
			echo "<div class='page_section_title'>";
			echo "Batch ".htmlentities($bp["batch_name"]).", ".htmlentities($bp["name"]);
			echo "</div>";
			$bp_subjects = $ap->getSubjectsForBatch($bp["id"]);
			if (count($bp_subjects) == 0)
				echo "<i>No subject defined for this period</i>";
			$spes = array();
			foreach ($bp_subjects as $s) {
				if ($s["period"] <> $bp["id"]) continue;
				if (!in_array($s["specialization"], $spes))
					array_push($spes, $s["specialization"]);
			}
			foreach ($spes as $spe_id) {
				if ($spe_id <> null)
					echo "<div class='page_section_title2'><img src='/static/curriculum/curriculum_16.png'/> Specialization ".htmlentities($ap->getSpecializationName($spe_id))."</div>";
				$classes = $ap->getClassesFor($bp["id"], $spe_id);
				$subjects = $ap->getSubjectsFor($bp["id"], $spe_id);
				if (count($classes) == 0) {
					echo "<i>No class defined for this period</i>";
				} else if (count($subjects) == 0) {
					echo "<i>No subject defined for this period</i>";
				} else {
					$id = $this->generateID();
					echo "<table class='subjects_table'><tbody id='$id'>";
					echo "<tr><th>Code</th><th>Subject Description</th><th>Hours</th><th>Class(es)</th><th>Teacher(s) assigned</th></tr>";
					echo "</tbody></table>";
					$subjects_classes = $ap->getMergedClasses($subjects, $classes);
					$json = "[";
					$first = true;
					foreach ($subjects_classes as $sc) {
						if ($first) $first = false; else $json .= ",";
						$json .= "{";
						$json .= "subject:".CurriculumJSON::SubjectJSON($sc["subject"]);
						$json .= ",classes:[";
						$first_merged_class = true;
						foreach ($sc["classes"] as $scs) {
							if ($first_merged_class) $first_merged_class = false; else $json .= ",";
							$json .= json_encode($scs);
						}
						$json .= "]";
						$json .= "}";
					}
					$json .= "]";
					$this->onload("new PeriodSubjects('$id',".$json.");");
				}
			}
			echo "</div>";
		}
		?>
		</div>
		<div id='teachers_section' style='display:inline-block;flex:1 1 auto;background-color:white' icon='/static/curriculum/teacher_16.png' title='Available Teachers' collapsable='false'>
		<div style='background-color:white'>
		<?php $id = $this->generateID();?>
		<table class='teachers_table'><tbody id='<?php echo $id;?>'>
		<tr><th>Teacher</th><th>Hours</th></tr>
		</tbody></table>
		<?php $this->onload("TeachersTable('$id');")?>
		</div>
		<?php $this->onload("sectionFromHTML('teachers_section');")?>
		</div>
		</div>
		</div>
		<style type='text/css'>
		.subjects_table {
			border-collapse: collapse;
			font-family: Verdana;
		}
		.subjects_table td {
			border-top: 1px solid #A0A0A0;
			border-bottom: 1px solid #A0A0A0;
			padding-left: 5px;
			padding-right: 5px;
		}
		.teachers_table th {
			text-align: center;
		}
		.teachers_table td {
			padding-left: 5px;
			padding-right: 5px;
		}
		</style>
		<script type='text/javascript'>
		var academic_period = <?php echo CurriculumJSON::AcademicPeriodJSONFromDB($ap->academic_period);?>;
		var nb_weeks = (academic_period.weeks-academic_period.weeks_break);
		var categories = <?php echo CurriculumJSON::SubjectCategoriesJSON($ap->categories);?>;
		var classes = <?php echo CurriculumJSON::AcademicClassesJSON($ap->classes);?>;
		var subjects = <?php echo CurriculumJSON::SubjectsJSON($ap->subjects);?>;
		var teachers = <?php echo $ap->teachersJSON();?>;
		var teachers_assignments = <?php echo CurriculumJSON::TeachersAssignedJSON($ap->teachers_assignments);?>;
		
		function getSubject(id) {
			for (var i = 0; i < subjects.length; ++i)
				if (subjects[i].id == id) return subjects[i];
		}
		
		function getClassName(id) {
			for (var i = 0; i < classes.length; ++i)
				if (classes[i].id == id) return classes[i].name;
		}

		function getTeacher(people_id) {
			for (var i = 0; i < teachers.length; ++i)
				if (teachers[i].id == people_id) return teachers[i];
		}
		
		function PeriodSubjects(table_id, subjects) {
			var table = document.getElementById(table_id);
			var tr, td;
			for (var i = 0; i < subjects.length; ++i) {
				table.appendChild(tr = document.createElement("TR"));
				tr.appendChild(td = document.createElement("TD"));
				td.appendChild(document.createTextNode(subjects[i].subject.code));
				tr.appendChild(td = document.createElement("TD"));
				td.appendChild(document.createTextNode(subjects[i].subject.name));
				tr.appendChild(td = document.createElement("TD"));
				var total = 0;
				if (subjects[i].subject.hours_type == "Per week")
					total = subjects[i].subject.hours*nb_weeks;
				else
					total = subjects[i].subject.hours;
				td.appendChild(document.createTextNode((total/nb_weeks).toFixed(2)+"h/week x "+nb_weeks+" = "+total+"h"));
				new SubjectClasses(tr, subjects[i].classes, subjects[i].subject);
			}
		}
		function SubjectClasses(main_tr, classes, subject) {
			this.main_tr = main_tr;
			this.classes = classes;
			this.sub_tr = [];
			this.update = function() {
				// remove sub_tr
				for (var i = 0; i < this.sub_tr.length; ++i) this.sub_tr[i].parentNode.removeChild(this.sub_tr[i]);
				this.sub_tr = [];
				// reset rowSpan
				this.main_tr.childNodes[0].rowSpan = this.classes.length; // code
				this.main_tr.childNodes[1].rowSpan = this.classes.length; // name
				this.main_tr.childNodes[2].rowSpan = this.classes.length; // hours
				// remove first row
				while (this.main_tr.childNodes.length > 3) this.main_tr.removeChild(this.main_tr.childNodes[3]);
				// create each row
				var next = this.main_tr.nextSibling;
				for (var i = 0; i < this.classes.length; ++i) {
					var tr;
					if (i == 0) tr = this.main_tr;
					else {
						tr = document.createElement("TR");
						if (next) this.main_tr.parentNode.insertBefore(tr, next);
						else this.main_tr.parentNode.appendChild(tr);
						this.sub_tr.push(tr);
					}
					this.createClassRow(tr, this.classes[i], i==0, i == this.classes.length-1); 
				}
			};
			this.getClassesText = function(classes) {
				var s = "Class";
				if (classes.length > 1) s += "es";
				s += " ";
				for (var i = 0; i < classes.length; ++i) {
					if (i > 0) s += "+";
					s += getClassName(classes[i]);
				}
				return s;
			};
			this.createClassRow = function(tr, classes, first, last) {
				var td;
				tr.appendChild(td = document.createElement("TD"));
				td.appendChild(document.createTextNode(this.getClassesText(classes)));
				if (!last) td.style.borderBottom = "none";
				if (!first) td.style.borderTop = "none";
				<?php if ($can_edit) { ?>
				if (this.classes.length > 1) {
					var merge = document.createElement("BUTTON");
					merge.className = "flat small_icon";
					merge.innerHTML = "<img src='"+theme.icons_10.merge+"'/>";
					merge.title = "Merge with other(s) class(es)";
					td.appendChild(merge);
					var t=this;
					merge.onclick = function() {
						var button=this;
						require("context_menu.js",function(){
							var menu = new context_menu();
							menu.addTitleItem(null, "Merge "+t.getClassesText(classes)+" with");
							for (var i = 0; i < t.classes.length; ++i) {
								if (t.classes[i] == classes) continue;
								menu.addIconItem(null, t.getClassesText(t.classes[i]), function(to_classes) {
									var lock = lock_screen();
									service.json("curriculum","merge_classes",{subject:subject.id,to:to_classes[0],classes:classes},function(res) {
										unlock_screen(lock);
										if (!res) return;
										for (var j = 0; j < classes.length; ++j)
											to_classes.push(classes[j]);
										t.classes.remove(classes);
										t.update();
									});
								},t.classes[i]);
							}
							menu.showBelowElement(button);
						});
					};
				}
				if (classes.length > 1) {
					var split = document.createElement("BUTTON");
					split.className = "flat small_icon";
					split.innerHTML = "<img src='"+theme.icons_10.split+"'/>";
					split.title = "Split classes";
					td.appendChild(split);
					var t=this;
					split.onclick = function() {
						var lock = lock_screen();
						service.json("curriculum","split_classes",{subject:subject.id,classes:classes},function(res){
							unlock_screen(lock);
							if (!res) return;
							t.classes.remove(classes);
							for (var i = 0; i < classes.length; ++i)
								t.classes.push([classes[i]]);
							t.update();
						});
					};
				}
				<?php } ?>
				tr.appendChild(td = document.createElement("TD"));
				if (!last) td.style.borderBottom = "none";
				if (!first) td.style.borderTop = "none";
				var class_teachers = [];
				var class_teachers_assigned = [];
				for (var i = 0; i < teachers_assignments.length; ++i) {
					var ta = teachers_assignments[i];
					if (ta.subject_id != subject.id) continue;
					var found = false;
					for (var j = 0; j < classes.length; ++j)
						if(classes[j] == ta.class_id) { found = true; break; }
					if (!found) continue;
					class_teachers.push(getTeacher(ta.people_id));
					class_teachers_assigned.push(ta);
				}
				if (class_teachers.length == 0) {
					td.innerHTML = "<i style='color:red'>No teacher</i>";
					<?php if ($can_edit) { ?>
					var assign = document.createElement("BUTTON");
					assign.className = 'flat small_icon';
					assign.innerHTML = "<img src='"+theme.icons_10.add+"'/>";
					assign.title = "Assign a teacher";
					td.appendChild(assign);
					var t=this;
					assign.onclick = function() {
						var button=this;
						require("context_menu.js",function() {
							var menu = new context_menu();
							menu.addTitleItem(null, "Assign Teacher");
							for (var i = 0; i < teachers.length; ++i)
								menu.addIconItem(null, teachers[i].first_name+" "+teachers[i].last_name, function(teacher_id) {
									var lock = lock_screen();
									service.json("curriculum","assign_teacher",{people_id:teacher_id,subject_id:subject.id,classes_ids:[classes[0]]},function(res) {
										unlock_screen(lock);
										if (!res) return;
										teachers_assignments.push({people_id:teacher_id,subject_id:subject.id,class_id:classes[0]});
										t.update();
										updateTeacherRow(teacher_id);
									});
								}, teachers[i].id);
							menu.showBelowElement(button);
						});
					};
					<?php } ?>
				} else {
					var remaining_period = subject.hours;
					if (subject.hours_type == "Per week") remaining_period *= nb_weeks;
					for (var i = 0; i < class_teachers.length; ++i) {
						if (i > 0) {
							td.appendChild(document.createElement("BR"));
							td.appendChild(document.createTextNode(" + "));
						}
						td.appendChild(document.createTextNode(class_teachers[i].first_name+" "+class_teachers[i].last_name));
						if (class_teachers_assigned[i].hours != null) {
							td.appendChild(document.createTextNode("("+class_teachers_assigned[i].hours+"h"+(class_teachers_assigned[i].hours_type == "Per week" ? "/week" : "")+")"));
							if (class_teachers_assigned[i].hours_type == "Per week")
								remaining_period -= class_teachers_assigned[i].hours*nb_weeks;
							else
								remaining_period -= class_teachers_assigned[i].hours;
						} else
							remaining_period = 0;
						<?php if ($can_edit) { ?>
						var schedule = document.createElement("BUTTON");
						schedule.className = 'flat small_icon';
						schedule.innerHTML = "<img src='"+theme.icons_10.time+"'/>";
						schedule.title = "Change number of hours for this teacher";
						schedule.teacher = class_teachers[i];
						schedule.assignment = class_teachers_assigned[i];
						td.appendChild(schedule);
						schedule.onclick = function() {
							var tt=this;
							require(["popup_window.js",["typed_field.js","field_integer.js"]],function() {
								var total_per_week;
								var total_period;
								if (subject.hours_type == "Per week") {
									total_per_week = subject.hours;
									total_period = subject.hours*nb_weeks;
								} else {
									total_period = subject.hours;
									total_per_week = subject.hours/nb_weeks;
								}
								var assigned_period = 0;
								for (var i = 0; i < class_teachers.length; ++i) {
									if (class_teachers[i].id == tt.teacher.id) continue; // this is the current teacher
									if (class_teachers[i].hours_type == "Per week")
										assigned_period += class_teachers_assigned[i].hours*nb_weeks;
									else
										assigned_period += class_teachers_assigned[i].hours;
								}
								var content = document.createElement("DIV");
								content.style.padding = "10px";
								content.innerHTML = "Number of hours teaching hours for ";
								content.appendChild(document.createTextNode(tt.teacher.first_name+" "+tt.teacher.last_name+":"));
								content.appendChild(document.createElement("BR"));
								var current_hours = tt.assignment.hours;
								var current_hours_type = tt.assignment.hours_type;
								if (!current_hours) {
									current_hours = subject.hours;
									current_hours_type = subject.hours_type;
								}
								var max = total_period-assigned_period;
								if (current_hours_type == "Per week") max /= nb_weeks;
								var field = new field_integer(current_hours,true,{min:1,max:max,can_be_null:false});
								content.appendChild(field.getHTMLElement());
								content.appendChild(document.createTextNode("hour(s)"));
								var select = document.createElement("SELECT");
								content.appendChild(select);
								var o = document.createElement("OPTION");
								o.value = o.text = "Per week";
								select.appendChild(o);
								o = document.createElement("OPTION");
								o.value = o.text = "Per period";
								select.appendChild(o);
								if (current_hours_type == "Per week") select.selectedIndex = 0; else select.selectedIndex = 1;
								select.onchange = function() {
									var max = total_period-assigned_period;
									if (this.value == "Per week") max /= nb_weeks;
									field.setMaximum(max);
								};
								var popup = new popup_window("Teaching hours",null,content);
								popup.addOkCancelButtons(function() {
									if (field.hasError()) { alert("Please enter a valid number of hours"); return; }
									var lock = lock_screen();
									tt.assignment.hours = field.getCurrentData();
									tt.assignment.hours_type = select.value;
									service.json("curriculum","assign_teacher",{people_id:tt.teacher.id,subject_id:subject.id,classes_ids:[classes[0]],hours:field.getCurrentData(),hours_type:select.value},function(res) {
										t.update();
										updateTeacherRow(tt.teacher.id);
										unlock_screen(lock);
										popup.close();
									});
								});
								popup.show();
							});
						};
						var unassign = document.createElement("BUTTON");
						unassign.className = 'flat small_icon';
						unassign.innerHTML = "<img src='"+theme.icons_10.remove+"'/>";
						unassign.title = "Unassign teacher";
						unassign.teacher = class_teachers[i];
						unassign.assignment = class_teachers_assigned[i];
						td.appendChild(unassign);
						var t=this;
						unassign.onclick = function() {
							var lock = lock_screen();
							var tt=this;
							service.json("curriculum","unassign_teacher",{people_id:tt.teacher.id,subject_id:subject.id,class_id:classes[0]},function(res) {
								unlock_screen(lock);
								if (!res) return;
								teachers_assignments.remove(tt.assignment);
								t.update();
								updateTeacherRow(tt.teacher.id);
							});
						};
						<?php } ?>
					}
					if (remaining_period > 0) {
						td.appendChild(document.createElement("BR"));
						td.appendChild(document.createTextNode(" + "));
						var span = document.createElement("SPAN");
						span.style.fontStyle = "italic";
						span.style.color = "#808080";
						span.style.whiteSpace = "nowrap";
						if (subject.hours_type == "Per week")
							span.innerHTML = (remaining_period/nb_weeks)+"h/week remaining";
						else
							span.innerHTML = remaining_period+"h remaining";
						td.appendChild(span);
						<?php if ($can_edit) { ?>
						var assign = document.createElement("BUTTON");
						assign.className = 'flat small_icon';
						assign.innerHTML = "<img src='"+theme.icons_10.add+"'/>";
						assign.title = "Assign a teacher for remaining hours";
						td.appendChild(assign);
						var t=this;
						assign.onclick = function() {
							var button=this;
							require("context_menu.js",function() {
								var menu = new context_menu();
								menu.addTitleItem(null, "Assign Teacher");
								for (var i = 0; i < teachers.length; ++i) {
									var found = false;
									for (var j = 0; j < class_teachers.length; ++j) if (class_teachers[j].id == teachers[i].id) { found = true; break; }
									if (found) continue;
									menu.addIconItem(null, teachers[i].first_name+" "+teachers[i].last_name, function(teacher_id) {
										var lock = lock_screen();
										var hours = remaining_period;
										if (subject.hours_type == "Per week") hours /= nb_weeks;
										service.json("curriculum","assign_teacher",{people_id:teacher_id,subject_id:subject.id,classes_ids:[classes[0]],hours:hours,hours_type:subject.hours_type},function(res) {
											unlock_screen(lock);
											if (!res) return;
											teachers_assignments.push({people_id:teacher_id,subject_id:subject.id,class_id:classes[0],hours:hours,hours_type:subject.hours_type});
											t.update();
											updateTeacherRow(teacher_id);
										});
									}, teachers[i].id);
								}
								menu.showBelowElement(button);
							});
						};
						<?php } ?>
					}
				}
				<?php if ($can_edit) { ?>
				td.ondragenter = function(event) {
					if (event.dataTransfer.types.contains("teacher")) {
						this.style.backgroundColor = "#D0D0D0";
						this.style.outline = "1px dotted #808080";
						event.dataTransfer.dropEffect = "copy";
						event.preventDefault();
						return true;
					}
				};
				td.ondragover = function(event) {
					if (event.dataTransfer.types.contains("teacher")) {
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
				var t=this;
				td.ondrop = function(event) {
					this.style.backgroundColor = "";
					this.style.outline = "";
					var teacher_id = event.dataTransfer.getData("teacher");
					for (var i = 0; i < class_teachers.length; ++i) if (class_teachers[i].id == teacher_id) return; // same teacher
					var lock = lock_screen();
					var remaining_hours_period = subject.hours;
					if (subject.hours_type == "Per week")
						remaining_hours_period *= nb_weeks;
					var total_hours_period = remaining_hours_period;
					for (var i = 0; i < class_teachers.length; ++i) {
						if (class_teachers[i].hours == null) { remaining_hours_period = 0; break; }
						if (class_teachers[i].hours_type == "Per period")
							remaining_hours_period -= class_teachers[i].hours;
						else
							remaining_hours_period -= class_teachers[i].hours*nb_weeks;
					}
					var assign = function() {
						var data = {people_id:teacher_id,subject_id:subject.id,classes_ids:[classes[0]]};
						var assignment = {people_id:teacher_id,subject_id:subject.id,class_id:classes[0]};
						if (remaining_hours_period > 0 && remaining_hours_period < total_hours_period) {
							if (subject.hours_type == "Per week") {
								data.hours = remaining_hours_period/nb_weeks;
								data.hours_type = "Per week";
							} else {
								data.hours = remaining_hours_period;
								data.hours_type = "Per period";
							}
							assignment.hours = data.hours;
							assignment.hours_type = data.hours_type;
						}
						service.json("curriculum","assign_teacher",data,function(res) {
							if (res)
								teachers_assignments.push(assignment);
							t.update();
							updateTeacherRow(teacher_id);
							unlock_screen(lock);
						});
					};					
					if (class_teachers.length > 0 && remaining_hours_period == 0) {
						var nb_done = 0;
						var done = function() {
							if (++nb_done < class_teachers.length) return;
							for (var i = 0; i < class_teachers.length; ++i) {
								teachers_assignments.remove(class_teachers_assigned[i]);
								updateTeacherRow(class_teachers[i].id);
							}
							assign();
						};
						for (var i = 0; i < class_teachers.length; ++i)
							service.json("curriculum","unassign_teacher",{people_id:class_teachers[i].id,subject_id:subject.id,class_id:classes[0]},function(res) {
								if (!res) {
									unlock_screen(lock);
									return;
								}
								done();
							});
					} else
						assign();
				};
				<?php } ?>
			};
			this.update();
		}

		var teachers_rows = [];
		function TeachersTable(table_id) {
			var table = document.getElementById(table_id);
			if (teachers.length == 0) {
				var tr = document.createElement("TR");
				var td = document.createElement("TD");
				td.colSpan = 2;
				td.innerHTML = "<i>No teacher for this period</i>";
				tr.appendChild(td);
				table.appendChild(tr);
				return;
			}
			for (var i = 0; i < teachers.length; ++i)
				teachers_rows.push(new TeacherRow(table, teachers[i]));
		}
		function TeacherRow(table, teacher) {
			this.teacher = teacher;
			var tr = document.createElement("TR"); table.appendChild(tr);
			var td_name = document.createElement("TD"); tr.appendChild(td_name);
			var span = document.createElement("SPAN"); td_name.appendChild(span);
			span.appendChild(document.createTextNode(teacher.first_name+" "+teacher.last_name));
			span.style.cursor = "default";
			span.style.whiteSpace = "nowrap";
			span.title = "Click to see teacher's profile\nDrag and drop to assign this teacher to a subject";
			span.draggable = true;
			span.teacher = teacher;
			span.ondragstart = function(event) {
				event.dataTransfer.setData('teacher',this.teacher.id);
				event.dataTransfer.effectAllowed = 'copy';
				return true;
			};
			span.onmouseover = function() { this.style.textDecoration = "underline"; };
			span.onmouseout = function() { this.style.textDecoration = ""; };
			span.onclick = function() {
				window.top.popup_frame('/static/people/profile_16.png','Profile','/dynamic/people/page/profile?people='+this.teacher.id,null,95,95);
			};
			var td_hours = document.createElement("TD"); tr.appendChild(td_hours);
			td_hours.style.whiteSpace = "nowrap";
			this.update = function() {
				var total = 0;
				for (var i = 0; i < teachers_assignments.length; ++i) {
					var ta = teachers_assignments[i];
					if (ta.people_id != teacher.id) continue;
					if (!ta.hours) {
						var subject = getSubject(ta.subject_id);
						if (subject.hours_type == "Per week") total += (subject.hours*nb_weeks);
						else total += subject.hours;
					} else {
						if (ta.hours_type == "Per week") total += ta.hours*nb_weeks;
						else total += ta.hours;
					}
				}
				td_hours.innerHTML = (total/nb_weeks).toFixed(2)+"h/week x "+nb_weeks+" = "+total+"h";
				layout.invalidate(td_hours);
			};
			this.update();
		}
		function updateTeacherRow(people_id) {
			for (var i = 0; i < teachers_rows.length; ++i)
				if (teachers_rows[i].teacher.id == people_id) {
					teachers_rows[i].update();
					return;
				}
		}
		</script>
		<?php 
	}
	
	/** Look for the given academic year
	 * @param integer $id academic year to find
	 * @param array $years list of known academic years
	 * @return array the academic year found 
	 */
	private function getAcademicYear($id, $years) {
		foreach ($years as $y) if ($y["id"] == $id) return $y;
	}
	
}
?>