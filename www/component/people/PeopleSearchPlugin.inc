<?php
require_once("component/search/SearchPlugin.inc"); 
class PeopleSearchPlugin implements SearchPlugin {
	
	public function getId() { return "people"; }
	public function getName() { return "Persons"; }
	public function getPriority() { return 10; }
	
	public function getTitle($nb) {
		echo $nb." Person".($nb > 1 ? "s" : "");
	}
	
	public function hasCategories() { return true; }
	public function sortByCategory($results) {
		$plugins = array();
		require_once("component/people/PeopleTypePlugin.inc");
		foreach (PNApplication::$instance->components as $c)
			foreach ($c->getPluginImplementations() as $pi)
				if ($pi instanceof PeopleTypePlugin)
					$plugins[$pi->getId()] = $pi;
		$res = array();
		foreach ($results as $row) {
			$types = PNApplication::$instance->people->parseTypes($row["types"]);
			foreach ($types as $type) {
				if (!isset($res[$type]))
					$res[$type] = array("rows"=>array(),"title"=>toHTML($plugins[$type]->getName()));
				array_push($res[$type]["rows"], $row);
			}
		}
		return $res;
	}
	
	
	public function genericSearch($s) {
		$words = explode(" ", $s);
		$q = SQLQuery::create()->select("People");
		$where = "";
		foreach ($words as $w) {
			if ($where <> "") $where .= " OR ";
			$where .= "`first_name` LIKE '%".SQLQuery::escape($w)."%'";
			$where .= " OR ";
			$where .= "`last_name` LIKE '%".SQLQuery::escape($w)."%'";
		}
		$q->where($where);
		return $q->execute();
	}
	
	public function generateResultRow($row) {
		echo "<td>".toHTML($row["first_name"])."</td>";
		echo "<td>".toHTML($row["last_name"])."</td>";
	}
	
	public function getResultIcon($row) { return "/static/people/profile_16.png"; }
	public function getResultTitle($row) { return "Profile"; }
	public function getResultUrl($row) { return "/dynamic/people/page/profile?people=".$row["id"]; }
	
}
?>