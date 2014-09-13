<?php header("Content-Type: text/javascript");?>
var javascripts_paths = [
<?php 
function browse($path, $component, $sub_path) {
	$d = opendir($path);
	while (($filename = readdir($d)) <> null) {
		if ($filename == "." || $filename == "..") continue;
		if (is_dir($path."/".$filename))
			browse($path."/".$filename, $component, $sub_path.$filename."/");
		else if (substr($filename,strlen($filename)-3) == ".js")
			echo "{name:".json_encode($filename).",path:".json_encode("/static/".$component.$sub_path.$filename)."},";
	}
	closedir($d);
}
$dir = opendir("component");
while (($filename = readdir($dir)) <> null) {
	if (!is_dir("component/".$filename)) continue;
	if ($filename == "." || $filename == "..") continue;
	if (file_exists("component/".$filename."/static"))
		browse("component/".$filename."/static", $filename, "/");
}
closedir($dir);
?>
	{name:"",path:""}
];

function require(javascript,handler) {
	if (javascript instanceof Array || getObjectClassName(javascript) == "Array") {
		var nb = javascript.length;
		for (var i = 0; i < javascript.length; ++i) {
			if (javascript[i] instanceof Array || getObjectClassName(javascript[i]) == "Array")
				require_sequential(javascript[i],function(){
					if (--nb == 0 && handler) handler();
				});
			else
				require(javascript[i],function(){
					if (--nb == 0 && handler) handler();
				});
		}
		return;
	}
	for (var i = 0; i < javascripts_paths.length; ++i)
		if (javascripts_paths[i].name == javascript) {
			addJavascript(javascripts_paths[i].path, handler);
			return;
		}
	alert("Unknown javascript '"+javascript+"'");
}
function require_sequential(scripts, handler) {
	var pos = 0;
	var next = function() {
		require(scripts[pos], function(){
			if (++pos >= scripts.length) {
		 		if (handler) handler();
		 		next = null;
		 		return;
			}
			next();
		});
	};
	next();
}