<?php 
class service_get_latests extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Retrieve latests news since last request"; }
	public function inputDocumentation() {
		echo "<ul>";
		echo "<li><code>latests</code>: array of latests news id received last request</li>";
		echo "<li><code>latests_timestamp</code>: timestamp of the latests</li>";
		echo "<li><code>sections</code>: list of sections with categories and tags to get the news</li>";
		echo "</ul>";
	}
	public function outputDocumentation() { echo "List of NewsObject"; }
	
	public function execute(&$component, $input) {
		require_once("component/news/NewsPlugin.inc");

		$q = SQLQuery::create()->bypassSecurity()->select("News");
		$q->whereNull("News", "reply_to");
		$q->where("update_timestamp", ">=", $input["latests_timestamp"]);
		$q->orderBy("News", "update_timestamp", false);
		
		$where = "(";
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof NewsPlugin)) continue;
				foreach ($pi->getSections() as $section) {
					if ($section->getAccessRight() == 0) continue;
					$found = null;
					foreach ($input["sections"] as $s)
						if ($s["name"] == $section->getName()) { $found = $s; break; }
					if ($found == null) continue;
					if (strlen($where) > 1) $where .= " OR ";
					$where .= "(";
					$where .= "`section`='".SQLQuery::escape($section->getName())."' AND `category` IS NULL";
					if (isset($found["tags"]) && $found["tags"] <> null) {
						sort($found["tags"]);
						$where .= " AND (";
						$first_tag = true;
						foreach ($found["tags"] as $tag) {
							if ($first_tag) $first_tag = false; else $where .= " OR ";
							$where .= "`tags` LIKE '%".SQLQuery::escape("/".$tag."/")."%'";
						}
						$where .= ")";
					}
					$where .= ")";
					foreach ($section->getCategories() as $cat) {
						if ($cat->getAccessRight() == 0) continue;
						if (!in_array($cat->getName(), $found["categories"])) continue;
						if (strlen($where) > 1) $where .= " OR ";
						$where .= "(";
						$where .= "`section`='".SQLQuery::escape($section->getName())."' AND `category`='".SQLQuery::escape($cat->getName())."'";
						if (isset($found["tags"]) && $found["tags"] <> null) {
							sort($found["tags"]);
							$where .= " AND (";
							$first_tag = true;
							foreach ($found["tags"] as $tag) {
								if ($first_tag) $first_tag = false; else $where .= " OR ";
								$where .= "`tags` LIKE '%".SQLQuery::escape("/".$tag."/")."%'";
							}
							$where .= ")";
						}
						$where .= ")";
					}					
				}				
			}
		}
		$where .= ")";
		if (strlen($where) == 2) {
			echo "[]";
			return;
		}
		$q->where($where);
		$news = $q->execute();
				
		// retrieve people names
		$people_names = array();
		foreach ($news as $n) {
			if (in_array($n["id"], $input["latests"])) continue; // already in latests
			if (!isset($people_names[$n["domain"]]))
				$people_names[$n["domain"]] = array();
			if (!in_array($n["username"], $people_names[$n["domain"]]))
				array_push($people_names[$n["domain"]], $n["username"]);
		}
		
		if (count($people_names) > 0) {
			$a = array();
			foreach ($people_names as $domain=>$users) {
				$q = PNApplication::$instance->user_management->selectUsers($users, $domain);
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
		echo "[";
		$first = true;
		foreach ($news as $n) {
			if (in_array($n["id"], $input["latests"])) continue; // already in latests
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "id:".$n["id"];
			echo ",section:".json_encode($n["section"]);
			echo ",category:".json_encode($n["category"]);
			echo ",html:".json_encode($n["html"]);
			echo ",domain:".json_encode($n["domain"]);
			echo ",user:{domain:".json_encode($n["domain"]).",username:".json_encode($n["username"])."}";
			$r = $people_names[$n["domain"]][$n["username"]];
			echo ",people:".PeopleJSON::People($r);
			echo ",timestamp:".$n["timestamp"];
			echo ",update_timestamp:".$n["timestamp"];
			echo "}";
		}
		echo "]";
	}
	
}
?>