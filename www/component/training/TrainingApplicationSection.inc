<?php 
require_once("component/application/ApplicationSectionPlugin.inc");
class TrainingApplicationSection implements ApplicationSectionPlugin {
	
	public function getId() { return "training"; }
	public function getName() { return "Training"; }
	public function getDescription() { return "Consult the curriculum, students list by batch and class, grades..."; }
	public function getPriority() { return 50; }
	public function getIcon16White() { return "/static/training/training_white.png"; }
	public function getIcon32() { return "/static/curriculum/curriculum_32.png"; }
	public function getComponentsForURL() { return array("training","transcripts","curriculum"); }
	public function getMenuURL() { return "/dynamic/training/service/menu"; }
	public function getDefaultPageURL() {
		if (PNApplication::$instance->user_management->has_right("consult_students_list"))
			return "/dynamic/students_groups/page/tree_frame?section=training#/dynamic/students/page/list";
		if (in_array("student",PNApplication::$instance->user_management->people_types))
			return "/dynamic/transcripts/page/student_grades?title=true&people=".PNApplication::$instance->user_management->people_id;
		if (in_array("teacher",PNApplication::$instance->user_management->people_types))
			return "/dynamic/teaching/page/teacher_assignments?people=".PNApplication::$instance->user_management->people_id;
		return "/dynamic/students_groups/page/tree_frame?section=training#/dynamic/students/page/list";
	}
	public function canAccess() {
		if (PNApplication::$instance->user_management->has_right("consult_students_list")) return true;
		if (in_array("student",PNApplication::$instance->user_management->people_types)) return true;
		if (in_array("teacher",PNApplication::$instance->user_management->people_types)) return true;
		return false;
	}
	
}
?>