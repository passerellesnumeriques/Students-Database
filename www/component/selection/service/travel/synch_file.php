<?php 
class service_travel_synch_file extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Receive files from travel version to synchronize database"; }
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		$campaign_id = $_GET["campaign"];
		$username = $_POST["username"];
		$uid = $_POST["uid"];
		$key = $_POST["key"];
		$username = $_POST["username"];
		$travel = SQLQuery::create()->bypassSecurity()
			->selectSubModel("SelectionCampaign", $campaign_id)
			->select("TravelVersion")
			->executeSingleRow();
		if ($travel == null) {
			PNApplication::error("The campaign is not currently locked for travel");
			return;
		}
		if ($travel["synch_key_expiration"] == null || $travel["synch_key_expiration"] < time()) {
			PNApplication::error("The time allocated to synchronize the database has been exceeded. Please retry.");
			return;
		}
		if ($travel["uid"] <> $uid || $travel["synch_key"] <> $key) {
			PNApplication::error("You are trying to synchronize with the wrong computer.");
			return;
		}
		$u = PNApplication::$instance->user_management->getUsernameFromId($travel["user"], true);
		if ($u <> $username) {
			PNApplication::error("Access denied");
			return;
		}
		if ($_GET["type"] == "done") {
			if ($travel["database_diff"] == null) {
				PNApplication::error("We cannot synchronize the database because we didn't receive the modifications!");
				return;
			}
			$path = PNApplication::$instance->storage->get_data_path($travel["database_diff"]);
			if (!file_exists($path)) {
				PNApplication::error("Time to synchronize database exceeded. Please try again.");
				return;
			}
			$diff = file_get_contents($path);
			$diff = explode("\n",$diff);
			SQLQuery::startTransaction();
			$inserted = array();
			foreach ($diff as $sql) {
				$sql = trim($sql);
				if ($sql == "") continue;
				// process ids
				while (($i = strpos($sql, "##ID")) !== false) {
					$j = strpos($sql, "##", $i+4);
					if ($j === false) {
						PNApplication::error("Invalid SQL: ".$sql);
						return;
					}
					$id = substr($sql, $i+4, $j-$i-4);
					if (!isset($inserted[$id])) {
						PNApplication::error("Invalid ID ($id) on SQL line: ".$sql);
						return;
					}
					$sql = substr($sql,0,$i).$inserted[$id].substr($sql,$j+2);
				}
				if (substr($sql,0,3) == "#ID") {
					// this is an insert, and we need to keep the id
					$i = strpos($sql, "=");
					if ($i === false) {
						PNApplication::error("Invalid SQL line: ".$sql);
						return;
					}
					$insert_id = substr($sql,3,$i-3);
					$sql = substr($sql,$i+1);
					$res = SQLQuery::getDataBaseAccessWithoutSecurity()->execute($sql);
					if (!$res) return;
					$real_id = SQLQuery::getDataBaseAccessWithoutSecurity()->getInsertID();
					$inserted[$insert_id] = $real_id;
				} else {
					// normal query
					$res = SQLQuery::getDataBaseAccessWithoutSecurity()->execute($sql);
					if (!$res) return;
				}
			}
			if (PNApplication::hasErrors()) return;
			// everything went fine, we can put the storage file
			$files = SQLQuery::create()->bypassSecurity()
				->selectSubModel("SelectionCampaign", $campaign_id)
				->select("TravelVersionSynchStorage")
				->execute();
			foreach ($files as $file) {
				if ($file["new"] == 1) {
					// this is a new file
					if (!isset($inserted[$file["id"]])) {
						PNApplication::error("We have a new file to store, but we miss the insert!");
						return;
					}
					$real_id = $inserted[$file["id"]];
					$path = PNApplication::$instance->storage->get_data_path($real_id);
					$dir = dirname($path);
					if (!file_exists($dir)) mkdir($dir, null, true);
					$src = PNApplication::$instance->storage->get_data_path($file["storage"]);
					copy($src, $path);
					PNApplication::$instance->storage->remove_data($file["storage"]);
				} else {
					// this was an existing file
					$path = PNApplication::$instance->storage->get_data_path($file["id"]);
					$src = PNApplication::$instance->storage->get_data_path($file["storage"]);
					copy($src, $path);
					PNApplication::$instance->storage->remove_data($file["storage"]);
				}
			}
			if (PNApplication::hasErrors()) return;
			// we are done !
			SQLQuery::create()->bypassSecurity()->selectSubModel("SelectionCampaign", $campaign_id)->removeKey("TravelVersion", $travel["user"]);
			$files = SQLQuery::create()->bypassSecurity()
				->selectSubModel("SelectionCampaign", $campaign_id)
				->select("TravelVersionSynchStorage")
				->execute();
			if (count($files) > 0)
				SQLQuery::create()->bypassSecurity()->selectSubModel("SelectionCampaign", $campaign_id)->removeRows("TravelVersionSynchStorage",$files);
			SQLQuery::commitTransaction();
			echo "true";
			return;
		} else {
			$filesize = $_POST["filesize"];
			$ids = array();
			$names = array();
			$types = array();
			$sizes = array();
			PNApplication::$instance->storage->receive_upload($ids, $names, $types, $sizes, 60*60);
			if (PNApplication::hasErrors()) return;
			if (count($ids) == 0) {
				PNApplication::error("No file received");
				return;
			}
			if ($sizes[0] <> intval($filesize)) {
				PNApplication::error("The uploaded file is invalid (we received ".$sizes[0]." bytes while we were expecting $filesize). Please try again.");
				return;
			}
			if ($_GET["type"] == "database_diff") {
				SQLQuery::create()->bypassSecurity()
					->selectSubModel("SelectionCampaign", $campaign_id)
					->updateByKey("TravelVersion", $travel["user"], array("database_diff"=>$ids[0],"synch_key_expiration"=>time()+60*60));
				echo "true";
				return;
			}
			SQLQuery::create()->bypassSecurity()
				->selectSubModel("SelectionCampaign", $campaign_id)
				->insert("TravelVersionSynchStorage", array(
					"storage"=>$ids[0],
					"new"=>($_GET["type"] == "new_storage" ? 1 : 0),
					"id"=>$_GET["file_id"]
				));
			echo "true";
			return;
		}
	}
	
}
?>