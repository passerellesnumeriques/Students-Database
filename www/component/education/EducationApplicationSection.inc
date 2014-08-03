<?php 
require_once("component/application/ApplicationSectionPlugin.inc");
class EducationApplicationSection implements ApplicationSectionPlugin {
	
	public function getId() { return "education"; }
	public function getName() { return "Education"; }
	public function getDescription() { return "Students information and life in PN: discipline, health, finance, housing..."; }
	public function getPriority() { return 70; }
	public function getIcon16White() { return "/static/education/education_white.png"; }
	public function getIcon32() { return "/static/education/education_32.png"; }
	public function getComponentsForURL() { return array("education","health","discipline"); }
	public function getMenuURL() { return "/dynamic/education/service/menu"; }
	public function getDefaultPageURL() { return "/dynamic/curriculum/page/tree_frame#/dynamic/education/page/overview"; }
	public function canAccess() { return PNApplication::$instance->user_management->has_right("consult_students_list"); }
	
}
?>