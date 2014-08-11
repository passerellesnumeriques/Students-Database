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
	public function getDisplayName() { return "Students"; }
	public function getIcon() { return "/static/students/student_32.png"; }
	public function getAccessRight() {
		if (PNApplication::$instance->user_management->has_right("edit_curriculum"))
			return 2;
		if (PNApplication::$instance->user_management->has_right("consult_students_list"))
			return 1;
		return 0;
	}
	public function getCategories() {
		$list = array();
		require_once("StudentsPlugin.inc");
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi)
				if ($pi instanceof StudentsPlugin)
					$list = array_merge($list, $pi->getNewsCategories());
		}
		return $list;
	}
	
}
?>