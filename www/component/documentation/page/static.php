<?php 
class page_static extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$component = $_GET["component"];
		if (strpos($component, "..") !== false) die("Access denied");
		$path = $_GET["path"];
		if (strpos($path, "..") !== false) die("Access denied");
		$dir = dirname("component/$component/doc/$path");
		$content = "#include(".basename("component/$component/doc/$path").")";
		while (($i = strpos($content, "#include(")) !== false) {
			$j = strpos($content, ")", $i);
			if ($j === false) return;
			$inc = file_get_contents($dir."/".substr($content, $i+9, $j-$i-9));
			$content = substr($content, 0, $i).$inc.substr($content,$j+1);
		}
		echo $content;
	}
	
}
?>