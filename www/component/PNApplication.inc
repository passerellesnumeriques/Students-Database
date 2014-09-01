<?php
require_once("Component.inc");
require_once("utilities.inc");

/**
 * PNApplication is the root point, used to:
 *   - access to any component
 *   - access to the global data model
 *   - indicate errors along the process, that may be then display to the user
 * 
 * @property administration $administration
 * @property application $application
 * @property authentication $authentication
 * @property calendar $calendar
 * @property contact $contact
 * @property curriculum $curriculum
 * @property data_import $data_import
 * @property data_model $data_model
 * @property development $development
 * @property discipline $discipline
 * @property documents $documents
 * @property excel $excel
 * @property facebook $facebook
 * @property geography $geography
 * @property google $google
 * @property health $health
 * @property help $help
 * @property javascript $javascript
 * @property news $news
 * @property people $people 
 * @property selection $selection
 * @property staff $staff
 * @property storage $storage
 * @property students $students
 * @property test $test
 * @property theme $theme
 * @property transcripts $transcripts
 * @property user_config $user_config
 * @property user_management $user_management
 * @property widgets $widgets
 */
class PNApplication {

	/**
	 * @static 
	 * @var PNApplication singleton
	 */
	public static $instance = null;

	/** @static
	 * @var string[] list of error in HTML format
	 */
	public static $errors = array();
	/** @static
	 * @var string[] list of warning in HTML format
	 */
	public static $warnings = array();
	/** @var string domain currently selected */
	public $current_domain;
	/** @var string local domain */
	public $local_domain;
	/** @var Component[] list of components */
	public $components = array();
	/** @var integer timezone offset in minutes */
	public $timezone_offset = 0;

	/**
	 * Signal an error
	 * @param string|Exception $message in plain text, which will be converted into html, or an Exception
	 * @param Exception the exception causing the error
	 */
	public static function error($message, $exception = null) {
		if (count(PNApplication::$errors) == 20) {
			array_push(PNApplication::$errors, "Too many errors");
			return;
		}
		if (count(PNApplication::$errors) > 20) return;
		if (!($message instanceof Exception) && $exception == null) {
			try {
				throw new Exception($message);
			} catch (Exception $e) {
				$message = $e;
			}
		}
		if ($exception == null) {
			$e = $message;
			$message = "";
		} else {
			$e = $exception;
			$message .= ": ";
		}
		$message .= str_replace("\n", "<br/>", htmlentities($e->getMessage(), ENT_QUOTES, "UTF-8"));
		$message .= " <a style='font:small' href='#' onclick=\"var e=document.getElementById('ex_".count(PNApplication::$errors)."');e.style.visibility='visible';e.style.position='static';if (layout) layout.invalidate(e); else if (window.parent && window.parent.layout) window.parent.layout.invalidate(window.frameElement);return false;\">details</a>";
		$message .= "<ul style='margin:0px;visibility:hidden;position:absolute;top:-10000px' id='ex_".count(PNApplication::$errors)."'>";
		do {
			foreach ($e->getTrace() as $trace)
				if (isset($trace["file"]) && isset($trace["line"]))
					$message .= "<li>".$trace["file"]." line ".$trace["line"]."</li>";
			$e = $e->getPrevious();
			if ($e <> null) $message .= "<li><b>CAUSED BY:</b></li>";
		} while ($e <> null);
		$message .= "</ul>";
		array_push(PNApplication::$errors, $message);
	}
	/**
	 * Signal an error
	 * @param string $message in html format
	 */
	public static function errorHTML($message) {
		try {
			throw new Exception();
		} catch (Exception $e) {
			$message .= " <a style='font:small' href='#' onclick=\"var e=document.getElementById('ex_".count(PNApplication::$errors)."');e.style.visibility='visible';e.style.position='static';if (typeof layout != 'undefined') layout.invalidate(e); return false;\">details</a>";
			$message .= "<ul style='margin:0px;visibility:hidden;position:absolute;top:-10000px' id='ex_".count(PNApplication::$errors)."'>";
			do {
				foreach ($e->getTrace() as $trace)
					if (isset($trace["file"]))
						$message .= "<li>".toHTML($trace["file"]." line ".$trace["line"])."</li>";
				$e = $e->getPrevious();
				if ($e <> null) $message .= "<li><b>CAUSED BY:</b></li>";
			} while ($e <> null);
			$message .= "</ul>";
			array_push(PNApplication::$errors, $message);
		}
	}
	
