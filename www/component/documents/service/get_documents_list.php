<?php 
class service_get_documents_list extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Returns the list of documents attached to the given entity"; }
	public function inputDocumentation() { echo "table, sub_model, key and type defining the row in the table and the plug-in"; }
	public function outputDocumentation() {
?>The array of documents:<ul>
	<li><code>id</code></li>
	<li><code>name</code></li>
	<li><code>lock</code></li>
	<li><code>versions</code></li>
</ul>
<?php
	}
	
	public function execute(&$component, $input) {
		$table = $input["table"];
		$sub_model = @$input["sub_model"];
		$key = $input["key"];
		$type = $input["type"];
		
		$pi = $component->getAttachedDocumentsPlugin($table, $type);
		if ($pi == null) {
			PNApplication::error("Invalid request");
			return;
		}
		if (!$pi->canRead($key, $sub_model)) {
			PNApplication::error("Access denied");
			return;
		}
		
		$documents = SQLQuery::create()->bypassSecurity()
			->select("AttachedDocument")
			->whereValue("AttachedDocument", "table", $table)
			->whereValue("AttachedDocument", "type", $type)
			->whereValue("AttachedDocument", "key", $key)
			->join("AttachedDocument","Document",array("document"=>"id"))
			->execute();
		$versions = array();
		if (count($documents) > 0) {
			$ids = array();
			foreach ($documents as $doc) array_push($ids, $doc["document"]);
			$q = SQLQuery::create()->bypassSecurity()->select("DocumentVersion")->whereIn("DocumentVersion", "document", $ids);
			$q->orderBy("DocumentVersion","timestamp",false);
			PNApplication::$instance->storage->joinRevision($q, "DocumentVersion", "file", null);
			PNApplication::$instance->people->joinPeople($q, "DocumentVersion", "people");
			$q->field("DocumentVersion","id","version_id");
			$q->field("DocumentVersion","document","document");
			$q->field("DocumentVersion","file","storage_id");
			$q->field("DocumentVersion","timestamp","version_time");
			$q->field("DocumentVersion","people","version_people");
			$q->field("Storage","mime","doc_type");
			$q->field("Storage","revision","storage_revision");
			$versions = $q->execute();
			require_once("component/data_model/DataBaseLock.inc");
			for ($i = 0; $i < count($documents); $i++) {
				$lock = DataBaseLock::isLocked("Document", $documents[$i]["document"], null);
				if ($lock == null)
					$documents[$i]["lock"] = null;
				else if (is_string($lock)) {
					$i = strpos($lock, "\\");
					$documents[$i]["lock"] = PNApplication::$instance->user_management->getUserFullName(substr($lock,0,$i),substr($lock,$i+1));
				} else {
					$documents[$i]["lock"] = "You";
				} 
			}
		}
		echo "[";
		$first = true;
		foreach ($documents as $doc) {
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "id:".$doc["document"];
			echo ",name:".json_encode($doc["name"]);
			echo ",lock:".json_encode($doc["lock"]);
			echo ",versions:[";
			$first_version = true;
			foreach ($versions as $v) {
				if ($v["document"] <> $doc["document"]) continue;
				if ($first_version) $first_version = false; else echo ",";
				echo "{";
				echo "id:".$v["version_id"];
				echo ",type:".json_encode($v["doc_type"]);
				echo ",time:".$v["version_time"];
				echo ",storage_id:".$v["storage_id"];
				echo ",revision:".$v["storage_revision"];
				echo ",people:".PeopleJSON::People($v);
				echo "}";
			}
			echo "]";
			echo "}";
		}
		echo "]";
	}
	
}
?>