<?php 
class service_menu extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Provides the training menu"; }
	public function inputDocumentation() { echo "No"; }
	public function outputDocumentation() { echo "The HTML to put in the menu"; }
	public function getOutputFormat($input) { return "text/html"; }
		
	public function execute(&$component, $input) {
		//$current_batches = PNApplication::$instance->curriculum->getCurrentBatches();
?>
<?php if (PNApplication::$instance->user_management->has_right("consult_students_list")) { ?>
<a class='application_left_menu_item' href='/dynamic/curriculum/page/tree_frame?section=training#/dynamic/students/page/updates'>
	<img src='/static/news/news_white.png'/>
	Updates
</a>
<a class='application_left_menu_item' href='/dynamic/curriculum/page/tree_frame?section=training#/dynamic/students/page/list'>
	<img src='/static/students/students_white.png'/>
	Students List
</a>
<?php } else { ?>
<a class='application_left_menu_item' href='/dynamic/students/page/updates'>
	<img src='/static/news/news_white.png'/>
	Updates
</a>
<?php } ?>
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
<?php if (PNApplication::$instance->user_management->has_right("consult_curriculum")) { ?>
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
<?php } else if (in_array("student",PNApplication::$instance->user_management->people_types)) { ?>
<a class='application_left_menu_item' href='/dynamic/curriculum/page/curriculum'>
	<img src='/static/curriculum/curriculum_white.png'/>
	Curriculum
</a>
<?php } ?>
<?php if (PNApplication::$instance->user_management->has_right("consult_students_grades")) { ?>
<a class='application_left_menu_item' href='/dynamic/curriculum/page/tree_frame#/dynamic/transcripts/page/students_grades'>
	<img src='/static/transcripts/grades_white.png'/>
	Grades
</a>
<a class='application_left_menu_item' style='padding-left:20px' href='/dynamic/curriculum/page/tree_frame#/dynamic/transcripts/page/subject_grades' alternate_hrefs='/dynamic/transcripts/page/subject_grades'>
	<img src='/static/curriculum/curriculum_white.png'/>
	By subject
</a>
<?php if (PNApplication::$instance->user_management->has_right("edit_transcripts_design")) { ?>
<a class='application_left_menu_item' style='padding-left:20px' href='/dynamic/curriculum/page/tree_frame#/dynamic/transcripts/page/configure_transcripts'>
	<img src='<?php echo theme::$icons_16["config_white"];?>'/>
	Design transcripts
</a>
<?php } ?>
<a class='application_left_menu_item' style='padding-left:20px' href='/dynamic/curriculum/page/tree_frame#/dynamic/transcripts/page/transcripts'>
	<img src='/static/transcripts/transcript_white.png'/>
	Transcripts
</a>
<?php } else if (in_array("student",PNApplication::$instance->user_management->people_types)) { ?>
<a class='application_left_menu_item' href='/dynamic/transcripts/page/student_grades?people=<?php echo PNApplication::$instance->user_management->people_id;?>'>
	<img src='/static/transcripts/grades_white.png'/>
	My Grades
</a>
<?php } ?>
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
require("search_student.js", function() {
	new search_student('search_student_container','training');
});
/*
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
});*/
</script>
<?php 
	}
	
}
?>