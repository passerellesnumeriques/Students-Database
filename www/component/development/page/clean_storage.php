<?php
class page_clean_storage extends Page {
	
	public function getRequiredRights() {
		return array();
	}
	
	protected function execute() {
		foreach (PNApplication::$instance->getDomains() as $domain=>$descr)
			$this->cleanDomain($domain);
		echo "<br/>Cleaning done.<br/><br/><a href='tools'>Back to Development tools page</a>";
	}
	private function cleanDomain($domain) {
		echo "<br/>";
		echo "Cleaning storage for domain ".$domain."<br/>";
		$path = realpath(dirname($_SERVER["SCRIPT_FILENAME"])."/data");
		if (!file_exists($path."/".$domain)) {
			echo " - No storage for this domain<br/>";
			return;
		}
		$this->cleanDirectory($path."/".$domain, false);
	}
	private function cleanDirectory($path, $remove_if_empty) {
		$dir = opendir($path);
		$has_content = false;
		while (($filename = readdir($dir)) <> null) {
			if ($filename == ".") continue;
			if ($filename == "..") continue;
			if (is_dir($path."/".$filename)) {
				$this->cleanDirectory($path."/".$filename, true);
				if (file_exists($path."/".$filename)) $has_content = true;
				continue;
			}
			$has_content = true;
		}
		closedir($dir);
		if (!$has_content && $remove_if_empty) {
			echo " - Remove empty directory ".$path."<br/>";
			rmdir($path);
		}
	}
}
?>