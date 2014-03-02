<?php 
class page_assign_classes extends Page {
	
	public function get_required_rights() { return array("manage_batches"); }
	
	public function execute() {
		/* different cases:
		 *  - on a period which is not specialized: all students can be assigned in any class
		 *  - on a period which is specialized: only students assign to a given specializtion can be assign to classes of this specialization
		 *  - on a class which is not specialized: all students non-assigned to any class during this period can be assigned
		 *  - on a class which is specialized: all students of this specialization and not yet assign to a class during this period can be assigned
		 */
		$period_id = @$_GET["period"];
		$sections = array();
		if ($period_id <> null) {
			// we are on a period
			$period = PNApplication::$instance->curriculum->getAcademicPeriod($period_id);
			// get all students for this period
			$q_students = PNApplication::$instance->students->getStudentsQueryForPeriod($period, true, false);
			$students = $q_students->execute();
			$students_ids = array();
			foreach ($students as $s) array_push($students_ids, $s["people"]);
			// get class assignment
			$q = SQLQuery::create()->select("StudentClass");
			PNApplication::$instance->curriculum->joinAcademicClass($q, "StudentClass", "class", $period_id);
			$q->whereIn("StudentClass", "people", $students_ids);
			$q->field("StudentClass", "people", "people");
			$q->field("AcademicClass", "id", "class");
			$students_classes = $q->execute();
			foreach ($students as &$s) {
				foreach ($students_classes as $sc) {
					if ($sc["people"] == $s["people"]) {
						$s["class"] = $sc["class"];
						break;
					}
				}
				if (!isset($s["class"])) $s["class"] = null;
			}
			// check if this period is specialized
			$specializations = PNApplication::$instance->curriculum->getAcademicPeriodSpecializationsWithName($period_id);
			$classes = PNApplication::$instance->curriculum->getAcademicClassesForPeriod($period_id);
			if (count($specializations) > 0) {
				// specialized period
				foreach ($specializations as $spe) {
					$list_students = array();
					foreach ($students as $s) if ($s["specialization"] == $spe["id"]) array_push($list_students, $s);
					$list_classes = array();
					foreach ($classes as $cl) if ($cl["specialization"] == $spe["id"]) array_push($list_classes, $cl);
					array_push($sections, array($spe["name"],$list_classes,$list_students));
				}
			} else {
				// not specialized period
				array_push($sections, array(null,$classes,$students));
			}
		} else {
			// we are on a class
			$class = PNApplication::$instance->curriculum->getAcademicClass($_GET["class"]);
			$period = PNApplication::$instance->curriculum->getAcademicPeriod($class["period"]);
			if ($class["specialization"] <> null) {
				// specialized class
				// get classes in this specialization
				$classes = PNApplication::$instance->curriculum->getAcademicClassesForPeriod($period["id"], $class["specialization"]);
				// get students from the specialization
				$q_students = PNApplication::$instance->students->getStudentsQueryForPeriod($period, true, false, $class["specialization"]);
				$students = $q_students->execute();
			} else {
				// not specialized class
				$classes = PNApplication::$instance->curriculum->getAcademicClassesForPeriod($period["id"]);
				$q_students = PNApplication::$instance->students->getStudentsQueryForPeriod($period, true, false);
				$students = $q_students->execute();
			}
			$students_ids = array();
			foreach ($students as $s) array_push($students_ids, $s["people"]);
			// get class assignment
			$q = SQLQuery::create()->select("StudentClass");
			PNApplication::$instance->curriculum->joinAcademicClass($q, "StudentClass", "class", $period["id"]);
			$q->whereIn("StudentClass", "people", $students_ids);
			$q->field("StudentClass", "people", "people");
			$q->field("AcademicClass", "id", "class");
			$students_classes = $q->execute();
			foreach ($students as &$s) {
				foreach ($students_classes as $sc) {
					if ($sc["people"] == $s["people"]) {
						$s["class"] = $sc["class"];
						break;
					}
				}
				if (!isset($s["class"])) $s["class"] = null;
			}
			array_push($sections, array(null, $classes, $students));
		}
		
		$this->require_javascript("assign_peoples.js");
		$this->require_javascript("section.js");
		$this->require_javascript("horizontal_layout.js");
		$this->require_javascript("vertical_layout.js");
		?>
		<div id='top_container' style='width:100%;height:100%;overflow-x: auto;overflow-y:hidden;background-color:white'>
			<div layout='fill' id='container' style='margin:5px;display:inline-block'>
				<?php if (count($sections) > 0) echo "<div id='sections_container' layout='fill' style='padding-bottom:2px;white-space: nowrap;'></div>";?>
			</div>
		</div>
		<script type='text/javascript'>
		var popup = window.parent.get_popup_window_from_frame(window);
		popup.addIconTextButton(theme.icons_16.save, "Save", 'save', save);
		popup.addCancelButton();
		var assign, container, sec;
		var assigns = [];
		<?php
		foreach ($sections as $section) {
			$section_name = $section[0];
			$classes = $section[1];
			$students = $section[2];
			if ($section_name <> null) {
				echo "container = document.createElement('DIV');\n";
				echo "container.style.height = '100%';\n";
				echo "sec = new section(null,".json_encode($section_name).",container,false,true);\n";
				echo "sec.element.style.height = '100%';\n";
				echo "sec.element.style.marginRight = '5px';\n";
				echo "sec.element.style.display = 'inline-block';\n";
				echo "document.getElementById('sections_container').appendChild(sec.element);\n";
			} else
				echo "container = document.getElementById('container');\n";
			echo "assign = new assign_peoples(container);\n";
			echo "assigns.push(assign);";
			foreach ($classes as $cl) {
				echo "assign.addPossibleAssignment(".$cl["id"].",".json_encode($cl["name"]).");\n";
			}
			foreach ($students as &$s) {
				echo "assign.addPeople(".PeopleJSON::People($q_students, $s).",".json_encode($s["class"]).",true);\n";
			}
		}
		if (count($sections) > 1) echo "new vertical_layout('container',true);\n";
		?>
		new vertical_layout('top_container',true);
		function save() {
			var lock = lock_screen(null, "Saving class assignments...");
			var peoples_list = [];
			for (var i = 0; i < assigns.length; ++i) peoples_list.push(assigns[i].getPeoples());
			var next = function(index_assign, index_people) {
				var peoples = peoples_list[index_assign];
				if (index_people == peoples.length) {
					if (index_assign == assigns.length-1) {
						<?php if (isset($_GET["onsave"])) echo "window.parent.".$_GET["onsave"]."();"?>
						unlock_screen(lock);
						return;
					}
					next(index_assign+1,0);
					return;
				}
				var original = assigns[index_assign].getOriginalAssignment(peoples[index_people].id);
				var current = assigns[index_assign].getNewAssignment(peoples[index_people].id);
				if (original == current) {
					next(index_assign, index_people+1);
					return;
				}
				if (current == null)
					set_lock_screen_content(lock,"Unassign "+peoples[index_people].first_name+" "+peoples[index_people].last_name+" from class "+assigns[index_assign].getAssignmentName(original));
				else
					set_lock_screen_content(lock,"Assign "+peoples[index_people].first_name+" "+peoples[index_people].last_name+" to class "+assigns[index_assign].getAssignmentName(current));
				service.json("students","assign_class",{student:peoples[index_people].id,clas:current,period:<?php echo $period["id"];?>},function(res){
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