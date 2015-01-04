<?php 
require_once("component/people/PeopleProfilePagePlugin.inc");
/**
 * Add the page 'Assigned Subjects' to the profile of a teacher
 */
class TeacherAssignmentsPeopleProfilePagePlugin implements PeopleProfilePagePlugin {
	
	public function isValidFor($people_id, $types) { return in_array("teacher", $types); }
	public function getPriority() { return 10; }
	public function getIcon() { return "/static/curriculum/curriculum_16.png"; }
	public function getName() { return "Assigned Subjects"; }
	public function getTooltip() { return "List of subjects assigned to this teacher"; } 
	public function getURL($people_id) { return "/dynamic/teaching/page/teacher_assignments?people=".$people_id; }
		
}
?>