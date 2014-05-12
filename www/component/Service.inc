<?php 
/**
 * Abstract class to define a service.
 */
abstract class Service {

	/**
	 * Generate general documentation (description) of this service.
	 */
	public abstract function documentation();
	/**
	 * Return an array: to access to this service, the user must comply with at least one of the element of this array.
	 * Each element can be either <ul>
	 * <li>the name of a right: the user needs this right</li>
	 * <li>a list of rights' name: the user needs all those rights</li>
	 * <li>a function: if it returns true, the user can access to the service</li>
	 * </ul>
	 */
	public abstract function getRequiredRights();
	/**
	 * Generate the documentation of the expected input of this service.
	 */
	public abstract function inputDocumentation();
	/**
	 * Return the format of the output of this service (by default: text/json) 
	 * @param array $input the input data of the service
	 * @return string the MIME format that will be returned by the service
	 */
	public function getOutputFormat($input) { return "text/json"; }
	/**
	 * Generate the documentation of the output of this service.
	 */
	public abstract function outputDocumentation();
	/**
	 * Execute the service
	 * @param Component $component the component this service belongs to
	 * @param array $input input of the service, from HTTP request (parsed from JSON or from GET)
	 */
	public abstract function execute(&$component, $input);
	
	/**
	 * Simulate the execution of a service, and returns the structure, parsed with json_decode
	 * @param string $component_name name of the component containing the service to execute
	 * @param string $service_name name of the service to execute
	 * @param array $input data to give as input to the service
	 */
	public static function internalExecution($component_name, $service_name, $input) {
		$current_output = ob_get_contents();
		ob_end_clean();
		ob_start();
		require_once("component/".$component_name."/service/".$service_name.".php");
		$classname = "service_".$service_name;
		$service = new $classname();
		$component = PNApplication::$instance->components[$component_name];
		$service->execute($component, $input);
		$service_output = ob_get_contents();
		ob_end_clean();
		ob_start();
		if ($service_output === "" || $service_output === false) $service_output = null;
		else {
			$json_output = json_decode(json_normalize($service_output), true);
			if ($json_output == null) {
				PNApplication::error("Service ".$service_name." on component ".$component_name." failed:\nService output:".$service_output."\nParsed output:".json_normalize($service_output));
			}
		}
		if ($current_output !== false)
			echo $current_output;
		return $json_output; 
	}
	
}

