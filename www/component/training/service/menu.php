<?php 
class service_menu extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Provides the training menu"; }
	public function input_documentation() { echo "No"; }
	public function output_documentation() { echo "The HTML to put in the menu"; }
	public function get_output_format($input) { return "text/html"; }
		
	public function execute(&$component, $input) {
?>
<a class='application_left_menu_item' href='/dynamic/students/page/list'>
	<img src='/static/students/students_white.png'/>
	Students List
</a>
<a class='application_left_menu_item' href='/dynamic/curriculum/page/academic_calendar'>
	<img src='/static/calendar/calendar_white.png'/>
	Years and Periods
</a>
<a class='application_left_menu_item' href='/dynamic/curriculum/page/curriculum'>
	<img src='/static/curriculum/curriculum_white.png'/>
	Curriculum
</a>
<a class='application_left_menu_item' href='/dynamic/transcripts/page/students_grades'>
	<img src='/static/transcripts/grades_white.png'/>
	Grades
</a>
<?php 
	}
	
}
?>