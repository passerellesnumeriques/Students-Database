<?php 
require_once("component/news/NewsPlugin.inc");
class TranscriptsNewsPlugin implements NewsPlugin {
	
	public function getSections() {
		return array(new TranscriptsNewsSection());
	}
	
}

class TranscriptsNewsSection implements NewsSection {
	
	public function getName() { return "transcripts"; }
	public function getDisplayName() { return "Grades"; }
	public function getIcon() { return "/static/transcripts/grades_32.png"; }
	public function getAccessRight() {
		return 2; // TODO
	}
	public function getCategories() {
		return array();
	}
	
}
?>