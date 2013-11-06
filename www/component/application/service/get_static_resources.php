<?php 
class service_get_static_resources extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Return all static resources for background loading"; }
	public function input_documentation() { echo "No input"; }
	public function output_documentation() { echo "Scripts with dependencies, and images to load"; }
	
	public function execute(&$component, $input) {
		$components = $this->get_components();
		$components = $this->order_components($components);
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
		// do others
		foreach ($components as $c)
			$this->browse("component/".$c."/static/", "/static/".$c."/", $scripts, $images);
		// result
		echo "{scripts:".json_encode($scripts).",images:".json_encode($images)."}";
	}

	private function get_components() {
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
	private function order_components($list) {
		$result = array();
		foreach ($list as $c)
			$this->_order_components($c, $result);
		return $result;
	}
	private function _order_components($c, &$result) {
		if (in_array($c, $result)) return;
		foreach (PNApplication::$instance->components[$c]->dependencies() as $dep)
			$this->_order_components($dep, $result);
		array_push($result, $c);
	}
	
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
					case "js": $this->add_script($scripts, $path.$filename, $url.$filename); break;
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
	
	private function add_script(&$scripts, $path, $url) {
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