<?php 
class service_get_replies extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Retrieve replies to a root news"; }
	public function inputDocumentation() { echo "<code>ids</code>: the root news' ids"; }
	public function outputDocumentation() { echo "List of NewsObject, containing reply_to as well"; }
	
	public function execute(&$component, $input) {
		$accessible = "";
		require_once("component/news/NewsPlugin.inc");
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations("NewsPlugin") as $pi) {
				foreach ($pi->getSections() as $section) {
					if ($section->getAccessRight() == 0) continue;
					$where = "(`section`='".SQLQuery::escape($section->getName())."' AND `category` IN (NULL";
					foreach ($section->getCategories() as $cat) {
						if ($cat->getAccessRight() == 0) continue;
						$where .= ",'".SQLQuery::escape($cat->getName())."'";
					}
					$where .= "))";
					if ($accessible <> "") $accessible .= " OR ";
					$accessible .= $where;
				}
			}
		}
		if ($accessible == "") {
			echo "[]";
			return;
		}
		$ids = SQLQuery::create()->bypassSecurity()
			->select("News")
			->whereIn("News", "id", $input["ids"])
			->where($accessible)
			->field("News", "id")
			->executeSingleField();
		if (count($ids) == 0) { echo "[]"; return; }
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
				$q->bypassSecurity(); // we accept to see the name of the people who post...
				PNApplication::$instance->user_management->joinPeopleToUsers($q);
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