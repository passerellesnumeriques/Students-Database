<?php 
/**
 * News component stores and display messages, organized into sections and categories.
 * Messages can contain tags to attach to specific elements.
 * Messages can have replies.
 * There are 2 types of messages: updates or activities. Activities are generated messages to display what people are doing in the software, while updates are messages posted by the users to inform the others.
 */
class news extends Component {

	/**
	 * Check if the current user can post messages in the given section
	 * @param string $section section
	 * @return boolean true if the current user can post messages in the given section
	 */
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
	
	/**
	 * Post a message
	 * @param string $section section
	 * @param string $category category
	 * @param string $tags tags
	 * @param string $type type
	 * @param string $message message
	 * @return boolean|number message id, or false
	 */
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
	
	/**
	 * Post a reply to another message
	 * @param integer $parent_id parent message
	 * @param string $message reply message
	 * @return boolean|number message id or false
	 */
	public function postReply($parent_id, $message) {
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
	
	/**
	 * Get the most recent post, in the limit of 10 minutes from now, with a specific message
	 * @param string $section section
	 * @param string $category category
	 * @param string $html content to search, used in a LIKE so it may contain %
	 * @return array|null the row from the database, or null
	 */
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
	/**
	 * Change a message
	 * @param integer $id message id
	 * @param string $html new message content
	 */
	public function updatePost($id, $html) {
		SQLQuery::create()->bypassSecurity()->updateByKey("News", $id, array("html"=>$html,"update_timestamp"=>time()));
	}
}
?>