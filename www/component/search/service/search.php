<?php 
class service_search extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function getOutputFormat($input) {
		return "text/html";
	}
	
	public function execute(&$component, $input) {
		$pi = $component->getPlugin($input["plugin"]);
		if ($pi == null) {
			PNApplication::error("Unknown search plugin '".$input["plugin"]."'");
			return;
		}
		if (isset($input["generic"])) {
			$result = $pi->genericSearch($input["generic"]);
		}
		if ($result == null || count($result) == 0) return;
		echo "<div class='search_result_title'>";
		echo $pi->getTitle(count($result));
		echo "</div>";
		if ($pi->hasCategories()) {
			$cats = $pi->sortByCategory($result);
			foreach ($cats as $id=>$cat)
				$this->generateCategory($cat, $pi);
		} else {
			$this->generateResultsRows($result, $pi);
		}
	}
	
	private function generateCategory($cat, $pi) {
		echo "<div class='search_result_category'>";
		echo "<div class='search_result_category_title'>";
		echo $cat["title"].": ".$this->countCategoryRows($cat);
		echo "</div>";
		if (isset($cat["categories"]))
			foreach ($cat["categories"] as $id=>$sub_cat)
				$this->generateCategory($sub_cat, $pi);
		if (isset($cat["rows"])) {
			$this->generateResultsRows($cat["rows"], $pi);
		}
		echo "</div>";
	}
	private function countCategoryRows($cat) {
		$nb = 0;
		if (isset($cat["categories"]))
			foreach ($cat["categories"] as $id=>$sub_cat)
				$nb += $this->countCategoryRows($sub_cat);
		if (isset($cat["rows"]))
			$nb += count($cat["rows"]);
		return $nb;
	}
	
	private function generateResultsRows($rows, $pi) {
		echo "<div class='search_results'><table>";
		foreach ($rows as $row) {
			echo "<tr class='search_result_row' onclick=\"popup_frame('".$pi->getResultIcon($row)."', '".toHTML($pi->getResultTitle($row))."', '".$pi->getResultUrl($row)."', null, 90, 90, null);\">";
			echo $pi->generateResultRow($row);
			echo "</tr>";
		}
		echo "</table></div>";
	}
	
}
?>