<?php 
class page_assign_specializations extends Page {
	
	public function get_required_rights() { return array("manage_batches"); }
	
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
		
		$this->require_javascript("assign_peoples.js");
		?>
		<div id='assign_container' style='width:100%;height:100%'>
		</div>
		<script type='text/javascript'>
		var assign = new assign_peoples('assign_container');
		var popup = window.parent.get_popup_window_from_frame(window);
		popup.addIconTextButton(theme.icons_16.save, "Save", 'save', save);
		popup.addCancelButton();
		assign.setNonMovableReason("Students who cannot be moved (in gray) are already assigned to a class in a specialization. To change specialization for those students, you must first unassign them from the classes.");
		<?php foreach ($specializations as $spe) echo "assign.addPossibleAssignment(".$spe["id"].",".json_encode($spe["name"]).");\n";?>
		<?php foreach ($students as $s) echo "assign.addPeople(".PeopleJSON::People($q, $s).",".json_encode($s["specialization"]).",".(in_array($s["student_people"],$students_class_spe) ? "false" : "true").");\n";?>
		function save() {
			var lock = lock_screen(null,"<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Saving specializations...");
			var peoples = assign.getPeoples();
			var next = function(index) {
				if (index == peoples.length) {
					<?php if (isset($_GET["onsave"])) echo "window.parent.".$_GET["onsave"]."();"?>
					unlock_screen(lock);
					return;
				}
				var original = assign.getOriginalAssignment(peoples[index].id);
				var current = assign.getNewAssignment(peoples[index].id);
				if (original == current) {
					next(index+1);
					return;
				}
				if (current == null)
					set_lock_screen_content(lock, "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Unassigning specialization from "+peoples[index].first_name+" "+peoples[index].last_name);
				else
					set_lock_screen_content(lock, "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Assigning specialization "+assign.getAssignmentName(current)+" to "+peoples[index].first_name+" "+peoples[index].last_name);
				service.json("students", "assign_specialization", {student:peoples[index].id,specialization:current}, function(res) {
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