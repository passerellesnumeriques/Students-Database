<?php 
class page_ui extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$this->add_javascript("/static/widgets/vertical_layout.js");
		$this->add_javascript("/static/widgets/horizontal_layout.js");
		$this->onload("new vertical_layout('test_ui_container');");
		$this->onload("new horizontal_layout('test_ui_footer');");
		?>
		<div style='width:100%;height:100%' id='test_ui_container'>
			<iframe name='test_ui_frame' layout="fill" src='/dynamic/application/page/enter?testing=true' style='border:0px;margin:0px;padding:0px;'></iframe>
			<div layout="50" style='border-top:1px solid black;background-color:#FFFF80;'>
				<div id='test_ui_footer' style='width:100%;height:100%'>
					<div layout="fill" style='overflow:auto'>
						Test Step: <span id='test_ui_action_name'>Starting Browser</span><br/>
						Waiting: <span id='test_ui_wait_time'></span><br/>
					</div>
					<div layout="fixed">
						<img src='/static/test/wait_50.gif' id='test_ui_play'/>
					</div>
					<div layout="fill" id='test_ui_message' style='overflow:auto'>
					</div>
				</div>
			</div>
		</div>
		<?php 
	}
	
}
?>