<?php 
require_once("component/application/ApplicationSectionPlugin.inc");
class InternshipApplicationSection implements ApplicationSectionPlugin {
	
	public function getId() { return "internship"; }
	public function getName() { return "Internship"; }
	public function getDescription() { return "Companies information, internships follow-up..."; }
	public function getPriority() { return 100; }
	public function getIcon16White() { return "/static/internship/internship_white.png"; }
	public function getIcon32() { return "/static/internship/internship_32.png"; }
	public function getComponentsForURL() { return array("internship"); }
	public function getMenuURL() { return "/dynamic/internship/service/menu"; }
	public function getDefaultPageURL() { return "/dynamic/internship/page/home"; }
	
}
?>