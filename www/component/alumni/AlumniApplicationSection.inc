<?php 
require_once("component/application/ApplicationSectionPlugin.inc");
class AlumniApplicationSection implements ApplicationSectionPlugin {
	
	public function getId() { return "alumni"; }
	public function getName() { return "Alumni"; }
	public function getDescription() { return "Alumni current situation and contacts"; }
	public function getPriority() { return 1000; }
	public function getIcon16White() { return "/static/alumni/alumni_white.png"; }
	public function getIcon32() { return "/static/students/student_32.png"; }
	public function getComponentsForURL() { return array("alumni"); }
	public function getMenuURL() { return "/dynamic/alumni/service/menu"; }
	public function getDefaultPageURL() { return "/dynamic/alumni/page/home"; }
	public function canAccess() { return PNApplication::$instance->user_management->has_right("consult_alumni_info"); }
	
}
?>