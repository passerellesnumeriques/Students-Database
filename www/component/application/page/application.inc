<?php 
require_once("component/data_model/page/utils.inc");
$this->add_javascript("/static/widgets/vertical_layout.js");
$this->add_javascript("/static/widgets/horizontal_menu.js");
$this->add_javascript("/static/widgets/context_menu.js");
$this->add_javascript("/static/google/google_status.js");
$this->onload("new vertical_layout('pn_application_container');");
$this->onload("new horizontal_menu('application_menu');");
$this->onload("new google_status('google_status');");
$this->onload("setBorderRadius(document.forms['app_search'].elements['q'],8,8,8,8,8,8,8,8);");
$this->onload("setBoxShadow(document.forms['app_search'].elements['q'],-1,2,2,0,'#D8D8F0',true);");
$this->onload("setBorderRadius(document.getElementById('app_domain'),5,5,5,5,5,5,5,5);");
$this->onload("setBoxShadow(document.getElementById('app_domain'),-1,2,2,0,'#D8D8F0',true);");
$this->onload("window.top.pn_loading_end();");
if (PNApplication::$instance->current_domain == "Dev") {
	$this->add_javascript("/static/development/debug_status.js");
	$this->onload("new debug_status('debug_status');");
}
?>
<div style='width:100%;height:100%;overflow:hidden' id='pn_application_container'>
	<div layout='28' id='pn_application_header'>
		<table style='width:100%;height:100%;border-collapse:collapse;border-spacing:0;'>
			<tr>
				<td width='100%' style='padding-left:40px' align=center>
					<div class='horizontal_menu' id='application_menu'>
						<div class='application_menu_item' onclick="document.getElementById('pn_application_content').src='/dynamic/selection/page/home';"><img src='/static/selection/selection_16.png'/>Selection</div>
						<div class='application_menu_item' onclick="document.getElementById('pn_application_content').src='/dynamic/training_education/page/home';"><img src='/static/students/student_16.png'/>Training & Education</div>
						<div class='application_menu_item' onclick="document.getElementById('pn_application_content').src='/dynamic/administration/page/home';"><img src='/static/administration/admin_16.png'/>Administration</div>
						<div class='application_menu_item'><img src='<?php echo theme::$icons_16["more_menu"];?>'/>More</div>
					</div>
				</td>
				<td nowrap="nowrap" class='pn_application_header_section'>
					<form name='app_search' action="/dynamic/application/page/search" method="get" target="pn_application_content" style='display:inline;'>
						<input name="q" type='text' size=15 style="background:#ffffff url('<?php echo theme::$icons_16["search"];?>') no-repeat 3px 1px;margin:0px;padding:2px 4px 2px 23px;border:1px solid #8080FF;font-size:11px" autocomplete="off"/>
					</form>
				</td>
				<td nowrap="nowrap" class='pn_application_header_section' style='cursor:pointer' onclick="new context_menu('user_settings_menu').showBelowElement(this, true);">
					<span id='user_first_name'></span> <span id='user_last_name'></span>
					<?php 
					$people = SQLQuery::create()->bypass_security()->select("People")->where("id",PNApplication::$instance->user_people->user_people_id)->execute_single_row();
					datamodel_cell($this, "user_first_name", false, "People", "first_name", PNApplication::$instance->user_people->user_people_id, null, $people["first_name"]);
					datamodel_cell($this, "user_last_name", false, "People", "last_name", PNApplication::$instance->user_people->user_people_id, null, $people["last_name"]);
					?>
					<img src='/static/application/settings.png' style='vertical-align:bottom'/>
					<div id='user_settings_menu' class='context_menu'>
						<div class='context_menu_item' onclick="document.getElementById('pn_application_content').src='/dynamic/people/page/profile?people=<?php echo PNApplication::$instance->user_people->user_people_id?>';">
							<img src='/static/people/profile_16.png'/> Profile
						</div>
						<div class='context_menu_item' onclick="document.getElementById('pn_application_content').src='/dynamic/calendar/page/calendars';">
							<img src='/static/calendar/event.png'/> Calendars
						</div>
						<div class='context_menu_item' onclick="logout();" id='user_logout_menu'>
							<img src='/static/application/logout.gif'/> Logout
						</div>
					</div>
				</td><td nowrap="nowrap" class='pn_application_header_section' title="Database">
					<select id='app_domain' style="padding:0px 0px 0px 16px;margin:0px;background:#ffffff url('/static/application/database.png') no-repeat 0px 1px;border:1px solid #8080FF;" onchange="service.json('application','set_domain',{domain:this.value},function(result){if(result)location.reload();});">
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
		<span style='position:fixed;bottom:0px;right:3px;'>
			<span id='google_status'></span>
		</span>
		<?php if (PNApplication::$instance->current_domain == "Dev") { ?>
		<span style='position:fixed;bottom:0px;right:23px;'>
			<span id='debug_status'></span>
		</span>
		<?php } ?>

		<span style='position:fixed;bottom:0px;left:3px;' id='background_loading_application'>
		</span>
	</div>
</div>
<a href="/dynamic/application/page/home" target="pn_application_content" style='position:fixed;top:-2px;left:2px'>
	<img src='/static/application/logo.png' height=32px border=0 style='vertical-align:top'/>
</a>
<script type='text/javascript'>
window.top.default_country_code = <?php $d = PNApplication::$instance->get_domain_descriptor(); echo json_encode($d["country_code"]).";"; ?>

setInterval(function(){
	service.json("application","ping",null,function(r){});
},30*1000); // every 30 seconds
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

function logout() {
	window.top.pn_loading_start();
	window.top.set_loading_message('Logging out...');
	location='/dynamic/application/page/logout';
}

require("load_static.js",function(){
	new load_static_resources('background_loading_application');
});
</script>