<?php 
function create_wizard_page(&$page, $icon, $title, $first_page) {
?>
<div id='wizard_page' style='width:100%;height:100%'>
	<div id='wizard_page_header' layout='fixed' class='wizard_header' style='padding-top:3px'>
		<img src="<?php echo $icon;?>" style='vertical-align:bottom;padding-left:5px;padding-right:5px'/>
		<span id='wizard_page_title'><?php echo $title;?></span>
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
		<button id='wizard_page_button_finish' disabled='disabled' onclick='wizard_page_finish();'>
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
function hide_wizard_page_previous(hide) {
	document.getElementById('wizard_page_button_previous').style.visibility = hide ? "hidden" : "visible";
}
function hide_wizard_page_next(hide) {
	document.getElementById('wizard_page_button_next').style.visibility = hide ? "hidden" : "visible";
}
function wizard_page_previous() {
	wizard_freeze();
	var frame = document.getElementById('wizard_page_content');
	var w = getIFrameWindow(frame);
	enable_wizard_page_previous(false);
	enable_wizard_page_next(false);
	enable_wizard_page_finish(false);
	w.wizard_page_go_previous();
}
function wizard_page_next() {
	wizard_freeze();
	var frame = document.getElementById('wizard_page_content');
	var w = getIFrameWindow(frame);
	enable_wizard_page_previous(false);
	enable_wizard_page_next(false);
	enable_wizard_page_finish(false);
	w.wizard_page_go_next();
}
function wizard_page_finish() {
	wizard_freeze();
	var frame = document.getElementById('wizard_page_content');
	var w = getIFrameWindow(frame);
	enable_wizard_page_previous(false);
	enable_wizard_page_next(false);
	enable_wizard_page_finish(false);
	w.wizard_page_go_finish();
}
function wizard_page_loaded() {
	wizard_unfreeze();
}
wizard_freeze_div = document.createElement("DIV");
wizard_freeze_div.style.position = "absolute";
wizard_freeze_div.style.backgroundColor = "rgba(192, 192, 192, 0.5)";
wizard_freeze_div.innerHTML = "<table style='width:100%;height:100%'><tr><td align=center valign=middle id='wizard_freezer'></td></tr></table>";
function wizard_freeze() {
	var frame = document.getElementById('wizard_page_content');
	wizard_freeze_div.style.top = absoluteTop(frame)+"px";
	wizard_freeze_div.style.left = absoluteLeft(frame)+"px";
	wizard_freeze_div.style.width = getWidth(frame)+"px";
	wizard_freeze_div.style.height = getHeight(frame)+"px";
	wizard_freeze_div.frozen = true;
	frame.parentNode.appendChild(wizard_freeze_div);
	document.getElementById('wizard_freezer').innerHTML = "";
}
function wizard_unfreeze() {
	if (wizard_freeze_div.frozen) {
		wizard_freeze_div.parentNode.removeChild(wizard_freeze_div);
		wizard_freeze_div.frozen = false;
	}
}
function wizard_freeze_message(msg) {
	document.getElementById('wizard_freezer').innerHTML = msg;
}
</script>
<?php
	$page->addJavascript("/static/widgets/vertical_layout.js");
	$page->onload("new vertical_layout('wizard_page');");
} 
?>