<?php 
$components_names = array();
$dir = @opendir(dirname(__FILE__));
if (!$dir) die("Directory not found: ".dirname(__FILE__));
while (($filename = readdir($dir)) <> null) {
	if (substr($filename, 0, 1) == ".") continue;
	if (is_dir(dirname(__FILE__)."/".$filename)) array_push($components_names, $filename);
}
closedir($dir);

function processComponent($name, &$done, &$components_order, &$components_content) {
	if (in_array($name, $done)) return;
	array_push($done, $name);
	
	$deps = array();
	if (file_exists(dirname(__FILE__)."/$name/dependencies")) {
		$f = fopen(dirname(__FILE__)."/$name/dependencies","r");
		while (($line = fgets($f,4096)) !== FALSE) {
			$line = trim($line);
			if (strlen($line) == 0) continue;
			$i = strpos($line,":");
			if ($i !== FALSE) $line = substr($line,0,$i);
			array_push($deps, $line);
		}
		fclose($f);
		unlink(dirname(__FILE__)."/$name/dependencies");
	}
	
	$content = file_get_contents(dirname(__FILE__)."/$name/$name.inc");
	unlink(dirname(__FILE__)."/$name/$name.inc");
	
	$readable_rights = "return array();";
	if (file_exists(dirname(__FILE__)."/$name/readable_rights.inc")) {
		$readable_rights = file_get_contents(dirname(__FILE__)."/$name/readable_rights.inc");
		unlink(dirname(__FILE__)."/$name/readable_rights.inc");
		$i = strpos($readable_rights, "<?php");
		$readable_rights = substr($readable_rights,$i+5);
		$i = strrpos($readable_rights, "?>");
		$readable_rights = substr($readable_rights,0,$i);
	}
	$writable_rights = "return array();";
	if (file_exists(dirname(__FILE__)."/$name/writable_rights.inc")) {
		$writable_rights = file_get_contents(dirname(__FILE__)."/$name/writable_rights.inc");
		unlink(dirname(__FILE__)."/$name/writable_rights.inc");
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
	
	$components_content .= $content;
	
	foreach ($deps as $dep) processComponent($dep, $done, $components_order, $components_content);
	array_push($components_order, $name);
}
$done = array();
$components_order = array();
$components_content = "";
foreach ($components_names as $name) processComponent($name, $done, $components_order, $components_content);

$s = file_get_contents(dirname(__FILE__)."/PNApplication.inc");
$i = strrpos($s, "?>");
$s = substr($s,0,$i);

$create = "";
$order = "\$components = array(";
$first = true;
foreach ($components_order as $name) {
	if ($first) $first = false; else $order .= ",";
	$order .= "\"".$name."\"";
	$create .= "\$this->components[\"$name\"] = \$this->{".$name."} = new $name(\"$name\");";
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
?>