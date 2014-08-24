<?php 
class news extends Component {
	
	public function canPostInSection($section) {
		require_once("NewsPlugin.inc");
		$found = false;
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof NewsPlugin)) continue;
				foreach ($pi->getSections() as $s) {
					if ($s->getName() <> $section) continue;
					if ($s->getAccessRight() == 2) return true;
				}
			}
		}
		return false;
	}
	
	public function post($section, $category, $tags, $type, $message) {
		require_once("NewsPlugin.inc");
		$found = false;
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof NewsPlugin)) continue;
				foreach ($pi->getSections() as $s) {
					if ($s->getName() <> $section) continue;
					if ($category == null) {
						if ($s->getAccessRight() <> 2) break;
						$found = true;
						break;
					}
					foreach ($s->getCategories() as $c) {
						if ($c->getName() <> $category) continue;
						if ($c->getAccessRight() <> 2) break;
						$found = true;
						break;
					}
				}
				if ($found) break;
			}
			if ($found) break;
		}
		if (!$found) {
			PNApplication::error("Invalid section/category to post message: ".$section."/".$category);
			return false;
		}
		try {
			$now = time();
			$s = "";
			if ($tags <> null && count($tags) > 0) {
				sort($tags);
				foreach ($tags as $tag) $s .= "/".$tag."/";
			}
			return SQLQuery::create()->bypassSecurity()->insert("News", array(
				"section"=>$section,
				"category"=>$category,
				"html"=>$message,
				"domain"=>PNApplication::$instance->user_management->domain,
				"username"=>PNApplication::$instance->user_management->username,
				"timestamp"=>$now,
				"update_timestamp"=>$now,
				"tags"=>$s,
				"type"=>$type
			));
		} catch (Exception $e) {
			PNApplication::error($e);
			return false;
		}
	}
	
	public function post_reply($parent_id, $message) {
		// first get the parent
		$parent = SQLQuery::create()->bypassSecurity()->select("News")->whereValue("News", "id", $parent_id)->executeSingleRow();
		if ($parent == null) {
			PNApplication::error("Invalid parent news id");
			return false;
		}
		// check access to this section / category
		require_once("NewsPlugin.inc");
		$found = false;
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof NewsPlugin)) continue;
				foreach ($pi->getSections() as $s) {
					if ($s->getName() <> $section) continue;
					if ($s->getAccessRight() <> 2) break;
					if ($category == null) {
						$found = true;
						break;
					}
					foreach ($s->getCategories() as $c) {
						if ($c->getName() <> $category) continue;
						if ($c->getAccessRight() <> 2) break;
						$found = true;
						break;
					}
				}
				if ($found) break;
			}
			if ($found) break;
		}
		if (!$found) {
			PNApplication::error("You cannot reply to this section/category: ".$section."/".$category);
			return false;
		}
		try {
			$now = time();
			$reply_id = SQLQuery::create()->bypassSecurity()->insert("News", array(
				"section"=>$parent["section"],
				"category"=>$parent["category"],
				"html"=>$message,
				"domain"=>PNApplication::$instance->user_management->domain,
				"username"=>PNApplication::$instance->user_management->username,
				"timestamp"=>$now,
				"update_timestamp"=>$now,
				"tags"=>null,
				"type"=>$parent["type"]
			));
			// update time of parent
			SQLQuery::create()->bypassSecurity()->updateByKey("News", $parent_id, array("update_timestamp"=>$now));
			return $reply_id;
		} catch (Exception $e) {
			PNApplication::error($e);
			return false;
		}
	}
	
	public function getRecentPost($section, $category, $html) {
		$now = time();
		return SQLQuery::create()
			->bypassSecurity()
			->select("News")
			->whereValue("News", "section", $section)
			->whereValue("News", "category", $category)
			->whereValue("News", "domain", PNApplication::$instance->user_management->domain)
			->whereValue("News", "username", PNApplication::$instance->user_management->username)
			->where("`update_timestamp` > ".($now-10*60))
			->where("`html` LIKE '".str_replace("\\","\\\\",str_replace("'","\\'", str_replace("\"","\\\"", $html)))."'")
			->orderBy("News", "update_timestamp", false)
			->limit(0,1)
			->executeSingleRow();
	}
	public function updatePost($id, $html) {
		SQLQuery::create()->bypassSecurity()->updateByKey("News", $id, array("html"=>$html,"update_timestamp"=>time()));
	}
}
?>