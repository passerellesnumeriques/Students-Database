<?php 
require_once("component/lab_component/LabComponentPlugin.inc");
class FoobarLabComponent implements LabComponentPlugin {
	
	public function getSound() {
		return "Ahhh";
		}
		
	 public function getSmell(){
		return "cheese";
	 }
	
	
}
?>