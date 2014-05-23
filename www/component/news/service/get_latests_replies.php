<?php 
class service_get_latests_replies extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Retrieve latests replies"; }
	public function inputDocumentation() {
		echo "to_refresh: array of {id,latest} where id is the id of a root news, while latest is the timestamp of the latest reply known (or 0 if no reply is known)";
	}
	public function outputDocumentation() { echo "List of NewsObject, containing reply_to"; }
	
	public function execute(&$component, $input) {
		require_once("component/news/NewsPlugin.inc");

		$q = SQLQuery::create()->bypassSecurity()->select("News");
		$where = "";
		foreach ($input["to_refresh"] as $n) {
			if ($where <> "") $where .= " OR ";
			$where .= "(`reply_to`='".SQLQuery::escape($n["id"])."'";
			if (intval($n["latest"]) > 0) $where .= " AND `timestamp` > ".intval($n["latest"]);
			$where .= ")";
		}
		$q->where($where);
		$q->orderBy("News", "timestamp", false);
		$news = $q->execute();
				
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
		echo "[";
		$first = true;
		foreach ($news as $n) {
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
			echo ",reply_to:".$n["reply_to"];
			echo "}";
		}
		echo "]";
	}
	
}
?>