<?php
function page_shutdown_catch($page) {
	if ($page->done) return;
	$msg = "The page '".$page->path."' on component '".$page->component->name."' didn't finish correctly.";
	$error = error_get_last();
	if ($error <> null)
		$msg.= " Last error was in ".$error["file"]." line ".$error["line"].": ".$error["message"];
	PNApplication::error($msg);
	PNApplication::print_errors();
	$content = ob_get_clean();
	echo $content;
}

abstract class Page {

	public abstract function get_required_rights();
	protected abstract function execute();
	
	private $javascripts = array();
	private $stylesheets = array();
	private $onload = "";
	public $component;
	public $path;
	public $done = false;
	private $generate_id = 0;

	public final function add_javascript($path, $onload = "") {
		$found = false;
		foreach ($this->javascripts as $js)
			if ($js[0] == $path) {
				$found = true;
				$js[1] .= $onload;
				break;		
			}
		if (!$found)
			array_push($this->javascripts, array($path,$onload));
	}
	private $javascript_paths = null;
	public final function require_javascript($js_filename, $onload = "") {
		if ($this->javascript_paths == null) {
			$this->javascript_paths = array();
			$dir = opendir("component");
			while (($filename = readdir($dir)) <> null) {
				if (!is_dir("component/".$filename)) continue;
				if ($filename == "." || $filename == "..") continue;
				if (file_exists("component/".$filename."/static"))
					$this->browse_javascripts("component/".$filename."/static", $filename, "/");
			}
			closedir($dir);
		}
		$this->add_javascript($this->javascript_paths[$js_filename], $onload);
	}
	private function browse_javascripts($path, $component, $sub_path) {
		$d = opendir($path);
		while (($filename = readdir($d)) <> null) {
			if ($filename == "." || $filename == "..") continue;
			if (is_dir($path."/".$filename))
				$this->browse_javascripts($path."/".$filename, $component, $sub_path.$filename."/");
			else if (substr($filename,strlen($filename)-3) == ".js")
				$this->javascript_paths[$filename] = "/static/".$component.$sub_path.$filename;
		}
		closedir($d);
	}
		
	public final function add_stylesheet($path) {
		if (!in_array($path, $this->stylesheets))
			array_push($this->stylesheets, $path);
	}
	public final function onload($script) { $this->onload .= str_replace("\"", "&quot;", $script); }
	
	public final function generateID() {
		return str_replace("/", "_", str_replace(".", "_", $this->path))."_".($this->generate_id++);
	}

	public final function generate(&$component, $path) {
		$this->component = &$component;
		$this->path = $path;
		ob_start();
		register_shutdown_function("page_shutdown_catch", $this);
		try {
			$this->execute();
		} catch (Exception $e) {
			PNApplication::error($e);
		}
		$content = ob_get_clean();
		ob_end_clean();
		$this->start_page();
		echo $content;
		$this->end_page();
		$this->done = true;
	}

	private function start_page() {
		theme::css($this, "global.css");
		pageHeaderStart();
		foreach ($this->javascripts as $js) {
			echo "<script type='text/javascript' src='".$js[0]."' onload='javascript_loaded(\"".$js[0]."\");".$js[1]."'>";
			echo "</script>";
		}
		foreach ($this->stylesheets as $css) echo "<link rel='stylesheet' href='".$css."' onload='if (window.layout && window.document.body) window.layout.invalidate(window.document.body);'/>";
		pageHeaderEnd($this->onload);
	}

	private function end_page() {
		PNApplication::print_errors();
		pageFooter();
	}
	
	/**
	 * Performed the lock of the given data
	 * If well performed, the managing script is generated
	 * @param string $table
	 * @param string|null (optional) $row
	 * @param string|null (optional)$column
	 * @param string|null (optional) $sm submodel
	 * @return null|number null if the lock cannot be performed, else the lock id 
	 */
	public final function performRequiredLocks($table, $row = null, $column = null, $sm = null){
		require_once("component/data_model/DataBaseLock.inc");
		require_once("component/data_model/Model.inc");
		$model = DataModel::get();
		$table = $model->getTable($table); // here check is done is the user can access this table
		$locked_by = null;
		$name = null;
		$db_lock = null;
		//Call the matching method
		if($column <> null){
			if($row <> null){
				$db_lock = DataBaseLock::lockCell($table->getSQLNameFor($sm), $row, $column, $locked_by);
				$name = "cell";
			} else {
				$db_lock = DataBaseLock::lockColumn($table->getSQLNameFor($sm), $column, $locked_by);
				$name = "column";
			}
		} else if ($row <> null){
			$db_lock = DataBaseLock::lockRow($table->getSQLNameFor($sm), $row, $locked_by);
			$name = "row";
		} else {
			$db_lock = DataBaseLock::lockTable($table->getSQLNameFor($sm), $locked_by);
			$name = "table";
		}
		if ($db_lock == null) {
			PNApplication::error("This ".$name." is already locked by ".$locked_by);
			return;
		} else
			DataBaseLock::generateScript($db_lock);
		return $db_lock;
	}
}

function pageHeaderStart() {
	require_once("component/Component.inc");
	require_once("component/theme/theme.inc");
?>
<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=UTF-8"/>
	<title>PN Students DataBase</title>
	<script type="text/javascript">
	if (window==window.top){location.href='/?page='+encodeURIComponent(location.pathname+location.search+location.hash);};
	</script>
	<script type="text/javascript" src="/static/javascript/utils.js"></script>
	<script type="text/javascript" src="/static/javascript/browser.js"></script>
	<script type="text/javascript" src="/static/application/application.js"></script>
	<script type="text/javascript" src="/static/javascript/layout.js"></script>
	<script type="text/javascript" src="/static/theme/theme.js.php"></script>
	<script type="text/javascript" src="/static/javascript/ajax.js"></script>
	<script type="text/javascript" src="/static/application/service.js"></script>
	<script type="text/javascript" src="/static/application/config.js.php"></script>
	<script type="text/javascript" src="/static/widgets/common_dialogs.js"></script>
	<script type="text/javascript" src="/static/data_model/databaselock.js"></script>
	<script type="text/javascript">
	javascript_loaded("/static/javascript/utils.js");
	javascript_loaded("/static/javascript/browser.js");
	javascript_loaded("/static/application/application.js");
	javascript_loaded("/static/javascript/layout.js");
	javascript_loaded("/static/theme/theme.js.php");
	javascript_loaded("/static/javascript/ajax.js");
	javascript_loaded("/static/application/service.js");
	javascript_loaded("/static/application/config.js.php");
	javascript_loaded("/static/widgets/common_dialogs.js");
	javascript_loaded("/static/data_model/databaselock.js");
	</script>
<?php 
}
function pageHeaderEnd($onload = "") {
?>
</head>
<body onload="<?php echo $onload;?>;window._onload_done=true;">
<?php
}
function pageFooter() {
?>
<script type='text/javascript'>window._page_ready = true;layout.invalidate(document.body);</script>
</body>
</html>
<?php
}
?>