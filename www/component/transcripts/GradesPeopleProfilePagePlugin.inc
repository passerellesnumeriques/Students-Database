<?php 
require_once("component/people/PeopleProfilePagePlugin.inc");
class GradesPeopleProfilePagePlugin implements PeopleProfilePagePlugin {
	
	public function isValidFor($people_id, $types) { return in_array("student", $types); }
	public function getPriority() { return 10; }
	public function getIcon() { return "/static/transcripts/grades.gif"; }
	public function getName() { return "Grades"; }
	public function getTooltip() { return "Grades of this student"; } 
	public function getURL($people_id) { return "/dynamic/transcripts/page/student_grades?people=".$people_id; }
		
}
?>