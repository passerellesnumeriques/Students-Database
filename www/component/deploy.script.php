<?php 
$components_names = array();
$dir = @opendir(dirname(__FILE__));
if (!$dir) die("Directory not found: ".dirname(__FILE__));
while (($filename = readdir($dir)) <> null) {
	if (substr($filename, 0, 1) == ".") continue;
	if (is_dir(dirname(__FILE__)."/".$filename)) array_push($components_names, $filename);
}
closedir($dir);

if (!function_exists("processRelativePath")) {
	
function processRelativePath($dirname, $component_name, $content, $directive) {
	$i = 0;
	while (($i = strpos($content, $directive, $i)) !== false) {
		$j = strpos($content, "\"", $i);
		if ($j === false || $j <= 0) break;
		$k = strpos($content, "\"", $j+1);
		if ($k === false) break;
		$p = substr($content, $j+1, $k-$j-1);
		$i += strlen($directive);
		if (substr($p, 0, 10) <> "component/" && file_exists("$dirname/$component_name/$p")) {
			$content = substr($content,0,$j+1)."component/$component_name/$p".substr($content,$k);
		}
	}
	return $content;
}

function searchEnding($content, $pos, $open, $close) {
	$count = 0;
	$quote = null;
	while ($pos < strlen($content)) {
		$c = substr($content,$pos,1);
		$pos++;
		if ($quote <> null) {
			if ($c == "\\") {
				$pos++;
				continue;
			}
			if ($c == $quote) {
				$quote = null;
				continue;
			}
			continue;
		}
		if ($c == "'") {
			$quote = "'";
			continue;
		}
		if ($c == "\"") {
			$quote = "\"";
			continue;
		}
		if ($c == $close) {
			if ($count == 0) return $pos-1;
			$count--;
			continue;
		}
		if ($c == $open) {
			$count++;
			continue;
		}
		if ($c == "{") {
			$pos = searchEnding($content,$pos,"{","}");
			if ($pos === false) return false;
			$pos++;
			continue;
		}
		if ($c == "(") {
			$pos = searchEnding($content,$pos,"(",")");
			if ($pos === false) return false;
			$pos++;
			continue;
		}
	}
	return false;
}

function removeFunction(&$content, $fct_name) {
	$i = 0;
	while (($i = strpos($content, "function", $i)) !== false) {
		$j = strpos($content,"(",$i+8);
		if ($j === false) break;
		$name = trim(substr($content,$i+8,$j-$i-8));
		if ($name <> $fct_name) {
			$i = $j+1;
			continue;
		}
		// search for visibility
		$k = strrpos(substr($content,0,$i), "public");
		if ($k !== false) {
			$between = substr($content,$k+6,$i-$k-6);
			if (trim($between) == "") $i = $k;
		}
		$j = strpos($content,")",$j+1);
		if ($j === false) break;
		$j = strpos($content,"{",$j+1);
		if ($j === false) break;
		$k = searchEnding($content, $j+1, "{", "}");
		if ($k === false) break;
		$content = trim(substr($content,0,$i))."\n".trim(substr($content,$k+1));
		break;
	}
}

function processService($filename) {
	$content = file_get_contents($filename);
	removeFunction($content, "documentation");
	removeFunction($content, "inputDocumentation");
	removeFunction($content, "outputDocumentation");
	$f = fopen($filename,"w");
	fwrite($f,$content);
	fclose($f);
}

function processComponentServices($path) {
	$dir = opendir($path);
	while (($file = readdir($dir)) <> null) {
		if ($file == "." || $file == "..") continue;
		if (is_dir($path."/".$file))
			processComponentServices($path."/".$file);
		else if (substr($file,strlen($file)-4) == ".php")
			processService($path."/".$file);
	}
	closedir($dir);
}

function processComponent($dirname, $name, &$done, &$components_order, &$components_content) {
	if (in_array($name, $done)) return;
	array_push($done, $name);

	$deps = array();
	if (file_exists("$dirname/$name/dependencies")) {
		$f = fopen("$dirname/$name/dependencies","r");
		while (($line = fgets($f,4096)) !== FALSE) {
			$line = trim($line);
			if (strlen($line) == 0) continue;
			$i = strpos($line,":");
			if ($i !== FALSE) $line = substr($line,0,$i);
			array_push($deps, $line);
		}
		fclose($f);
		unlink("$dirname/$name/dependencies");
	}
	
	$content = file_get_contents("$dirname/$name/$name.inc");
	unlink("$dirname/$name/$name.inc");
	
	$readable_rights = "return array();";
	if (file_exists("$dirname/$name/readable_rights.inc")) {
		$readable_rights = file_get_contents("$dirname/$name/readable_rights.inc");
		unlink("$dirname/$name/readable_rights.inc");
		$i = strpos($readable_rights, "<?php");
		$readable_rights = substr($readable_rights,$i+5);
		$i = strrpos($readable_rights, "?>");
		$readable_rights = substr($readable_rights,0,$i);
	}
	$writable_rights = "return array();";
	if (file_exists("$dirname/$name/writable_rights.inc")) {
		$writable_rights = file_get_contents("$dirname/$name/writable_rights.inc");
		unlink("$dirname/$name/writable_rights.inc");
		$i = strpos($writable_rights, "<?php");
		$writable_rights = substr($writable_rights,$i+5);
		$i = strrpos($writable_rights, "?>");
		$writable_rights = substr($writable_rights,0,$i);
	}
	$i = 0;
	do {
		$i = strpos($content, "extends", $i);
		if ($i === false) throw new Exception("Error in component $name: unable to find where the class begins (expected is extends Component)");
		$next = trim(substr($content, $i+8));
		if (substr($next,0,9) <> "Component") {
			$i += 7;
			continue;
		}
		$i = strpos($content, "{", $i);
		if ($i === false) throw new Exception("Error in component $name: unable to find where the class begins (no { after extends Component)");
		$content = 
			substr($content,0,$i+1).
			"function getReadableRights(){".$readable_rights."} function getWritableRights(){".$writable_rights."}".
			substr($content,$i+1);
		break;
	} while ($i > 0);
	
	$content = trim($content);
	
	if (substr($content,0,5) == "<?php" && substr($content,strlen($content)-2) == "?>") {
		$content = substr($content,5,strlen($content)-5-2);
	} else
		$content = "?>".$content."<?php ";
	
	$content = processRelativePath($dirname, $name, $content, "require_once");
	$content = processRelativePath($dirname, $name, $content, "require");
	$content = processRelativePath($dirname, $name, $content, "include_once");
	$content = processRelativePath($dirname, $name, $content, "include");
	$content = processRelativePath($dirname, $name, $content, "file_get_contents");
	$content = processRelativePath($dirname, $name, $content, "readfile");
	
	$components_content .= $content;
	
	// process files of component
	if (file_exists("$dirname/$name/service"))
		processComponentServices("$dirname/$name/service");
	
	foreach ($deps as $dep) processComponent($dirname, $dep, $done, $components_order, $components_content);
	array_push($components_order, $name);
}

function browseComponentForJS($fs_path, $url_path, &$mapping) {
	$d = opendir($fs_path);
	while (($filename = readdir($d)) <> null) {
		if ($filename == "." || $filename == "..") continue;
		if (is_dir($path."/".$filename))
			browseComponentForJS($path."/".$filename, $url_path.$filename."/");
		else if (substr($filename,strlen($filename)-3) == ".js")
			$mapping[$filename] = $url_path.$filename;
	}
	closedir($d);
}

} // end of functions
$done = array();
$components_order = array();
$components_content = "";
foreach ($components_names as $name) processComponent(dirname(__FILE__), $name, $done, $components_order, $components_content);

$s = file_get_contents(dirname(__FILE__)."/PNApplication.inc");
$i = strrpos($s, "?>");
$s = substr($s,0,$i);

$create = "";
$order = "\$components = array(";
$first = true;
foreach ($components_order as $name) {
	if ($first) $first = false; else $order .= ",";
	$order .= "\"".$name."\"";
	$create .= "\$this->components[\"$name\"] = \$this->{\"".$name."\"} = new $name(\"$name\");";
}
$order .= ");";

$s = str_replace("##CREATE_COMPONENTS##", $create, $s);
$s = str_replace("##COMPONENTS_ORDER##", $order, $s);
$s .= $components_content;
$s .= "?>";

$f = fopen(dirname(__FILE__)."/PNApplication.inc","w");
if (!$f) die("Unable to write in PNApplication.inc");
fwrite($f, $s);
fclose($f);

// generate javascript mapping for require function
$js_map = array();
foreach ($components_order as $name) {
	browseComponentForJS(dirname(__FILE__)."/$name/static", "/static/$name/", $js_map);
}
$f = fopen(dirname(__FILE__)."/javascript.paths","w");
fwrite($f, "<?php return ".var_export($js_map,true).";?>");
fclose($f);
?>