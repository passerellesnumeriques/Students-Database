<?php 
require_once("component/people/PeopleProfilePagePlugin.inc");
class DisciplinePeopleProfilePagePlugin implements PeopleProfilePagePlugin {
	
	public function isValidFor($people_id, $types) { return in_array("student",$types); }
	public function getPriority() { return 20; }
	public function getIcon() { return "/static/discipline/discipline.png"; }
	public function getName() { return "Discipline"; }
	public function getTooltip() { return "History of volations, absences, lateness..."; }
	public function getURL($people_id) { return "/dynamic/discipline/page/home?people=".$people_id; }
	
}
?>