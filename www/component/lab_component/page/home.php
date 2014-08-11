<?php 
class page_home extends Page {
	
	public function getRequiredRights() { return array(); } // TODO
	
	public function execute() {
		/* Step 2 */
		$this->addJavascript("/static/lab_component/lab.js");
		$this->onload("initLab();");
		$this->addJavascript("/static/widgets/section/section.js");
		$this->onload("sectionFromHTML('lab_section');");
		//echo "Here the home page of lab_component !";
		
		
	/* Step 2  just  a button to use some javascript */ 	
		?>
		<!--Step final : widget-->
		<div id="lab_section" title='section widget !' collapsable='true' icon="/static/theme/default/icons_16/info.png" style="margin: 10px;width: 250px;">
			<button id="lab_button" class="action" >Click Me!</button>
			<div id="lab_result">
			</div>
		</div>
	<?php
	}
	
}
?>