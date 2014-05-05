<?php 
class service_menu extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Provides the training menu"; }
	public function input_documentation() { echo "No"; }
	public function output_documentation() { echo "The HTML to put in the menu"; }
	public function get_output_format($input) { return "text/html"; }
		
	public function execute(&$component, $input) {
		//$current_batches = PNApplication::$instance->curriculum->getCurrentBatches();
?>
<a class='application_left_menu_item' href='/dynamic/curriculum/page/tree_frame#/dynamic/students/page/list'>
	<img src='/static/students/students_white.png'/>
	Students List
</a>
<?php
/*
foreach ($current_batches as $b) {
	echo "<a class='application_left_menu_item' style='padding-left:20px' href='/dynamic/curriculum/page/tree_frame?node=batch".$b["id"]."#/dynamic/students/page/list'>";
	echo "<img src='/static/curriculum/arrow_right_white.png'/> ";
	echo "Batch ".htmlentities($b["name"]);
	echo "</a>";
} 
*/
?>
<a class='application_left_menu_item' href='/dynamic/curriculum/page/academic_calendar'>
	<img src='/static/calendar/calendar_white.png'/>
	Years and Periods
</a>
<a class='application_left_menu_item' href='/dynamic/curriculum/page/tree_frame#/dynamic/curriculum/page/curriculum'>
	<img src='/static/curriculum/curriculum_white.png'/>
	Curriculum
</a>
<?php
/*
foreach ($current_batches as $b) {
	echo "<a class='application_left_menu_item' style='padding-left:20px' href='/dynamic/curriculum/page/tree_frame?node=batch".$b["id"]."#/dynamic/curriculum/page/curriculum'>";
	echo "<img src='/static/curriculum/arrow_right_white.png'/> ";
	echo "Batch ".htmlentities($b["name"]);
	echo "</a>";
} 
*/
?>
<a class='application_left_menu_item' href='/dynamic/curriculum/page/teachers'>
	<img src='/static/curriculum/teacher_white.png'/>
	Teachers
</a>
<a class='application_left_menu_item' href='/dynamic/curriculum/page/teachers_assignments' style='padding-left:20px'>
	<img src='/static/curriculum/teacher_assign_white.png'/>
	Assignments
</a>
<a class='application_left_menu_item' href='/dynamic/curriculum/page/tree_frame#/dynamic/transcripts/page/students_grades'>
	<img src='/static/transcripts/grades_white.png'/>
	Grades
</a>
<?php
/*
foreach ($current_batches as $b) {
	echo "<a class='application_left_menu_item' style='padding-left:20px' href='/dynamic/curriculum/page/tree_frame?node=batch".$b["id"]."#/dynamic/transcripts/page/students_grades'>";
	echo "<img src='/static/curriculum/arrow_right_white.png'/> ";
	echo "Batch ".htmlentities($b["name"]);
	echo "</a>";
} 
*/
?>
<div class="application_left_menu_separator"></div>
<div id="search_student_container" style="width:100%;padding:2px 5px 2px 5px;"></div>
<script type='text/javascript'>
require("autocomplete.js",function() {
	var container = document.getElementById('search_student_container');
	var ac = new autocomplete(container, 3, 'Search a student', function(name, handler) {
		service.json("students","search_student_by_name", {name:name}, function(res) {
			if (!res) { handler([]); return; }
			var items = [];
			for (var i = 0; i < res.length; ++i) {
				var item = new autocomplete_item(res[i].people_id, res[i].first_name+' '+res[i].last_name, res[i].first_name+' '+res[i].last_name+" (Batch "+res[i].batch_name+")");
				items.push(item); 
			}
			handler(items);
		});
	}, function(item) {
		ac.reset();
		window.top.popup_frame("/static/people/profile_16.png","Profile","/dynamic/people/page/profile?people="+item.value,null,95,95);
	}, 250);
	setBorderRadius(ac.input,8,8,8,8,8,8,8,8);
	setBoxShadow(ac.input,-1,2,2,0,'#D8D8F0',true);
	ac.input.style.background = "#ffffff url('"+theme.icons_16.search+"') no-repeat 3px 1px";
	ac.input.style.padding = "2px 4px 2px 23px";
	ac.input.style.width = "130px";
});
</script>
<?php 
	}
	
}
?>