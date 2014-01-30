<?php 
class service_get_replies extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Retrieve replies to a root news"; }
	public function input_documentation() { echo "<code>id</code>: the root news"; }
	public function output_documentation() { echo "List of NewsObject"; }
	
	public function execute(&$component, $input) {
		$news = SQLQuery::create()->bypass_security()->select("News")->where_value("News", "id", $input["id"])->field("News", "section")->field("News", "category")->execute_single_row();
		if ($news == null) {
			PNApplication::error("Invalid news id");
			return;
		}
		require_once("component/news/NewsPlugin.inc");
		$found = false;
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof NewsPlugin)) continue;
				foreach ($pi->getSections() as $section) {
					if ($section->getName() <> $news["section"]) continue;
					if ($section->getAccessRight() == 0) continue;
					if ($news["category"] == null) {
						$found = true;
						break;
					}
					foreach ($section->getCategories() as $cat) {
						if ($cat->getName() <> $news["category"]) continue;
						if ($cat->getAccessRight() == 0) continue;
						$found = true;
						break;
					}
					if ($found) break;
				}
				if ($found) break;
			}
			if ($found) break;
		}
		if (!$found) {
			PNApplication::error("Invalid section/category");
			return;
		}
		$news = SQLQuery::create()->bypass_security()
			->select("News")
			->where_value("News", "reply_to", $input["id"])
			->order_by("News", "timestamp", true)
			->order_by("News", "id", true)
			->execute();
		
		echo "[";
		// retrieve people names
		$people_names = array();
		foreach ($news as $n) {
			if (!isset($people_names[$n["domain"]]))
				$people_names[$n["domain"]] = array();
			if (!in_array($n["username"], $people_names[$n["domain"]]))
				array_push($people_names[$n["domain"]], $n["username"]);
		}
			
		if (count($people_names) > 0) {
			$a = array();
			foreach ($people_names as $domain=>$users) {
				$res = SQLQuery::create()->bypass_security()
				->database("students_".$domain)
				->select("Users")
				->where_in("Users", "username", $users)
				->join("Users", "UserPeople", array("id"=>"user"))
				->join("UserPeople", "People", array("people"=>"id"))
				->field("Users", "username", "username")
				->field("People", "id", "people_id")
				->field("People", "first_name", "first_name")
				->field("People", "last_name", "last_name")
				->execute();
				$a[$domain] = array();
				foreach ($res as $r)
					$a[$domain][$r["username"]] = $r;
			}
			$people_names = $a;
		}
			
		$first = true;
		foreach ($news as $n) {
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "id:".$n["id"];
			echo ",section:".json_encode($n["section"]);
			echo ",category:".json_encode($n["category"]);
			echo ",html:".json_encode($n["html"]);
			echo ",domain:".json_encode($n["domain"]);
			echo ",username:".json_encode($n["username"]);
			$r = @$people_names[$n["domain"]][$n["username"]];
			echo ",people_id:".json_encode(@$r["people_id"]);
			echo ",people_name:";
			if ($r == null) echo "\"\"";
			else echo json_encode($r["first_name"]." ".$r["last_name"]);
			echo ",timestamp:".$n["timestamp"];
			echo "}";
		}
		echo "]";
	}
	
}
?>