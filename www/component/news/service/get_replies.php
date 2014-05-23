<?php 
class service_get_replies extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Retrieve replies to a root news"; }
	public function inputDocumentation() { echo "<code>ids</code>: the root news' ids"; }
	public function outputDocumentation() { echo "List of NewsObject, containing reply_to as well"; }
	
	public function execute(&$component, $input) {
		$news = SQLQuery::create()->bypassSecurity()
			->select("News")
			->whereIn("News", "id", $input["ids"])
			->field("News", "id")
			->field("News", "section")
			->field("News", "category")
			->execute();
		require_once("component/news/NewsPlugin.inc");
		for ($i = 0; $i < count($news); $i++) {
			$found = false;
			foreach (PNApplication::$instance->components as $c) {
				foreach ($c->getPluginImplementations() as $pi) {
					if (!($pi instanceof NewsPlugin)) continue;
					foreach ($pi->getSections() as $section) {
						if ($section->getName() <> $news[$i]["section"]) continue;
						if ($section->getAccessRight() == 0) continue;
						if ($news[$i]["category"] == null) {
							$found = true;
							break;
						}
						foreach ($section->getCategories() as $cat) {
							if ($cat->getName() <> $news[$i]["category"]) continue;
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
				array_splice($news, $i, 1);
				$i--;
			}
		}
		if (count($news) == 0) { echo "[]"; return; }
		
		$ids = array();
		foreach ($news as $n) array_push($ids, $n["id"]);
		$news = SQLQuery::create()->bypassSecurity()
			->select("News")
			->whereIn("News", "reply_to", $ids)
			->orderBy("News", "timestamp", true)
			->orderBy("News", "id", true)
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
				$q = PNApplication::$instance->user_management->selectUsers($users, $domain);
				PNApplication::$instance->user_people->joinPeopleToUsers($q);
				$res = $q->execute();
				$a[$domain] = array();
				foreach ($res as $r) {
					$username = PNApplication::$instance->user_management->getSelectedUsername($r);
					$a[$domain][$username] = $r;
				}
			}
			$people_names = $a;
		}
			
		require_once("component/people/PeopleJSON.inc");
		$first = true;
		foreach ($news as $n) {
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "id:".$n["id"];
			echo ",section:".json_encode($n["section"]);
			echo ",category:".json_encode($n["category"]);
			echo ",html:".json_encode($n["html"]);
			echo ",user:{domain:".json_encode($n["domain"]).",username:".json_encode($n["username"])."}";
			$r = $people_names[$n["domain"]][$n["username"]];
			echo ",people:".PeopleJSON::People($r);
			echo ",timestamp:".$n["timestamp"];
			echo ",reply_to:".$n["reply_to"];
			echo "}";
		}
		echo "]";
	}
	
}
?>