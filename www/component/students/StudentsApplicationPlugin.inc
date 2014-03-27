<?php 
require_once("component/application/ApplicationPlugin.inc"); 
class StudentsApplicationPlugin implements ApplicationPlugin {
	public function getPriority() { return 20; }
	
	public function getIcon16() { return "/static/students/student_16.png"; }
	public function getIcon32() { return "/static/students/student_32.png"; }
	public function getName() { return "Students"; }
	public function getInfoText() { return "Students information: education, training, finance"; }
	
	public function getPageURL() { return "/dynamic/students/page/tree#current_students"; }
	public function getMenuProviderService() { return "/static/students/application_menu_provider.js"; }
}
?>