	/**
	 * Signal a warning
	 * @param string $message in html format
	 */
	public static function warning($message) {
		array_push(self::$warnings, $message);
	}

	/** Indicates if there is at least one error
	 * @return boolean true if there is at least one error
	 */
	public static function hasErrors() { return count(PNApplication::$errors) > 0; }
	/** Indicates if there is at least one warning
	 * @return boolean true if there is at least one warning
	 */
	public static function hasWarnings() { return count(PNApplication::$warnings) > 0; }
	
	/** Remove all errors */
	public static function clearErrors() { PNApplication::$errors = array(); }

	/**
	 * Generate error messages to the output in html format
	 */
	public static function printErrors() {
		if (PNApplication::hasErrors()) {
			foreach (PNApplication::$errors as $e)
				echo "<div style='color:#C00000;font-familiy:Tahoma;font-size:10pt'><img src='".theme::$icons_16["error"]."' style='vertical-align:bottom'/> ".$e."</div>";
			echo "<script type='text/javascript'>";
			echo "if (typeof window.top.status_manager != 'undefined'){";
			foreach (PNApplication::$errors as $e)
				echo "window.top.status_manager.add_status(new window.top.StatusMessageError(null,".json_encode($e).",5000));";
			echo "};";
			echo "window.page_errors=[";
			$first = true;
			foreach (PNApplication::$errors as $e) {
				if ($first) $first = false; else echo ",";
				echo json_encode($e);
			}
			echo "];";
			echo "</script>";
		}
		if (PNApplication::hasWarnings()) {
			echo "<script type='text/javascript'>";
			echo "if (typeof window.top.status_manager != 'undefined'){";
			foreach (PNApplication::$warnings as $e)
				echo "window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_WARNING,".json_encode($e).",[{action:'popup'},{action:'close'}],5000));";
			echo "};";
			echo "</script>";
		}
	}
	/**
	 * Generate the output of a JSON service: put the list of errors if any, then the given result 
	 * @param string $result the JSON string corresponding to the result of the service
	 */
	public static function printJSONResult($result) {
		echo "{";
		if (count(PNApplication::$errors) > 0) {
			echo "\"errors\":[";
			$first = true;
			foreach (PNApplication::$errors as $e) {
				if ($first) $first = false; else echo ",";
				echo json_encode($e);
			}
			echo "],";
		}
		if (count(PNApplication::$warnings) > 0) {
			echo "\"warnings\":[";
			$first = true;
			foreach (PNApplication::$warnings as $e) {
				if ($first) $first = false; else echo ",";
				echo json_encode($e);
			}
			echo "],";
		}
		echo "\"result\":";
		if ($result === null) echo "null";
		else {
			if (count(PNApplication::$errors) == 0)
				echo $result;
			else {
				// check the result is a valid JSON, as it may be corrupted due to errors
				$res = json_normalize($result);
				$res = json_decode($res, true);
				echo json_encode($res);
			}
		}
		echo "}";
	}

