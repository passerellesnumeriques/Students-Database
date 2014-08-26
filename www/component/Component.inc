<?php 
/**
 * Abstract class of a Component
 */
abstract class Component {

	/** @var string name of this component */
	public $name;
	
	/**
	 * @param string $name name of this component
	 */
	public function __construct($name) {
		$this->name = $name;
	}
	
	/** Initialize the component: by default it does nothing, 
	 * but a component may override this function. 
	 * This function is called only once, when the session 
	 * of the user is started, but it is not called 
	 * for subsequent requests */
	public function init() {}
	
	/** Perform operations at the beginning of every request.
	 * By default it does nothing, but a component may override this function. 
	 */
	public function initRequest() {}
	
	/** @var string[] list of pages which can be accessed without being authenticated */
	private static $allowed_pages_without_login = array();
	/** Add a page which can be accessed without being authenticated
	 * @param string $component_name component name
	 * @param string $page_path path of the page in the component
	 */
	protected static function allowPageWithoutLogin($component_name, $page_path) {
		if (!isset(Component::$allowed_pages_without_login[$component_name]))
			Component::$allowed_pages_without_login[$component_name] = array();
		array_push(Component::$allowed_pages_without_login[$component_name], $page_path);
	}
	/**
	 * Request for a page on this component: check it exists, the user has necessary rights, then execute it
	 * @param string $path path of the page
	 */
	public final function page($path) {
		// check the user is logged
		if (PNApplication::$instance->user_management->username == null) {
			if (!isset(Component::$allowed_pages_without_login[$this->name]) || !in_array($path, Component::$allowed_pages_without_login[$this->name])) {
				echo "<script type='text/javascript'>window.top.location = '/';</script>";
				return;
			}
		}
		
		// check the page exists
		$file = "component/".$this->name."/page/".$path.".php";
		if (!file_exists($file)) {
			header("Content-Type: text/html;charset=UTF-8");
			PNApplication::error("Page '$path' does not exist in component '".$this->name."'");
			PNApplication::printErrors();
			return;
		}
		
		// instantiate the page
		require_once("Page.inc");
		require_once($file);
		$classname = "page_".str_replace("/","_",$path);
		/* @var $service Service */
		$page = new $classname();
		
		if (!$page->mayUpdateSession()) { session_write_close(); }
		
		// check the page is allowed for the current user
		$required_rights = $page->getRequiredRights();
		if (count($required_rights) == 0) 
			$final_allowed = true; // no restriction
		else {
			$final_allowed = false;
			foreach ($required_rights as $rights) {
				$list = is_array($rights) ? $rights : array($rights);
				$allowed = true;
				foreach ($list as $right) {
					if (is_string($right)) {
						if (!PNApplication::$instance->user_management->has_right($right)) { $allowed = false; break; }
					} else
						if (!call_user_func($right)) { $allowed = false; break; }
				}
				if ($allowed) { $final_allowed = true; break; }
			}
		}
		if (!$final_allowed) {
			PNApplication::error("Access denied to this page");
			PNApplication::printErrors();
			return;
		}
		
		set_error_handler(function($severity, $message, $filename, $lineno) {
			if (error_reporting() == 0) return true;
			PNApplication::error("PHP Error: ".$message." in ".$filename.":".$lineno);
			return true;
		});
		// generate the page
		$page->generate($this, $path);
		restore_error_handler();
	}

	/** @var string[] list of services which can be accessed without being authenticated */
	private static $allowed_services_without_login = array();
	/** Add a service which can be accessed without being authenticated
	 * @param string $component_name component name
	 * @param string $service_path path of the service in the component
	 */
	protected static function allowServiceWithoutLogin($component_name, $service_path) {
		if (!isset(Component::$allowed_services_without_login[$component_name]))
			Component::$allowed_services_without_login[$component_name] = array();
		array_push(Component::$allowed_services_without_login[$component_name], $service_path);
	}
	
