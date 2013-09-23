<?php 
$this->add_javascript("/static/widgets/vertical_layout.js");
$this->add_javascript("/static/widgets/context_menu.js");
$this->add_javascript("/static/google/google_status.js");
$this->onload("new vertical_layout('pn_application_container');");
$this->onload("new google_status('google_status');");
$this->onload("window.top.pn_loading_end();");
if (PNApplication::$instance->current_domain == "Dev") {
	$this->add_javascript("/static/development/debug_status.js");
	$this->onload("new debug_status('debug_status');");
}
?>
<div style='width:100%;height:100%;overflow:hidden' id='pn_application_container'>
	<div layout='28' id='pn_application_header'>
		<table style='width:100%;height:100%'>
			<tr>
				<td width='100%' style='padding-left:40px'>Menu</td>
				<td nowrap="nowrap" class='pn_application_header_section'>
					<img src='<?php echo theme::$icons_16["search"];?>' style='vertical-align:bottom'/><form action="/dynamic/application/page/search" method="get" target="pn_application_content" style='display:inline;'><input name="q" type='text' size=15 style='margin:0px;padding:0px;border:1px solid #8080FF;font-size:11px' autocomplete="off"/></form>
				</td>
				<td nowrap="nowrap" class='pn_application_header_section' style='cursor:pointer' onclick="new context_menu('user_settings_menu').showBelowElement(this);">
					<?php echo PNApplication::$instance->user_people->first_name." ".PNApplication::$instance->user_people->last_name;?>
					<img src='/static/application/settings.png' style='vertical-align:bottom'/>
					<div id='user_settings_menu' class='context_menu'>
						<div class='context_menu_item' onclick="document.getElementById('pn_application_content').src='/dynamic/people/page/profile?people=<?php echo PNApplication::$instance->user_people->user_people_id?>';">
							<img src='/static/people/profile_16.png'/> Profile
						</div>
						<div class='context_menu_item' onclick="location='/dynamic/application/page/logout';">
							<img src='/static/application/logout.gif'/> Logout
						</div>
					</div>
				</td><td nowrap="nowrap" class='pn_application_header_section' title="Database">
					<img src='/static/application/database.png' style='vertical-align:bottom'/>
					<select style='padding:0px;margin:0px' onchange="service.json('application','set_domain',{domain:this.value},function(result){if(result)location.reload();});">
					<?php
					foreach (PNApplication::$instance->get_domains() as $domain=>$descr)
						echo "<option value='".$domain."'".($domain == PNApplication::$instance->current_domain ? " selected" : "").">".$domain."</option>";
					?>
					</select>
				</td>
			</tr>
		</table>
	</div>
	<iframe layout='fill' src='/dynamic/application/page/home' id='pn_application_content' name='pn_application_content' frameBorder=0 onload='pn_application_content_load();'></iframe>
	<div layout='17' id='pn_application_footer'>
		Copyright (c) 2013<?php $now = date("Y");if ($now <> "2013") echo "-".$now;?> - Passerelles numériques
		<span id='application_loading' style='position:fixed;bottom:1px;left:2px;z-index:5;visibility:hidden'><span>Loading application</span> <iframe style='width:100px;height:10px;border:1px solid black;overflow:hidden;vertical-align:bottom' scrolling='no' src='/static/application/load.php?delay=10000'></iframe><span id='application_loading_text'></span></span>
		<span style='position:fixed;bottom:0px;right:3px;'>
			<span id='google_status'></span>
		</span>
		<?php if (PNApplication::$instance->current_domain == "Dev") { ?>
		<span style='position:fixed;bottom:0px;right:23px;'>
			<span id='debug_status'></span>
		</span>
		<?php } ?>
	</div>
</div>
<a href="/dynamic/application/page/home" target="pn_application_content" style='position:fixed;top:-2px;left:2px'>
	<img src='/static/application/logo.png' height=32px border=0 style='vertical-align:top'/>
</a>
<script type='text/javascript'>
setBackgroundGradient(
	document.getElementById('pn_application_header'),
	'vertical',
	[
	 {pos:55,color:'#d0d0f0'},
	 {pos:85,color:'#b0b0e0'},
	 {pos:100,color:'#686898'}
	]
);
function pn_application_content_unload() {
	if (window.pn_application_content_hider != null) return;
	var frame = document.getElementById('pn_application_content');
	var div = document.createElement("DIV");
	div.style.position = 'fixed';
	div.style.top = absoluteTop(frame)+"px";
	div.style.left = absoluteLeft(frame)+"px";
	div.style.width = frame.offsetWidth+"px";
	div.style.height = frame.offsetHeight+"px";
	div.style.backgroundColor = '#808080';
	div.style.zIndex = 25;
	if (typeof animation != 'undefined')
		div.anim = animation.fadeIn(div, 500, function(){}, 0, 50);
	else
		setOpacity(div, 50);
	document.body.appendChild(div);
	window.pn_application_content_hider = div;
}
function pn_application_content_load() {
	var frame = document.getElementById('pn_application_content');
	listenEvent(getIFrameWindow(frame),'unload',pn_application_content_unload);
	if (window.pn_application_content_hider == null) return;
	var div = window.pn_application_content_hider;
	window.pn_application_content_hider = null;
	if (typeof animation != 'undefined') {
		if (div.anim) animation.stop(div.anim);
		animation.fadeOut(div, 300, function(){document.body.removeChild(div);}, 50, 0);
	} else
		document.body.removeChild(div);
}
</script>