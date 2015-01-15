<?php 
class service_import_names_from_geonames extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		$f = fopen('php://input','r');
		$adm1 = array();
		//echo "[\"\"";
		do {
			$s = fgets($f, 16384);
			if ($s === false || strlen($s) == 0) break;
			//echo ",{line:".json_encode($s)."}";
			$item = array();
			do {
				$i = mb_strpos($s, "\t", 0, "UTF-8");
				//echo ",{i:".json_encode($i)."}";
				if ($i === false) {
					array_push($item, $s);
					break;
				}
				array_push($item, mb_substr($s, 0, $i, "UTF-8"));
				$s = mb_substr($s, $i+1, null, "UTF-8");
			} while (mb_strlen($s, "UTF-8") > 0);
			//echo ",{item:".json_encode($item)."}";
			//break;
			if (count($item) < 12) continue;
			$adm1_code = $item[10];
			if ($adm1_code == "00") continue; // obsolete
			$adm2_code = $item[11];
			$adm3_code = $item[12];
			switch ($item[7]) {
				case "ADM1":
					if (!isset($adm1[$adm1_code])) $adm1[$adm1_code] = array("children"=>array());
					$adm1[$adm1_code]["name"] = $item[1];
					break;
				case "ADM2":
					if (!isset($adm1[$adm1_code])) $adm1[$adm1_code] = array("children"=>array());
					if (!isset($adm1[$adm1_code]["children"][$adm2_code])) $adm1[$adm1_code]["children"][$adm2_code] = array("children"=>array());
					$adm1[$adm1_code]["children"][$adm2_code]["name"] = $item[1];
					break;
				case "ADM3":
					if (!isset($adm1[$adm1_code])) $adm1[$adm1_code] = array("children"=>array());
					if (!isset($adm1[$adm1_code]["children"][$adm2_code])) $adm1[$adm1_code]["children"][$adm2_code] = array("children"=>array());
					if (!isset($adm1[$adm1_code]["children"][$adm2_code]["children"][$adm3_code])) $adm1[$adm1_code]["children"][$adm2_code]["children"][$adm3_code] = array("children"=>array());
					$adm1[$adm1_code]["children"][$adm2_code]["children"][$adm3_code]["name"] = $item[1];
					break;
			}
		} while (true);
		fclose($f);
		//echo "]";
		echo json_encode($adm1);
	}
	
}
?>