	/**
	 * Request for a service on this component: check it exists, the user has necessary rights, then execute it
	 * @param string $path path of the service
	 */
	public final function service($path) {
		// check the user is logged
		if (PNApplication::$instance->user_management->username == null) {
			if (!isset(Component::$allowed_services_without_login[$this->name]) || !in_array($path, Component::$allowed_services_without_login[$this->name])) {
				header("Content-Type: application/json;charset=UTF-8", true, 403);
				PNApplication::error("Access denied: you must login before to use services");
				PNApplication::printJSONResult(null);
				return;
			}
		}
		
		// check the service exists
		$file = "component/".$this->name."/service/".$path.".php";
		if (!file_exists($file)) {
			header("Content-Type: application/json;charset=UTF-8");
			PNApplication::error("Service '$path' does not exist in component '".$this->name."'");
			PNApplication::printJSONResult(null);
			return;
		}
	
		// instantiate the service
		require_once("Service.inc");
		require_once($file);
		$classname = "service_".str_replace("/","_",$path);
		/* @var $service Service */
		$service = new $classname();
	
		if (!$service->mayUpdateSession()) { session_write_close(); }
		
		// build input
		if (isset($_SERVER["CONTENT_TYPE"]) && substr($_SERVER["CONTENT_TYPE"],0,16) == "application/json") {
			$in = file_get_contents('php://input');
			if (strlen($in)==0)
				$input = array();
			else
				$input = json_decode($in, true);
			$err = json_last_error();
			if ($input === null && $err <> JSON_ERROR_NONE) {
				PNApplication::error("Invalid input for service ".$path.": JSON error ".$err."\nReceived input (length ".strlen($in)."):\n".$in);
				// get output format
				$output_format = $service->getOutputFormat(array());
				if ($output_format == "application/json") {
					header("Content-Type: application/json;charset=UTF-8");
					PNApplication::printJSONResult(null);
				} else {
					PNApplication::printErrors();
				}
				return;
			}			
		} else {
			$input = array();
			foreach ($_POST as $n=>$v) {
				$input[$n] = @json_decode($v, true);
				if ($input[$n] === null && $v != "null") $input[$n] = $v;
			}
		}
		
		// get output format
		$output_format = $service->getOutputFormat($input);
		if ($output_format == "application/json")
			header("Content-Type: application/json;charset=UTF-8");
		else
			header("Content-Type: ".$output_format);
		
		// check the service is allowed for the current user
		$required_rights = $service->getRequiredRights();
		if (count($required_rights) == 0)
			$final_allowed = true; // no restriction
		else {
			$final_allowed = false;
			foreach ($required_rights as $rights) {
				$list = is_array($rights) ? $rights : array($rights);
				$allowed = true;
				foreach ($list as $right) {
					if (is_string($right)) {
						if (!PNApplication::$instance->user_management->has_right($right)) { $allowed = false; break; }
					} else
						if (!call_user_func($right)) { $allowed = false; break; }
				}
				if ($allowed) { $final_allowed = true; break; }
			}
		}
		if (!$final_allowed) {
			PNApplication::error("Access denied to this service");
			PNApplication::printJSONResult(null);
			return;
		}
	
		// execute the service
		if ($output_format == "application/json") {
			$done = new ServiceDone();
			register_shutdown_function("service_shutdown_catch", $done);
			set_error_handler(function($severity, $message, $filename, $lineno) {
				if (error_reporting() == 0) return true;
				PNApplication::error("PHP Error: ".$message." in ".$filename.":".$lineno);
				return true;
			});
			ob_start();
			try {
				$service->execute($this, $input);
			} catch (Exception $e) {
				PNApplication::error($e);
			}
			$result = ob_get_contents();
			ob_end_clean();
			if ($result === "" || $result === false) $result = null;
			restore_error_handler();
			PNApplication::printJSONResult($result);
			$done->done = true;
		} else {
			$service->execute($this, $input);
		}
	}

#DEV
	/**
	 * Return the list of components this component depends on.
	 * Read it from the file <i>dependencies</i> if it exists.
	 * @return string[] list of dependencies
	 */
	public final function dependencies() {
		if (!file_exists("component/".$this->name."/dependencies")) return array();
		$deps = array();
		$f = fopen("component/".$this->name."/dependencies","r");
		while (($line = fgets($f,4096)) !== FALSE) {
			$line = trim($line);
			if (strlen($line) == 0) continue;
			$i = strpos($line,":");
			if ($i !== FALSE) $line = substr($line,0,$i);
			array_push($deps, $line);
		}
		fclose($f);
		return $deps;
	}
#END
#DEV	
	/** Return the list of read rights defined by this component (from readable_rights.inc)
	 * @return AccessRightCategory[] list of categories, containing the list of rights
	 */
	public final function getReadableRights() {
		if (!file_exists("component/".$this->name."/readable_rights.inc")) return array();
		return include("component/".$this->name."/readable_rights.inc");
	}
	/** Return the list of write rights defined by this component (from writable_rights.inc)
	 * @return AccessRightCategory[] list of categories, containing the list of rights
	 */
	public final function getWritableRights() {
		if (!file_exists("component/".$this->name."/writable_rights.inc")) return array();
		return include("component/".$this->name."/writable_rights.inc");
	}
#PROD
#	public abstract function getReadableRights();
#	public abstract function getWritableRights();
#END
	/**
	 * Returns all implementations of <i>plug-in</i> for the component. This is used, instead of having the 
	 * component directly implementing the <i>plug-in</i> interfaces, so the component class is clearer
	 * (contains only functionalities, without any additional <i>polutant</i> things)
	 * @return array list of plugin instances
	 */
	public function getPluginImplementations() { return array(); }

}

/** Internal usage: to check if a service successfully terminated, or a fatal error occured during its execution */
class ServiceDone { /** $var boolean indicates if it succeed */ public $done = false; };
function service_shutdown_catch($done) {
	if ($done->done) return;
	$msg = "The service didn't finish correctly.";
	$error = error_get_last();
	if ($error <> null)
		$msg.= " Last error was in ".$error["file"]." line ".$error["line"].": ".$error["message"];
	$content = ob_get_clean();
	if ($content <> "")
		$msg .= "<br/>Output generated at failing time:<br/>".str_replace("\n", "<br/>", toHTML($content));
	PNApplication::errorHTML($msg);
	PNApplication::printJSONResult(null);
}

?>