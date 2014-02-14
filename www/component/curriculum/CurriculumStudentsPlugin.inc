<?php 
require_once("component/students/StudentsPlugin.inc");
require_once("component/news/NewsPlugin.inc");
/**
 * Implementation of NewsPlugin for the curriculum
 */
class CurriculumStudentsPlugin implements StudentsPlugin {
	
	public function getNewsCategories() {
		return array(
			new CurriculumNewsCategory(),
			new BatchNewsCategory()
		);
	}
	
}

/** Category for curriculum updates */
class CurriculumNewsCategory implements NewsCategory {
	
	public function getName() { return "curriculum"; }
	public function getDisplayName() { return "Curriculum"; }
	public function getIcon() { return "/static/curriculum/curriculum_16.png"; }
	public function getAccessRight() {
		return 2; // TODO
	}
	
}

/** Category for batches updates */
class BatchNewsCategory implements NewsCategory {

	public function getName() { return "batch"; }
	public function getDisplayName() { return "Batch"; }
	public function getIcon() { return "/static/curriculum/batch_16.png"; }
	public function getAccessRight() {
		return 2; // TODO
	}
	
}
?>