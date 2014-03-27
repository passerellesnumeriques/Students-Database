<?php 
class page_tree extends Page {
	
	public function get_required_rights() { return array(); } // TODO
	
	public function execute() {
		require_once("component/curriculum/page/curriculum_tree.inc");
		curriculum_tree($this, array(
			new CurriculumMenuItem("list", "/static/curriculum/batch_16.png", "Students List", "/dynamic/students/page/list", "List of students"),
			new CurriculumMenuItem("discipline", "/static/discipline/discipline.png", "Discipline", "/dynamic/discipline/page/home", "Follow-up violations, abscences, lateness..."),
			new CurriculumMenuItem("health", "/static/health/health.png", "Health", "/dynamic/health/page/home", "Follow-up health situation and medical information")
		));
	}
	
}
?>