<?php 
class news extends Component {
	
	public function post($section, $category, $tags, $message) {
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
			PNApplication::error("Invalid section/category to post message: ".$section."/".$category);
			return false;
		}
		try {
			$now = time();
			$s = "";
			if ($tags <> null) {
				sort($tags);
				foreach ($tags as $tag) $s .= "/".$tag."/";
			}
			return SQLQuery::create()->bypass_security()->insert("News", array(
				"section"=>$section,
				"category"=>$category,
				"html"=>$message,
				"domain"=>PNApplication::$instance->user_management->domain,
				"username"=>PNApplication::$instance->user_management->username,
				"timestamp"=>$now,
				"update_timestamp"=>$now,
				"tags"=>$s
			));
		} catch (Exception $e) {
			PNApplication::error($e);
			return false;
		}
	}
	
	public function post_reply($parent_id, $message) {
		// first get the parent
		$parent = SQLQuery::create()->bypass_security()->select("News")->where_value("News", "id", $parent_id)->execute_single_row();
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
			$reply_id = SQLQuery::create()->bypass_security()->insert("News", array(
				"section"=>$parent["section"],
				"category"=>$parent["category"],
				"html"=>$message,
				"domain"=>PNApplication::$instance->user_management->domain,
				"username"=>PNApplication::$instance->user_management->username,
				"timestamp"=>$now,
				"update_timestamp"=>$now,
				"tags"=>null
			));
			// update time of parent
			SQLQuery::create()->bypass_security()->update_by_key("News", $parent_id, array("update_timestamp"=>$now));
			return $reply_id;
		} catch (Exception $e) {
			PNApplication::error($e);
			return false;
		}
	}
}
?>