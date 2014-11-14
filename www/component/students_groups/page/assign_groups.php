<?php 
class page_assign_groups extends Page {
	
	public function getRequiredRights() { return array("manage_batches"); }
	
	public function execute() {
		/* different cases:
		 *  - on a period which is not specialized: all students can be assigned in any group
		 *  - on a period which is specialized: only students assign to a given specializtion can be assign to groups of this specialization
		 *  - on a class which is not specialized: all students non-assigned to any group during this period can be assigned
		 *  - on a class which is specialized: all students of this specialization and not yet assign to a group during this period can be assigned
		 */
		$period_id = @$_GET["period"];
		$sections = array();
		if ($period_id <> null) {
			// we are on a period
			$group_type_id = $_GET["group_type"];
			$group_type = PNApplication::$instance->students_groups->getGroupType($group_type_id);
			// get all students for this period
			$q_students = PNApplication::$instance->students->getStudentsQueryForBatchPeriod($period_id, true, false, false, false);
			$q_students->orderBy("People", "last_name");
			$q_students->orderBy("People", "first_name");
			$students = $q_students->execute();
			$students_ids = array();
			foreach ($students as $s) array_push($students_ids, $s["people"]);
			// get group assignment
			if (count($students) > 0) {
				$students_groups = SQLQuery::create()->select("StudentGroup")
					->join("StudentGroup","StudentsGroup",array("group"=>"id"))
					->whereValue("StudentsGroup","type",$group_type_id)
					->whereValue("StudentsGroup","period",$period_id)
					->whereIn("StudentGroup", "people", $students_ids)
					->field("StudentGroup", "people", "people")
					->field("StudentsGroup", "id", "group")
					->execute();
			} else
				$students_groups = array();
			foreach ($students as &$s) {
				foreach ($students_groups as $g) {
					if ($g["people"] == $s["people"]) {
						$s["group"] = $g["group"];
						break;
					}
				}
				if (!isset($s["group"])) $s["group"] = null;
			}
			// check if this period is specialized and the groups are specialization dependent
			$specializations = $group_type["specialization_dependent"] == 1 ? PNApplication::$instance->curriculum->getBatchPeriodSpecializationsWithName($period_id) : array();
			$groups = PNApplication::$instance->students_groups->getGroups($group_type_id, $period_id);
			$groups_tree = PNApplication::$instance->students_groups->buildGroupTree($groups);
			$groups = PNApplication::$instance->students_groups->getFinalGroupsFromTree($groups_tree);
			if (count($specializations) > 0) {
				// specialized period
				foreach ($specializations as $spe) {
					$list_students = array();
					foreach ($students as &$s) if ($s["specialization"] == $spe["id"]) array_push($list_students, $s);
					$list_groups = array();
					foreach ($groups as $g) if ($g["specialization"] == $spe["id"]) array_push($list_groups, $g);
					array_push($sections, array($spe["name"],$list_groups,$list_students));
				}
			} else {
				// not specialized period
				array_push($sections, array(null,$groups,$students));
			}
		} else {
			// we are on a group
			$group = PNApplication::$instance->students_groups->getGroup($_GET["group"]);
			$period_id = $group["period"];
			$group_type_id = $group["type"];
			$group_type = PNApplication::$instance->students_groups->getGroupType($group_type_id);
			// get groups with the same specialization
			$groups = PNApplication::$instance->students_groups->getGroups($group_type_id, $period_id, $group["specialization"]);
			$groups_tree = PNApplication::$instance->students_groups->buildGroupTree($groups);
			$groups = PNApplication::$instance->students_groups->getFinalGroupsFromTree($groups_tree);
			if ($group["specialization"] <> null) {
				// specialized group
				// get students from the specialization
				$q_students = PNApplication::$instance->students->getStudentsQueryForBatchPeriod($period_id, true, false, $group["specialization"],false);
				$q_students->orderBy("People", "last_name");
				$q_students->orderBy("People", "first_name");
				$students = $q_students->execute();
			} else {
				// not specialized group
				$q_students = PNApplication::$instance->students->getStudentsQueryForBatchPeriod($period_id, true, false, false,false);
				$q_students->orderBy("People", "last_name");
				$q_students->orderBy("People", "first_name");
				$students = $q_students->execute();
			}
			$students_ids = array();
			foreach ($students as &$s) array_push($students_ids, $s["people"]);
			// get group assignment
			if (count($students) > 0) {
				$students_groups = SQLQuery::create()->select("StudentGroup")
					->join("StudentGroup","StudentsGroup",array("group"=>"id"))
					->whereValue("StudentsGroup","type",$group_type_id)
					->whereValue("StudentsGroup","period",$period_id)
					->whereIn("StudentGroup", "people", $students_ids)
					->field("StudentGroup", "people", "people")
					->field("StudentsGroup", "id", "group")
					->execute();
			} else
				$students_groups = array();
			foreach ($students as &$s) {
				foreach ($students_groups as $g) {
					if ($g["people"] == $s["people"]) {
						$s["group"] = $g["group"];
						break;
					}
				}
				if (!isset($s["group"])) $s["group"] = null;
			}
			array_push($sections, array(null, $groups, $students));
		}
		
		// get previous period if any, and students assignments
		$period = PNApplication::$instance->curriculum->getAcademicPeriodAndBatchPeriod($period_id);
		$previous_academic_period = PNApplication::$instance->curriculum->getPreviousAcademicPeriod($period["academic_period_start"]);
		$previous_groups = null;
		$previous_batch_period = null;
		while ($previous_academic_period <> null) {
			$previous_groups = null;
			$previous_batch_period = PNApplication::$instance->curriculum->getBatchPeriodFromAcademicPeriod($period["batch"], $previous_academic_period["id"]);
			if ($previous_batch_period <> null) {
				$previous_groups = PNApplication::$instance->students_groups->getGroups($group_type_id, $previous_batch_period["id"]);
				$previous_groups = PNApplication::$instance->students_groups->buildGroupTree($previous_groups);
				$previous_groups = PNApplication::$instance->students_groups->getFinalGroupsFromTree($previous_groups);
				$previous_groups_ids = array();
				foreach ($previous_groups as $pg) array_push($previous_groups_ids, $pg["id"]);
				$previous_groups_students = SQLQuery::create()->select("StudentGroup")
					->whereIn("StudentGroup","group",$previous_groups_ids)
					->execute();
				if (count($previous_groups) > 0) break;
			}
			$previous_academic_period = PNApplication::$instance->curriculum->getPreviousAcademicPeriod($previous_academic_period["start"]);
		}
		
		$this->requireJavascript("assign_elements.js");
		$this->requireJavascript("section.js");
		?>
		<div style='width:100%;height:100%;overflow-x:auto;display:flex;flex-direction:column;'>
		<div id='top_container' style='display:flex;flex-direction:row;flex:1 1 auto;'></div> 
		</div>
		<script type='text/javascript'>
		var popup = window.parent.get_popup_window_from_frame(window);
		popup.addIconTextButton(theme.icons_16.save, "Save", 'save', save);
		popup.disableButton('save');
		popup.addCloseButton();

		function display_people(people, container) {
			container.appendChild(document.createTextNode(people.last_name+" "+people.first_name));
		}
		
		var assign, container, sec;
		var assigns = [];

		var group_type = <?php echo json_encode($group_type);?>;
		var groups = [];
		function getGroupName(id) {
			for (var i = 0; i < groups.length; ++i)
				if (groups[i].id == id) return groups[i].name;
			return "";
		}
		var students = <?php echo PeopleJSON::Peoples($students);?>;
		function getStudent(id) {
			for (var i = 0; i < students.length; ++i) if (students[i].id == id) return students[i];
			return null;
		}

		var previous_groups = <?php echo json_encode($previous_groups);?>;
		<?php
		if ($previous_groups <> null && count($previous_groups) > 0)
			echo "var previous_assignments = ".json_encode($previous_groups_students).";"; 
		?>

		function fromPreviousPeriod(button, assign) {
			require("context_menu.js",function() {
				var menu = new context_menu();
				for (var i = 0; i < previous_groups.length; ++i) {
					menu.addIconItem(null, group_type.name+" "+previous_groups[i].name+(previous_groups[i].path.length > 0 ? "("+previous_groups[i].path+")" : ""), function(ev,group_id) {
						var elements = [];
						for (var i = 0; i < previous_assignments.length; ++i)
							if (previous_assignments[i]["group"] == group_id) {
								var student = getStudent(previous_assignments[i]["people"]);
								if (student != null)
									elements.push(student);
							}
						assign.selectUnassigned(elements);
					}, previous_groups[i].id);
				}
				menu.showBelowElement(button);
			});
		}

		function changed() {
			var has_change = false;
			for (var i = 0; i < assigns.length && !has_change; ++i) {
				var changes = assigns[i].getChanges();
				if (changes.length > 0) has_change = true;
			}
			if (has_change)
				popup.enableButton('save');
			else
				popup.disableButton('save');
		}
		
		<?php
		foreach ($sections as $section) {
			$section_name = $section[0];
			$groups = $section[1];
			$students = $section[2];
			if ($section_name <> null) {
				echo "container = document.createElement('DIV');\n";
				//echo "container.style.height = '100%';\n";
				echo "container.style.backgroundColor = '#f0f0f0';\n";
				echo "sec = new section(null,".json_encode($section_name).",container,false,true);\n";
				//echo "sec.element.style.display = 'inline-block';\n";
				//echo "sec.element.style.margin = '5px';\n";
				echo "document.getElementById('top_container').appendChild(sec.element);\n";
			} else
				echo "container = document.getElementById('top_container');\n";
			echo "assign = new assign_elements(container,".($section_name<>null?"'sub'":"null").",null,display_people,function(assign){\n";
			foreach ($groups as $g) {
				echo "\tassign.addPossibleAssignment(".$g["id"].",null,".json_encode($g["name"].($g["path"] <> "" ? "(".$g["path"].")" : "")).");\n";
				echo "\tgroups.push({id:".$g["id"].",name:".json_encode($g["name"].($g["path"] <> "" ? "(".$g["path"].")" : ""))."});\n";
			}
			foreach ($students as &$s) {
				echo "\tassign.addElement(getStudent(".$s["people_id"]."),".json_encode($s["group"]).",true);\n";
			}
			echo "\tif (previous_groups) assign.addUnassignedButton(null,'From '+".json_encode($previous_batch_period["name"]).",function(){fromPreviousPeriod(this,assign);});\n";
			echo "\tassign.onchange.add_listener(changed);\n";
			echo "\tlayout.changed(assign.container);\n";
			echo "});\n";
			echo "assigns.push(assign);\n";
		}
		?>
		function save() {
			var lock = lock_screen(null, "Saving assignments...");
			var changes = [];
			for (var i = 0; i < assigns.length; ++i) changes.push(assigns[i].getChanges());
			var next = function(index_assign, index_people) {
				var peoples = changes[index_assign];
				if (index_people == peoples.length) {
					assigns[index_assign].changesSaved();
					if (index_assign == assigns.length-1) {
						<?php if (isset($_GET["onsave"])) echo "window.frameElement.".$_GET["onsave"]."();"?>
						unlock_screen(lock);
						return;
					}
					next(index_assign+1,0);
					return;
				}
				var original = peoples[index_people].original;
				var current = peoples[index_people].current;
				var people = peoples[index_people].element;
				if (current == null)
					set_lock_screen_content(lock,"Unassign "+people.first_name.toHTML()+" "+people.last_name.toHTML()+" from "+group_type.name+" "+getGroupName(original).toHTML());
				else
					set_lock_screen_content(lock,"Assign "+people.first_name.toHTML()+" "+people.last_name.toHTML()+" to "+group_type.name+" "+getGroupName(current).toHTML());
				service.json("students_groups","assign_group",{student:people.id,group:current,period:<?php echo $period_id;?>,group_type:<?php echo $group_type_id;?>},function(res){
					next(index_assign,index_people+1);
				});
			};
			next(0,0);
		}
		</script>
		<?php 
	}
	
}
?>