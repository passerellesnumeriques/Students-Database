<!DOCTYPE html>
<html>
<head>
	<title>PN Students Database</title>
	<meta http-equiv="Content-Type" content="text/html;charset=UTF-8"/>
	<style type='text/css'>body,html{width:100%;height:100%;margin:0px;padding:0px;}</style>
</head>
<body>
<table id='__loading_table' style='width:100%;height:100%;margin:0px;padding:0px;z-index:100;background-color:white;position:fixed;top:0px;left:0px;'>
<tr><td valign=middle align=center>
<img src='/static/application/logo.png'/>
<br/>
<img src='/static/application/loading_100.gif'/>
</td></tr>
</table>
<script type='text/javascript'>
var _loading_ready = 0;
function _add_javascript(url, callback) {
	var head = document.getElementsByTagName("HEAD")[0];
	var s = document.createElement("SCRIPT");
	s.type = "text/javascript";
	head.appendChild(s);
	s.src = url;
	s.onload = function() { this._loaded = true; if (callback) setTimeout(callback,1); };
	s.onreadystatechange = function() { if (this.readyState == 'loaded' || this.readyState == 'complete') { this._loaded = true; if (callback) setTimeout(callback,1); this.onreadystatechange = null; } };

}
window.pn_loading = document.all ? document.all['__loading_table'] : document.getElementById('__loading_table');
function __load_enter_page() {
	next_optional();
	window.status_manager = new StatusManager();
	window.status_manager.status_ui = new StatusUI_Top(window.status_manager);

	add_stylesheet('/static/theme/default/style.css');
	
	var frame = document.createElement("IFRAME");
	frame.frameBorder = "0";
	frame.style.width = "100%";
	frame.style.height = "100%";
	var url = "/dynamic/application/page/enter";
	if (location.search) url += location.search;
	frame.src = url;
	frame.style.position = "fixed";
	frame.style.top = "0px";
	frame.style.left = "0px";
	frame.style.zIndex = "0";
	document.body.appendChild(frame);
}

var _mandatory_scripts = [
	"/static/javascript/utils.js",
	"/static/javascript/browser.js",
	"/static/application/application.js",
	"/static/theme/theme.js.php",
	"/static/widgets/Status.js",
	"/static/widgets/StatusUI_Top.js",
	"/static/javascript/ajax.js",
	"/static/data_model/databaselock.js"
];
var _optional_scripts = [
	"/static/javascript/animation.js",
	"/static/application/service.js"
];

function next_mandatory() {
	_add_javascript(_mandatory_scripts[_loading_ready],function() {
		if (++_loading_ready == _mandatory_scripts.length) __load_enter_page();
		else next_mandatory(); 
	});
}
next_mandatory();
var optional_index = 0;
function next_optional() {
	_add_javascript(_optional_scripts[optional_index],function() {
		if (++optional_index == _optional_scripts.length) return;
		next_optional(); 
	});
}

function pn_loading_start() {
	window.pn_loading.style.visibility = 'visible';
	setOpacity(window.pn_loading, 1);
}
function pn_loading_end() {
	if (typeof animation == 'undefined') {
		setTimeout(pn_loading_end, 100);
		return;
	}
	if (typeof setOpacity == 'undefined') {
		setTimeout(pn_loading_end, 100);
		return;
	}
	animation.fadeOut(window.pn_loading,500);
}
</script>
</body>
</html>