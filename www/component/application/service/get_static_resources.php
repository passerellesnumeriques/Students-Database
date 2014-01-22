<?php 
class service_get_static_resources extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Return all static resources for background loading"; }
	public function input_documentation() { echo "No input"; }
	public function output_documentation() { echo "Scripts with dependencies, and images to load"; }
	
	public function execute(&$component, $input) {
		$components = $this->getComponents();
		$components = $this->orderComponents($components);
		$scripts = array();
		$images = array();
		// start with theme
		for ($i = 0; $i < count($components); $i++)
			if ($components[$i] == "theme") {
				array_splice($components, $i, 1);
				break;			
			}
		global $theme;
		$this->browse("component/theme/static/".$theme."/", "/static/theme/".$theme."/", $scripts, $images);
		// /*do others*/
		foreach ($components as $c)
			$this->browse("component/".$c."/static/", "/static/".$c."/", $scripts, $images);
		// result
		echo "{scripts:".json_encode($scripts).",images:".json_encode($images)."}";
	}

	/**
	 * Retrieve the list of components (list of directories in component/) 
	 * @return string[] list of components' name
	 */
	private function getComponents() {
		$list = array();
		$dir = @opendir("component");
		if ($dir == null) return;
		while (($filename = readdir($dir)) <> null) {
			if (substr($filename, 0, 1) == ".") continue;
			if (is_dir("component/".$filename))
				array_push($list, $filename);
		}
		closedir($dir);
		return $list;
	}
	/**
	 * Order the given list of components by dependency
	 * @param string[] $list list of components to sort
	 * @return string[] sorted list
	 */
	private function orderComponents($list) {
		$result = array();
		foreach ($list as $c)
			$this->orderComponentsRecurse($c, $result);
		return $result;
	}
	/**
	 * Recursive function to sort components by dependency
	 * @param string $c component name
	 * @param string[] $result sorted list
	 */
	private function orderComponentsRecurse($c, &$result) {
		if (in_array($c, $result)) return;
		foreach (PNApplication::$instance->components[$c]->dependencies() as $dep)
			$this->orderComponentsRecurse($dep, $result);
		array_push($result, $c);
	}
	
	/**
	 * Browse the given directory to search javascripts and images files
	 * @param string $path directory's path
	 * @param string $url URL to access to this directory
	 * @param array[] $scripts list of scripts found: array('url'=>x,'size'=>y)
	 * @param array[] $images list of images found: array('url'=>x,'size'=>y)
	 */
	private function browse($path, $url, &$scripts, &$images) {
		$dir = @opendir($path);
		if ($dir == null) return;
		while (($filename = readdir($dir)) <> null) {
			if (substr($filename, 0, 1) == ".") continue;
			if (is_dir($path."/".$filename))
				$this->browse($path.$filename."/", $url.$filename."/", $scripts, $images);
			else {
				$i = strrpos($filename, ".");
				if ($i === FALSE) continue;
				$ext = substr($filename, $i+1);
				switch ($ext) {
					case "js": $this->addScript($scripts, $path.$filename, $url.$filename); break;
					case "gif":
					case "jpg":
					case "jpeg":
					case "png":
						array_push($images, array("url"=>$url.$filename,"size"=>filesize($path.$filename)));
						break;
				}
			}
		}
		closedir($dir);
	}
	
	/**
	 * Add the given script to the list of scripts found
	 * @param array[] $scripts list of scripts found: array('url'=>x,'size'=>y)
	 * @param string $path path of the script to add
	 * @param string $url URL of the script to add
	 */
	private function addScript(&$scripts, $path, $url) {
		$content = file_get_contents($path);
		$script = array("url"=>$url,"size"=>filesize($path),"dependencies"=>array());
		$i = 0;
		while (($i = strpos($content,"#depends[", $i)) !== FALSE) {
			$j = strpos($content, "]", $i);
			$dep = substr($content,$i+9,$j-$i-9);
			if (substr($dep,0,1) <> "/") {
				$k = strrpos($url, "/");
				$dep = substr($url,0,$k+1).$dep;
			}
			array_push($script["dependencies"], $dep);
			$i = $j+1;
		}
		array_push($scripts, $script);
	}
	
}
?>