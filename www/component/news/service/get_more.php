<?php 
class service_get_more extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Retrieve more news (older) since last request"; }
	public function input_documentation() {
		echo "<ul>";
		echo "<li><code>olders</code>: array of olders news id received last request</li>";
		echo "<li><code>olders_timestamp</code>: timestamp of the olders, or 0 for first request</li>";
		echo "<li><code>sections</code>: list of sections with categories and tags to get the news</li>";
		echo "<li><code>nb</code>: number of news to get</li>";
		echo "</ul>";
	}
	public function output_documentation() { echo "List of NewsObject"; }
	
	public function execute(&$component, $input) {
		require_once("component/news/NewsPlugin.inc");
		$q = SQLQuery::create()->bypass_security()->select("News");
		$q->where_null("News", "reply_to");
		if ($input["olders_timestamp"] == 0) {
			$q->limit(0, intval($input["nb"]));
		} else {
			$q->where("update_timestamp", "<=", $input["olders_timestamp"]);
			$q->limit(0, intval($input["nb"])+count($input["olders"])+10);
		} 
		$q->order_by("News", "update_timestamp", false);
		
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
			if (in_array($n["id"], $input["olders"])) continue; // already in olders
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
			
		echo "[";
		$num = 0;
		foreach ($news as $n) {
			if (in_array($n["id"], $input["olders"])) continue; // already in olders
			if ($num > 0) echo ",";
			$num++;
			if ($num > intval($input["nb"])) break;
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
			echo ",update_timestamp:".$n["update_timestamp"];
			echo "}";
		}
		echo "]";
	}
	
}
?>