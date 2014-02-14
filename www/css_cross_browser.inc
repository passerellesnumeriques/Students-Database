<?php 
function parse_css($path) {
	$css = file_get_contents($path);
	// remove comments
	$i = 0;
	while (($j = strpos($css, "/*", $i)) !== false) {
		$k = strpos($css, "*/", $j+2);
		if ($k === false) break;
		$css = substr($css, 0, $j).substr($css,$k+2);
		$i = $j;
	}
	while (($i = strpos($css, "//")) !== false) {
		$j = strpos($css, "\n", $i);
		if ($j === false) $css = substr($css, 0, $i);
		else $css = substr($css, 0, $i).substr($css,$j);
	}
	
	do {
		$i = strpos($css, "{");
		if ($i === false) break;
		echo substr($css, 0, $i+1);
		$css = substr($css, $i+1);
		$i = strpos($css, "}");
		if ($i === false) break;
		$content = substr($css, 0, $i);
		$css = substr($css,$i+1);
		do {
			$attr = read_css_attribute($content);
			if ($attr <> null)
				write_css_attribute($attr);
			else
				break;
		} while (true);
		echo "}";
	} while (true);
	
	echo $css;
}

function read_css_attribute(&$s) {
	$i = strpos($s, ":");
	if ($i === false) return null;
	$name = trim(substr($s, 0, $i));
	$s = trim(substr($s,$i+1));
	$value = "";
	$quot = null;
	for ($i = 0; $i < strlen($s); ++$i) {
		$c = substr($s,$i,1);
		if ($quot <> null) {
			$value .= $c;
			if ($c == $quot)
				$quot = null;
			else if ($c == "\\") {
				$c = substr($s,++$i,1);
				$value .= $c;
			}
			continue;
		}
		if ($c == ";") {
			$i++;
			break;
		}
		$value .= $c;
		if ($c == "'" || $c == "\"") $quot = $c;
	}
	$s = substr($s, $i);
	return array("name"=>$name, "value"=>trim($value));
}
function write_css_attribute($attr) {
	switch ($attr["name"]) {
		case "border-radius":
			echo "border-radius: ".$attr["value"].";";
			echo "-moz-border-radius: ".$attr["value"].";";
			echo "-webkit-border-radius: ".$attr["value"].";";
			return;
		case "border-top-left-radius":
			echo "border-top-left-radius: ".$attr["value"].";";
			echo "-moz-border-radius-topleft: ".$attr["value"].";";
			echo "-webkit-border-top-left-radius: ".$attr["value"].";";
			return;
		case "border-top-right-radius":
			echo "border-top-right-radius: ".$attr["value"].";";
			echo "-moz-border-radius-topright: ".$attr["value"].";";
			echo "-webkit-border-top-right-radius: ".$attr["value"].";";
			return;
		case "border-bottom-left-radius":
			echo "border-bottom-left-radius: ".$attr["value"].";";
			echo "-moz-border-radius-bottomleft: ".$attr["value"].";";
			echo "-webkit-border-bottom-left-radius: ".$attr["value"].";";
			return;
		case "border-bottom-right-radius":
			echo "border-bottom-right-radius: ".$attr["value"].";";
			echo "-moz-border-radius-bottomright: ".$attr["value"].";";
			echo "-webkit-border-bottom-right-radius: ".$attr["value"].";";
			return;
		case "box-shadow":
			echo "box-shadow: ".$attr["value"].";";
			echo "-moz-box-shadow: ".$attr["value"].";";
			echo "-webkit-box-shadow: ".$attr["value"].";";
			return;
		case "background":
			// TODO
			break;
		case "opacity":
			echo "opacity: ".$attr["value"].";";
			echo "-moz-opacity: ".$attr["value"].";";
			echo "-khtml-opacity: ".$attr["value"].";";
			$op = floatval($attr["value"]);
			$op = intval($op*100);
			echo "filter: alpha(opacity=".$op.");";
			echo "-ms-filter: progid:DXImageTransform.Microsoft.Alpha(Opacity=".$op.");";
			return;
	}
	echo $attr["name"].": ".$attr["value"].";";
}
?>