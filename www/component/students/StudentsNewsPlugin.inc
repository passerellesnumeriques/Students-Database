<?php 
require_once("component/news/NewsPlugin.inc");
/**
 * Implementation of NewsPlugin for the curriculum
 */
class StudentsNewsPlugin implements NewsPlugin {
	
	public function getSections() {
		return array(new StudentsNewsSection());
	}
	
}

/** Education section of updates, for the curriculum */
class StudentsNewsSection implements NewsSection {
	
	public function getName() { return "students"; }
	public function getDisplayName() { return "Training / Education"; }
	public function getIcon() { return "/static/students/student_32.png"; }
	public function getAccessRight() {
		if (PNApplication::$instance->user_management->hasRight("edit_curriculum"))
			return 2;
		if (PNApplication::$instance->user_management->hasRight("consult_students_list"))
			return 1;
		if (in_array("student",PNApplication::$instance->user_management->people_types))
			return 1;
		return 0;
	}
	public function getCategories() {
		$list = array();
		require_once("StudentsPlugin.inc");
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations("StudentsPlugin") as $pi)
				$list = array_merge($list, $pi->getNewsCategories());
		}
		return $list;
	}
	public function getTagsFilters() {
		if (PNApplication::$instance->user_management->hasRight("edit_curriculum")) return array();
		if (PNApplication::$instance->user_management->hasRight("consult_students_list")) return array();
		if (in_array("student",PNApplication::$instance->user_management->people_types)) {
			$student = PNApplication::$instance->students->getStudent(PNApplication::$instance->user_management->people_id);
			return array("batch"=>array($student["batch"]));
		}
		return array();
	}
}
?>