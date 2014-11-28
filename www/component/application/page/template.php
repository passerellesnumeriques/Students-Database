<?php 
class page_template extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$name = $_GET["name"];
		$input = isset($_POST["input"]) ? json_decode($_POST["input"], true) : null;
		if (strpos($name, "..")) die();
		$content = file_get_contents("component/application/static/templates/$name.html");
		echo $this->compute($content, $input);
	}
	
	private function compute($s, $input) {
		$s = $this->computeForEach($s, $input);
		$s = $this->computeValue($s, $input);
		return $s;
	}
	
	private function computeForEach($s, $input) {
		while (($i = strpos($s, "#FOREACH:")) !== false) {
			$j = strpos($s, ":", $i+9);
			if ($j === false) break;
			$k = strpos($s, "#", $j+1);
			if ($k === false) break;
			$array_name = substr($s, $i+9, $j-$i-9);
			$loop_value_name = substr($s, $j+1, $k-$j-1);
			$end = strpos($s, "#ENDFOREACH:$array_name#");
			if ($end === false) break;
			$loop_content = substr($s, $k+1, $end-$k-1);
			$prev_value = @$input[$loop_value_name];
			$list = @$input[$array_name];
			$result = "";
			if ($list <> null && is_array($list))
				foreach ($list as $value) {
					$input[$loop_value_name] = $value;
					$result .= $this->compute($loop_content, $input);
				}
			$input[$loop_value_name] = $prev_value;
			$s = substr($s, 0, $i).$result.substr($s, $end+12+strlen($array_name)+1);
		}
		return $s;
	}

	private function computeValue($s, $input) {
		while (($i = strpos($s, "#VALUE:")) !== false) {
			$j = strpos($s, "#", $i+7);
			$name = substr($s, $i+7, $j-$i-7);
			$value = @$input[$name];
			if ($value === null) $value = "";
			$s = substr($s, 0, $i).$value.substr($s, $j+1);		
		}
		return $s;
	}
	
}
?>