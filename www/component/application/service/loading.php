<?php 
class service_loading extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Loading code, to be in dynamic section and not in /"; }
	public function inputDocumentation() { echo "None"; }
	public function outputDocumentation() { echo "The JavaScript code"; }
	
	public function getOutputFormat($input) { return "text/javascript"; }
	
	public function execute(&$component, $input) {
$mandatory = array(
		"/static/javascript/utils.js",
		"/static/javascript/browser.js",
		"/static/javascript/utils_js.js",
		"/static/javascript/utils_html.js",
		"/static/javascript/utils_dom.js",
		"/static/javascript/utils_scroll.js",
		"/static/application/config.js.php",
		"/static/javascript/layout.js",
		"/static/application/application.js",
		"/static/theme/theme.js.php",
		"/static/javascript/ajax.js",
		"/static/data_model/databaselock.js",
		"/static/data_model/datamodel.js"
);
$optional = array("/static/widgets/common_dialogs.js");
$optional_delayed = array();
if (PNApplication::$instance->user_management->username == null) {
	array_push($optional, "/static/javascript/animation.js");
	array_push($optional, "/static/application/service.js");
	array_push($optional, "/static/widgets/status.js");
	array_push($optional, "/static/widgets/status_ui_top.js");
	array_push($optional_delayed, "/static/google/google.js");
} else {
	array_push($mandatory, "/static/application/service.js");
	array_push($optional, "/static/javascript/animation.js");
	array_push($optional, "/static/widgets/status.js");
	array_push($optional, "/static/widgets/status_ui_top.js");
	array_push($optional_delayed, "/static/google/google.js");
}
function get_script_info(&$a) {
	for ($i = 0; $i < count($a); ++$i) {
		$j = strpos($a[$i], "/", 8);
		$a[$i] = array($a[$i], filesize("component/".substr($a[$i],8,$j-8)."/static/".substr($a[$i],$j+1)));
	}
}
get_script_info($mandatory);
get_script_info($optional);
get_script_info($optional_delayed);
$total = 0;
foreach ($mandatory as $s) $total += $s[1];
foreach ($optional as $s) $total += $s[1];
?>
window.top.google_local_config = {};
<?php 
$secrets = include("conf/secrets.inc");
echo "window.top.google_local_config.api_key = ".json_encode($secrets["Google"]["client_api_key"]).";";
echo "window.top.google_local_config.client_id = ".json_encode($secrets["Google"]["client_id"]).";";
?>
window.top.google_local_config = <?php

$d = PNApplication::$instance->getDomainDescriptor(); 
echo json_encode($d["google"]);
?>;
window.top.pn_app_version = <?php
global $pn_app_version;
echo json_encode($pn_app_version); 
?>;

var _mandatory_index = 0;
function _addJavascript(url, callback) {
	var head = document.getElementsByTagName("HEAD")[0];
	var s = document.createElement("SCRIPT");
	s.type = "text/javascript";
	s.onload = function() { this._loaded = true; if (callback) setTimeout(callback,1); };
	//s.onerror = function() { alert("Error loading initial javascript file: "+this.src); };
	s.onreadystatechange = function() { if (this.readyState == 'loaded') { this._loaded = true; if (callback) setTimeout(callback,1); this.onreadystatechange = null; } };
	head.appendChild(s);
	s.src = url;
}
window.pn_loading = document.all ? document.all['__loading_table'] : document.getElementById('__loading_table');
var pn_loading_visible = true;
var enter_page_called = false;
function __load_enter_page() {
	next_optional();
	if (enter_page_called) return;
	enter_page_called = true;
	
	addStylesheet('/static/theme/default/style/global.css');
	
	var frame = document.createElement("IFRAME");
	frame.frameBorder = "0";
	frame.style.width = "100%";
	frame.style.height = "100%";
	var url = "/dynamic/application/page/enter";
	if (location.search) url += location.search;
	frame.onload = pn_loading_frame_loaded;
	frame.onerror = pn_loading_frame_loaded;
	frame.src = url;
	frame.style.position = "fixed";
	frame.style.top = "0px";
	frame.style.left = "0px";
	frame.style.zIndex = "0";
	frame.name = "pn_application_frame";
	document.body.appendChild(frame);
}

var _mandatory_scripts = <?php echo json_encode($mandatory);?>;
var _optional_scripts = <?php echo json_encode($optional);?>;
var _optional_delayed_scripts = <?php echo json_encode($optional_delayed);?>;
var total_size = <?php echo $total;?>;
var loaded_size = 0;

function update_size() {
	if (loaded_size < total_size) {
		var pc = Math.floor(loaded_size*100/total_size);
		document.getElementById('loading_status').innerHTML = "Loading ("+pc+"%)";
	} else {
		document.getElementById('loading_status').innerHTML = "<?php echo PNApplication::$instance->user_management->username <> null ? "Starting Application" : "Loading Authentication Page";?>...";
	}
}

var mandatory_loaded = 0;
function next_mandatory() {
	if (_mandatory_index >= _mandatory_scripts.length) return;
	var i = _mandatory_index++;
	_addJavascript(_mandatory_scripts[i][0],function() {
		loaded_size += _mandatory_scripts[i][1];
		update_size();
		if (++mandatory_loaded >= _mandatory_scripts.length) __load_enter_page();
		else next_mandatory(); 
	});
}
update_size();
next_mandatory();
next_mandatory();
next_mandatory();
next_mandatory();
next_mandatory();
var optional_index = 0;
var optional_loaded = 0;
function next_optional() {
	if (optional_index >= _optional_scripts.length) return;
	var i = optional_index++;
	_addJavascript(_optional_scripts[i][0],function() {
		loaded_size += _optional_scripts[i][1];
		update_size();
		if (++optional_loaded >= _optional_scripts.length) {
			window.status_manager = new StatusManager();
			window.status_manager.status_ui = new StatusUI_Top(window.status_manager);
			setTimeout(function(){
				for (var i = 0; i < _optional_delayed_scripts.length; ++i) {
					_addJavascript(_optional_delayed_scripts[i][0]);
				}
			},5000);
			return;
		}
		next_optional(); 
	});
}

function pn_loading_frame_loaded() {
	setTimeout(function() {
		pn_loading_end();
	},1000);
}
function pn_loading_start() {
	if (pn_loading_visible) return;
	pn_loading_visible = true;
	window.pn_loading.style.visibility = 'visible';
	setOpacity(window.pn_loading, 1);
}
function pn_loading_end() {
	document.getElementById('loading_status').innerHTML = "";
	if (!pn_loading_visible) return;
	if (typeof animation == 'undefined') {
		setTimeout(pn_loading_end, 100);
		return;
	}
	if (typeof setOpacity == 'undefined') {
		setTimeout(pn_loading_end, 100);
		return;
	}
	animation.fadeOut(window.pn_loading,500);
	pn_loading_visible = false;
}
function set_loading_message(msg) {
	document.getElementById('loading_status').innerHTML = msg;
}
	<?php 	
	}
	
}
?>