<?php 
class page_php_search extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		ob_start();
		$this->browse(realpath(dirname($_SERVER["SCRIPT_FILENAME"])));
		ob_clean();
		$classes = array_merge(get_declared_classes(), get_declared_interfaces());
		foreach ($classes as $cl) {
			if (strtolower($cl) == strtolower($_GET["type"])) {
				$c = new ReflectionClass($cl);
				echo "<script type='text/javascript'>";
				echo "location.href = 'php?file=".urlencode($c->getFileName())."&class=".$_GET["type"]."';";
				echo "</script>";
				return;
			}
		}
	}
	
	private function browse($path) {
		$dir = opendir($path);
		while (($filename = readdir($dir)) <> null) {
			if (substr($filename, 0, 1) == ".") continue;
			if (is_dir($path."/".$filename)) {
				if ($filename == "page") continue;
				if ($filename == "service") continue;
				if ($filename == "test") continue;
				if ($filename == "static") continue;
				if (substr($filename, 0, 4) == "lib_") continue;
				$this->browse($path."/".$filename);
				continue;
			}
			$i = strrpos($filename, ".");
			if ($i === FALSE) continue;
			$ext = substr($filename, $i+1);
			$ext = strtolower($ext);
			if ($ext == "php" || $ext == "inc") {
				if ($filename == "datamodel.inc") continue;
				if ($filename == "init_data.inc") continue;
				try {
					require_once($path."/".$filename);
				} catch (Exception $e) {}
			}
		}
		closedir($dir);
	}
	
}
?>