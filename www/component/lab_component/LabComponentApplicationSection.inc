<?php 
require_once("component/application/ApplicationSectionPlugin.inc");
class LabComponentApplicationSection implements ApplicationSectionPlugin {
	
	public function getId() { return "lab_component"; }
	public function getName() { return "lab_component"; }
	public function getDescription() { return "Just a lab to understand component"; }
	public function getPriority() { return 1000; }
	public function getIcon16White() { return "/static/lab_component/lab_component_white.png"; }
	public function getIcon32() { return "/static/lab_component/lab_component_32px.png"; }
	public function getComponentsForURL() { return array("lab_component"); }
	public function getMenuURL() { return "/dynamic/lab_component/service/menu"; }
	public function getDefaultPageURL() { return "/dynamic/lab_component/page/home"; }
	public function canAccess() { return PNApplication::$instance->user_management->has_right("see_lab_component_info"); }
	
}
?>