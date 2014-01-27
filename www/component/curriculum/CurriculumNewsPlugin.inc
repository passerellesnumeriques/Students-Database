<?php 
require_once("component/news/NewsPlugin.inc");
class CurriculumNewsPlugin implements NewsPlugin {
	
	public function getSections() {
		return array(new CurriculumEducationNewsSection());
	}
	
}

class CurriculumEducationNewsSection implements NewsSection {
	
	public function getName() { return "education"; }
	public function getDisplayName() { return "Education"; }
	public function getIcon() { return "/static/students/student_32.png"; }
	public function getAccessRight() {
		return 2; // TODO
	}
	public function getCategories() {
		return array(
			new CurriculumNewsCategory(),
			new BatchNewsCategory()
		);
	}
	
}

class CurriculumNewsCategory implements NewsCategory {

	public function getName() { return "curriculum"; }
	public function getDisplayName() { return "Curriculum"; }
	public function getIcon() { return "/static/curriculum/curriculum_16.png"; }
	public function getAccessRight() {
		return 2; // TODO
	}
	
}
class BatchNewsCategory implements NewsCategory {

	public function getName() { return "batch"; }
	public function getDisplayName() { return "Batch"; }
	public function getIcon() { return "/static/curriculum/batch_16.png"; }
	public function getAccessRight() {
		return 2; // TODO
	}
	
}
?>