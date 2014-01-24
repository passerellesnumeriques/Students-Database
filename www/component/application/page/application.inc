<?php 
require_once("component/data_model/page/utils.inc");
$this->add_javascript("/static/widgets/vertical_layout.js");
$this->add_javascript("/static/widgets/horizontal_menu.js");
$this->add_javascript("/static/widgets/context_menu.js");
$this->add_javascript("/static/google/google_status.js");
//$this->add_javascript("/static/facebook/facebook_status.js");
$this->onload("new vertical_layout('pn_application_container');");
$this->onload("new horizontal_menu('application_menu');");
$this->onload("new google_status('google_status');");
//$this->onload("new facebook_status('facebook_status');");
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
				</td><td nowrap="nowrap" class='pn_application_header_section'>
					<div class='button' onclick='help();'><img src='<?php echo theme::$icons_16["help"];?>'/> Help</div>
				</td>
			</tr>
		</table>
	</div>
	<div layout='fill'>
		<div style='width:100%;height:100%' id='help_split'>
			<iframe style='width:100%;height:100%;' src='<?php if (isset($_GET["page"])) echo $_GET["page"]; else echo "/dynamic/application/page/home";?>' id='pn_application_content' name='pn_application_content' frameBorder=0 onload='pn_application_content_load();'></iframe>
		</div>
	</div>
	<div layout='17' id='pn_application_footer'>
		Copyright (c) 2013<?php $now = date("Y");if ($now <> "2013") echo "-".$now;?> - Passerelles numériques
		<span style='position:fixed;bottom:0px;right:3px;'>
			<span id='google_status'></span>
		</span>
		<!-- <span style='position:fixed;bottom:0px;right:23px;'>
			<span id='facebook_status'></span>
		</span> -->
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
/*setBackgroundGradient(
	document.getElementById('pn_application_header'),
	'vertical',
	[
	 {pos:55,color:'#62e8ff'},
	 {pos:85,color:'#22bbea'},
	 {pos:100,color:'#0080a0'}
	]
);*/
setBackgroundGradient(
	document.getElementById('pn_application_header'),
	'vertical',
	[
	 {pos:55,color:'#e0e0ff'},
	 {pos:85,color:'#d0d0f0'},
	 {pos:100,color:'#b0b0d0'}
	]
);
/*setBackgroundGradient(
	document.getElementById('pn_application_header'),
	'vertical',
	[
	 {pos:55,color:'#d0d0f0'},
	 {pos:85,color:'#b0b0e0'},
	 {pos:100,color:'#686898'}
	]
);*/
function pn_application_content_unload() {
	var frame = document.getElementById('pn_application_content');
	if (typeof animation != 'undefined') {
		if (frame.anim) animation.stop(frame.anim);
		frame.anim = animation.fadeOut(frame, 300, function(){frame.style.visibility='hidden';});
	} else
		frame.style.visibility = 'hidden';
}
function pn_application_content_load() {
	var frame = document.getElementById('pn_application_content');
	listenEvent(getIFrameWindow(frame),'unload',pn_application_content_unload);
	if (help_frame) update_help();
	if (typeof animation != 'undefined') {
		if (frame.anim) animation.stop(frame.anim);
		frame.anim = animation.fadeIn(frame, 300, function(){});
		frame.style.visibility = 'visible';
	} else
		frame.style.visibility = 'visible';
}

function logout() {
	window.top.pn_loading_start();
	window.top.set_loading_message('Logging out...');
	location='/dynamic/application/page/logout';
}

window.top.require("google.js",function(){
	window.top.google.need_connection(function(){
		service.json("google","set_google_id",{auth_token:window.top.gapi.auth.getToken()["access_token"]},function(res){});
	});
});
/*window.top.require("facebook.js",function(){
	window.top.facebook.onconnect(function(){
		service.json("facebook","set_facebook_id",{auth_token:window.top.facebook.access_token},function(res){});
	});
});*/

require("load_static.js",function(){
	new load_static_resources('background_loading_application');
});

var help_frame = null;
function update_help() {
	var frame = document.getElementById('pn_application_content');
	var url = new URL(frame.src);
	url.params = {};
	url.path = url.path.substring(9); // remove /dynamic/
	var i = url.path.indexOf('/');
	var component = url.path.substring(0,i);
	var path = url.path.substring(i+5); // remove /page/
	url.path = "/help/"+component+"/"+path;
	help_frame.src = url.toString(); 
}
function help() {
	var split = document.getElementById('help_split');
	if (help_frame == null) {
		help_frame = document.createElement("IFRAME");
		help_frame.frameBorder = 0;
		help_frame.style.border = "none";
		update_help();
		split.appendChild(help_frame);
		require("splitter_vertical.js",function() {
			new splitter_vertical(split, 0.6);
		});
	} else {
		split.widget.remove();
		split.removeChild(help_frame);
		help_frame = null;
		var frame = document.getElementById('pn_application_content');
		frame.style.width = "100%";
		frame.style.height = "100%";
	}
}
require("splitter_vertical.js");
</script>