<?php 
class page_teachers_assignments extends Page {
	
	public function getRequiredRights() { return array("consult_curriculum"); }
	
	public function execute() {
		$academic_period_id = @$_GET["period"];
		if ($academic_period_id == null) {
			// by default, get the current one
			$academic_period = PNApplication::$instance->curriculum->getCurrentAcademicPeriod(true);
			$academic_period_id = @$academic_period["id"];
			if ($academic_period_id == null) $academic_period_id = 0;
		} else
			$academic_period = PNApplication::$instance->curriculum->getAcademicPeriod($academic_period_id);
		
		$current_academic_period = PNApplication::$instance->curriculum->getCurrentAcademicPeriod();

		$years = PNApplication::$instance->curriculum->getAcademicYears();
		$periods = PNApplication::$instance->curriculum->getAcademicPeriods();

		$can_edit = isset($_GET["edit"]) && PNApplication::$instance->user_management->has_right("edit_curriculum");
		
		$locked_by = null;
		if ($can_edit) {
			require_once("component/data_model/DataBaseLock.inc");
			$lock_id = DataBaseLock::lockTable("TeacherAssignment", $locked_by);
			if ($lock_id == null)
				$can_edit = false;
			else
				DataBaseLock::generateScript($lock_id);
		}
		
		// load teachers present during this period
		if ($academic_period <> null) {
			$q = SQLQuery::create()
				->select("TeacherDates")
				->where("`start` < '".$academic_period["end"]."'")
				->where("(`end` IS NULL OR `end` > '".$academic_period["start"]."')")
				;
			PNApplication::$instance->people->joinPeople($q, "TeacherDates", "people");
			$teachers = $q->execute();
		} else
			$teachers = array();
		
		require_once("component/curriculum/CurriculumJSON.inc");
		require_once("component/students_groups/StudentsGroupsJSON.inc");
		require_once("component/people/PeopleJSON.inc");
		$this->requireJavascript("section.js");
		theme::css($this, "section.css");
		?>
		<div style="width:100%;height:100%;display:flex;flex-direction:column">
		<div class='page_title' style='flex:none'>
			<img src='/static/teaching/teacher_assign_32.png'/>
			Teachers Assignments
			<?php 
			if (PNApplication::$instance->user_management->has_right("edit_curriculum")) {
				if ($can_edit) {
					echo "<button onclick=\"var u = new window.URL(location.href);delete u.params.edit;location.href=u.toString();\" class='action'><img src='".theme::$icons_16["no_edit"]."'/>Stop Editing</button>";
				} else {
					echo "<button id='edit_teachers_assignments_button' onclick=\"var u = new window.URL(location.href);u.params.edit = 'true';location.href=u.toString();\" class='action'><img src='".theme::$icons_16["edit"]."'/>Edit</button>";
				}
			}
			?>
		</div>
		<?php
		if ($academic_period == null) {
			echo "<div style='background-color:white;padding:15px;'><center><img src='".theme::$icons_16["warning"]."' style='vertical-align:bottom'/> <i>No academic period defined yet.</i><center></div>";
			return;
		} 
		?>
		<div class='page_section_title shadow' style='background-color:white;flex:none'>
			Academic Period: <select id='select_academic_period' onchange="if (this.value == <?php echo $academic_period_id;?>) return; location.href='?period='+this.value;">
			<?php
			foreach ($periods as $period) {
				echo "<option value='".$period["id"]."'";
				if ($period["id"] == $academic_period_id) echo " selected='selected'";
				$year = $this->getAcademicYear($period["year"], $years);
				echo ">Academic Year ".toHTML($year["name"]).", ".toHTML($period["name"]);
				if ($period["id"] == $current_academic_period["id"]) echo " (current)";
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
		<div style="flex:1 1 auto;display:flex;flex-direction:row">
		<div style="flex:1 1 auto;overflow:auto;">
		<?php 
		$batch_periods_ids = array();
		$all_groups = array();
		$periods_js = array();
		if ($academic_period <> null) {
			$batch_periods = PNApplication::$instance->curriculum->getBatchPeriodsForAcademicPeriods(array($academic_period_id), true);
			foreach ($batch_periods as $bp) {
				array_push($batch_periods_ids, $bp["id"]);
				echo "<div style='background-color:white;margin-left:5px;' class='section'>";
				echo "<div class='page_section_title'>";
				echo "Batch ".toHTML($bp["batch_name"]).", ".toHTML($bp["name"]);
				echo "</div>";
				$bp_subjects = PNApplication::$instance->curriculum->getSubjects($bp["batch"], $bp["id"]);
				$bp_subjects_ids = array();
				foreach ($bp_subjects as $s) array_push($bp_subjects_ids, $s["id"]);
				if (count($bp_subjects_ids) > 0)
					$bp_subjects_teaching = SQLQuery::create()->select("SubjectTeaching")->whereIn("SubjectTeaching","subject",$bp_subjects_ids)->execute();
				else
					$bp_subjects_teaching = array();
				$bp_subjects_teaching_ids = array();
				foreach ($bp_subjects_teaching as $s) array_push($bp_subjects_teaching_ids, $s["id"]);
				if (count($bp_subjects_teaching_ids) > 0) {
					$teaching_groups = SQLQuery::create()->select("SubjectTeachingGroups")->whereIn("SubjectTeachingGroups","subject_teaching",$bp_subjects_teaching_ids)->execute();
					$teachers_assignments = SQLQuery::create()->select("TeacherAssignment")->whereIn("TeacherAssignment","subject_teaching",$bp_subjects_teaching_ids)->execute();
				} else {
					$teaching_groups = array();
					$teachers_assignments = array();
				}
				if (count($bp_subjects) == 0)
					echo "<i>No subject defined for this period</i>";
				else {
					$spes = array();
					foreach ($bp_subjects as $s) {
						if ($s["period"] <> $bp["id"]) continue;
						if (!in_array($s["specialization"], $spes))
							array_push($spes, $s["specialization"]);
					}
					global $specializations;
					$specializations = PNApplication::$instance->curriculum->getSpecializations();
					usort($spes, function($s1,$s2) {
						if ($s1 == null) return -1;
						if ($s2 == null) return 1;
						global $specializations;
						$sn1 = "";
						foreach ($specializations as $s) if ($s["id"] == $s1) { $sn1 = $s["name"]; break; }
						$sn2 = "";
						foreach ($specializations as $s) if ($s["id"] == $s2) { $sn2 = $s["name"]; break; }
						return strcasecmp($sn1, $sn2);
					});
					foreach ($spes as $spe_id) {
						if ($spe_id <> null)
							echo "<div class='page_section_title2'><img src='/static/curriculum/curriculum_16.png'/> Specialization ".toHTML($this->getSpecializationName($spe_id))."</div>";
						$is_common_to_all_spes = count($spes) > 1 && $spe_id == null;
						// get subjects for this specialization
						$subjects = array();
						foreach ($bp_subjects as $s) if ($s["specialization"] == $spe_id) array_push($subjects, $s);
						// get groups for this period and spe
						$groups = PNApplication::$instance->students_groups->getGroups(null, $bp["id"], $is_common_to_all_spes ? false : $spe_id);
						$classes = array();
						foreach ($groups as $g) {
							if ($g["type"] == 1) array_push($classes, $g);
							$found = false;
							foreach ($all_groups as $gg) if ($gg["id"] == $g["id"]) { $found = true; break; }
							if (!$found) array_push($all_groups, $g);
						}
						if (count($classes) == 0 && count($groups) > 0) {
							echo "<div><img src='".theme::$icons_16["warning"]."' style='vertical-align:bottom'/> <i>No class defined for this period</i></div>";
						}
						if (count($groups) == 0) {
							if (!$is_common_to_all_spes)
								echo "<i>No class or group defined for this period</i>";
						} else if (count($subjects) == 0) {
							if (!$is_common_to_all_spes)
								echo "<i>No subject defined for this period</i>";
						} else {
							if ($is_common_to_all_spes)
								echo "<div class='page_section_title2'><img src='/static/curriculum/curriculum_16.png'/> Subjects common to all specializations </div>";
							$id = $this->generateID();
							echo "<table class='subjects_table'><tbody id='$id'>";
							echo "<tr><th>Code</th><th>Subject Description</th><th>Hours</th><th>Class/Group</th><th>Teacher(s) assigned</th></tr>";
							echo "</tbody></table>";
							$json = "[";
							$first = true;
							foreach ($subjects as $s) {
								if ($first) $first = false; else $json .= ",";
								$json .= "{";
								$json .= "subject:".CurriculumJSON::SubjectJSON($s);
								$json .= ",grouping:[";
								$first_grouping = true;
								foreach ($bp_subjects_teaching as $st) {
									if ($st["subject"] <> $s["id"]) continue;
									if ($first_grouping) $first_grouping = false; else $json .= ",";
									$json .= "{";
									$json .= "teaching_id:".$st["id"];
									$json .= ",groups:[";
									$first_group = true;
									foreach ($teaching_groups as $tg) {
										if ($tg["subject_teaching"] <> $st["id"]) continue;
										if ($first_group) $first_group = false; else $json .= ",";
										$json .= json_encode($tg["group"]);
									}
									$json .= "]";
									$json .= ",teachers:[";
									$first_teacher = true;
									foreach ($teachers_assignments as $ta) {
										if ($ta["subject_teaching"] <> $st["id"]) continue;
										if ($first_teacher) $first_teacher = false; else $json .= ",";
										$json .= "{";
										$json .= "people_id:".$ta["people"];
										$json .= ",hours:".($ta["hours"] == null ? "null" : $ta["hours"]);
										$json .= ",hours_type:".json_encode($ta["hours_type"]);
										$json .= "}";
									}
									$json .= "]";
									$json .= "}";
								}
								$json .= "]";
								$json .= "}";
							}
							$json .= "]";
							$periods_js[$id] = $json;
						}
					}
				}
				echo "</div>";
			}
		}
		?>
		</div>
		<div id='teachers_section' style='display:inline-block;flex:1 0 auto;min-width:0px;background-color:white;overflow-y:auto;overflow-x:hidden;' icon='/static/teaching/teacher_16.png' title='Available Teachers' collapsable='false'>
			<div id='teachers_list' style='background-color:white;margin-right:15px;'>
				<?php $teachers_table_id = $this->generateID();?>
				<table class='teachers_table'><tbody id='<?php echo $teachers_table_id;?>'>
					<tr><th>Teacher</th><th>Hours</th></tr>
				</tbody></table>
			</div>
		</div>
		</div>
		</div>
		<?php
		if (PNApplication::$instance->help->isShown("teachers_assignments")) {
			$help_div_id = PNApplication::$instance->help->startHelp("teachers_assignments", $this, "left", "bottom", false);
			if (!$can_edit) {
				echo "This screen displays the list of subjects for the ";
				PNApplication::$instance->help->spanArrow($this, "selected academic period", "#select_academic_period");
				echo ",<br/>";
				echo "with for each subject which teacher is assigned.<br/>";
				echo "<br/>";
				echo "If you don't see any subject, you need first to specify the list of subjects in the curriculum page.<br/>";
				echo "If you don't see any available teacher, you need first to add teachers on the teachers page.<br/>";
				echo "<br/>";
				echo "On the right side is the list of available teachers during this academic period.";
				echo "<br/>";
				if (PNApplication::$instance->user_management->has_right("edit_curriculum")) {
					echo "<br/>To modify teachers' assignments, you need first to click on the ";
					PNApplication::$instance->help->spanArrow($this, "edit button", "#edit_teachers_assignments_button");
					echo " (try now to see<br/>";
					echo "the help on how to assign and unassign teachers)";
				}
			} else {
				echo "<div style='max-height:200px;max-width:450px;overflow:auto'>";
				echo "Great ! Now you can modify the teachers' assignments.<br/>";
				echo "<br/>";
				echo "First, for each subject, you need to specify if several classes are following the subject together ";
				echo "(for example, a lecture subject may be given to 2 classes at the same time, while laboratory is ";
				echo "given for each class separately).<br/>";
				echo "<br/>";
				echo "To assign a teacher to a subject/class, you can<ul>";
				echo "<li>Use the <img src='".theme::$icons_10["add"]."'/> button</li>";
				echo "<li>Or drag and drop a teacher, from the available teachers list, to a subject</li>";
				echo "</ul>";
				echo "<br/>";
				echo "Once a teacher is assigned<ul>";
				echo "<li>Use the <img src='".theme::$icons_10["remove"]."'/> button to unassign him/her</li>";
				echo "<li>Use the <img src='".theme::$icons_10["time"]."'/> button to specify the number or hours (only if several teachers are assigned to the same subject/class)<br/>";
				echo "Indeed, it is possible to assign several teachers for the same subject and class. In this case, ";
				echo "you can specify for each teacher the number of hours. This will be used to calculate correctly ";
				echo "the teachers' load. However, you cannot exceed the total number of hours of the subject (This is ";
				echo "not supported to have 2 teachers at the same time in the same class).";
				echo "</li>";
				echo "</ul>";
				echo "</div>";
			}
			PNApplication::$instance->help->endHelp($help_div_id, "teachers_assignments");
		} else
			PNApplication::$instance->help->availableHelp("teachers_assignments");
		?>
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
		var academic_period = <?php echo CurriculumJSON::AcademicPeriodJSONFromDB($academic_period);?>;
		var nb_weeks = (academic_period.weeks-academic_period.weeks_break);
		var categories = <?php echo CurriculumJSON::getSubjectsCategories();?>;
		var group_types = <?php echo StudentsGroupsJSON::getGroupsTypes(); ?>;
		var all_groups = <?php echo json_encode($all_groups); ?>;
		var editing = <?php echo json_encode($can_edit);?>;
		var teachers = <?php echo PeopleJSON::Peoples($teachers);?>;
		var teachers_section = sectionFromHTML('teachers_section');

		teachers.sort(function(p1,p2) {
			return (p1.last_name+' '+p1.first_name).localeCompare(p2.last_name+' '+p2.first_name);
		});
		function getTeacher(people_id) {
			for (var i = 0; i < teachers.length; ++i)
				if (teachers[i].id == people_id) return teachers[i];
		}
		
		function getGroupType(id) {
			for (var i = 0; i < group_types.length; ++i)
				if (group_types[i].id == id) return group_types[i];
			return null;
		}

		function getGroup(group_id) {
			for (var i = 0; i < all_groups.length; ++i)
				if (all_groups[i].id == group_id) return all_groups[i];
			return null;
		}

		function getGroupsForPeriod(period_id) {
			var groups = [];
			for (var i = 0; i < all_groups.length; ++i) if (all_groups[i].period == period_id) groups.push(all_groups[i]);
			return groups;
		}
		function getGroupsForPeriodAndType(period_id, type_id, spe_id) {
			var groups = [];
			var gt = getGroupType(type_id);
			for (var i = 0; i < all_groups.length; ++i) if (all_groups[i].period == period_id && all_groups[i].type == type_id && (spe_id == null || !gt.specialization_dependent || all_groups[i].specialization == spe_id)) groups.push(all_groups[i]);
			return groups;
		}
		function getGroupsByType(groups) {
			var types = {};
			for (var i = 0; i < groups.length; ++i)
				if (typeof types[groups[i].type] == 'undefined')
					types[groups[i].type] = [groups[i]];
				else
					types[groups[i].type].push(groups[i]);
			return types;
		}
		function getGroupsTree(groups) {
			roots = [];
			buildGroupTreeLevel(roots, null, groups);
			return roots;
		}
		function buildGroupTreeLevel(list, parent_id, groups) {
			for (var i = 0; i < groups.length; i++) {
				if (groups[i].parent != parent_id) continue;
				var g = groups[i];
				groups.splice(i,1);
				i--;
				g.sub_groups = [];
				list.push(g);
			}
			if (groups.length > 0)
				for (var i = list.length-1; i >= 0; i--)
					buildGroupTreeLevel(list[i].sub_groups, list[i].id, groups);
		}
		function isParentGroup(parent_id, group) {
			if (group.id == parent_id) return false;
			if (group.parent == parent_id) return true;
			if (group.parent == null) return false;
			return isParentGroup(parent_id, getGroup(group.parent));
		}

		function getGroupsHTML(groups_ids) {
			var span = document.createElement("SPAN");
			for (var i = 0; i < groups_ids.length; ++i) {
				var group = getGroup(groups_ids[i]);
				var s = document.createElement("SPAN");
				s.style.whiteSpace = "nowrap";
				if (i > 0) span.appendChild(document.createTextNode(" + "));
				else {
					var group_type = getGroupType(group.type);
					s.appendChild(document.createTextNode(group_type.name+" "));
				}
				s.appendChild(document.createTextNode(group.name));
				span.appendChild(s);
			}
			return span;
		};

		function hoursString(hours) {
			var h = Math.floor(hours);
			var s = h+"h";
			var min = Math.floor((hours-h)*60);
			if (min > 0) s += _2digits(min);
			return s;
		}

		var periods = [];
		function getTeacherAssignments(people_id) {
			var list = [];
			for (var i = 0; i < periods.length; ++i)
				for (var j = 0; j < periods[i].subjects.length; ++j)
					for (var k = 0; k < periods[i].subjects[j].groupings.length; ++k)
						for (var l = 0; l < periods[i].subjects[j].groupings[k].teachers.length; ++l)
							if (periods[i].subjects[j].groupings[k].teachers[l].people_id == people_id)
								list.push({
									subject: periods[i].subjects[j].subject,
									grouping: periods[i].subjects[j].groupings[k],
									hours: periods[i].subjects[j].groupings[k].teachers[l].hours,
									hours_type: periods[i].subjects[j].groupings[k].teachers[l].hours_type
								});
			return list;
		}

		function PeriodSubjects(table_id, subjects) {
			this.subjects = [];
			var table = document.getElementById(table_id);
			var tr, td;
			for (var i = 0; i < subjects.length; ++i) {
				table.appendChild(tr = document.createElement("TR"));
				tr.appendChild(td = document.createElement("TD"));
				td.style.whiteSpace = 'nowrap';
				td.appendChild(document.createTextNode(subjects[i].subject.code));
				tr.appendChild(td = document.createElement("TD"));
				td.appendChild(document.createTextNode(subjects[i].subject.name));
				tr.appendChild(td = document.createElement("TD"));
				td.style.fontSize = "8pt";
				var total = 0;
				if (subjects[i].subject.hours_type == "Per week")
					total = subjects[i].subject.hours*nb_weeks;
				else
					total = subjects[i].subject.hours;
				if (!total)
					td.innerHTML = "<i>Not specified</i>";
				else
					td.appendChild(document.createTextNode(hoursString(total/nb_weeks)+"/week x "+nb_weeks+" = "+total+"h"));
				this.subjects.push(new SubjectGroupings(tr, subjects[i].grouping, subjects[i].subject));
			}
		}
		
		function SubjectGroupings(main_tr, groupings, subject) {
			this.main_tr = main_tr;
			this.groupings = groupings;
			this.subject = subject;
			this.sub_tr = [];
			this.update = function() {
				// remove sub_tr
				for (var i = 0; i < this.sub_tr.length; ++i) this.sub_tr[i].parentNode.removeChild(this.sub_tr[i]);
				this.sub_tr = [];
				// reset rowSpan
				var rowspan = editing ? this.groupings.length+1 : (this.groupings.length == 0 ? 1 : this.groupings.length);
				this.main_tr.childNodes[0].rowSpan = rowspan; // code
				this.main_tr.childNodes[1].rowSpan = rowspan; // name
				this.main_tr.childNodes[2].rowSpan = rowspan; // hours
				// remove first row
				while (this.main_tr.childNodes.length > 3) this.main_tr.removeChild(this.main_tr.childNodes[3]);
				// create each row
				var next = this.main_tr.nextSibling;
				for (var i = 0; i < this.groupings.length; ++i) {
					var tr;
					if (i == 0) tr = this.main_tr;
					else {
						tr = document.createElement("TR");
						if (next) this.main_tr.parentNode.insertBefore(tr, next);
						else this.main_tr.parentNode.appendChild(tr);
						this.sub_tr.push(tr);
					}
					this.createGroupingRow(tr, this.groupings[i], i==0, i == this.groupings.length-1 && !editing); 
				}
				if (this.groupings.length == 0 || editing) {
					var td = document.createElement("TD");
					td.colSpan = 2;
					if (this.groupings.length > 0) td.style.borderTop = "none";
					if (this.groupings.length == 0)
						td.innerHTML = "<span style='color:red;font-style:italic'>No class planned yet</span>";
					if (editing) {
						// display if not all groups are already used
						var all_used;
						if (this.groupings.length == 0) all_used = false;
						else {
							var type_id = getGroup(this.groupings[0].groups[0]).type;
							var group_type = getGroupType(type_id);
							var groups = getGroupsForPeriodAndType(subject.period_id, type_id, subject.specialization_id);
							var used = [];
							for (var i = 0; i < this.groupings.length; ++i)
								for (var j = 0; j < this.groupings[i].groups.length; ++j)
									used.push(this.groupings[i].groups[j]);
							if (group_type.sub_groups) {
								for (var i = 0; i < groups.length; ++i) {
									if (used.contains(groups[i].id)) continue;
									var found = false;
									for (var j = 0; j < used.length && !found; ++j)
										if (isParentGroup(used[j], groups[i])) found = true;
									if (found) { used.push(groups[i].id); continue; }
								}
								for (var i = 0; i < groups.length; ++i) {
									if (used.contains(groups[i].id)) continue;
									var found = false;
									for (var j = 0; j < used.length && !found; ++j)
										if (isParentGroup(groups[i].id, getGroup(used[j]))) found = true;
									if (found) { used.push(groups[i].id); continue; }
								}
							}
							if (used.length == groups.length) all_used = true;
						}
						if (!all_used) {
							if (this.groupings.length > 0)
								td.innerHTML = "<span style='color:darkorange;font-style:italic'>Some groups are not planned yet</span>";
							this.plan_button = document.createElement("BUTTON");
							this.plan_button.style.marginLeft = "5px";
							this.plan_button.innerHTML = "<img src='"+theme.icons_10.add+"'/>";
							this.plan_button.className = "flat small_icon";
							this.plan_button.title = "Plan a new class (one or more groups following this subject together)";
							this.plan_button.t = this;
							this.plan_button.onclick = function() {
								this.t.showGroupsMenu(null, this);
							};
							this.plan_button.ondomremoved(function(e){e.t=null;});
							td.appendChild(this.plan_button);
						}
					}
					var tr;
					if (this.groupings.length == 0) tr = this.main_tr;
					else {
						tr = document.createElement("TR");
						if (next) this.main_tr.parentNode.insertBefore(tr, next);
						else this.main_tr.parentNode.appendChild(tr);
						this.sub_tr.push(tr);
					}
					tr.appendChild(td);
				}
			};

			this.showGroupsMenu = function(grouping, button) {
				var t=this;
				require("mini_popup.js",function() {
					var menu = new mini_popup("Which groups ?");
					var checkboxes = [];
					if (t.groupings.length == 0) {
						for (var i = 0; i < group_types.length; ++i)
							t.fillGroupsMenu(menu, group_types[i], null, checkboxes);
					} else {
						var group = getGroup(t.groupings[0].groups[0]);
						var type = getGroupType(group.type);
						t.fillGroupsMenu(menu, type, grouping, checkboxes);
					}
					t.refreshGroupsMenu(checkboxes, grouping);
					menu.addOkButton(function() {
						var groups_ids = [];
						for (var i = 0; i < checkboxes.length; ++i) if (checkboxes[i].checked) groups_ids.push(checkboxes[i].group.id);
						if (groups_ids.length == 0) {
							// no more group
							if (grouping == null) return true; // nothing to do
							t.removeGrouping(grouping);
							return true;
						}
						if (grouping == null) {
							t.createGroupingFromGroups(groups_ids);
							return true;
						}
						t.setGroupingGroups(grouping, groups_ids);
						return true;
					});
					menu.showBelowElement(button);
				});
			};
			this.fillGroupsMenu = function(menu, group_type, grouping, checkboxes) {
				var groups = getGroupsForPeriodAndType(subject.period_id, group_type.id, subject.specialization_id);
				if (groups.length == 0) return;
				var div = document.createElement("DIV");
				div.className = "mini_popup_section_title";
				div.appendChild(document.createTextNode(group_type.name));
				menu.content.appendChild(div);
				var roots = getGroupsTree(groups);
				this.fillGroupsMenuTree(menu, 0, roots, grouping, checkboxes);
			};
			this.fillGroupsMenuTree = function(menu, indent, nodes, grouping, checkboxes) {
				for (var i = 0; i < nodes.length; ++i) {
					var div = document.createElement("DIV");
					var cb = document.createElement("INPUT");
					cb.type = "checkbox";
					cb.style.marginRight = "3px";
					cb.style.marginBottom = "1px";
					cb.style.verticalAlign = "bottom";
					cb.group = nodes[i];
					checkboxes.push(cb);
					if (grouping) for (var j = 0; j < grouping.groups.length; ++j) if (grouping.groups[j] == nodes[i].id) cb.checked = 'checked';
					cb.t = this;
					cb.onchange = function() {
						this.t.refreshGroupsMenu(checkboxes, grouping);
					};
					cb.ondomremoved(function(e){e.t=null;e.group=null;});
					div.appendChild(cb);
					div.appendChild(document.createTextNode(nodes[i].name));
					div.style.marginLeft = indent+"px";
					menu.content.appendChild(div);
					if (nodes[i].sub_groups)
						this.fillGroupsMenuTree(menu, indent+20, nodes[i].sub_groups, grouping, checkboxes);
				}
			};
			this.refreshGroupsMenu = function(checkboxes, grouping) {
				var checked = [];
				for (var i = 0; i < checkboxes.length; ++i) if (checkboxes[i].checked) checked.push(checkboxes[i]);
				var restricted_type = null;
				if (checked.length > 0) restricted_type = checked[0].group.type;
				else if (this.groupings.length > 0) {
					for (var i = 0; i < this.groupings.length; ++i)
						if (this.groupings[i] != grouping) {
							restricted_type = getGroup(this.groupings[i].groups[0]).type;
							break;
						}
				}
				for (var i = 0; i < checkboxes.length; ++i) {
					var cb = checkboxes[i];
					// disabled and unchecked if not in restricted type
					if (restricted_type != null && cb.group.type != restricted_type) {
						cb.disabled = 'disabled';
						cb.checked = '';
						cb.parentNode.style.color = '#808080';
						continue;
					}
					// disabled if already used by another grouping
					var found = false;
					for (var j = 0; j < this.groupings.length; ++j)
						if (this.groupings[j] != grouping) {
							for (var k = 0; k < this.groupings[j].groups.length; ++k)
								if (this.groupings[j].groups[k] == cb.group.id) { found = true; break; }
							if (found) break;
						}
					if (found) {
						cb.disabled = 'disabled';
						cb.checked = '';
						cb.parentNode.style.color = '#808080';
						continue;
					}
					// disabled if already partially included by another group (a child)
					found = false;
					for (var j = 0; j < checked.length && !found; ++j) if (isParentGroup(checkboxes[i].group.id, checked[j].group)) found = true;
					for (var j = 0; j < this.groupings.length && !found; ++j)
						if (this.groupings[j] != grouping)
							for(var k = 0; k < this.groupings[j].groups.length && !found; ++k)
								if (isParentGroup(checkboxes[i].group.id, getGroup(this.groupings[j].groups[k])))
									found = true;
					if (found) {
						cb.disabled = 'disabled';
						cb.checked = '';
						cb.parentNode.style.color = '#808080';
						continue;
					}
					// disabled if already included by a parent group
					found = false;
					for (var j = 0; j < checked.length && !found; ++j) if (isParentGroup(checked[j].group.id, checkboxes[i].group)) found = true;
					for (var j = 0; j < this.groupings.length && !found; ++j)
						if (this.groupings[j] != grouping)
							for(var k = 0; k < this.groupings[j].groups.length && !found; ++k)
								if (isParentGroup(this.groupings[j].groups[k], checkboxes[i].group))
									found = true;
					if (found) {
						cb.disabled = 'disabled';
						cb.checked = '';
						cb.parentNode.style.color = '#808080';
						continue;
					}
					// enabled
					cb.disabled = '';
					cb.parentNode.style.color = 'black';
				}
			};

			this.createGroupingRow = function(tr, grouping, first, last) {
				var td;
				tr.appendChild(td = document.createElement("TD"));
				var span = getGroupsHTML(grouping.groups);
				td.appendChild(span);
				if (!last) td.style.borderBottom = "none";
				if (!first) td.style.borderTop = "none";
				<?php if ($can_edit) { ?>
				span.style.cursor = "pointer";
				span.onmouseover = function() { this.style.textDecoration = 'underline'; };
				span.onmouseout = function() { this.style.textDecoration = ''; };
				span.t = this;
				span.onclick = function() {
					this.t.showGroupsMenu(grouping,this);
				};
				span.ondomremoved(function(e){e.t=null;});
				var remove_button = document.createElement("BUTTON");
				remove_button.className = "flat small_icon";
				remove_button.innerHTML = "<img src='"+theme.icons_10.remove+"'/>";
				remove_button.title = "Cancel groups and teachers assignment";
				remove_button.style.marginLeft = "3px";
				remove_button.t = this;
				remove_button.onclick = function() {
					this.t.removeGrouping(grouping);
				};
				remove_button.ondomremoved(function(e){e.t=null;});
				td.appendChild(remove_button);
				<?php } ?>
				tr.appendChild(td = document.createElement("TD"));
				if (!last) td.style.borderBottom = "none";
				if (!first) td.style.borderTop = "none";
				if (grouping.teachers.length == 0) {
					td.innerHTML = "<i style='color:red'>No teacher</i>";
					<?php if ($can_edit) { ?>
					var assign = document.createElement("BUTTON");
					assign.className = 'flat small_icon';
					assign.innerHTML = "<img src='"+theme.icons_10.add+"'/>";
					assign.title = "Assign a teacher";
					assign.style.marginLeft = "3px";
					td.appendChild(assign);
					assign.t = this;
					assign.onclick = function() {
						var button=this;
						var t=button.t;
						require("context_menu.js",function() {
							var menu = new context_menu();
							menu.addTitleItem(null, "Assign Teacher");
							for (var i = 0; i < teachers.length; ++i)
								menu.addIconItem(null, teachers[i].last_name+" "+teachers[i].first_name, function(ev,teacher_id) {
									var lock = lock_screen();
									service.json("teaching","assign_teacher",{people_id:teacher_id,subject_teaching_id:grouping.teaching_id},function(res) {
										unlock_screen(lock);
										if (!res) return;
										grouping.teachers.push({people_id:teacher_id,hours:null,hours_type:null});
										t.update();
										updateTeacherRow(teacher_id);
									});
								}, teachers[i].id);
							menu.showBelowElement(button);
						});
					};
					assign.ondomremoved(function(e){e.t=null;});
					<?php } ?>
				} else {
					var total_subject_hours = subject.hours;
					if (subject.hours_type == "Per week") total_subject_hours *= nb_weeks;
					var remaining_period = total_subject_hours;
					for (var i = 0; i < grouping.teachers.length; ++i) {
						if (i > 0) {
							td.appendChild(document.createElement("BR"));
							td.appendChild(document.createTextNode(" + "));
						}
						var teacher = getTeacher(grouping.teachers[i].people_id);
						var span_teacher_name = document.createElement("SPAN");
						span_teacher_name.appendChild(document.createTextNode(teacher.last_name+" "+teacher.first_name));
						span_teacher_name.style.whiteSpace = 'nowrap';
						td.appendChild(span_teacher_name);
						if (grouping.teachers[i].hours != null) {
							var teacher_hours_total = grouping.teachers[i].hours;
							if (grouping.teachers[i].hours_type == "Per week") teacher_hours_total *= nb_weeks;
							if (teacher_hours_total < total_subject_hours)
								td.appendChild(document.createTextNode("("+grouping.teachers[i].hours+"h"+(grouping.teachers[i].hours_type == "Per week" ? "/week" : "")+")"));
							remaining_period -= teacher_hours_total;
						} else
							remaining_period = 0;
						<?php if ($can_edit) { ?>
						var schedule = document.createElement("BUTTON");
						schedule.className = 'flat small_icon';
						schedule.innerHTML = "<img src='"+theme.icons_10.time+"'/>";
						schedule.title = "Change number of hours for this teacher";
						schedule.assignment = grouping.teachers[i];
						schedule.t=this;
						schedule.ondomremoved(function(e){e.t=null;e.assignment=null;});
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
								for (var i = 0; i < grouping.teachers.length; ++i) {
									if (grouping.teachers[i].people_id == tt.assignment.people_id) continue; // this is the current teacher
									if (grouping.teachers[i].hours_type == "Per week")
										assigned_period += grouping.teachers[i].hours*nb_weeks;
									else
										assigned_period += grouping.teachers[i].hours;
								}
								var content = document.createElement("DIV");
								content.style.padding = "10px";
								content.innerHTML = "Number of teaching hours for ";
								var teacher = getTeacher(tt.assignment.people_id);
								content.appendChild(document.createTextNode(teacher.last_name+" "+teacher.first_name+":"));
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
									service.json("teaching","assign_teacher",{people_id:tt.assignment.people_id,subject_teaching_id:grouping.teaching_id,hours:field.getCurrentData(),hours_type:select.value},function(res) {
										updateTeacherRow(tt.assignment.people_id);
										tt.t.update();
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
						unassign.assignment = grouping.teachers[i];
						td.appendChild(unassign);
						unassign.t=this;
						unassign.onclick = function() {
							var lock = lock_screen();
							var tt=this;
							service.json("teaching","unassign_teacher",{people_id:tt.assignment.people_id,subject_teaching_id:grouping.teaching_id},function(res) {
								unlock_screen(lock);
								if (!res) return;
								grouping.teachers.removeUnique(tt.assignment);
								updateTeacherRow(tt.assignment.people_id);
								tt.t.update();
							});
						};
						unassign.ondomremoved(function(e){e.t=null;e.assignment=null;});
						<?php } ?>
					}
					if (remaining_period > 0) {
						td.appendChild(document.createElement("BR"));
						td.appendChild(document.createTextNode(" + "));
						var span = document.createElement("SPAN");
						span.style.fontStyle = "italic";
						span.style.color = "#808080";
						span.style.whiteSpace = "nowrap";
						if (subject.hours_type == "Per week") {
							var hs = (remaining_period/nb_weeks).toFixed(2);
							if (hs.substring(hs.length-3) == ".00") hs = Math.floor(remaining_period/nb_weeks); 
							span.innerHTML = hs+"h/week remaining";
						} else
							span.innerHTML = remaining_period+"h remaining";
						td.appendChild(span);
						<?php if ($can_edit) { ?>
						var assign = document.createElement("BUTTON");
						assign.className = 'flat small_icon';
						assign.innerHTML = "<img src='"+theme.icons_10.add+"'/>";
						assign.title = "Assign a teacher for remaining hours";
						td.appendChild(assign);
						assign.t=this;
						assign.ondomremoved(function(e){e.t=null;});
						assign.onclick = function() {
							var button=this;
							var tt=this.t;
							require("context_menu.js",function() {
								var menu = new context_menu();
								menu.addTitleItem(null, "Assign Teacher");
								for (var i = 0; i < teachers.length; ++i) {
									var found = false;
									for (var j = 0; j < grouping.teachers.length; ++j) if (grouping.teachers[j].people_id == teachers[i].id) { found = true; break; }
									if (found) continue;
									menu.addIconItem(null, teachers[i].last_name+" "+teachers[i].first_name, function(ev,teacher_id) {
										var lock = lock_screen();
										var hours = remaining_period;
										if (subject.hours_type == "Per week") hours /= nb_weeks;
										service.json("teaching","assign_teacher",{people_id:teacher_id,subject_teaching_id:grouping.teaching_id,hours:hours,hours_type:subject.hours_type},function(res) {
											unlock_screen(lock);
											if (!res) return;
											grouping.teachers.push({people_id:teacher_id,hours:hours,hours_type:subject.hours_type});
											tt.update();
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
				td.t=this;
				td.ondomremoved(function(e){e.t=null;});
				td.ondrop = function(event) {
					var t=this.t;
					this.style.backgroundColor = "";
					this.style.outline = "";
					var teacher_id = event.dataTransfer.getData("teacher");
					for (var i = 0; i < grouping.teachers.length; ++i) if (grouping.teachers[i].people_id == teacher_id) return; // same teacher
					var lock = lock_screen();
					var remaining_hours_period = subject.hours;
					if (subject.hours_type == "Per week")
						remaining_hours_period *= nb_weeks;
					var total_hours_period = remaining_hours_period;
					for (var i = 0; i < grouping.teachers.length; ++i) {
						if (grouping.teachers[i].hours == null) { remaining_hours_period = 0; break; }
						if (grouping.teachers[i].hours_type == "Per period")
							remaining_hours_period -= grouping.teachers[i].hours;
						else
							remaining_hours_period -= grouping.teachers[i].hours*nb_weeks;
					}
					var assign = function() {
						var data = {people_id:teacher_id,subject_teaching_id:grouping.teaching_id};
						var assignment = {people_id:teacher_id};
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
						service.json("teaching","assign_teacher",data,function(res) {
							if (res)
								grouping.teachers.push(assignment);
							t.update();
							updateTeacherRow(teacher_id);
							unlock_screen(lock);
						});
					};					
					if (grouping.teachers.length > 0 && remaining_hours_period == 0) {
						var nb_done = 0;
						var done = function() {
							if (++nb_done < grouping.teachers.length) return;
							while (grouping.teachers.length > 0) {
								var teacher_id = grouping.teachers[0].people_id;
								grouping.teachers.splice(0,1);
								updateTeacherRow(teacher_id);
							}
							assign();
						};
						for (var i = 0; i < grouping.teachers.length; ++i)
							service.json("teaching","unassign_teacher",{people_id:grouping.teachers[i].people_id,subject_teaching_id:grouping.teaching_id},function(res) {
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

			this.removeGrouping = function(grouping) {
				var lock = lock_screen();
				var t=this;
				service.json("data_model","remove_row",{table:"SubjectTeaching",row_key:grouping.teaching_id},function(res) {
					unlock_screen(lock);
					if (!res) return;
					t.groupings.removeUnique(grouping);
					t.update();
					for (var i = 0; i < grouping.teachers.length; ++i)
						updateTeacherRow(grouping.teachers[i].people_id);
				});
			};

			this.createGroupingFromGroups = function(groups_ids) {
				var lock = lock_screen();
				var t=this;
				service.json("teaching","create_teaching_group",{subject:subject.id,groups:groups_ids},function(res) {
					unlock_screen(lock);
					if (!res) return;
					var grouping = {
						teaching_id: res.id,
						groups: groups_ids,
						teachers: []
					};
					t.groupings.push(grouping);
					t.update();
				});
			};

			this.setGroupingGroups = function(grouping, groups_ids) {
				var lock = lock_screen();
				var t=this;
				service.json("teaching","update_teaching_groups",{subject_teaching:grouping.teaching_id,groups:groups_ids},function(res) {
					unlock_screen(lock);
					if (!res) return;
					grouping.groups = groups_ids;
					t.update();
					for (var i = 0; i < grouping.teachers.length; ++i)
						updateTeacherRow(grouping.teachers[i].people_id);
				});
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
			} else
				for (var i = 0; i < teachers.length; ++i)
					teachers_rows.push(new TeacherRow(table, teachers[i]));
			this.addTeacher = function(teacher) {
				if (teachers.length == 1) {
					// first one, remove the 'No teacher'
					table.removeChild(table.childNodes[table.childNodes.length-1]);
				}
				teachers_rows.push(new TeacherRow(table, teacher, true));				
			};
		}
		function TeacherRow(table, teacher, is_new) {
			this.teacher = teacher;
			this.tr = document.createElement("TR");
			if (!is_new) table.appendChild(this.tr);
			else {
				// newly created teacher, we need to put it in alphabetical order
				var i;
				for (i = 0; i < teachers_rows.length; ++i) {
					var ln = teachers_rows[i].teacher.last_name.localeCompare(teacher.last_name);
					if (ln == 0) ln = teachers_rows[i].teacher.first_name.localeCompare(teacher.first_name);
					if (ln > 0) break;
				}
				if (i == teachers_rows.length)
					table.appendChild(this.tr);
				else
					table.insertBefore(this.tr, teachers_rows[i].tr);
			}
			var td_name = document.createElement("TD"); this.tr.appendChild(td_name);
			var span = document.createElement("SPAN"); td_name.appendChild(span);
			span.appendChild(document.createTextNode(teacher.last_name+" "+teacher.first_name));
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
				window.top.popupFrame('/static/people/profile_16.png','Profile','/dynamic/people/page/profile?people='+this.teacher.id,null,95,95);
			};
			var td_hours = document.createElement("TD"); this.tr.appendChild(td_hours);
			td_hours.style.whiteSpace = "nowrap";
			this.update = function() {
				var total = 0;
				var list = getTeacherAssignments(teacher.id);
				for (var i = 0; i < list.length; ++i) {
					var ta = list[i];
					if (!ta.hours) {
						if (ta.subject.hours_type == "Per week") total += (ta.subject.hours*nb_weeks);
						else total += ta.subject.hours;
					} else {
						if (ta.hours_type == "Per week") total += ta.hours*nb_weeks;
						else total += ta.hours;
					}
				}
				td_hours.innerHTML = "<span style='font-size:8pt'><span style='font-weight:bold'>"+hoursString(total/nb_weeks)+"</span>/week x "+nb_weeks+" = <span style='font-weight:bold'>"+total+"h</span></span>";
				layout.changed(td_hours);
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

		<?php
		foreach ($periods_js as $id=>$json)
			echo "periods.push(new PeriodSubjects('$id',$json));\n"; 
		?>
		var teachers_table = new TeachersTable('<?php echo $teachers_table_id;?>');

		<?php if (PNApplication::$instance->user_management->has_right("edit_curriculum")) { ?>
		var add_teacher_button = document.createElement("BUTTON");
		add_teacher_button.className = "flat icon";
		add_teacher_button.innerHTML = "<img src='"+theme.build_icon("/static/teaching/teacher_16.png",theme.icons_10.add)+"'/>";
		add_teacher_button.title = "Create a new teacher";
		add_teacher_button.style.marginRight = "3px";
		teachers_section.addToolRight(add_teacher_button);
		add_teacher_button.onclick = function() {
			require("popup_window.js", function() {
				var p = new popup_window("New Teacher", theme.build_icon("/static/teaching/teacher_16.png",theme.icons_10.add), "");
				var frame = p.setContentFrame("/dynamic/people/page/popup_new_person?type=teacher&ondone=teacher_created");
				frame.teacher_created = function(people_id) {
					service.json("people","get_peoples",{ids:[people_id]},function(res) {
						var teacher = res[0];
						teachers.push(teacher);
						teachers_table.addTeacher(teacher);
					});
				};
				p.show();
			});
		};
		<?php } ?>
		
		window.help_display_ready = true;
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
	
	/** Search the name of the specialization
	 * @param integer $spe_id specialization id
	 * @return string the name
	 */
	private function getSpecializationName($spe_id) {
		global $specializations;
		foreach ($specializations as $s) if ($s["id"] == $spe_id) return $s["name"];
	}
	
}
?>