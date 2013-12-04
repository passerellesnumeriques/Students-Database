<?php 
class page_home extends Page {
	
	public function get_required_rights() { return array(); } // TODO
	
	public function execute() {
		$this->add_javascript("/static/widgets/frame_header.js");
		$this->onload("new frame_header('training_education_page');");
		?>
		<div id='training_education_page' style='width:100%;height:100%' icon='/static/students/student_32.png' title='Training & Education' page="batches_classes">
			<div class='button' onclick="document.getElementById('training_education_page_content').src = 'batches_classes';"><img src='/static/curriculum/batch_16.png'/> Batches & Classes</div>
			<span id='student_search'></span>
		</div>
		<script type='text/javascript'>
		require("autocomplete.js",function() {
			var students = [<?php
			$students = SQLQuery::create()
				->select("Student")
				->join("Student","People",array("people"=>"id"))
				->join("Student", "StudentBatch", array("batch"=>"id"))
				->field("Student", "people", "people")
				->field("People", "first_name", "first_name")
				->field("People", "last_name", "last_name")
				->field("StudentBatch", "name", "batch")
				->execute();
			$first = true;
			foreach ($students as $s) {
				if ($first) $first = false; else echo ",";
				echo "{";
				echo "people_id:".$s["people"];
				echo ",first_name:".json_encode($s["first_name"]);
				echo ",last_name:".json_encode($s["last_name"]);
				echo ",batch:".json_encode($s["batch"]);
				echo "}";
			}
			?>];
			var ac = new autocomplete('student_search', function(name) {
				var items = [];
				var words = name.split(" ");
				for (var i = 0; i < students.length; ++i) {
					var ok = true;
					for (var j = 0; j < words.length; ++j)
						if (students[i].first_name.toLowerCase().indexOf(words[j].toLowerCase()) == -1 &&
							students[i].last_name.toLowerCase().indexOf(words[j].toLowerCase()) == -1) {
							ok = false;
							break;
						}
					if (ok) {
						items.push({
							text: students[i].first_name+" "+students[i].last_name+" (Batch "+students[i].batch+")",
							people_id: students[i].people_id
						});
					}
				}
				return items;
			}, 3, 'Search a student', function(item) {
				document.getElementById('training_education_page_content').src = "/dynamic/people/page/profile?people="+item.people_id;
			}, 250);
			setBorderRadius(ac.input,8,8,8,8,8,8,8,8);
			setBoxShadow(ac.input,-1,2,2,0,'#D8D8F0',true);
			ac.input.style.background = "#ffffff url('"+theme.icons_16.search+"') no-repeat 3px 1px";
			ac.input.style.padding = "2px 4px 2px 23px";
		});
		</script>
		<?php 
	}
	
}
?>