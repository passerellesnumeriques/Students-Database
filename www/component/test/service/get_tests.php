<?php 
class service_get_tests extends Service {
	
	public function getRequiredRights() { return array(); }
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		$cname = $input["component"];
		require_once("component/test/ComponentTests.inc");
		$all_functions = $this->get_all_functions($cname);
		$all_services = $this->get_all_services($cname);
		$component_test = null;
		if (file_exists("component/".$cname."/test/Tests.inc")) {
			require_once("component/".$cname."/test/Tests.inc");
			$classname = $cname."_Tests";
			$component_test = new $classname();
		}
		$function_tests = $component_test <> null ? $component_test->getFunctionalitiesTests() : array();
		$service_tests = $component_test <> null ? $component_test->getServicesTests() : array();
		$ui_tests = $component_test <> null ? $component_test->getUITests() : array();
		echo "{";
		echo "functions:";
		if (count($all_functions) == 0 && count($function_tests) == 0)
			echo "null";
		else {
			echo "{scenarios:[";
			$first = true;
			foreach ($function_tests as $file) {
				if ($first) $first = false; else echo ",";
				echo "{";
				echo "path:".json_encode($file);
				require_once("component/".$cname."/test/functionalities/".$file.".php");
				$scenario_class = str_replace("/","_",$file);
				$scenario = new $scenario_class();
				echo ",name:".json_encode($scenario->getName());
				echo ",steps:[";
				$steps = $scenario->getSteps();
				for ($i = 0; $i < count($steps); $i++) {
					if ($i>0) echo ",";
					echo json_encode($steps[$i]->getName());
				}
				echo "]";
				echo "}";
				foreach ($scenario->getCoveredFunctions() as $f) {
					$index = array_search($f, $all_functions);
					if ($index === FALSE) continue;
					array_splice($all_functions, $index, 1);
				}
			}
			echo "]";
			echo ",not_covered:[";
			for ($i = 0; $i < count($all_functions); $i++) {
				if ($i>0) echo ",";
				echo json_encode($all_functions[$i]);
			}
			echo "]";
			echo "}";
		}
		echo ",services:";
		if (count($all_services) == 0 && count($service_tests) == 0)
			echo "null";
		else {
			echo "{scenarios:[";
			$first = true;
			foreach ($service_tests as $file) {
				if ($first) $first = false; else echo ",";
				echo "{";
				echo "path:".json_encode($file);
				require_once("component/".$cname."/test/services/".$file.".php");
				$scenario_class = str_replace("/","_",$file);
				$scenario = new $scenario_class();
				echo ",name:".json_encode($scenario->getName());
				echo ",steps:[";
				$steps = $scenario->getSteps();
				for ($i = 0; $i < count($steps); $i++) {
					if ($i>0) echo ",";
					echo json_encode($steps[$i]->getName());
				}
				echo "]";
				echo "}";
				foreach ($scenario->getCoveredServices() as $f) {
					$index = array_search($f, $all_services);
					if ($index === FALSE) continue;
					array_splice($all_services, $index, 1);
				}
			}
			echo "]";
			echo ",not_covered:[";
			for ($i = 0; $i < count($all_services); $i++) {
				if ($i>0) echo ",";
				echo json_encode($all_services[$i]);
			}
			echo "]";
			echo "}";
		}
		echo ",ui:";
		if (count($ui_tests) == 0)
			echo "null";
		else {
			echo "{scenarios:[";
			$first = true;
			foreach ($ui_tests as $file) {
				if ($first) $first = false; else echo ",";
				echo "{";
				echo "path:".json_encode($file);
				require_once("component/".$cname."/test/ui/".$file.".php");
				$scenario_class = str_replace("/","_",$file);
				$scenario = new $scenario_class();
				echo ",name:".json_encode($scenario->getName());
				echo "}";
			}
			echo "]";
			echo "}";
		}
		echo "}";
	}

	private function browse($dir_path, $rel_path, &$list) {
		$dir = opendir($dir_path);
		while (($filename = readdir($dir)) <> null) {
			if ($filename == ".") continue;
			if ($filename == "..") continue;
			if (is_dir($dir_path."/".$filename)) {
				$this->browse($dir_path."/".$filename, $rel_path.$filename."/", $list);
			} else {
				if (substr($filename, strlen($filename)-4) == ".php")
					array_push($list, $rel_path.$filename);
			}
		}
		closedir($dir);
	}
	
	private function get_all_functions($cname) {
		$c = PNApplication::$instance->components[$cname];
		$cl = new ReflectionClass($c);
		$list = array();
		$interfaces = $cl->getInterfaces();
		$parents = array();
		$pc = $cl->getParentClass();
		while ($pc <> null) {
			array_push($parents, $pc);
			$pc = $pc->getParentClass();
		}
		foreach ($cl->getMethods() as $m) {
			if ($m->isStatic()) continue;
			if (!$m->isPublic()) continue;
			if ($m->isConstructor()) continue;
			if ($m->isDestructor()) continue;
			if ($m->getDeclaringClass() <> $cl) continue;
			$from_interface = false;
			foreach ($interfaces as $iname=>$i) {
				foreach ($i->getMethods() as $im)
					if ($im->name == $m->name) {
						$from_interface = true;
						break;
					}
				if ($from_interface) break;
			}
			if ($from_interface) continue;
			foreach ($parents as $pc) {
				foreach ($pc->getMethods() as $im)
					if ($im->name == $m->name) {
						$from_interface = true;
						break;
					}
				if ($from_interface) break;
			}
			if ($from_interface) continue;
			$doc = $m->getDocComment();
			if ($doc <> null && strpos($doc, "@not_tested") !== FALSE) continue;
			array_push($list, $m->getName());
		}
		return $list;
	}
	
	private function get_all_services($cname) {
		$path = "component/".$cname."/service";
		$list = array();
		if (!file_exists($path)) return $list;
		$this->browse_services($path, "", $list);
		return $list;
	}
	private function browse_services($path, $rel, &$list) {
		$dir = opendir($path);
		while (($filename = readdir($dir)) <> null) {
			if ($filename == ".") continue;
			if ($filename == "..") continue;
			if (is_dir($path."/".$filename))
				$this->browse_services($path."/".$filename, $rel.$filename."/", $list);
			else if (substr($filename, strlen($filename)-4) == ".php")
				array_push($list, $rel.substr($filename, 0, strlen($filename)-4));
		}
		closedir($dir);
	}
}
?>