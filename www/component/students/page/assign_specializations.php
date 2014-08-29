<?php 
class page_assign_specializations extends Page {
	
	public function getRequiredRights() { return array("manage_batches"); }
	
	public function execute() {
		$batch_id = $_GET["batch"];
		require_once("component/people/PeopleJSON.inc");
		$q = SQLQuery::create()
			->select("Student")
			->whereValue("Student", "batch", $batch_id)
			->field("Student", "specialization", "specialization")
			->field("Student", "people", "student_people")
			;
		PNApplication::$instance->people->joinPeople($q, "Student", "people");
		PeopleJSON::PeopleSQL($q);
		$q->orderBy("People", "last_name");
		$q->orderBy("People", "first_name");
		$students = $q->execute();
		$specializations = PNApplication::$instance->curriculum->getBatchSpecializationsWithName($batch_id);
		// search students already assign to a class with specialization, and who cannot be moved to another
		$q = SQLQuery::create() 
			->select("Student")
			->whereValue("Student", "batch", $batch_id)
			->whereNotNull("Student", "specialization")
			->join("Student", "StudentClass", array("people"=>"people"))
			;
		PNApplication::$instance->curriculum->joinAcademicClass($q, "StudentClass", "class");
		$q->whereNotNull("AcademicClass", "specialization");
		$q->groupBy("Student", "people");
		$q->field("Student","people");
		$students_class_spe = $q->executeSingleField();
		
		$this->requireJavascript("assign_elements.js");
		require_once("component/curriculum/CurriculumJSON.inc");
		?>
		<div id='assign_container' style='width:100%;height:100%'>
		</div>
		<script type='text/javascript'>
		var popup = window.parent.get_popup_window_from_frame(window);
		popup.addIconTextButton(theme.icons_16.save, "Save", 'save', save);
		popup.addCancelButton();
		popup.disableButton('save');

		function display_people(people, container) {
			container.appendChild(document.createTextNode(people.last_name+" "+people.first_name));
		}

		var specializations = <?php echo CurriculumJSON::SpecializationsJSONFromDB($specializations);?>;
		function getSpecializationName(id) {
			for (var i = 0; i < specializations.length; ++i)
				if (specializations[i].id == id)
					return specializations[i].name;
			return "";
		}
		
		var assign = new assign_elements('assign_container',null,null,display_people,function(assign) {
			assign.onchange.add_listener(function(){
				var changes = assign.getChanges();
				if (changes.length > 0)
					popup.enableButton('save');
				else
					popup.disableButton('save');
			});
			assign.setNonMovableReason("Students who cannot be moved (in gray) are already assigned to a class in a specialization. To change specialization for those students, you must first unassign them from the classes.");
			<?php foreach ($specializations as $spe) echo "assign.addPossibleAssignment(".$spe["id"].",null,".json_encode($spe["name"]).");\n";?>
			<?php foreach ($students as $s) echo "assign.addElement(".PeopleJSON::People($s).",".json_encode($s["specialization"]).",".(in_array($s["student_people"],$students_class_spe) ? "false" : "true").");\n";?>
		});
		function save() {
			var lock = lock_screen(null,"<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Saving specializations...");
			var peoples = assign.getChanges();
			var next = function(index) {
				if (index == peoples.length) {
					assign.changesSaved();
					<?php if (isset($_GET["onsave"])) echo "window.frameElement.".$_GET["onsave"]."();"?>
					unlock_screen(lock);
					return;
				}
				var original = peoples[index].original;
				var current = peoples[index].current;
				var people = peoples[index].element;
				if (current == null)
					set_lock_screen_content(lock, "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Unassigning specialization from "+people.first_name.toHTML()+" "+people.last_name.toHTML());
				else
					set_lock_screen_content(lock, "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Assigning specialization "+getSpecializationName(current).toHTML()+" to "+people.first_name.toHTML()+" "+people.last_name.toHTML());
				service.json("students", "assign_specialization", {student:people.id,specialization:current}, function(res) {
					next(index+1);
				});
			};
			next(0);
		}
		</script>
		<?php 
	}
	
}
?>