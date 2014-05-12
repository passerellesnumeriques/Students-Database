<?php 
class service_post_reply extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Post a reply to a message"; }
	public function inputDocumentation() { echo "id (the id of the root message) and message"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		// get the root
		$root = SQLQuery::create()->bypassSecurity()->select("News")->whereValue("News", "id", $input["id"])->executeSingleRow();
		if ($root == null) {
			PNApplication::error("The message does not exist anymore: your reply was not saved.");
			return;
		}
		if ($root["reply_to"] <> null) {
			PNApplication::error("Internal error: you cannot reply to a reply, you must specify the id of the root message");
			return;
		}
		// check access rights
		require_once("component/news/NewsPlugin.inc");
		$found = false;
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof NewsPlugin)) continue;
				foreach ($pi->getSections() as $section) {
					if ($section->getName() <> $root["section"]) continue;
					if ($section->getAccessRight() <> 2) continue;
					if ($root["category"] == null) {
						$found = true;
						break;
					}
					foreach ($section->getCategories() as $cat) {
						if ($cat->getName() <> $root["category"]) continue;
						if ($cat->getAccessRight() <> 2) continue;
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
			PNApplication::error("You are not allowed to reply to a message in this section/category");
			return;
		}
		$now = time();
		SQLQuery::startTransaction();
		SQLQuery::create()->bypassSecurity()->insert("News", array(
			"section"=>$root["section"],
			"category"=>$root["category"],
			"html"=>$input["message"],
			"domain"=>PNApplication::$instance->user_management->domain,
			"username"=>PNApplication::$instance->user_management->username,
			"timestamp"=>$now,
			"update_timestamp"=>$now,
			"reply_to"=>$root["id"]
		));
		SQLQuery::create()->bypassSecurity()->updateByKey("News", $root["id"], array("update_timestamp"=>$now));
		SQLQuery::commitTransaction();
		echo "true";
	}
	
}
?>