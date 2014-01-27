<?php 
require_once("component/news/NewsPlugin.inc");
class TranscriptsNewsPlugin implements NewsPlugin {
	
	public function getSections() {
		return array(new TranscriptsEducationNewsSection());
	}
	
}

class TranscriptsEducationNewsSection implements NewsSection {
	
	public function getName() { return "education"; }
	public function getDisplayName() { return "Education"; }
	public function getIcon() { return "/static/students/student_32.png"; }
	public function getAccessRight() {
		return 2; // TODO
	}
	public function getCategories() {
		return array();
	}
	
}
?>