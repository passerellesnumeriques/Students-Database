<?php 
require_once("component/students/StudentsPlugin.inc");
require_once("component/news/NewsPlugin.inc");

class TranscriptsStudentsPlugin implements StudentsPlugin {

	public function getNewsCategories() {
		return array(
				new TranscriptsNewsCategory()
		);
	}

}
class TranscriptsNewsCategory implements NewsCategory {
	
	public function getName() { return "transcripts"; }
	public function getDisplayName() { return "Grades"; }
	public function getIcon() { return "/static/transcripts/grades_16.png"; }
	public function getAccessRight() {
		if (in_array("teacher",PNApplication::$instance->user_management->people_types))
			return 2;
		if (PNApplication::$instance->user_management->has_right("edit_transcripts_design"))
			return 2;
		if (PNApplication::$instance->user_management->has_right("edit_students_grades"))
			return 2;
		if (in_array("student",PNApplication::$instance->user_management->people_types))
			return 1;
		if (PNApplication::$instance->user_management->has_right("consult_students_grades"))
			return 1;
		return 0;
	}
}
?>