<?php 
class service_menu extends Service {
	
	public function getRequiredRights() { return array(); } // TODO
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function getOutputFormat($input) { return "text/html"; }
	
	public function execute(&$component, $input) {
?>
<a class='application_left_menu_item' href='/dynamic/curriculum/page/tree_frame#/dynamic/education/page/overview'>
	<img src='/static/application/overview_white.png'/>
    Overview
</a>
<a class='application_left_menu_item' href='/dynamic/curriculum/page/tree_frame?section=education#/dynamic/students/page/list'>
	<img src='/static/students/students_white.png'/>
    Students List
</a>
<a class='application_left_menu_item'>
	<img src='/static/discipline/discipline_white.png'/>
    Discipline
</a>
<a class='application_left_menu_item'>
	<img src='/static/health/health_white.png'/>
    Health
</a>
<a class='application_left_menu_item'>
	<img src='/static/application/overview_white.png'/>
    Housing
</a>
<a class='application_left_menu_item'>
	<img src='/static/finance/finance_white.png'/>
    Finance
</a>
<div class="application_left_menu_separator"></div>
<div id="search_student_container" style="width:100%;padding:2px 5px 2px 5px;"></div>
<script type='text/javascript'>
require("search_student.js", function() {
	new search_student('search_student_container','education');
});
</script>
<?php 
	}
	
}
?>