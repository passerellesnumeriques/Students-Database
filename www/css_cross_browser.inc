<?php
require_once("Browser.inc");
function parse_css($path) {
	$css = file_get_contents($path);
	// remove comments
	$len = strlen($css);
	$quot = null;
	for ($i = 0; $i < $len; ++$i) {
		$c = substr($css,$i,1);
		if ($quot <> null) {
			if ($c == "\\") $i++;
			else if ($c == $quot) $quot = null;
			continue;
		}
		if ($c == "'" || $c == "\"") {
			$quot = $c;
			continue;
		}
		if ($c == "/" && $i < $len-1) {
			$c2 = substr($css, $i+1, 1);
			if ($c2 == "/") {
				$j = strpos($css, "\n", $i);
				if ($j === false) { $css = substr($css, 0, $i); break; }
				else { $css = substr($css, 0, $i).substr($css,$j); $i--; }
			} else if ($c2 == "*") {
				$j = strpos($css, "*/", $i+2);
				if ($j === false) break;
				$css = substr($css, 0, $i).substr($css,$j+2);
				$i--;
			}
		}
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
		case "filter":
			$webkit_support = $browser->Chrome >= 18 || $browser->SafariBrowser >= 6 || $browser->OperaBrowser >= 15;
			$result = "";
			$functions = read_css_functions($attr["value"]);
			$svg_filters = "";
			foreach ($functions as $func) {
				$name = $func[0];
				$params = $func[1];
				$f = "";
				switch ($name) {
					case "url":
						foreach ($params as $p) {
							if (strlen($f) > 0) $f .= ",";
							$f .= $p;
						}
						$f = "url(".$f.")";
						break;
					case "blur":
						if (count($params) <> 1) break;
						$i = strpos($params[0], "px");
						if ($i === false) break;
						$px = intval(substr($params[0],0,$i));
						if ($webkit_support)
							$f = "blur(".$px."px)";
						else if ($browser->isOldIE(9))
							$f = "progid:DXImageTransform.Microsoft.Blur('".$px."')";
						else
							$svg_filters .= "<feGaussianBlur stdDeviation=\\'".$px."\\'/>";
						break;
					case "brightness":
						if (count($params) <> 1) break;
						$i = strpos($params[0], "%");
						if ($i !== false)
							$val = floatval(substr($params[0],0,$i))/100;
						else
							$val = floatval($params[0]);
						if ($webkit_support)
							$f = "brightness(".$val.")";
						else
							$svg_filters .= "<feComponentTransfer><feFuncR type=\\'linear\\' slope=\\'".$val."\\'/><feFuncG type=\\'linear\\' slope=\\'".$val."\\'/><feFuncB type=\\'linear\\' slope=\\'".$val."\\'/></feComponentTransfer>";
						break;
					case "contrast":
						if (count($params) <> 1) break;
						$i = strpos($params[0], "%");
						if ($i !== false)
							$val = floatval(substr($params[0],0,$i))/100;
						else
							$val = floatval($params[0]);
						if ($webkit_support)
							$f = "contrast(".$val.")";
						else
							$svg_filters .= "<feComponentTransfer><feFuncR type=\\'linear\\' slope=\\'".$val."\\' intercept=\\'-(0.5 * ".$val.") + 0.5\\'/><feFuncG type=\\'linear\\' slope=\\'".$val."\\' intercept=\\'-(0.5 * ".$val.") + 0.5\\'/><feFuncB type=\\'linear\\' slope=\\'".$val."\\' intercept=\\'-(0.5 * ".$val.") + 0.5\\'/></feComponentTransfer>";
						break;
					case "grayscale":
						if (count($params) <> 1) break;
						$i = strpos($params[0], "%");
						if ($i !== false)
							$val = floatval(substr($params[0],0,$i))/100;
						else
							$val = floatval($params[0]);
						if ($webkit_support)
							$f = "grayscale(".$val.")";
						else if ($browser->isOldIE(9))
							$f = "gray";
						else
							$svg_filters .= "<feColorMatrix type=\\'matrix\\' values=\\'0.3333 0.3333 0.3333 0 0 0.3333 0.3333 0.3333 0 0 0.3333 0.3333 0.3333 0 0 0 0 0 1 0\\'/>";
						break;
				}
				if ($f <> "") {
					if ($result <> "") $result .= " ";
					$result .= $f;
				}
			}
			if ($svg_filters <> "") {
				$f = "url(\"data:image/svg+xml;utf8,<svg xmlns=\\'http://www.w3.org/2000/svg\\'><filter id=\\'svg_filters\\'>";
				$f .= $svg_filters;
				$f .= "</filter></svg>#svg_filters\")";
				if ($result <> "") $result .= " ";
				$result .= $f;
			}
			if ($result <> "") {
				if ($webkit_support)
					echo "-webkit-";
				echo "filter: ".$result.";";
			}
			return;
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
function read_css_functions($s) {
	$functions = array();
	$s = trim($s);
	$name = "";
	for ($i = 0; $i < strlen($s); $i++) {
		$c = substr($s,$i,1);
		if ($c == " ") {
			// space is delimiter between functions
			if (strlen($name) > 0) {
				array_push($functions, array($name, null));
				$name = "";
			}
			continue;
		}
		if ($c == "(") {
			$params = read_css_function_parameters($s, $i+1);
			array_push($functions, array($name, $params[0]));
			$i = $params[1];
			$name = "";
			continue;
		}
		$name .= $c;
	}
	if (strlen($name) > 0)
		array_push($functions, array($name, null));
	return $functions;
}
function read_css_function_parameters($s, $i) {
	$params = array();
	$quot = null;
	$param = "";
	for (; $i < strlen($s); $i++) {
		$c = substr($s,$i,1);
		if ($quot <> null) {
			$param .= $c;
			if ($c == "\\" && $i < strlen($s)-1) {
				$c = substr($s, ++$i, 1);
				$param .= $c;
			} else if ($c == $quot)
				$quot = null;
			continue;
		}
		if ($c == "'" || $c == "\"") {
			$param .= $c;
			$quot = $c;
			continue;
		}
		if ($c == ",") {
			array_push($params, $param);
			$param = "";
			continue;
		}
		if ($c == ")") break;
		$param .= $c;
	}
	if ($param <> "") array_push($params, $param);
	return array($params, $i);
}
?>