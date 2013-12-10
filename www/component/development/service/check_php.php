<?php 
class service_check_php extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}

	public function execute(&$component, $input) {
		require_once("component/Page.inc");
		require_once("component/documentation/page/PHPDoc.inc");
		$path = realpath($input["path"]);
		$type = $input["type"];
		try {
			@require_once($path);
		} catch (Exception $e) {
			// analyze the error ???
		}
		ob_clean();
		$output = array();
		$this->check_classes($path, $type, $output);
		echo json_encode($output);
	}
	
	private function check_classes($path, $type, &$output) {
		$all = get_declared_classes();
		foreach ($all as $cname) {
			$cl = new ReflectionClass($cname);
			if (realpath($cl->getFileName()) <> $path) continue;
			$this->check_class($cl, $type, $output);
		}
	}
	/**
	 * @param ReflectionClass $cl
	 */
	private function check_class($cl, $type, &$output) {
		$comment = "";
		$tags = array();
		PHPDoc::parse_comment($cl->getDocComment(), $comment, $tags);
		$comment = trim($comment);
		if (strlen($comment) == 0 && $type == "")
			array_push($output, "Class ".$cl->getName().": No comment describing the purpose of the class");
		foreach ($cl->getProperties() as $p)
			if ($p->getDeclaringClass() == $cl)
				$this->check_property($p, $type, $output);
		foreach ($cl->getMethods() as $m)
			if ($m->getDeclaringClass() == $cl)
				$this->check_method($m, $type, $output);
	}
	
	/**
	 * @param ReflectionProperty $p
	 */
	private function check_property($p, $type, &$output) {
		$comment = "";
		$tags = "";
		PHPDoc::parse_comment($p->getDocComment(), $comment, $tags);
		if ($comment == "")
			array_push($output, "Class ".$p->getDeclaringClass()->getName().", Property \$".$p->getName().": No comment");
		$first = substr($p->getName(),0,1);
		if (strtolower($first) <> $first || !ctype_alpha($first))
			array_push($output, "Class ".$p->getDeclaringClass()->getName().", Property \$".$p->getName().": Must start with small letter");
		else
			for ($i = 0; $i < strlen($p->getName()); $i++)
				if (!ctype_alnum(substr($p->getName(), $i, 1))) {
				array_push($output, "Class ".$p->getDeclaringClass()->getName().", Property \$".$p->getName().": Must contain only letters and digits");
					break;
				}
	}
	
	/**
	 * @param ReflectionMethod $m
	 */
	private function check_method($m, $type, &$output) {
		$comment = "";
		$tags = "";
		PHPDoc::parse_comment($m->getDocComment(), $comment, $tags);
		if ($comment == "")
			array_push($output, "Class ".$m->getDeclaringClass()->getName().", Method ".$m->getName().": No comment");
		
		foreach ($m->getParameters() as $p) {
			$param_doc = "";
			$param_type = "";
			if (isset($tags["param"])) {
				foreach ($tags["param"] as $t) {
					$name = null;
					$type = null;
					$doc = null;
					$w = PHPDoc::getWord($t);
					if (substr($w,0,1) <> "$") {
						$type = $w;
						if (strlen($t) > 0) {
							$name = substr(PHPDoc::getWord($t),1);
							$doc = $t;
						}
					} else {
						$name = substr($w,1);
						$doc = $t;
					}
					if ($name == $p->getName()) {
						$param_doc = $doc;
						$param_type = $type;
						break;
					}
				}
			}
			if ($param_doc == "")
				array_push($output, "Class ".$m->getDeclaringClass()->getName().", Method ".$m->getName().", Parameter ".$p->getName().": No comment");
			if ($param_type == "")
				array_push($output, "Class ".$m->getDeclaringClass()->getName().", Method ".$m->getName().", Parameter ".$p->getName().": No type");
		}
		
		$first = substr($m->getName(),0,1);
		if (strtolower($first) <> $first || !ctype_alpha($first))
			array_push($output, "Class ".$m->getDeclaringClass()->getName().", Method ".$m->getName().": Must start with small letter");
		else
			for ($i = 0; $i < strlen($m->getName()); $i++)
			if (!ctype_alnum(substr($m->getName(), $i, 1))) {
			array_push($output, "Class ".$m->getDeclaringClass()->getName().", Method ".$m->getName().": Must contain only letters and digits");
					break;
			}
	}
	
}
?>