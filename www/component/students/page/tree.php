<?php 
class page_tree extends Page {
	
	public function getRequiredRights() { return array(); } // TODO
	
	public function execute() {
		require_once("component/curriculum/page/curriculum_tree.inc");
		curriculum_tree($this, array(
			new CurriculumMenuItem("list", "/static/curriculum/batch_16.png", "Students List", "/dynamic/students/page/list", "List of students"),
			new CurriculumMenuItem("updates", "/static/news/news.png", "Updates", "/dynamic/students/page/updates", "What's happening ? What other users did recently ?")
		));
	}
	
}
?>