	/**
	 * Initialize PNApplication: instantiate and initialize every component
	 */
	public function init() {
		global $local_domain;
#DEV
		$this->local_domain = isset($_GET["testing"]) ? "Test" : $local_domain;
#END
#PROD
#		$this->local_domain = $local_domain;
#END
		$this->current_domain = $this->local_domain;
#DEV
		$components_names = array();
		$dir = @opendir("component");
		while (($filename = readdir($dir)) <> null) {
			if (substr($filename, 0, 1) == ".") continue;
			if (is_dir("component/".$filename)) array_push($components_names, $filename);
		}
		closedir($dir);
		foreach ($components_names as $name) $this->createComponent($name);
		$done = array();
		foreach ($this->components as $c) $this->initComponent($c, $done);
#END
#PROD
# ##CREATE_COMPONENTS##
#foreach (self::getOrderedComponentsNames() as $c) $this->components[$c]->init();
#END
		$this->initRequest();
	}
	/**
	 * Called at the beginning of any request, it calls the initRequest on all components
	 */
	public function initRequest() {
		date_default_timezone_set(timezone_name_from_abbr("",$this->timezone_offset*60,1));
#DEV
		$done = array();
		foreach ($this->components as $c) $this->initRequestForComponent($c, $done);
#END
#PROD
#foreach (self::getOrderedComponentsNames() as $c) $this->components[$c]->initRequest();
#END
	}
#DEV
	/** Instantiate the given component
	 * @param string $name name of the component
	 */
	private function createComponent($name) {
		require_once("component/".$name."/".$name.".inc");
		$c = new $name($name);
		$this->components[$name] = &$c;
		$this->{$name} = &$c;
	}
	/** Initialize a component: initialize and instantiate any dependency, then call init method on the component
	 * @param Component $c the component to initialize
	 * @param array $done lsit of components already initialized
	 */ 
	private function initComponent($c, &$done) {
		if (in_array($c->name, $done)) return;
		array_push($done, $c->name);
		foreach ($c->dependencies() as $dep) {
			if (!isset($this->components[$dep])) die("Missing component '".$dep."' which is a dependency of component '".$c->name."'");
			$this->initComponent($this->components[$dep], $done);
		}
		$c->init();
	}
	/** Call initRequest on all dependencies, then on the component itself
	 * @param Component $c the component
	 * @param array $done list of components on which the initRequest is already called
	 */
	private function initRequestForComponent($c, &$done) {
		if (in_array($c->name, $done)) return;
		array_push($done, $c->name);
		foreach ($c->dependencies() as $dep) {
			if (!isset($this->components[$dep])) die("Missing component '".$dep."' which is a dependency of component '".$c->name."'");
			$this->initRequestForComponent($this->components[$dep], $done);
		}
		$c->initRequest();
	}
	
	/** Return the list of components, in the dependency order (first are the components without dependency) 
	 * @return Component[] ordered list of components
	 */
	public static function sortComponentsByDependencies() {
		$list = array();
		foreach (PNApplication::$instance->components as $name=>$c)
			PNApplication::internalSortComponentsByDependencies($c, $list);
		return $list;
	}
	/** Go through all dependencies first, then add the component
	 * @param Component $c the component to add to the list
	 * @param array $list the list of components
	 */
	private static function internalSortComponentsByDependencies(&$c, &$list) {
		if (in_array($c, $list)) return;
		foreach ($c->dependencies() as $dep)
			PNApplication::internalSortComponentsByDependencies(PNApplication::$instance->components[$dep], $list);
		array_push($list, $c);
	}
#END
#PROD
#public static function sortComponentsByDependencies() {
#$list = array();
#foreach (self::getOrderedComponentsNames() as $c) array_push($list, PNApplication::$instance->components[$c]);
#return $list;
#}
#public static function getOrderedComponentsNames() {
# ##COMPONENTS_ORDER##
#return $components;
#}
#END
	
	/** Get the list of domains with their configuration: from file conf/domains
	 * @return array associative array of domain=&gt;domain_descriptor
	 */
	public function getDomains() {
		return include("conf/domains");
	}
	
	/** Get the configuration of the given domain
	 * @param string $domain if null it will return the descriptor of the current domain
	 * @return array attributes of the domain
	 */
	public function getDomainDescriptor($domain = null) {
		if ($domain == null) $domain = $this->current_domain;
		$domains = include("conf/domains");
		return $domains[$domain];
	}
}
?>