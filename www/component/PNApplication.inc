<?php
require_once("Component.inc");

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
 * @property excel $excel
 * @property facebook $facebook
 * @property geography $geography
 * @property google $google
 * @property health $health
 * @property javascript $javascript
 * @property news $news
 * @property people $people 
 * @property selection $selection
 * @property staff $staff
 * @property storage $storage
 * @property students $students
 * @property test $test
 * @property theme $theme
 * @property training_education $training_education
 * @property transcripts $transcripts
 * @property user_management $user_management
 * @property user_people $user_people 
 * @property widgets $widgets
 */
class PNApplication {

	/**
	 * @static 
	 * @var PNApplication 
	 */
	public static $instance;

	public static $errors = array();
	/** @var string */
	public $current_domain;
	/** @var string */
	public $local_domain;
	public $components = array();

	/**
	 * Signal an error
	 * @param string $message in plain text, which will be converted into html
	 */
	public static function error($message) {
		if (count(PNApplication::$errors) == 20) {
			array_push(PNApplication::$errors, "Too much errors");
			return;
		}
		if (count(PNApplication::$errors) > 50) return;
		if (!($message instanceof Exception)) {
			try {
				throw new Exception($message);
			} catch (Exception $e) {
				$message = $e;
			}
		}
		$e = $message;
		$message = htmlentities($e->getMessage(), ENT_QUOTES, "UTF-8");
		$message .= " <a style='font:small' href='#' onclick=\"var e=document.getElementById('ex_".count(PNApplication::$errors)."');e.style.visibility='visible';e.style.position='static';return false;\">details</a>";
		$message .= "<ul style='margin:0px;visibility:hidden;position:absolute;top:-10000px' id='ex_".count(PNApplication::$errors)."'>";
		foreach ($e->getTrace() as $trace)
			if (isset($trace["file"]) && isset($trace["line"]))
				$message .= "<li>".$trace["file"]." line ".$trace["line"]."</li>";
			else
				$message .= "<li>".$trace."</li>";
		$message .= "</ul>";
		array_push(PNApplication::$errors, $message);
	}
	/**
	 * Signal an error
	 * @param string $html_message in html format
	 */
	public static function error_html($message) {
		try {
			throw new Exception();
		} catch (Exception $e) {
			$message .= " <a style='font:small' href='#' onclick=\"var e=document.getElementById('ex_".count(PNApplication::$errors)."');e.style.visibility='visible';e.style.position='static';return false;\">details</a>";
			$message .= "<ul style='margin:0px;visibility:hidden;position:absolute;top:-10000px' id='ex_".count(PNApplication::$errors)."'>";
			foreach ($e->getTrace() as $trace)
				$message .= "<li>".$trace["file"]." line ".$trace["line"]."</li>";
			$message .= "</ul>";
			array_push(PNApplication::$errors, $message);
		}
	}

	/**
	 * @return boolean true if there are errors
	 */
	public static function has_errors() { return count(PNApplication::$errors) > 0; }
	
	public static function clear_errors() { PNApplication::$errors = array(); }

	/**
	 * Generate error messages to the output in html format
	 */
	public static function print_errors() {
		if (!PNApplication::has_errors()) return;
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
	/**
	 * Generate the output of a JSON service: put the list of errors if any, then the given result 
	 * @param unknown $result
	 */
	public static function print_json_result($result) {
		echo "{";
		if (count(PNApplication::$errors) > 0) {
			echo "errors:[";
			$first = true;
			foreach (PNApplication::$errors as $e) {
				if ($first) $first = false; else echo ",";
				echo json_encode($e);
			}
			echo "],";
		}
		echo "result:";
		if ($result === null) echo "null"; else echo $result;
		echo "}";
	}

	/**
	 * Initialize PNApplication: instantiate and initialize every component
	 */
	public function init() {
		$this->local_domain = isset($_GET["testing"]) ? "Test" : file_get_contents("conf/local_domain");
		$this->current_domain = $this->local_domain;
		$components_names = array();
		$dir = @opendir("component");
		while (($filename = readdir($dir)) <> null) {
			if (substr($filename, 0, 1) == ".") continue;
			if (is_dir("component/".$filename)) array_push($components_names, $filename);
		}
		closedir($dir);
		foreach ($components_names as $name) $this->create_component($name);
		$done = array();
		foreach ($this->components as $c) $this->init_component($c, $done);
		$this->init_request();
	}
	public function init_request() {
		$done = array();
		foreach ($this->components as $c) $this->init_request_for_component($c, $done);
	}
	private function create_component($name) {
		require_once("component/".$name."/".$name.".inc");
		$c = new $name($name);
		$this->components[$name] = &$c;
		$this->{$name} = &$c;
	}
	private function init_component($c, &$done) {
		if (in_array($c->name, $done)) return;
		array_push($done, $c->name);
		foreach ($c->dependencies() as $dep) {
			if (!isset($this->components[$dep])) die("Missing component '".$dep."' which is a dependency of component '".$c->name."'");
			$this->init_component($this->components[$dep], $done);
		}
		$c->init();
	}
	private function init_request_for_component($c, &$done) {
		if (in_array($c->name, $done)) return;
		array_push($done, $c->name);
		foreach ($c->dependencies() as $dep) {
			if (!isset($this->components[$dep])) die("Missing component '".$dep."' which is a dependency of component '".$c->name."'");
			$this->init_request_for_component($this->components[$dep], $done);
		}
		$c->init_request();
	}
	
	public static function sort_components_by_dependencies() {
		$list = array();
		foreach (PNApplication::$instance->components as $name=>$c)
			PNApplication::_sort_components_by_dependencies($c, $list);
		return $list;
	}
	private static function _sort_components_by_dependencies(&$c, &$list) {
		if (in_array($c, $list)) return;
		foreach ($c->dependencies() as $dep)
			PNApplication::_sort_components_by_dependencies(PNApplication::$instance->components[$dep], $list);
		array_push($list, $c);
	}

	/**
	 * @return array associative array of domain=>domain_descriptor
	 */
	public function get_domains() {
		return include("conf/domains");
	}
	
	/**
	 * @param string $domain if null it will return the descriptor of the current domain
	 * @return array attributes of the domain
	 */
	public function get_domain_descriptor($domain = null) {
		if ($domain == null) $domain = $this->current_domain;
		$domains = include("conf/domains");
		return $domains[$domain];
	}
}
?>
