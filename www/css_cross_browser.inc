<?php
require_once("Browser.inc");
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
	global $browser;
	switch ($attr["name"]) {
		case "border-radius":
			if ($browser->isOldChrome(4.0) || $browser->isOldSafari(5.0))
				echo "-webkit-border-radius: ".$attr["value"].";";
			else if ($browser->isOldFirefox(4.0))
				echo "-moz-border-radius: ".$attr["value"].";";
			else
				echo "border-radius: ".$attr["value"].";";
			return;
		case "border-top-left-radius":
			if ($browser->isOldChrome(4.0) || $browser->isOldSafari(5.0))
				echo "-webkit-border-top-left-radius: ".$attr["value"].";";
			else if ($browser->isOldFirefox(4.0))
				echo "-moz-border-radius-topleft: ".$attr["value"].";";
			else
				echo "border-top-left-radius: ".$attr["value"].";";
			return;
		case "border-top-right-radius":
			if ($browser->isOldChrome(4.0) || $browser->isOldSafari(5.0))
				echo "-webkit-border-top-right-radius: ".$attr["value"].";";
			else if ($browser->isOldFirefox(4.0))
				echo "-moz-border-radius-topright: ".$attr["value"].";";
			else
				echo "border-top-right-radius: ".$attr["value"].";";
			return;
		case "border-bottom-left-radius":
			if ($browser->isOldChrome(4.0) || $browser->isOldSafari(5.0))
				echo "-webkit-border-bottom-left-radius: ".$attr["value"].";";
			else if ($browser->isOldFirefox(4.0))
				echo "-moz-border-radius-bottomleft: ".$attr["value"].";";
			else
				echo "border-bottom-left-radius: ".$attr["value"].";";
			return;
		case "border-bottom-right-radius":
			if ($browser->isOldChrome(4.0) || $browser->isOldSafari(5.0))
				echo "-webkit-border-bottom-right-radius: ".$attr["value"].";";
			else if ($browser->isOldFirefox(4.0))
				echo "-moz-border-radius-bottomright: ".$attr["value"].";";
			else
				echo "border-bottom-right-radius: ".$attr["value"].";";
			return;
		case "box-shadow":
			if ($browser->isOldChrome(10.0) || $browser->isOldSafari(5.1))
				echo "-webkit-box-shadow: ".$attr["value"].";";
			else if ($browser->isOldFirefox(4.0))
				echo "-moz-box-shadow: ".$attr["value"].";";
			else
				echo "box-shadow: ".$attr["value"].";";
			return;
		case "background":
			// TODO
			break;
		case "opacity":
			if ($browser->isOldIE(8.0)) {
				$op = floatval($attr["value"]);
				$op = intval($op*100);
				echo "filter: alpha(opacity=".$op.");";
			} else if ($browser->isOldIE(9.0)) {
				$op = floatval($attr["value"]);
				$op = intval($op*100);
				echo "-ms-filter: progid:DXImageTransform.Microsoft.Alpha(Opacity=".$op.");";
			} else if ($browser->isOldFirefox(0.9))
				echo "-moz-opacity: ".$attr["value"].";";
			else if ($browser->isOldSafari(2.0))
				echo "-khtml-opacity: ".$attr["value"].";";
			else
				echo "opacity: ".$attr["value"].";";
			return;
	}
	echo $attr["name"].": ".$attr["value"].";";
}
?>