function json_normalize($s) {
	$res = "";
	$remains = _json_normalize_value($s, $res);
	if ($remains <> "") PNApplication::error("Invalid JSON: remaining string: ".$remains);
	return $res;
}
function _json_normalize_value($s, &$res) {
	if (strlen($s) == 0) return "";
	$c = substr($s,0,1);
	if ($c == ' ' || $c == '\t' || $c == '\r' || $c == '\n') {
		// skip spaces
		do {
			$s = substr($s,1);
			$c = substr($s,0,1);
		} while ($c == ' ' || $c == '\t' || $c == '\r' || $c == '\n');
	}
	if ($c == "{")
		return _json_normalize_object(substr($s,1), $res);
	if ($c == "[")
		return _json_normalize_array(substr($s,1), $res);
	if ($c == "'")
		return _json_normalize_string(substr($s,1), $c, $res);
	if ($c == "\"")
		return _json_normalize_string(substr($s,1), $c, $res);
	if (ctype_digit($c) || $c == "-")
		return _json_normalize_number($s, $res);
	if (substr($s,0,4)=="null") {
		$res .= "null";
		return substr($s,4);
	}
	if (substr($s,0,4)=="true") {
		$res .= "true";
		return substr($s,4);
	}
	if (substr($s,0,5)=="false") {
		$res .= "false";
		return substr($s,5);
	}
	if (substr($s,0,9)=="undefined") {
		$res .= "undefined";
		return substr($s,9);
	}
	if (substr($s,0,3)=="NaN") {
		$res .= "NaN";
		return substr($s,3);
	}
	PNApplication::error("Invalid JSON value: ".$s);
	return "";
}
function _json_normalize_object($s, &$res) {
	$res .= "{";
	do {
		if (strlen($s) == 0) return "";
		$c = substr($s,0,1);
		if ($c == "}") {
			// end of object
			$res .= "}";
			return substr($s,1);
		}
		if ($c == ' ' || $c == '\t' || $c == '\r' || $c == '\n') {
			// skip spaces
			$s = substr($s,1);
			continue;
		}
		if (ctype_alpha($c)) {
			// name of attribute
			$name = $c;
			$s = substr($s,1);
			while (strlen($s) > 0) {
				$c = substr($s,0,1);
				if (ctype_alnum($c) || $c == "_") {
					$name .= $c;
					$s = substr($s, 1);
					continue;
				}
				if ($c == ' ' || $c == '\t' || $c == '\r' || $c == '\n') {
					// skip spaces
					do {
						$s = substr($s,1);
						$c = substr($s,0,1);
					} while ($c == ' ' || $c == '\t' || $c == '\r' || $c == '\n');
					if ($c <> ":") {
						PNApplication::error("Invalid JSON: unexpected character '".$c."' in object");
						return "";
					}
				}
				if ($c == ":") break;
				PNApplication::error("Invalid JSON: unexpected character '".$c."' in object");
				return "";
			}
			if (strlen($s) == 0) {
				PNApplication::error("Invalid JSON: unexpected end in object");
				return "";
			}
			$s = substr($s,1); // remove the :
			$res .= "\"".$name."\":";
			$s = _json_normalize_value($s, $res);
			$c = substr($s,0,1);
			if ($c == ' ' || $c == '\t' || $c == '\r' || $c == '\n') {
				// skip spaces
				do {
					$s = substr($s,1);
					$c = substr($s,0,1);
				} while ($c == ' ' || $c == '\t' || $c == '\r' || $c == '\n');
			}
			if ($c == ",") { $res .= ","; $s = substr($s,1); }
			else if ($c <> "}") {
				PNApplication::error("Invalid JSON: unexpected character '".$c."' in object");
				return "";
			}
			continue;
		}
		PNApplication::error("Invalid JSON: unexpected character '".$c."' in object");
		return "";
	} while (true);
}
function _json_normalize_array($s, &$res) {
	$res .= "[";
	do {
		if (strlen($s) == 0) return "";
		$c = substr($s,0,1);
		if ($c == "]") {
			// end of array
			$res .= "]";
			return substr($s,1);
		}
		$s = _json_normalize_value($s, $res);
		$c = substr($s,0,1);
		if ($c == ' ' || $c == '\t' || $c == '\r' || $c == '\n') {
			// skip spaces
			do {
				$s = substr($s,1);
				$c = substr($s,0,1);
			} while ($c == ' ' || $c == '\t' || $c == '\r' || $c == '\n');
		}
		if ($c == ",") { $res .= ","; $s = substr($s,1); }
		else if ($c <> "]") {
			PNApplication::error("Invalid JSON: unexpected character '".$c."' in array");
			return "";
		}
	} while (true);
}
function _json_normalize_string($s, $quote, &$res) {
	$res .= $quote;
	while (strlen($s) > 0) {
		$c = substr($s,0,1);
		$s = substr($s,1);
		$res .= $c;
		if ($c == "\\") {
			$c = substr($s,0,1);
			$s = substr($s,1);
			$res .= $c;
			continue;
		}
		if ($c == $quote)
			return $s;
	}
	PNApplication::error("Unexpected end in string");
	return "";
}
function _json_normalize_number($s, &$res) {
	$first = true;
	while (strlen($s) > 0) {
		$c = substr($s,0,1);
		if (ctype_digit($c) || ($c == "-" && $first)) {
			$res .= $c;
			$s = substr($s,1);
			$first = false;
			continue;
		}
		$first = false;
		if ($c == ".") {
			// floating number
			$res .= ".";
			$s = substr($s,1);
			while (strlen($s) > 0) {
				$c = substr($s,0,1);
				if (ctype_digit($c)) {
					$res .= $c;
					$s = substr($s,1);
					continue;
				}
				// no digit anymore: end of number
				break;
			}
		}
		// no digit anymore: end of number
		return $s;
	}
}

?>