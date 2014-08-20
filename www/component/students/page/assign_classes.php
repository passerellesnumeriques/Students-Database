<?php 
class page_assign_classes extends Page {
	
	public function getRequiredRights() { return array("manage_batches"); }
	
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
			// get all students for this period
			$q_students = PNApplication::$instance->students->getStudentsQueryForBatchPeriod($period_id, true, false);
			$students = $q_students->execute();
			$students_ids = array();
			foreach ($students as $s) array_push($students_ids, $s["people"]);
			// get class assignment
			if (count($students) > 0) {
				$q = SQLQuery::create()->select("StudentClass");
				PNApplication::$instance->curriculum->joinAcademicClass($q, "StudentClass", "class", $period_id);
				$q->whereIn("StudentClass", "people", $students_ids);
				$q->field("StudentClass", "people", "people");
				$q->field("AcademicClass", "id", "class");
				$students_classes = $q->execute();
			} else
				$students_classes = array();
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
			$specializations = PNApplication::$instance->curriculum->getBatchPeriodSpecializationsWithName($period_id);
			$classes = PNApplication::$instance->curriculum->getAcademicClassesForPeriod($period_id);
			if (count($specializations) > 0) {
				// specialized period
				foreach ($specializations as $spe) {
					$list_students = array();
					foreach ($students as &$s) if ($s["specialization"] == $spe["id"]) array_push($list_students, $s);
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
			$period_id = $class["period"];
			if ($class["specialization"] <> null) {
				// specialized class
				// get classes in this specialization
				$classes = PNApplication::$instance->curriculum->getAcademicClassesForPeriod($period_id, $class["specialization"]);
				// get students from the specialization
				$q_students = PNApplication::$instance->students->getStudentsQueryForBatchPeriod($period_id, true, false, $class["specialization"]);
				$students = $q_students->execute();
			} else {
				// not specialized class
				$classes = PNApplication::$instance->curriculum->getAcademicClassesForPeriod($period_id);
				$q_students = PNApplication::$instance->students->getStudentsQueryForBatchPeriod($period_id, true, false);
				$students = $q_students->execute();
			}
			$students_ids = array();
			foreach ($students as &$s) array_push($students_ids, $s["people"]);
			// get class assignment
			if (count($students) > 0) {
				$q = SQLQuery::create()->select("StudentClass");
				PNApplication::$instance->curriculum->joinAcademicClass($q, "StudentClass", "class", $period_id);
				$q->whereIn("StudentClass", "people", $students_ids);
				$q->field("StudentClass", "people", "people");
				$q->field("AcademicClass", "id", "class");
				$students_classes = $q->execute();
			} else
				$students_classes = array();
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
			container.appendChild(document.createTextNode(people.first_name+" "+people.last_name));
		}
		
		var assign, container, sec;
		var assigns = [];

		var classes = [];
		function getClassName(id) {
			for (var i = 0; i < classes.length; ++i)
				if (classes[i].id == id) return classes[i].name;
			return "";
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
			$classes = $section[1];
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
			foreach ($classes as $cl) {
				echo "\tassign.addPossibleAssignment(".$cl["id"].",null,".json_encode($cl["name"]).");\n";
				echo "\tclasses.push({id:".$cl["id"].",name:".json_encode($cl["name"])."});\n";
			}
			foreach ($students as &$s) {
				echo "\tassign.addElement(".PeopleJSON::People($s).",".json_encode($s["class"]).",true);\n";
			}
			echo "\tassign.onchange.add_listener(changed);\n";
			echo "\tlayout.invalidate(assign.container);\n";
			echo "});\n";
			echo "assigns.push(assign);\n";
		}
		?>
		function save() {
			var lock = lock_screen(null, "Saving class assignments...");
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
					set_lock_screen_content(lock,"Unassign "+people.first_name.toHTML()+" "+people.last_name.toHTML()+" from class "+getClassName(original).toHTML());
				else
					set_lock_screen_content(lock,"Assign "+people.first_name.toHTML()+" "+people.last_name.toHTML()+" to class "+getClassName(current).toHTML());
				service.json("students","assign_class",{student:people.id,clas:current,period:<?php echo $period_id;?>},function(res){
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