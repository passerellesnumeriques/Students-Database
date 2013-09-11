<?php 
function create_wizard_page(&$page, $icon, $title, $first_page) {
?>
<div id='wizard_page' style='width:100%;height:100%'>
	<div id='wizard_page_header' layout='fixed' class='wizard_header' style='padding-top:3px'>
		<img src="<?php echo $icon;?>" style='vertical-align:bottom;padding-left:5px;padding-right:5px'/>
		<?php echo $title;?>
	</div>
	<iframe id='wizard_page_content' layout='fill' frameBorder=0 src="<?php echo $first_page;?>"></iframe>
	<div id='wizard_page_footer' layout='fixed' class='wizard_buttons'>
		<button id='wizard_page_button_previous' disabled='disabled' onclick='wizard_page_previous();'>
			<img src="<?php echo theme::$icons_16["back"];?>" style='vertical-align:bottom'/>
			Previous
		</button>
		<button id='wizard_page_button_next' disabled='disabled' onclick='wizard_page_next();'>
			Next
			<img src="<?php echo theme::$icons_16["forward"];?>" style='vertical-align:bottom'/>
		</button>
		<button id='wizard_page_button_finish' disabled='disabled' onclick='wizard_page_finsh();'>
			<img src="<?php echo theme::$icons_16["ok"];?>" style='vertical-align:bottom'/>
			Finish
		</button>
	</div>
</div>
<script type='text/javascript'>
function enable_wizard_page_previous(enabled) {
	document.getElementById('wizard_page_button_previous').disabled = enabled ? "" : "disabled";
}
function enable_wizard_page_next(enabled) {
	document.getElementById('wizard_page_button_next').disabled = enabled ? "" : "disabled";
}
function enable_wizard_page_finish(enabled) {
	document.getElementById('wizard_page_button_finish').disabled = enabled ? "" : "disabled";
}
function wizard_page_previous() {
	var frame = document.getElementById('wizard_page_content');
	var w = getIFrameWindow(frame);
	enable_wizard_page_previous(false);
	enable_wizard_page_next(false);
	enable_wizard_page_finish(false);
	w.wizard_page_go_previous();
}
function wizard_page_next() {
	var frame = document.getElementById('wizard_page_content');
	var w = getIFrameWindow(frame);
	enable_wizard_page_previous(false);
	enable_wizard_page_next(false);
	enable_wizard_page_finish(false);
	w.wizard_page_go_next();
}
function wizard_page_finish() {
	var frame = document.getElementById('wizard_page_content');
	var w = getIFrameWindow(frame);
	enable_wizard_page_previous(false);
	enable_wizard_page_next(false);
	enable_wizard_page_finish(false);
	w.wizard_page_go_finish();
}
</script>
<?php
	$page->add_javascript("/static/widgets/vertical_layout.js");
	$page->onload("new vertical_layout('wizard_page');");
} 
?>