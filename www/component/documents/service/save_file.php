<?php 
class service_save_file extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function getOutputFormat($input) {
		return "text/plain;charset=UTF-8";
	}
	
	public function execute(&$component, $input) {
		$id = $_GET["id"];

		$attached = SQLQuery::create()->bypassSecurity()->select("AttachedDocument")->whereValue("AttachedDocument","document",$id)->executeSingleRow();
		if ($attached <> null) {
			$pi = $component->getAttachedDocumentsPlugin($attached["table"],$attached["type"]);
			if ($pi == null) {
				echo "Invalid document";
				return;
			}
			if (!$pi->canWrite($attached["key"],$attached["sub_model"])) {
				echo "Access denied";
				return;
			}
			require_once("component/data_model/DataBaseLock.inc");
			$lock = DataBaseLock::isLocked("Document", $id, null);
			if ($lock == null || !is_integer($lock)) {
				if (is_string($lock)) {
					$i = strpos($lock, "\\");
					echo "You cannot save the file because it is now edited by ".PNApplication::$instance->user_management->getUserFullName(substr($lock,0,$i),substr($lock,$i+1));
				} else
					echo "You cannot save the file because you don't own it anymore.";
				return;
			}
			DataBaseLock::update($lock);
			$versions = SQLQuery::create()->bypassSecurity()
				->select("DocumentVersion")
				->whereValue("DocumentVersion","document",$id)
				->orderBy("DocumentVersion","timestamp",false)
				->execute();
			$max_versions = $pi->maxVersions();
			if ($max_versions == 1) {
				$data = file_get_contents('php://input');
				PNApplication::$instance->storage->update_file($versions[0]["file"], $data);
				SQLQuery::create()->bypassSecurity()->updateByKey("DocumentVersion", $versions[0]["id"], array("timestamp"=>time(),"user"=>PNApplication::$instance->user_management->user_id));
				if (!PNApplication::hasErrors()) {
					echo "OK";
					return;
				}
				echo PNApplication::$errors[0];
				return;
			} else {
				$type = PNApplication::$instance->storage->getFileType($versions[0]["file"]);
				$storage_id = PNApplication::$instance->storage->custom_upload($type, 60);
				SQLQuery::startTransaction();
				PNApplication::$instance->storage->convertTempFile($storage_id, "document");
				SQLQuery::create()->bypassSecurity()->insert("DocumentVersion",array("document"=>$id,"file"=>$storage_id,"timestamp"=>time(),"user"=>PNApplication::$instance->user_management->user_id));
				$to_remove = SQLQuery::create()->bypassSecurity()
					->select("DocumentVersion")
					->whereValue("DocumentVersion","document",$id)
					->orderBy("DocumentVersion","timestamp",false)
					->execute();
				for ($i = 0; $i < $max_versions; $i++)
					if (count($to_remove) == 0) break;
					else array_splice($to_remove,0,1);
				if (count($to_remove) > 0) {
					SQLQuery::create()->bypassSecurity()->removeRows($to_remove);
					if (!PNApplication::hasErrors()) {
						foreach ($to_remove as $r)
							PNApplication::$instance->storage->remove_data($r["file"]);
					}
				}
				if (!PNApplication::hasErrors()) {
					SQLQuery::commitTransaction();
					echo "OK";
					return;
				}
				echo PNApplication::$errors[0];
			}
		} else {
			echo "Invalid document";
		}
	}
	
}
?>