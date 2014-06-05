<?php 
class service_check_todo extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}

	public function execute(&$component, $input) {
		$path = realpath($input["path"]);
		$content = file_get_contents($path,true);
		$line = 1;
		echo "[";
		$first = true;
		for ($i = 0; $i < strlen($content); $i++) {
			$c = substr($content,$i,1);
			if ($c == "\n") $line++;
			else if ($c == "T" && substr($content,$i,4) == "TODO") {
				if ($first) $first = false; else echo ",";
				echo json_encode("TODO on line ".$line);
			}
		}
		echo "]";
	}
	
	private function check_classes($path, $type, &$output) {
		$all = get_declared_classes();
		$all2 = get_declared_interfaces();
		$all = array_merge($all, $all2);
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
			array_push($output, "Class <b>".$cl->getName()."</b>: No comment describing the purpose of the class");
		if ($type == "service") {
			$classname = $cl->getName();
			$service = new $classname();
			@ob_start();
			ob_clean();
			$service->documentation();
			$doc = ob_get_clean();
			if (strlen($doc) == 0)
				array_push($output, "Service <b>".$cl->getName()."</b>: No documentation");
			$service->inputDocumentation();
			$doc = ob_get_clean();
			if (strlen($doc) == 0)
				array_push($output, "Service <b>".$cl->getName()."</b>: No documentation for input");
			$service->outputDocumentation();
			$doc = ob_get_clean();
			if (strlen($doc) == 0)
				array_push($output, "Service <b>".$cl->getName()."</b>: No documentation for output");
		}
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
		if ($comment == "" && isset($tags["var"])) {
			$type = PHPDoc::getWord($tags["var"][0]);
			if ($type <> "") {
				$name = PHPDoc::getWord($tags["var"][0]);
				if ($name <> "") {
					if ($name <> "\$".$p->name) $tags["var"][0] = $name." ".$tags["var"][0];
					if ($comment == "") $comment = trim($tags["var"][0]);
				}
			}
				
		}
		if ($comment == "")
			array_push($output, "Class <b>".$p->getDeclaringClass()->getName()."</b>, Property <b>\$".$p->getName()."</b>: No comment");
		if (!isset($tags["no_name_check"])) {
			$first = substr($p->getName(),0,1);
			if (strtolower($first) <> $first || !ctype_alpha($first))
				array_push($output, "Class <b>".$p->getDeclaringClass()->getName()."</b>, Property <b>\$".$p->getName()."</b>: Must start with small letter");
			else
				for ($i = 0; $i < strlen($p->getName()); $i++) {
					$c = substr($p->getName(), $i, 1);
					if (strtolower($c) <> $c) {
						array_push($output, "Class <b>".$p->getDeclaringClass()->getName()."</b>, Property <b>\$".$p->getName()."</b>: Must contain only small letters");
						break;
					}
				}
		}
	}
	
	/**
	 * @param ReflectionMethod $m
	 */
	private function check_method($m, $type, &$output) {
		$comment = "";
		$tags = "";
		PHPDoc::parse_comment($m->getDocComment(), $comment, $tags);
		$from_parent = $this->has_method($m->getDeclaringClass()->getParentClass(), $m->getName());
		$is_language = $m->getName() == "__construct";
		if (!$from_parent)
			foreach ($m->getDeclaringClass()->getInterfaces() as $i) {
				$from_parent = $this->has_method($i, $m->getName());
				if ($from_parent) break;
			}
			
		// check method has a description
		if ($comment == "" && !$from_parent && !$is_language) {
			// accept if the method has no parameter, and output is documented
			$ok = false;
			if (count($m->getParameters()) == 0) {
				if (isset($tags["return"])) {
					$type = PHPDoc::getWord($tags["return"][0]);
					if (strlen(trim($tags["return"][0])) > 0)
						$ok = true;
				}
			}
			if (!$ok)
				array_push($output, "Class <b>".$m->getDeclaringClass()->getName()."</b>, Method <b>".$m->getName()."</b>: No comment");
		}

		// check parameters are documented and typed
		if (!$from_parent) {
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
					array_push($output, "Class <b>".$m->getDeclaringClass()->getName()."</b>, Method <b>".$m->getName()."</b>, Parameter <b>".$p->getName()."</b>: No comment");
				if ($param_type == "")
					array_push($output, "Class <b>".$m->getDeclaringClass()->getName()."</b>, Method <b>".$m->getName()."</b>, Parameter <b>".$p->getName()."</b>: No type");
			}
			if (isset($tags["return"])) {
				$type = PHPDoc::getWord($tags["return"][0]);
				if (strlen(trim($tags["return"][0])) == 0)
					array_push($output, "Class <b>".$m->getDeclaringClass()->getName()."</b>, Method <b>".$m->getName()."</b>: declares returned type ".$type." without comment");				
			}
		}
		
		// check name is compliant
		if (!$from_parent && !$is_language && !isset($tags["no_name_check"])) {
			$first = substr($m->getName(),0,1);
			if (strtolower($first) <> $first || !ctype_alpha($first))
				array_push($output, "Class <b>".$m->getDeclaringClass()->getName()."</b>, Method <b>".$m->getName()."</b>: Must start with small letter");
			else
				for ($i = 0; $i < strlen($m->getName()); $i++)
				if (!ctype_alnum(substr($m->getName(), $i, 1))) {
				array_push($output, "Class <b>".$m->getDeclaringClass()->getName()."</b>, Method <b>".$m->getName()."</b>: Must contain only letters and digits");
						break;
				}
		}
	}
	
	private function has_method($c, $name) {
		if ($c == null) return false;
		foreach ($c->getMethods() as $m)
			if ($m->getName() == $name) return true;
		if ($this->has_method($c->getParentClass(), $name)) return true;
		foreach ($c->getInterfaces() as $i)
			if ($this->has_method($i, $name)) return true;
		return false;
	}
	
}
?>