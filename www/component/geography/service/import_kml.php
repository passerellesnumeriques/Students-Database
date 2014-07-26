<?php 
class service_import_kml extends Service {
	
	public function getRequiredRights() { return array("edit_geography"); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		echo "[";
		$first = true;
		$xml = simplexml_load_file('php://input');
		foreach ($xml->children() as $node) {
			if ($node->getName() == "Document") {
				foreach ($node->children() as $node2) {
					if ($node2->getName() == "Placemark") {
						$name = null;
						$bounds = null;
						$descr = null;
						foreach ($node2->children() as $node3) {
							if ($node3->getName() == "name")
								$name = "".$node3;
							else if ($node3->getName() == "MultiGeometry") {
								$bounds = array();
								foreach ($node3->children() as $node4) {
									if ($node4->getName() == "Polygon") {
										$b = $this->getPolygonBounds($node4);
										if ($b <> null)
											array_push($bounds, $b);
									}
								}
								$bounds = $this->mergeBounds($bounds);
							} else if ($node3->getName() == "description")
								$descr = "".$node3;
						}
						if ($name <> null && $bounds <> null) {
							if ($first) $first = false; else echo ",";
							echo "{";
							echo "name:".json_encode($name);
							echo ",north:".json_encode($bounds[0]);
							echo ",west:".json_encode($bounds[1]);
							echo ",south:".json_encode($bounds[2]);
							echo ",east:".json_encode($bounds[3]);
							echo ",description:".json_encode($descr);
							echo "}";
						}
					}
				}
			}
		}
		echo "]";
	}
	
	private function getPolygonBounds($node) {
		foreach ($node->children() as $node2) {
			if ($node2->getName() == "outerBoundaryIs") {
				foreach ($node2->children() as $node3) {
					if ($node3->getName() == "LinearRing") {
						foreach ($node3->children() as $node4) {
							if ($node4->getName() == "coordinates") {
								$str = "".$node4;
								return $this->pointListToBounds($str);
							}
						}
					}
				}
			}
		}
		return null;
	}
	private function pointListToBounds($str) {
		$points = explode("\n", $str);
		//return array(count($points),$str,1,2);
		$north = null;
		$south = null;
		$west = null;
		$east = null;
		foreach ($points as $pt) {
			$i = strpos($pt, ",");
			if ($i === false) continue;
			$lng = floatval(trim(substr($pt,0,$i)));
			$lat = floatval(trim(substr($pt,$i+1)));
			if ($west === null || $lng < $west) $west = $lng;
			if ($east === null || $lng > $east) $east = $lng;
			if ($south === null || $lat < $south) $south = $lat;
			if ($north === null || $lat > $north) $north = $lat;
		}
		if ($north !== null) return array($north,$west,$south,$east);
		return null;
	}
	
	private function mergeBounds($list) {
		if (count($list) == 0) return null;
		$bounds = $list[0];
		for ($i = 1; $i < count($list); $i++) {
			$b = $list[$i];
			if ($b[0] > $bounds[0]) $bounds[0] = $b[0];
			if ($b[3] > $bounds[3]) $bounds[3] = $b[3];
			if ($b[1] < $bounds[1]) $bounds[1] = $b[1];
			if ($b[2] < $bounds[2]) $bounds[2] = $b[2];
		}
		return $bounds;
	}
	
}
?>