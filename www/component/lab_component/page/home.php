<?php 
class page_home extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		/* Lab 1  */
		$this->addJavascript("/static/lab_component/lab.js");
		$this->onload("initLab();");
		$this->addJavascript("/static/widgets/section/section.js");
		$this->onload("sectionFromHTML('lab_section');");
		/* Lab 2 */
		$this->requireJavascript("data_list.js"); 
			
		?>
		<!-- Lab1   : using a widget -->
		<div id="lab_section" title='Lab 1 : using a service ' collapsable='true' icon="/static/theme/default/icons_16/info.png" style="margin: 10px;width: 250px;">
			<button id="lab_button" class="action" >Click Me!</button>
			<div id="lab_result">
			</div>
		</div>
		<!--Lab 2 (DataDisplay) : datalist-->
		<div id="lab_list"></div>
	<?php
	}
	
}
?>