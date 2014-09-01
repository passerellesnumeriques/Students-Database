<?php 
class page_home extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		/* Lab 1  */
		$this->addJavascript("/static/lab_component/lab.js");
		$this->onload("initLab();");
		$this->addJavascript("/static/widgets/section/section.js");
		$this->onload("sectionFromHTML('lab_section');");
		/* For Lab 2 */
		$this->requireJavascript("data_list.js"); 
		
		?>
		<!-- Lab1   : using a widget -->
		<div id="lab_section" title='section widget !' collapsable='true' icon="/static/theme/default/icons_16/info.png" style="margin: 10px;width: 250px;">
			<button id="lab_button" class="action" >Click Me!</button>
			<div id="lab_result"></div>
		</div>
		<!--Lab 2 (DataDisplay) : datalist-->
		<div id="lab_list"></div>
		<!--Lab 3-->
		<br/>
		<span style="background-color: red;color: white;">
		<?php
		foreach (PNApplication::$instance->components as $cname=>$comp)
			foreach ($comp->getPluginImplementations() as $pi)
				if ($pi instanceof LabComponentPlugin){
					echo "I hear this sound : ".$pi->getSound()." from ".$cname."<br />";
					echo $cname." smells like ".$pi->getSmell();
				}
		?>
		</span>
	<?php
	}
	
}
?>