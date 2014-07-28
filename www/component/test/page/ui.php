<?php 
class page_ui extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		?>
		<div style='width:100%;height:100%;display:flex;flex-direction:column;'>
			<iframe name='test_ui_frame' src='/dynamic/application/page/enter?testing=true' style='border:0px;margin:0px;padding:0px;flex:1 1 auto;'></iframe>
			<div style='border-top:1px solid black;background-color:#FFFF80;flex:none;height:50px;'>
				<div id='test_ui_footer' style='width:100%;height:100%;display:flex;flex-direction:row;'>
					<div style='overflow:auto;flex:1 1 auto;'>
						Test Step: <span id='test_ui_action_name'>Starting Browser</span><br/>
						Waiting: <span id='test_ui_wait_time'></span><br/>
					</div>
					<div style='flex:none;'>
						<img src='/static/test/wait_50.gif' id='test_ui_play'/>
					</div>
					<div id='test_ui_message' style='overflow:auto;flex:1 1 auto;'>
					</div>
				</div>
			</div>
		</div>
		<?php 
	}
	
}
?>