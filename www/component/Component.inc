<?php 
/**
 * Abstract class of a Component
 */
abstract class Component {

	/**
	 * @var string name of this component
	 */
	public $name;
	
	/**
	 * @param string $name
	 */
	public function __construct($name) {
		$this->name = $name;
	}
	
	/** Initialize the component: by default it does nothing, but a component may override this function. */
	public function init() {}
	
	/**
	 * Request for a page on this component: check it exists, the user has necessary rights, then execute it
	 * @param string $path
	 */
	public final function page($path) {
		// check the user is logged
		if (PNApplication::$instance->user_management->username == null) {
			// development mode only
			if ($this->name <> "development" && $this->name <> "test") {
				// only allow the login page
				if ($this->name <> "application" || $path <> "enter") {
					echo "<script type='text/javascript'>window.top.location.reload();</script>";
					return;
				}
			}
		}
		
		// check the page exists
		$file = "component/".$this->name."/page/".$path.".php";
		if (!file_exists($file)) {
			header("Content-Type: text/html;charset=UTF-8");
			PNApplication::error("Page '$path' does not exist in component '".$this->name."'");
			PNApplication::print_errors();
			return;
		}
		
		// instantiate the page
		require_once("Page.inc");
		require_once($file);
		$classname = "page_".str_replace("/","_",$path);
		/* @var $service Service */
		$page = new $classname();
		
		// check the page is allowed for the current user
		$required_rights = $page->get_required_rights();
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
			PNApplication::error("Access denied");
			PNApplication::print_errors();
			return;
		}
		
		// generate the page
		$page->generate($this, $path);
	}

	/**
	 * Request for a service on this component: check it exists, the user has necessary rights, then execute it
	 * @param string $path
	 */
	public final function service($path) {
		// development mode only
		if ($this->name <> "development" && $this->name <> "test") {
			// check the user is logged
			if (PNApplication::$instance->user_management->username == null) {
				header("Content-Type: text/json;charset=UTF-8", true, 403);
				PNApplication::error("Access denied: you must login before to use services");
				PNApplication::print_json_result(null);
				return;
			}
		}
		
		// check the service exists
		$file = "component/".$this->name."/service/".$path.".php";
		if (!file_exists($file)) {
			header("Content-Type: text/json;charset=UTF-8");
			PNApplication::error("Service '$path' does not exist in component '".$this->name."'");
			PNApplication::print_json_result(null);
			return;
		}
	
		// instantiate the service
		require_once("Service.inc");
		require_once($file);
		$classname = "service_".str_replace("/","_",$path);
		/* @var $service Service */
		$service = new $classname();
	
		// build input
		if ($_SERVER["CONTENT_TYPE"] == "text/json")
			$input = json_decode(file_get_contents('php://input'), true);
		else
			$input = $_POST;
		
		// get output format
		$output_format = $service->get_output_format($input);
		if ($output_format == "text/json")
			header("Content-Type: text/json;charset=UTF-8");
		else
			header("Content-Type: ".$output_format);
		
		// check the service is allowed for the current user
		$required_rights = $service->get_required_rights();
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
			PNApplication::error("Access denied");
			PNApplication::print_json_result(null);
			return;
		}
	
		// execute the service
		if ($output_format == "text/json") {
			ob_start();
			try {
				$service->execute($this, $input);
			} catch (Exception $e) {
				PNApplication::error($e);
			}
			$result = ob_get_clean();
			if ($result === "") $result = null;
			PNApplication::print_json_result($result);
		} else
			$service->execute($this, $input);
	}
	
	/**
	 * Return the list of components this component depends on.
	 * Read it from the file <i>dependencies</i> if it exists.
	 * @return array
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
	
	public final function get_readable_rights() {
		if (!file_exists("component/".$this->name."/readable_rights.inc")) return array();
		return include("component/".$this->name."/readable_rights.inc");
	}
	public final function get_writable_rights() {
		if (!file_exists("component/".$this->name."/writable_rights.inc")) return array();
		return include("component/".$this->name."/writable_rights.inc");
	}
	
}
?>