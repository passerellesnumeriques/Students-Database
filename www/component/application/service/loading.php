<?php 
class service_loading extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Loading code, to be in dynamic section and not in /"; }
	public function input_documentation() { echo "None"; }
	public function output_documentation() { echo "The JavaScript code"; }
	
	public function get_output_format($input) { return "text/javascript"; }
	
	public function execute(&$component, $input) {
$mandatory = array(
		"/static/javascript/utils.js",
		"/static/javascript/browser.js",
		"/static/application/application.js",
		"/static/theme/theme.js.php",
		"/static/javascript/ajax.js",
		"/static/data_model/databaselock.js",
		"/static/data_model/datamodel.js",
		"/static/application/config.js.php",
		"/static/javascript/layout.js"
);
$optional = array();
$optional_delayed = array();
if (PNApplication::$instance->user_management->username == null) {
	// if we are not yet logged, to allow google or facebook login, we include them as soon as possible
	array_push($mandatory, "/static/google/google.js");
	//array_push($mandatory, "/static/facebook/facebook.js");
	// then the things that are not mandatory on the login page
	array_push($optional, "/static/javascript/animation.js");
	array_push($optional, "/static/application/service.js");
	array_push($optional, "/static/widgets/Status.js");
	array_push($optional, "/static/widgets/StatusUI_Top.js");
} else {
	array_push($mandatory, "/static/application/service.js");
	array_push($optional, "/static/javascript/animation.js");
	array_push($optional, "/static/widgets/Status.js");
	array_push($optional, "/static/widgets/StatusUI_Top.js");
	array_push($optional_delayed, "/static/google/google.js");
	//array_push($optional_delayed, "/static/facebook/facebook.js");
}
function get_script_info(&$a) {
	for ($i = 0; $i < count($a); ++$i) {
		$j = strpos($a[$i], "/", 8);
		$a[$i] = array($a[$i], filesize("component/".substr($a[$i],8,$j-8)."/static/".substr($a[$i],$j)));
	}
}
get_script_info($mandatory);
get_script_info($optional);
get_script_info($optional_delayed);
$total = 0;
foreach ($mandatory as $s) $total += $s[1];
foreach ($optional as $s) $total += $s[1];
?>
var _loading_ready = 0;
function _add_javascript(url, callback) {
	var head = document.getElementsByTagName("HEAD")[0];
	var s = document.createElement("SCRIPT");
	s.type = "text/javascript";
	head.appendChild(s);
	s.src = url;
	s.onload = function() { this._loaded = true; if (callback) setTimeout(callback,1); };
	s.onreadystatechange = function() { if (this.readyState == 'loaded') { this._loaded = true; if (callback) setTimeout(callback,1); this.onreadystatechange = null; } };

}
window.pn_loading = document.all ? document.all['__loading_table'] : document.getElementById('__loading_table');
var pn_loading_visible = true;
function __load_enter_page() {
	next_optional();

	add_stylesheet('/static/theme/default/style/global.css');
	
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
	var pc = Math.floor(loaded_size*100/total_size);
	document.getElementById('loading_status').innerHTML = "Loading ("+pc+"%)";
}

function next_mandatory() {
	_add_javascript(_mandatory_scripts[_loading_ready][0],function() {
		loaded_size += _mandatory_scripts[_loading_ready][1];
		update_size();
		if (++_loading_ready == _mandatory_scripts.length) __load_enter_page();
		else next_mandatory(); 
	});
}
update_size();
next_mandatory();
var optional_index = 0;
function next_optional() {
	_add_javascript(_optional_scripts[optional_index][0],function() {
		loaded_size += _optional_scripts[optional_index][1];
		update_size();
		if (++optional_index == _optional_scripts.length) {
			document.getElementById('loading_status').innerHTML = "<?php echo PNApplication::$instance->user_management->username <> null ? "Starting Application" : "Loading Authentication Page";?>...";
			window.status_manager = new StatusManager();
			window.status_manager.status_ui = new StatusUI_Top(window.status_manager);
			setTimeout(function(){
				for (var i = 0; i < _optional_delayed_scripts.length; ++i) {
					_add_javascript(_optional_delayed_scripts[i][0]);
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
	pn_loading_visible = false;
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
function set_loading_message(msg) {
	document.getElementById('loading_status').innerHTML = msg;
}
	<?php 	
	}
	
}
?>