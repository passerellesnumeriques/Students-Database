<?php 
class service_update_lock extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function getOutputFormat($input) {
		if (isset($_GET["format"]))
			switch ($_GET["format"]) {
				case "raw": return "text/plain;charset=UTF-8";
		}
		return parent::getOutputFormat($input);
	}
	
	public function execute(&$component, $input) {
		$id = $_GET["id"];
		$format = @$_GET["format"];
		
		$error = null;
		
		$attached = SQLQuery::create()->bypassSecurity()->select("AttachedDocument")->whereValue("AttachedDocument","document",$id)->executeSingleRow();
		if ($attached <> null) {
			$pi = $component->getAttachedDocumentsPlugin($attached["table"],$attached["type"]);
			if ($pi == null)
				$error = "Invalid document";
			else if (!$pi->canWrite($attached["key"],$attached["sub_model"]))
				$error = "Access denied";
			else {
				require_once("component/data_model/DataBaseLock.inc");
				$lock = DataBaseLock::isLocked("Document", $id, null);
				if ($lock <> null && is_integer($lock)) {
					DataBaseLock::update($lock);
					if (PNApplication::hasErrors()) {
						$error = PNApplication::$errors[0];
						PNApplication::clearErrors();
					}
				} else {
					$error = "You don't own the file anymore. Please re-open it.";
					if (is_string($lock)) {
						$i = strpos($lock, "\\");
						$error .= "\r\nThe file is now edited by ".PNApplication::$instance->user_management->getUserFullName(substr($lock,0,$i),substr($lock,$i+1));
					}
				}
			}
		} else {
			$error = "Invalid document";
		}
		if ($format == "raw") {
			if ($error <> null) echo $error;
			else echo "OK";
		} else {
			if ($error <> null)
				PNApplication::error($error);
			else
				echo "true";
		}
	}
	
}
?>