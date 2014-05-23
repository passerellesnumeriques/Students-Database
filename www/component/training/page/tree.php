<?php 
class page_tree extends Page {
	
	public function getRequiredRights() { return array(); } // TODO
	
	public function execute() {
		require_once("component/curriculum/page/curriculum_tree.inc");
		curriculum_tree($this, array(
			new CurriculumMenuItem("list", "/static/curriculum/batch_16.png", "Students List", "/dynamic/students/page/list", "List of students"),
			new CurriculumMenuItem("curriculum", "/static/curriculum/curriculum_16.png", "Curriculum", "/dynamic/curriculum/page/curriculum", "List of subjects for each academic period"),
			new CurriculumMenuItem("grades", "/static/transcripts/grades.gif", "Grades", "/dynamic/transcripts/page/students_grades", "Grades of students for an academic period")
		));
	}
	
}
?>